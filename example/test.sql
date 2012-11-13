-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Czas wygenerowania: 13 Lis 2012, 15:50
-- Wersja serwera: 5.5.24-log
-- Wersja PHP: 5.3.13

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Baza danych: `tikori5`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `test_team`
--

CREATE TABLE IF NOT EXISTS `test_team` (
  `team_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `team_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Zrzut danych tabeli `test_team`
--

INSERT INTO `test_team` (`team_id`, `team_name`) VALUES
(1, 'Team dobry');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `test_user`
--

CREATE TABLE IF NOT EXISTS `test_user` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `team` int(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `team` (`team`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Zrzut danych tabeli `test_user`
--

INSERT INTO `test_user` (`id`, `name`, `team`) VALUES
(1, 'Franek Kimono', 1),
(5, 'Adam Slodowy', 1);

--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `test_user`
--
ALTER TABLE `test_user`
  ADD CONSTRAINT `test_user_ibfk_1` FOREIGN KEY (`team`) REFERENCES `test_team` (`team_id`) ON DELETE SET NULL ON UPDATE SET NULL;
SET FOREIGN_KEY_CHECKS=1;