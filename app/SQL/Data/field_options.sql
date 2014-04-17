-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2014 at 04:30 PM
-- Server version: 5.6.11
-- PHP Version: 5.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dev_openemis_demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `field_options`
--

DROP TABLE IF EXISTS `field_options`;
CREATE TABLE IF NOT EXISTS `field_options` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent` varchar(50) DEFAULT NULL,
  `params` text,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;

--
-- Truncate table before insert `field_options`
--

TRUNCATE TABLE `field_options`;
--
-- Dumping data for table `field_options`
--

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'InstitutionProvider', 'Provider', 'Institution', '{"model":"InstitutionProvider"}', 1, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(2, 'InstitutionSector', 'Sector', 'Institution', '{"model":"InstitutionSector"}', 2, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(3, 'InstitutionStatus', 'Status', 'Institution', '{"model":"InstitutionStatus"}', 3, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(4, 'InstitutionSiteType', 'Type', 'Institution Site', '{"model":"InstitutionSiteType"}', 4, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(5, 'InstitutionSiteOwnership', 'Ownership', 'Institution Site', '{"model":"InstitutionSiteOwnership"}', 5, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(6, 'InstitutionSiteLocality', 'Locality', 'Institution Site', '{"model":"InstitutionSiteLocality"}', 6, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(7, 'InstitutionSiteStatus', 'Status', 'Institution Site', '{"model":"InstitutionSiteStatus"}', 7, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(8, 'FinanceNature', 'Nature', 'Finance', '{"model":"FinanceNature"}', 8, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(9, 'FinanceType', 'Types', 'Finance', '{"model":"FinanceType"}', 9, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(10, 'FinanceCategory', 'Categories', 'Finance', '{"model":"FinanceCategory"}', 10, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(11, 'FinanceSource', 'Source', 'Finance', '{"model":"FinanceSource"}', 11, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(12, 'Bank', 'Banks', 'Bank', '{"model":"Bank"}', 12, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(13, 'BankBranch', 'Branches', 'Bank', '{"model":"BankBranch"}', 13, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(14, 'AssessmentResultType', 'Result Type', 'Assessment', '{"model":"AssessmentResultType"}', 14, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(15, 'Country', 'Countries', NULL, '{"model":"Country"}', 15, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(16, 'Language', 'Languages', NULL, '{"model":"Language"}', 16, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(17, 'IdentityType', 'Identity Types', NULL, '{"model":"IdentityType"}', 17, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(18, 'QualityVisitType', 'Visit Types', 'Quality', NULL, 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(19, 'HealthRelationship', 'Relationships', 'Health', NULL, 19, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(20, 'HealthCondition', 'Conditions', 'Health', NULL, 20, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(21, 'HealthImmunization', 'Immunization', 'Health', NULL, 21, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(22, 'HealthAllergyType', 'Allergy Types', 'Health', NULL, 22, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(23, 'HealthTestType', 'Test Types', 'Health', NULL, 23, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(24, 'HealthConsultationType', 'Consultation Types', 'Health', NULL, 24, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(25, 'InfrastructureCategory', 'Categories', 'Infrastructure', '{"model":"InfrastructureCategory"}', 25, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(26, 'InfrastructureBuilding', 'Buildings', 'Infrastructure', '{"model":"InfrastructureBuilding"}', 26, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(27, 'InfrastructureEnergy', 'Energy', 'Infrastructure', '{"model":"InfrastructureEnergy"}', 27, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(28, 'InfrastructureFurniture', 'Furniture', 'Infrastructure', '{"model":"InfrastructureFurniture"}', 28, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(29, 'InfrastructureResource', 'Resources', 'Infrastructure', '{"model":"InfrastructureResource"}', 29, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(30, 'InfrastructureRoom', 'Rooms', 'Infrastructure', '{"model":"InfrastructureRoom"}', 30, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(31, 'InfrastructureSanitation', 'Sanitation', 'Infrastructure', '{"model":"InfrastructureSanitation"}', 31, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(32, 'InfrastructureWater', 'Water', 'Infrastructure', '{"model":"InfrastructureWater"}', 32, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(33, 'InfrastructureMaterial', 'Materials', 'Infrastructure', '{"model":"InfrastructureMaterial"}', 33, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(34, 'InfrastructureStatus', 'Statuses', 'Infrastructure', '{"model":"InfrastructureStatus"}', 34, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
