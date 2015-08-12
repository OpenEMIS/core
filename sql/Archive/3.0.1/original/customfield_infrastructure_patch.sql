-- 16th July 2015

UPDATE `custom_modules` SET `filter` = 'Infrastructure.InfrastructureLevels' WHERE `code` = 'Infrastructure';

--
-- For Institutions - Infrastructure
--

-- New table - infrastructure_custom_forms
DROP TABLE IF EXISTS `infrastructure_custom_forms`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_custom_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_custom_forms_fields
DROP TABLE IF EXISTS `infrastructure_custom_forms_fields`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_forms_fields` (
  `id` char(36) NOT NULL,
  `infrastructure_custom_form_id` int(11) NOT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_forms_fields`
  ADD PRIMARY KEY (`id`);

-- New table - infrastructure_custom_forms_filters
DROP TABLE IF EXISTS `infrastructure_custom_forms_filters`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_forms_filters` (
  `id` char(36) NOT NULL,
  `infrastructure_custom_form_id` int(11) NOT NULL,
  `infrastructure_custom_filter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_forms_filters`
  ADD PRIMARY KEY (`id`);

-- Alter table - infrastructure_levels
DROP TABLE IF EXISTS `infrastructure_levels`;
CREATE TABLE IF NOT EXISTS `infrastructure_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT '0',
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_levels`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Drop tables
DROP TABLE IF EXISTS `infrastructure_level_fields`;
DROP TABLE IF EXISTS `institution_site_infrastructures`;
DROP TABLE IF EXISTS `institution_site_infrastructure_custom_field_values`;
DROP TABLE IF EXISTS `institution_site_infrastructure_custom_table_cells`;

-- New table - institution_infrastructures
DROP TABLE IF EXISTS `institution_infrastructures`;
CREATE TABLE IF NOT EXISTS `institution_infrastructures` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `year_acquired` int(4) DEFAULT NULL,
  `year_disposed` int(4) DEFAULT NULL,
  `comment` text,
  `size` float DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `infrastructure_level_id` int(11) NOT NULL,
  `infrastructure_type_id` int(11) NOT NULL,
  `infrastructure_ownership_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_infrastructures`
  ADD PRIMARY KEY (`id`), ADD KEY `code` (`code`), ADD KEY `name` (`name`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `infrastructure_level_id` (`infrastructure_level_id`), ADD KEY `infrastructure_type_id` (`infrastructure_type_id`), ADD KEY `infrastructure_ownership_id` (`infrastructure_ownership_id`), ADD KEY `infrastructure_condition_id` (`infrastructure_condition_id`);


ALTER TABLE `institution_infrastructures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_custom_field_values
DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- New table - infrastructure_custom_table_cells
DROP TABLE IF EXISTS `infrastructure_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `infrastructure_custom_table_column_id` int(11) NOT NULL,
  `infrastructure_custom_table_row_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_table_cells`
  ADD PRIMARY KEY (`id`);
