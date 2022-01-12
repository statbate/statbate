package main

//import "fmt"

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
	Rid    int64
	Now    int64
	Mes    string
}

func saveDonate(did, rid, token, now int64) {
	Mysql.Exec("UPDATE `room` SET `last` = ? WHERE `id` = ?", now, rid);
	_, err := Mysql.Exec("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, ?)", did, rid, token, now); if err == nil {
		tx, err := Clickhouse.Begin()
		if err == nil{
			tx.Exec("INSERT INTO stat VALUES (?, ?, ?, ?)", did, rid, token, now)
			tx.Commit()
		}
	}
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

	//fmt.Println("donators in cache:", len(data))

	for {
		select {
		case m := <-save:
			//fmt.Println("Save channel:", len(save), cap(save))
			if _, ok := data[m.From]; !ok {
				data[m.From] = getDonId(m.From)
			}
			if m.Amount > 99 {
				msg, err := json.Marshal(AnnounceDonate{Room: m.Room, Donator: m.From, Amount: m.Amount})
				if err == nil {
					hub.broadcast <- msg
				}
			}
			saveDonate(data[m.From], m.Rid, m.Amount, m.Now)
		}
	}
}

func saveLogs() {
	for {
		select {
		case m := <-slog:
			Mysql.Exec("INSERT INTO `logs` (`rid`, `time`, `mes`) VALUES (?, ?, ?)", m.Rid, m.Now, m.Mes);
		}
	}
}
