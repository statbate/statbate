package main

import (
	"fmt"
	"time"
)

type tID struct {
	Id int64 `db:"id"`
}

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
	donator := new(tID)
	err := Mysql.Get(donator, "SELECT id FROM donator WHERE name=?", name)
	if err != nil {
		res, _ := Mysql.Exec("INSERT INTO donator (`name`) VALUES (?)", name)
		donator.Id, _ = res.LastInsertId()
	}
	return donator.Id
}

func getRoomInfo(name string) (*tID, bool) {
	result := true
	room := new(tID)
	err := Mysql.Get(room, "SELECT id FROM room WHERE name=?", name)
	if err != nil {
		result = false
	}
	return room, result
}

func saveDB() {
	last := time.Now().Unix()
	bulk := make(map[int]saveData)
	data := make(map[string]*DonatorCache)

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

			if randInt(0, 10000) == 777 { // 0.001%
				l := len(data)
				for k, v := range data {
					if now > v.Last+60*60*48 {
						delete(data, k)
					}
				}
				fmt.Println("Clean map:", l, "=>", len(data))
			}

			Mysql.Exec("UPDATE `room` SET `last` = ? WHERE `id` = ?", m.Now, m.Rid)

			num := len(bulk)

			bulk[num] = m

			if num >= 999 || now >= last+10 {
				tx, err := Mysql.Begin()
				if err == nil {
					for _, v := range bulk {
						tx.Exec("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, ?)", data[v.From].Id, v.Rid, v.Amount, v.Now)
					}
				}
				tx.Commit()

				tx, err = Clickhouse.Begin()
				if err == nil {
					st, _ := tx.Prepare("INSERT INTO stat VALUES (?, ?, ?, ?)")
					//fmt.Println("G:", err)
					for _, v := range bulk {
						st.Exec(uint32(data[v.From].Id), uint32(v.Rid), uint32(v.Amount), time.Unix(v.Now, 0))
						//fmt.Println("B:", aaa, sss)
					}
					tx.Commit()
					st.Close()
				}

				last = now
				bulk = make(map[int]saveData)
			}
			if m.Amount > 99 {
				msg, err := json.Marshal(AnnounceDonate{Room: m.Room, Donator: m.From, Amount: m.Amount})
				if err == nil {
					hub.broadcast <- msg
				}
			}
		}
	}
}

func saveLogs() {
	last := time.Now().Unix()
	bulk := make(map[int]saveLog)
	for {
		select {
		case m := <-slog:
			if len(m.Mes) > 0 {
				num := len(bulk)
				bulk[num] = m
				now := time.Now().Unix()
				if num >= 2047 || now >= last+10 {
					tx, err := Mysql.Begin()
					if err == nil {
						for _, v := range bulk {
							tx.Exec("INSERT INTO `logs` (`rid`, `time`, `mes`) VALUES (?, ?, ?)", v.Rid, v.Now, v.Mes)
						}
						tx.Commit()
					}
					last = now
					bulk = make(map[int]saveLog)
				}
			}
		}
	}
}
