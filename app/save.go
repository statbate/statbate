package main

type tID struct {
	Id int64 `db:"id"`
}

type saveData struct {
	rid     int64
	donator string
	token   int64
}

type Save struct {
	donate chan *saveData
}

func saveDonate(did, rid, token, now int64) {	
	res, _ := Mysql.Exec("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, ?)", did, rid, token, now)
	id, _ := res.LastInsertId()

	tx, _ := Clickhouse.Begin()
	tx.Exec("INSERT INTO stat VALUES (?, ?, ?, ?, ?)", id, did, rid, token, now)
	tx.Commit()
}

func getDonId(name string) int64{
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
