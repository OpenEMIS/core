-- 16th July 2015

--
-- For Institutions
--

-- New table - institution_custom_fields
DROP TABLE IF EXISTS `institution_custom_fields`;
CREATE TABLE IF NOT EXISTS `institution_custom_fields` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `field_type` varchar(100) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_fields`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_field_options
DROP TABLE IF EXISTS `institution_custom_field_options`;
CREATE TABLE IF NOT EXISTS `institution_custom_field_options` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `institution_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_field_options`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_field_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_table_columns
DROP TABLE IF EXISTS `institution_custom_table_columns`;
CREATE TABLE IF NOT EXISTS `institution_custom_table_columns` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_table_columns`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_table_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_table_rows
DROP TABLE IF EXISTS `institution_custom_table_rows`;
CREATE TABLE IF NOT EXISTS `institution_custom_table_rows` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_table_rows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_table_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_forms
DROP TABLE IF EXISTS `institution_custom_forms`;
CREATE TABLE IF NOT EXISTS `institution_custom_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_forms_fields
DROP TABLE IF EXISTS `institution_custom_forms_fields`;
CREATE TABLE IF NOT EXISTS `institution_custom_forms_fields` (
  `id` char(36) NOT NULL,
  `institution_custom_form_id` int(11) NOT NULL,
  `institution_custom_field_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_forms_fields`
  ADD PRIMARY KEY (`id`);

-- New table - institution_custom_forms_filters
DROP TABLE IF EXISTS `institution_custom_forms_filters`;
CREATE TABLE IF NOT EXISTS `institution_custom_forms_filters` (
  `id` char(36) NOT NULL,
  `institution_custom_form_id` int(11) NOT NULL,
  `institution_custom_filter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_forms_filters`
  ADD PRIMARY KEY (`id`);

-- Drop table
DROP TABLE IF EXISTS `institution_site_custom_field_values`;
DROP TABLE IF EXISTS `institution_site_custom_table_cells`;

-- Alter table - institution_custom_field_values
DROP TABLE IF EXISTS `institution_custom_field_values`;
CREATE TABLE IF NOT EXISTS `institution_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `institution_custom_field_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- Alter table - institution_custom_table_cells
DROP TABLE IF EXISTS `institution_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `institution_custom_field_id` int(11) NOT NULL,
  `institution_custom_table_column_id` int(11) NOT NULL,
  `institution_custom_table_row_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_table_cells`
  ADD PRIMARY KEY (`id`);
