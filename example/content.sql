SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `content` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `path` varchar(255) NOT NULL,
  `parent` int(11) unsigned DEFAULT NULL,
  `type` int(5) unsigned NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `comments` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `content_translation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) unsigned NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short` text NOT NULL,
  `long` text,
  `img` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `comm` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `language_id` (`language_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `language` (
  `language_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_code` varchar(2) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=3 ;


ALTER TABLE `content_translation`
  ADD CONSTRAINT `content_translation_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `content_translation_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;