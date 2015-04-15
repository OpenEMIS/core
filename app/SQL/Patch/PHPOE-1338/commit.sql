--
-- Table structure for table `import_mapping`
--

CREATE TABLE `import_mapping` (
`id` int(11) NOT NULL,
  `model` varchar(50) NOT NULL,
  `column_name` varchar(30) NOT NULL,
  `order` int(11) DEFAULT '0',
  `foreigh_key` int(11) DEFAULT '0' COMMENT '0: not foreign key, 1: normal foreign key, 2: heavy load foreign key',
  `lookup_model` varchar(50) DEFAULT NULL,
  `lookup_column` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `import_mapping`
--

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `order`, `foreigh_key`, `lookup_model`, `lookup_column`) VALUES
(1, 'InstitutionSite', 'name', 1, 0, NULL, NULL),
(2, 'InstitutionSite', 'alternative_name', 2, 0, NULL, NULL),
(3, 'InstitutionSite', 'code', 3, 0, NULL, NULL),
(4, 'InstitutionSite', 'address', 4, 0, NULL, NULL),
(5, 'InstitutionSite', 'postal_code', 5, 0, NULL, NULL),
(6, 'InstitutionSite', 'contact_person', 6, 0, NULL, NULL),
(7, 'InstitutionSite', 'telephone', 7, 0, NULL, NULL),
(8, 'InstitutionSite', 'fax', 8, 0, NULL, NULL),
(9, 'InstitutionSite', 'email', 9, 0, NULL, NULL),
(10, 'InstitutionSite', 'date_opened', 11, 0, NULL, NULL),
(11, 'InstitutionSite', 'year_opened', 12, 0, NULL, NULL),
(12, 'InstitutionSite', 'date_closed', 13, 0, NULL, NULL),
(13, 'InstitutionSite', 'year_closed', 14, 0, NULL, NULL),
(14, 'InstitutionSite', 'longitude', 15, 0, NULL, NULL),
(15, 'InstitutionSite', 'website', 10, 0, NULL, NULL),
(16, 'InstitutionSite', 'latitude', 16, 0, NULL, NULL),
(17, 'InstitutionSite', 'area_id', 17, 2, 'Area', 'code'),
(18, 'InstitutionSite', 'area_administrative_id', 18, 2, 'AreaAdministrative', 'code'),
(19, 'InstitutionSite', 'institution_site_locality_id', 19, 1, 'InstitutionSiteLocality', 'national_code'),
(20, 'InstitutionSite', 'institution_site_type_id', 20, 1, 'InstitutionSiteType', 'national_code'),
(21, 'InstitutionSite', 'institution_site_ownership_id', 21, 1, 'InstitutionSiteOwnership', 'national_code'),
(22, 'InstitutionSite', 'institution_site_status_id', 22, 1, 'InstitutionSiteStatus', 'national_code'),
(23, 'InstitutionSite', 'institution_site_sector_id', 23, 1, 'InstitutionSiteSector', 'national_code'),
(24, 'InstitutionSite', 'institution_site_provider_id', 24, 1, 'InstitutionSiteProvider', 'national_code'),
(25, 'InstitutionSite', 'institution_site_gender_id', 25, 1, 'InstitutionSiteGender', 'national_code');

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