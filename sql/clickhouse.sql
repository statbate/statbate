CREATE DATABASE statbate;
USE statbate;

CREATE TABLE room
(
    id Int32,
    gender Int32
) ENGINE = MySQL('127.0.0.1:3306', 'base', 'room', 'user', 'passwd');

CREATE TABLE stat
(
    id    Int32,
    did   Int32,
    rid   Int32,
    token Int32,
    time  Int32
) ENGINE = MergeTree ORDER BY rid
SETTINGS index_granularity = 8192;
