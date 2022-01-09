CREATE DATABASE statbate;
USE statbate;

CREATE TABLE room
(
	id Int32,
	gender UInt8
) ENGINE = MySQL('127.0.0.1:3306', 'base', 'room', 'user', 'passwd');


CREATE TABLE stat
(
	did   UInt32,
	rid   UInt32,
	token UInt32,
	time  Date,
	INDEX a did TYPE set(0) GRANULARITY 1,
	INDEX b rid TYPE set(0) GRANULARITY 1
) 
ENGINE = MergeTree()
PARTITION BY toYYYYMMDD(time)
ORDER BY (time, rid, did)
PRIMARY KEY (time)
SETTINGS index_granularity = 8192;
