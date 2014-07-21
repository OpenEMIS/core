-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2014 at 09:38 AM
-- Server version: 5.6.11
-- PHP Version: 5.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `dev_openemis_demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `config_item_options`
--

DROP TABLE IF EXISTS `config_item_options`;
CREATE TABLE IF NOT EXISTS `config_item_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_type` varchar(50) COLLATE utf8_general_ci NOT NULL,
  `option` varchar(100) COLLATE utf8_general_ci NOT NULL,
  `value` varchar(100) COLLATE utf8_general_ci NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `config_item_options`
--

INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES
(1, 'date_format', 'date(''Y-n-j'')', 'Y-m-d', 1, 1),
(2, 'date_format', 'date(''j-M-Y'')', 'd-M-Y', 2, 1),
(3, 'date_format', 'date(''j-n-Y'')', 'd-m-Y', 3, 1),
(4, 'date_format', 'date(''j/n/Y'')', 'd/m/Y', 4, 1),
(5, 'date_format', 'date(''n/d/Y'')', 'm/d/Y', 5, 1),
(6, 'date_format', 'date(''F j, Y'')', 'F d, Y', 6, 1),
(7, 'date_format', 'date(''jS F Y'')', 'dS F Y', 7, 1),
(10, 'authentication_type', 'Local', 'Local', 1, 1),
(11, 'authentication_type', 'LDAP', 'LDAP', 2, 1),
(12, 'language', 'العربية', 'ara', 1, 1),
(13, 'language', '中文', 'chi', 2, 1),
(14, 'language', 'English', 'eng', 3, 1),
(15, 'language', 'Français', 'fre', 4, 1),
(16, 'language', 'русский', 'ru', 5, 1),
(17, 'language', 'español', 'spa', 6, 1),
(18, 'yes_no', 'Yes', '1', 1, 1),
(19, 'yes_no', 'No', '0', 2, 1),
(20, 'wizard', 'Non-Mandatory', '0', 1, 1),
(21, 'wizard', 'Mandatory', '1', 2, 1),
(22, 'wizard', 'Excluded', '2', 3, 1),
(23, 'database:Country', 'Country.name', 'Country.id', 1, 1),
(24, 'database:SchoolYear', 'SchoolYear.name', 'SchoolYear.id', 1, 1),
(25, 'yearbook_orientation', 'Portrait', 'P', 1, 1),
(26, 'yearbook_orientation', 'Landscape', 'L', 2, 1),
(27, 'first_day_of_week', 'Monday', 'monday', 1, 1),
(28, 'first_day_of_week', 'Tuesday', 'tuesday', 2, 1),
(29, 'first_day_of_week', 'Wednesday', 'wednesday', 3, 1),
(30, 'first_day_of_week', 'Thursday', 'thursday', 4, 1),
(31, 'first_day_of_week', 'Friday', 'friday', 5, 1),
(32, 'first_day_of_week', 'Saturday', 'saturday', 6, 1),
(33, 'first_day_of_week', 'Sunday', 'sunday', 7, 1),
(34, 'database:AreaLevel', 'AreaLevel.name', 'AreaLevel.id', 1, 1);
