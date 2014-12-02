-- phpMyAdmin SQL Dump
-- version 3.5.8.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 01, 2013 at 01:59 AM
-- Server version: 5.5.34-0ubuntu0.13.04.1
-- PHP Version: 5.4.9-4ubuntu2.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `db_scraple`
--

CREATE DATABASE IF NOT EXISTS `db_scraple` CHARACTER SET utf8 COLLATE utf8_bin;
GRANT ALL ON `db_scraple`.* TO `scraplemysql`@localhost IDENTIFIED BY 'scraple123456';
FLUSH PRIVILEGES;

use db_scraple;

-- --------------------------------------------------------

--
-- Table structure for table `t_filedir_apps`
--

CREATE TABLE IF NOT EXISTS `t_filedir_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(200) COLLATE utf8_bin NOT NULL,
  `developer` varchar(200) COLLATE utf8_bin NOT NULL,
  `filedir_app_url` varchar(500) COLLATE utf8_bin NOT NULL,
  `googleplay_app_url` varchar(500) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=98740 ;

-- --------------------------------------------------------

--
-- Table structure for table `t_googleplay_apps`
--

CREATE TABLE IF NOT EXISTS `t_googleplay_apps` (
  `id` varchar(255) COLLATE utf8_bin NOT NULL,
  `app_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `date_released` varchar(255) COLLATE utf8_bin NOT NULL,
  `developer` varchar(255) COLLATE utf8_bin NOT NULL,
  `total_score` varchar(255) COLLATE utf8_bin NOT NULL,
  `total_num_ratings` varchar(255) COLLATE utf8_bin NOT NULL,
  `partial_num_ratings_5` varchar(255) COLLATE utf8_bin NOT NULL,
  `partial_num_ratings_4` varchar(255) COLLATE utf8_bin NOT NULL,
  `partial_num_ratings_3` varchar(255) COLLATE utf8_bin NOT NULL,
  `partial_num_ratings_2` varchar(255) COLLATE utf8_bin NOT NULL,
  `partial_num_ratings_1` varchar(255) COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  `reviews` text COLLATE utf8_bin NOT NULL,
  `last_updated` varchar(255) COLLATE utf8_bin NOT NULL,
  `app_size` varchar(255) COLLATE utf8_bin NOT NULL,
  `installs` varchar(255) COLLATE utf8_bin NOT NULL,
  `version` varchar(255) COLLATE utf8_bin NOT NULL,
  `requires_version` varchar(255) COLLATE utf8_bin NOT NULL,
  `content_rating` varchar(255) COLLATE utf8_bin NOT NULL,
  `developer_links` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
