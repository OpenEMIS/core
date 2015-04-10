-- PHPOE-650
UPDATE `security_functions` SET `_view` = 'roles|permissions', `_add` = '_edit:rolesAdd' 
WHERE `controller` LIKE 'Security' AND `module` LIKE 'Administration' AND `name` LIKE 'Roles';

-- PHPOE-1263
UPDATE config_items SET name = 'yearbook_school_year' WHERE name = 'yearbook_academic_period';
UPDATE config_items SET option_type = 'database:SchoolYear' WHERE name = 'yearbook_school_year';
UPDATE config_items SET label = 'School Year' WHERE name = 'yearbook_school_year';

UPDATE config_item_options SET option_type  = 'database:SchoolYear', config_item_options.option = 'SchoolYear.name', value = 'SchoolYear.id' WHERE option_type  = 'database:AcademicPeriod';

-- need to set default school year to latest one
SELECT id INTO @currentSchoolYear FROM school_years ORDER BY current DESC, available DESC, end_date DESC, id DESC LIMIT 1;
UPDATE config_items SET config_items.value = @currentSchoolYear, config_items.default_value = @currentSchoolYear WHERE name = 'yearbook_school_year';

-- PHPOE-1278
ALTER TABLE `institution_site_staff`
  RENAME `1278_institution_site_staff`;

CREATE TABLE IF NOT EXISTS `institution_site_staff` (
  `id` int(11) NOT NULL,
  `FTE` decimal(5,2) DEFAULT NULL,
  `staff_status_id` int(3) NOT NULL,
  `staff_type_id` int(5) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `staff_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `institution_site_position_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE `institution_site_staff`
  ADD PRIMARY KEY (`id`), ADD KEY `staff_id` (`staff_id`), ADD KEY `staff_type_id` (`staff_type_id`), ADD KEY `staff_status_id` (`staff_status_id`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `institution_site_position_id` (`institution_site_position_id`);

ALTER TABLE `institution_site_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

INSERT INTO `institution_site_staff` (`id`, `FTE`, `staff_status_id`, `staff_type_id`, `start_date`, `start_year`, `end_date`, `end_year`, `staff_id`, `institution_site_id`, `institution_site_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
  SELECT `id`, `FTE`, `staff_status_id`, `staff_type_id`, `start_date`, `start_year`, `end_date`, `end_year`, `staff_id`, `institution_site_id`, `institution_site_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created` from `institution_site_staff_bak` where 1;

DROP TABLE `1278_institution_site_staff`;
