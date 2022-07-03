package main

// import "fmt"
import (
	"database/sql"
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
	Mes string
	Rid int64
	Now int64
}

func getDonId(name string) (int64, bool) {
	donator := new(tID)
	err := Mysql.Get(donator, "SELECT id FROM donator WHERE name=?", name)
	if err != nil {
		if err == sql.ErrNoRows { // ошибка может быть разная, если мы ничего не нашли всегда возвращается sql.ErrNoRows, при других ошибках вернется иное, лучше делать разделение логики в этом случае
			res, _ := Mysql.Exec("INSERT INTO donator (`name`) VALUES (?)", name)
			donator.Id, _ = res.LastInsertId()
		} else {
			logErrorf("database err: %v", err)
			return 0, false
		}
	}
	return donator.Id, true
}

func getRoomInfo(name string) (*tID, bool) {
	result := true
	room := new(tID)
	err := Mysql.Get(room, "SELECT id FROM room WHERE name=?", name)
	if err != nil {
		if err != sql.ErrNoRows {
			logErrorf("database err: %v", err)
		}
		result = false
	}
	return room, result
}

func saveDB() {
	var id int64
	var name string

	data := make(map[string]int64)

	rows, err := Mysql.Query("SELECT * FROM donator")
	if err == nil {
		for rows.Next() {
			err := rows.Scan(&id, &name)
			if err == nil {
				data[name] = id
			}
		}
	}

	// fmt.Println("donators in cache:", len(data

	last := time.Now().Unix()
	bulk := make(map[int]saveData)

	for {
		select {
		case m := <-save:
			// fmt.Println("Save channel:", len(save), cap(save))
			if _, ok := data[m.From]; !ok {
				if from, ok := getDonId(m.From); ok { // check that donId returned without error from database
					data[m.From] = from
				}
			}
			if m.Amount > 99 {
				msg, err := json.Marshal(AnnounceDonate{Room: m.Room, Donator: m.From, Amount: m.Amount})
				if err == nil {
					hub.broadcast <- msg
				}
			}

			if _, err = Mysql.Exec("UPDATE `room` SET `last` = ? WHERE `id` = ?", m.Now, m.Rid); err != nil {
				logErrorf("database err: %v", err)
				return
			}

			num := len(bulk)

			bulk[num] = m

			now := time.Now().Unix()

			if num >= 999 || now >= last+10 {
				tx, err := Mysql.Begin()
				if err == nil {
					for _, v := range bulk {
						if _, err = tx.Exec("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, ?)", data[v.From], v.Rid, v.Amount, v.Now); err != nil {
							logErrorf("database err: %v", err)
							return
						}
					}
					if err = tx.Commit(); err != nil {
						logErrorf("database err: %v", err)
						return
					}
				} else {
					logErrorf("database err: %v", err)
					return
				}

				tx, err = Clickhouse.Begin()
				if err != nil {
					logErrorf("database err: %v", err)
					return
				}

				st, err := tx.Prepare("INSERT INTO stat VALUES (?, ?, ?, ?)")
				if err != nil {
					logErrorf("database err: %v", err)
					return
				}
				// fmt.Println("G:", err)
				for _, v := range bulk {
					_, err = st.Exec(uint32(data[v.From]), uint32(v.Rid), uint32(v.Amount), time.Unix(v.Now, 0))
					if err != nil {
						logErrorf("database err: %v", err)
						return
					}
					// fmt.Println("B:", aaa, sss)
				}
				if err = tx.Commit(); err != nil {
					logErrorf("database err: %v", err)
					return
				}

				if err = st.Close(); err != nil {
					logErrorf("database err: %v", err)
					return
				}

				last = now
				bulk = make(map[int]saveData)
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
			num := len(bulk)
			bulk[num] = m
			now := time.Now().Unix()
			if num >= 2047 || now >= last+10 {
				tx, err := Mysql.Begin()
				if err == nil {
					for _, v := range bulk {
						_, err = tx.Exec("INSERT INTO `logs` (`rid`, `time`, `mes`) VALUES (?, ?, ?)", v.Rid, v.Now, v.Mes)
						if err != nil {
							logErrorf("database err: %v", err)
							if err = tx.Rollback(); err != nil {
								logErrorf("database err: %v", err)
							}
							return
						}
					}
					if err = tx.Commit(); err != nil {
						logErrorf("database err: %v", err)
						if err = tx.Rollback(); err != nil {
							logErrorf("database err: %v", err)
						}
						return
					}
				}
				last = now
				bulk = make(map[int]saveLog)
			}
		}
	}
}
