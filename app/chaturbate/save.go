package main

import (
	"fmt"
	"time"
)

type saveData struct {
	Room   string
	From   string
	Rid    int64
	Amount int64
	Now    int64
}

type saveLog struct {
	Rid int64
	Now int64
	Mes string
}

type DonatorCache struct {
	Id   int64
	Last int64
}

func getDonId(name string) int64 {
	var id int64
	err := Mysql.Get(&id, "SELECT id FROM donator WHERE name=?", name)
	if err != nil {
		res, _ := Mysql.Exec("INSERT INTO donator (`name`) VALUES (?)", name)
		id, _ = res.LastInsertId()
	}
	return id
}

func getRoomInfo(name string) (int64, bool) {
	var id int64
	result := true
	err := Mysql.Get(&id, "SELECT id FROM room WHERE name=?", name)
	if err != nil {
		result = false
	}
	return id, result
}

func getSumTokens() int64 {
	r := struct {
		Date string
		Sum  int64
	}{}
	err := Clickhouse.Get(&r, "SELECT toStartOfHour(toDateTime(`unix`)) as date, SUM(`token`) as sum FROM `stat` WHERE time = today() GROUP BY date ORDER BY date DESC LIMIT 1")
	if err == nil && r.Sum > 0 {
		return r.Sum
	}
	return 0
}

func saveDB() {
	hours, _, _ := time.Now().Clock()

	bulk := make(map[int]saveData)
	update := make(map[int64]int64)
	data := make(map[string]*DonatorCache)
	index := make(map[string]int64)

	index = map[string]int64{"hours": int64(hours), "tokens": getSumTokens(), "last": time.Now().Unix()}

	for {
		select {
		case m := <-save:
			//fmt.Println("Save channel:", len(save), cap(save))

			now := time.Now().Unix()

			if _, ok := data[m.From]; ok {
				data[m.From].Last = now
			} else {
				data[m.From] = &DonatorCache{Id: getDonId(m.From), Last: now}
			}

			num := len(bulk)

			bulk[num] = m

			update[m.Rid] = m.Now

			if num > 512 {

				tx, err := Mysql.Begin()
				if err == nil {
					st, _ := tx.Prepare("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, ?)")
					for _, v := range bulk {
						st.Exec(data[v.From].Id, v.Rid, v.Amount, v.Now)
					}
					tx.Commit()
					st.Close()
				}

				tx, err = Mysql.Begin()
				if err == nil {
					st, _ := tx.Prepare("UPDATE `room` SET `last` = ? WHERE `id` = ?")
					for k, v := range update {
						st.Exec(v, k)
					}
					tx.Commit()
					st.Close()
				}

				tx, err = Clickhouse.Begin()
				if err == nil {
					st, _ := tx.Prepare("INSERT INTO stat VALUES (?, ?, ?, ?, ?)")
					for _, v := range bulk {
						st.Exec(uint32(data[v.From].Id), uint32(v.Rid), uint32(v.Amount), time.Unix(v.Now, 0), uint32(v.Now))
					}
					tx.Commit()
					st.Close()
				}

				bulk = make(map[int]saveData)
				update = make(map[int64]int64)
			}

			if m.Amount > 99 {
				msg, err := json.Marshal(struct {
					Room    string `json:"room"`
					Donator string `json:"donator"`
					Amount  int64  `json:"amount"`
				}{
					Room:    m.Room,
					Donator: m.From,
					Amount:  m.Amount,
				})
				if err == nil {
					ws.Send <- msg
				}
			}

			hours, minutes, seconds := time.Now().Clock()
			if int64(hours) == index["hours"] {
				index["tokens"] += m.Amount
			} else {
				index = map[string]int64{"hours": int64(hours), "tokens": 0, "last": 0}
			}

			if minutes >= 5 && now > index["last"]+30 {
				seconds += minutes * 60
				msg, err := json.Marshal(struct {
					Index int64 `json:"index"`
				}{Index: index["tokens"] / int64(seconds) * 3600 / 1000 * 5 / 100})
				if err == nil {
					ws.Send <- msg
				}
				index["last"] = now
			}

			if randInt(0, 10000) == 777 { // 0.001%
				l := len(data)
				for k, v := range data {
					if now > v.Last+60*60*48 {
						delete(data, k)
					}
				}
				fmt.Println("Clean map:", l, "=>", len(data))
			}
		}
	}
}

func saveLogs() {
	bulk := make(map[int]saveLog)
	for {
		select {
		case m := <-slog:
			if len(m.Mes) > 0 {
				num := len(bulk)
				bulk[num] = m
				if num > 2048 {
					tx, err := Mysql.Begin()
					if err == nil {
						st, _ := tx.Prepare("INSERT INTO `logs` (`rid`, `time`, `mes`) VALUES (?, ?, ?)")
						for _, v := range bulk {
							st.Exec(v.Rid, v.Now, v.Mes)
						}
						tx.Commit()
						st.Close()
					}
					bulk = make(map[int]saveLog)
				}
			}
		}
	}
}
