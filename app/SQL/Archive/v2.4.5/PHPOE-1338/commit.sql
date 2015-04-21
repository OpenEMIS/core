UPDATE `navigations` SET `pattern` = 'index$|advanced|import' WHERE `module` LIKE 'Institution' AND `title` LIKE 'List of Institutions';
UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|add' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'General' AND `name` LIKE 'Institution';

--
-- Table structure for table `import_mapping`
--

CREATE TABLE `import_mapping` (
`id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `column_name` varchar(30) NOT NULL,
  `is_code` int(11) NOT NULL DEFAULT '0',
  `order` int(11) DEFAULT '0',
  `foreigh_key` int(11) DEFAULT '0' COMMENT '0: not foreign key, 1: normal foreign key, 2: heavy load foreign key',
  `lookup_model` varchar(50) DEFAULT NULL,
  `lookup_column` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `import_mapping`
--

INSERT INTO `import_mapping` (`id`, `module`, `model`, `column_name`, `is_code`, `order`, `foreigh_key`, `lookup_model`, `lookup_column`) VALUES
(1, 'InstitutionSite', 'InstitutionSite', 'name', 0, 1, 0, NULL, NULL),
(2, 'InstitutionSite', 'InstitutionSite', 'alternative_name', 0, 2, 0, NULL, NULL),
(3, 'InstitutionSite', 'InstitutionSite', 'code', 0, 3, 0, NULL, NULL),
(4, 'InstitutionSite', 'InstitutionSite', 'address', 0, 4, 0, NULL, NULL),
(5, 'InstitutionSite', 'InstitutionSite', 'postal_code', 0, 5, 0, NULL, NULL),
(6, 'InstitutionSite', 'InstitutionSite', 'contact_person', 0, 6, 0, NULL, NULL),
(7, 'InstitutionSite', 'InstitutionSite', 'telephone', 0, 7, 0, NULL, NULL),
(8, 'InstitutionSite', 'InstitutionSite', 'fax', 0, 8, 0, NULL, NULL),
(9, 'InstitutionSite', 'InstitutionSite', 'email', 0, 9, 0, NULL, NULL),
(10, 'InstitutionSite', 'InstitutionSite', 'date_opened', 0, 11, 0, NULL, NULL),
(11, 'InstitutionSite', 'InstitutionSite', 'year_opened', 0, 12, 0, NULL, NULL),
(12, 'InstitutionSite', 'InstitutionSite', 'date_closed', 0, 13, 0, NULL, NULL),
(13, 'InstitutionSite', 'InstitutionSite', 'year_closed', 0, 14, 0, NULL, NULL),
(14, 'InstitutionSite', 'InstitutionSite', 'longitude', 0, 15, 0, NULL, NULL),
(15, 'InstitutionSite', 'InstitutionSite', 'website', 0, 10, 0, NULL, NULL),
(16, 'InstitutionSite', 'InstitutionSite', 'latitude', 0, 16, 0, NULL, NULL),
(17, 'InstitutionSite', 'InstitutionSite', 'area_id', 1, 17, 2, 'Area', 'code'),
(18, 'InstitutionSite', 'InstitutionSite', 'area_administrative_id', 1, 18, 2, 'AreaAdministrative', 'code'),
(19, 'InstitutionSite', 'InstitutionSite', 'institution_site_locality_id', 1, 19, 1, 'InstitutionSiteLocality', 'national_code'),
(20, 'InstitutionSite', 'InstitutionSite', 'institution_site_type_id', 1, 20, 1, 'InstitutionSiteType', 'national_code'),
(21, 'InstitutionSite', 'InstitutionSite', 'institution_site_ownership_id', 1, 21, 1, 'InstitutionSiteOwnership', 'national_code'),
(22, 'InstitutionSite', 'InstitutionSite', 'institution_site_status_id', 1, 22, 1, 'InstitutionSiteStatus', 'national_code'),
(23, 'InstitutionSite', 'InstitutionSite', 'institution_site_sector_id', 1, 23, 1, 'InstitutionSiteSector', 'national_code'),
(24, 'InstitutionSite', 'InstitutionSite', 'institution_site_provider_id', 1, 24, 1, 'InstitutionSiteProvider', 'national_code'),
(25, 'InstitutionSite', 'InstitutionSite', 'institution_site_gender_id', 1, 25, 1, 'InstitutionSiteGender', 'national_code');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `import_mapping`
--
ALTER TABLE `import_mapping`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `import_mapping`
--
ALTER TABLE `import_mapping`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;