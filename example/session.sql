CREATE TABLE IF NOT EXISTS `sessions` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `user_id` int(6) NOT NULL DEFAULT '0',
  `logged_in` int(1) NOT NULL DEFAULT '0',
  `start_time` int(11) NOT NULL DEFAULT '0',
  `current_time` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(16) NOT NULL DEFAULT '',
  `page` VARCHAR(255) NULL DEFAULT NULL,
  `browser` varchar(255) NULL DEFAULT NULL,
  `data` text NULL DEFAULT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB;
