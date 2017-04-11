-- PHPOE-2366
INSERT INTO `db_patches` VALUES ('PHPOE-2366', NOW());

CREATE TABLE `z_2366_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2366_import_mapping` SELECT * FROM `import_mapping`;

ALTER TABLE `import_mapping` CHANGE `foreign_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: field options, 2: direct table, 3: non-table list';

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('User.Users', 'openemis_no', '(Leave as blank for new entries)', 1, 0, NULL, NULL, NULL),
('User.Users', 'first_name', NULL, 2, 0, NULL, NULL, NULL),
('User.Users', 'middle_name', NULL, 3, 0, NULL, NULL, NULL),
('User.Users', 'third_name', NULL, 4, 0, NULL, NULL, NULL),
('User.Users', 'last_name', NULL, 5, 0, NULL, NULL, NULL),
('User.Users', 'preferred_name', NULL, 6, 0, NULL, NULL, NULL),
('User.Users', 'gender_id', 'Code (M/F)', 7, 2, 'User', 'Genders', 'code'),
('User.Users', 'date_of_birth', NULL, 8, 0, NULL, NULL, NULL),
('User.Users', 'address', NULL, 9, 0, NULL, NULL, NULL),
('User.Users', 'postal_code', NULL, 10, 0, NULL, NULL, NULL),
('User.Users', 'address_area_id', 'Code', 11, 2, 'Area', 'AreaAdministratives', 'code'),
('User.Users', 'birthplace_area_id', 'Code', 12, 2, 'Area', 'AreaAdministratives', 'code'),
('User.Users', 'account_type', 'Code', 13, 3, NULL, 'AccountTypes', 'code');
