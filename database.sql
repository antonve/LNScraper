CREATE TABLE `book` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `label_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(50) DEFAULT '',
  `artist` varchar(50) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `type` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `label` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `publisher_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=499 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `leaf_url` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `time_discovered` datetime NOT NULL,
  `time_last_scraped` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=352 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `publisher` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `scraped_html` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `leaf_id` int(11) unsigned NOT NULL,
  `html` longtext NOT NULL,
  `date_first_retrieved` datetime NOT NULL,
  `date_last_checked` datetime NOT NULL,
  `hash` varchar(32) NOT NULL DEFAULT '',
  `normalized` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `leaf_hash` (`leaf_id`,`hash`)
) ENGINE=InnoDB AUTO_INCREMENT=382 DEFAULT CHARSET=utf8mb4;
