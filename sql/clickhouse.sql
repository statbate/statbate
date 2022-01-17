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
	INDEX a did TYPE bloom_filter() GRANULARITY 1,
	INDEX b rid TYPE bloom_filter() GRANULARITY 1
) 
ENGINE = MergeTree()
PARTITION BY toYYYYMMDD(time)
ORDER BY (time, rid, did)
PRIMARY KEY (time)
SETTINGS index_granularity = 8192;

#CREATE TABLE stat_buffer (
#	did   UInt32,
#	rid   UInt32,
#	token UInt32,
#	time  Date
#)  
#ENGINE = Buffer('statbate', 'stat', 16, 5, 30, 1000, 10000, 1000000, 10000000);
