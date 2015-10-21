-- PHPOE-2081
INSERT INTO `db_patches` VALUES ('PHPOE-2081', NOW());

CREATE TABLE `z2081_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2081_import_mapping` SELECT * FROM `import_mapping`;
DROP TABLE `import_mapping`;

--
-- Table structure for table `import_mapping`
--

CREATE TABLE IF NOT EXISTS `import_mapping` (
  `id` int(11) NOT NULL,
  `model` varchar(50) NOT NULL,
  `column_name` varchar(30) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `order` int(11) DEFAULT '0',
  `foreign_key` int(11) DEFAULT '0' COMMENT '0: not foreign key, 1: field options, 2: direct table',
  `lookup_plugin` varchar(50) DEFAULT NULL,
  `lookup_model` varchar(50) DEFAULT NULL,
  `lookup_column` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `import_mapping`
--

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(1, 'Institutions', 'name', NULL, 1, 0, NULL, NULL, NULL),
(2, 'Institutions', 'alternative_name', NULL, 2, 0, NULL, NULL, NULL),
(3, 'Institutions', 'code', NULL, 3, 0, NULL, NULL, NULL),
(4, 'Institutions', 'address', NULL, 4, 0, NULL, NULL, NULL),
(5, 'Institutions', 'postal_code', NULL, 5, 0, NULL, NULL, NULL),
(6, 'Institutions', 'contact_person', NULL, 6, 0, NULL, NULL, NULL),
(7, 'Institutions', 'telephone', NULL, 7, 0, NULL, NULL, NULL),
(8, 'Institutions', 'fax', NULL, 8, 0, NULL, NULL, NULL),
(9, 'Institutions', 'email', NULL, 9, 0, NULL, NULL, NULL),
(10, 'Institutions', 'date_opened', NULL, 11, 0, NULL, NULL, NULL),
(11, 'Institutions', 'year_opened', NULL, 12, 0, NULL, NULL, NULL),
(12, 'Institutions', 'date_closed', NULL, 13, 0, NULL, NULL, NULL),
(13, 'Institutions', 'year_closed', NULL, 14, 0, NULL, NULL, NULL),
(14, 'Institutions', 'longitude', NULL, 15, 0, NULL, NULL, NULL),
(15, 'Institutions', 'website', NULL, 10, 0, NULL, NULL, NULL),
(16, 'Institutions', 'latitude', NULL, 16, 0, NULL, NULL, NULL),
(17, 'Institutions', 'area_id', 'Code', 17, 2, 'Area', 'Areas', 'code'),
(18, 'Institutions', 'area_administrative_id', 'Code', 18, 2, 'Area', 'AreaAdministratives', 'code'),
(19, 'Institutions', 'institution_site_locality_id', 'Code', 19, 1, 'Institution', 'Localities', 'national_code'),
(20, 'Institutions', 'institution_site_type_id', 'Code', 20, 1, 'Institution', 'Types', 'national_code'),
(21, 'Institutions', 'institution_site_ownership_id', 'Code', 21, 1, 'Institution', 'Ownerships', 'national_code'),
(22, 'Institutions', 'institution_site_status_id', 'Code', 22, 1, 'Institution', 'Statuses', 'national_code'),
(23, 'Institutions', 'institution_site_sector_id', 'Code', 23, 1, 'Institution', 'Sectors', 'national_code'),
(24, 'Institutions', 'institution_site_provider_id', 'Code', 24, 1, 'Institution', 'Providers', 'national_code'),
(25, 'Institutions', 'institution_site_gender_id', 'Code', 25, 1, 'Institution', 'Genders', 'national_code'),
(26, 'Students', 'openemis_no', '(Leave as blank for new entries)', 1, 0, NULL, NULL, NULL),
(27, 'Students', 'first_name', NULL, 2, 0, NULL, NULL, NULL),
(28, 'Students', 'middle_name', NULL, 3, 0, NULL, NULL, NULL),
(29, 'Students', 'third_name', NULL, 4, 0, NULL, NULL, NULL),
(30, 'Students', 'last_name', NULL, 5, 0, NULL, NULL, NULL),
(31, 'Students', 'preferred_name', NULL, 6, 0, NULL, NULL, NULL),
(32, 'Students', 'gender_id', 'Code (M/F)', 7, 2, 'User', 'Genders', 'code'),
(33, 'Students', 'date_of_birth', NULL, 8, 0, NULL, NULL, NULL),
(34, 'Students', 'address', NULL, 9, 0, NULL, NULL, NULL),
(35, 'Students', 'postal_code', NULL, 10, 0, NULL, NULL, NULL),
(36, 'Students', 'address_area_id', 'Code', 11, 2, 'Area', 'AreaAdministratives', 'code'),
(37, 'Students', 'birthplace_area_id', 'Code', 12, 2, 'Area', 'AreaAdministratives', 'code'),
(38, 'Students', 'is_student', '(Leave this blank)', 13, 0, NULL, NULL, NULL),
(39, 'Staff', 'openemis_no', '(Leave as blank for new entries)', 1, 0, NULL, NULL, NULL),
(40, 'Staff', 'first_name', NULL, 2, 0, NULL, NULL, NULL),
(41, 'Staff', 'middle_name', NULL, 3, 0, NULL, NULL, NULL),
(42, 'Staff', 'third_name', NULL, 4, 0, NULL, NULL, NULL),
(43, 'Staff', 'last_name', NULL, 5, 0, NULL, NULL, NULL),
(44, 'Staff', 'preferred_name', NULL, 6, 0, NULL, NULL, NULL),
(45, 'Staff', 'gender_id', 'Code (M/F)', 7, 2, 'User', 'Genders', 'code'),
(46, 'Staff', 'date_of_birth', NULL, 8, 0, NULL, NULL, NULL),
(47, 'Staff', 'address', NULL, 9, 0, NULL, NULL, NULL),
(48, 'Staff', 'postal_code', NULL, 10, 0, NULL, NULL, NULL),
(49, 'Staff', 'address_area_id', 'Code', 11, 2, 'Area', 'AreaAdministratives', 'code'),
(50, 'Staff', 'birthplace_area_id', 'Code', 12, 2, 'Area', 'AreaAdministratives', 'code'),
(51, 'Staff', 'is_staff', '(Leave this blank)', 13, 0, NULL, NULL, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=52;
