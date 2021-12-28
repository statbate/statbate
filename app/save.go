package main

import (
	_ "github.com/ClickHouse/clickhouse-go"
	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"

	"time"
	"strconv"
	//"encoding/json"
)

type tableRoom struct {
	Id     int64   `db:"id"`
	Name   string  `db:"name"`
	Gender int     `db:"gender"`
	Fans   int     `db:"fans"`
	Last   int64   `db:"last"`
}

type tableDonator struct {
	Id    int64    `db:"id"`
	Name  string  `db:"name"`
}

type saveData struct {
	room, donator string
	token int64
}

type Save struct {
	donate chan *saveData
}

func saveDonate(conn *sqlx.DB, did, rid, token int64) int64 {
	res, _ := conn.Exec("INSERT INTO `stat` (`did`, `rid`, `token`, `time`) VALUES (?, ?, ?, unix_timestamp(now()))", did, rid, token)
	id, _ := res.LastInsertId()
	return id
}

func saveClickhouse(conn *sqlx.DB, id, did, rid, token int64) {
	tx, _ := conn.Begin()
	tx.Exec("INSERT INTO stat VALUES (?, ?, ?, ?, ?)", id, did, rid, token, time.Now().Unix())
	tx.Commit()
}

func getDonatorID(conn *sqlx.DB, name string) int64 {
	var donator tableDonator
	err := conn.Get(&donator, "SELECT * FROM donator WHERE name=?", name)
	if err != nil {		
		res, _ := conn.Exec("INSERT INTO donator (`name`) VALUES (?)", name)
		id, _ := res.LastInsertId()
		return id
	}
	return donator.Id
}

func updateWorker(conn *sqlx.DB, id int64) bool {
	_, err := conn.Exec("UPDATE room SET last = unix_timestamp(now()) WHERE id =?", id)
	if err != nil {
		return false
	}
	return true
}

func getRoomInfo(conn *sqlx.DB, name string) (tableRoom, bool) {
	result := true
	var room tableRoom
	err := conn.Get(&room, "SELECT * FROM room WHERE name=?", name)
	if err != nil {
		result = false
	}
	return room, result
}

func countRooms() string {
	rooms.Lock()
    defer rooms.Unlock()
    return strconv.Itoa(len(rooms.name))
}

func saveBase(s *Save, h *Hub){
	conn, err := sqlx.Connect("mysql", "user:passwd@unix(/var/run/mysqld/mysqld.sock)/stat?interpolateParams=true")
	if err != nil {
		panic(err)
	}
	defer conn.Close()
	
	clickhouse, err := sqlx.Connect("clickhouse", "tcp://127.0.0.1:9000/?database=statbate&compress=true&debug=false")
	if err != nil {
		panic(err)
	}
	defer clickhouse.Close()
	
	for {
		select {
			case info := <-s.donate:
			room, ok := getRoomInfo(conn, info.room)
			if ok {
				updateWorker(conn, room.Id)
				
				d := getDonatorID(conn, info.donator)
				
				lastID := saveDonate(conn, d, room.Id, info.token)
				saveClickhouse(clickhouse, lastID, d, room.Id, info.token)
				if info.token >= 100 {
					msg, err := json.Marshal(map[string]string{"room": info.room, "donator": info.donator, "amount": strconv.FormatInt(info.token, 10), "trackCount": countRooms()})
					if err == nil {
						h.broadcast <- msg
					}
				}
			}
		}
	}
}

func sendPost(room string, name string, token int64) {
	//t, _ :=  strconv.ParseInt(token, 10, 64)
	data := &saveData{room: room, donator: name, token: token}
	saveStat.donate <- data
}
