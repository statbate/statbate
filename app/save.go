package main

import (
	"time"
)

type tableID struct {
	Id     int64   `db:"id"`
}

type saveData struct {
	rid     int64
	donator string
	token   int64
}

type Save struct {
	donate chan *saveData
}

func saveDonate(did, rid, token int64) int64 {
	res, _ := Mysql.Exec("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, unix_timestamp(now()))", did, rid, token)
	id, _ := res.LastInsertId()
	return id
}

func saveClickhouse(id, did, rid, token int64) {
	tx, _ := Clickhouse.Begin()
	tx.Exec("INSERT INTO stat VALUES (?, ?, ?, ?, ?)", id, did, rid, token, time.Now().Unix())
	tx.Commit()
}

func getDonatorID(name string) int64 {
	var donator tableID
	err := Mysql.Get(&donator, "SELECT id FROM donator WHERE name=?", name)
	if err != nil {		
		res, _ := Mysql.Exec("INSERT INTO donator (`name`) VALUES (?)", name)
		id, _ := res.LastInsertId()
		return id
	}
	return donator.Id
}

func getRoomInfo(name string) (tableID, bool) {
	result := true
	var room tableID
	err := Mysql.Get(&room, "SELECT id FROM room WHERE name=?", name)
	if err != nil {
		result = false
	}
	return room, result
}

func saveBase(s *Save){	
	for {
		select {
			case info := <-s.donate:
			d := getDonatorID(info.donator)
			lastID := saveDonate(d, info.rid, info.token)
			saveClickhouse(lastID, d, info.rid, info.token)
		}
	}
}
