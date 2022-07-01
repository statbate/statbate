CREATE TABLE `donator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varbinary(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=binary ROW_FORMAT=DYNAMIC;

CREATE TABLE `room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varbinary(30) NOT NULL,
  `gender` tinyint(1) NOT NULL DEFAULT 0,
  `fans` int(11) NOT NULL DEFAULT 0,
  `last` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `gender` (`gender`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=binary ROW_FORMAT=DYNAMIC;

CREATE TABLE `stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `did` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  `token` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=binary ROW_FORMAT=DYNAMIC;
