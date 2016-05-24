-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2714', NOW());

-- backup countries table
CREATE TABLE z_2714_countries LIKE countries;
INSERT INTO z_2714_countries SELECT * from countries;

SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'Countries';
UPDATE field_options SET field_options.order = field_options.order+1 WHERE field_options.order > @fieldOptionOrder;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('FieldOption', 'Nationalities', 'Nationalities', 'Others', '{"model":"FieldOption.Nationalities"}', @fieldOptionOrder+1, 1, 1, NOW());

CREATE TABLE `nationalities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `identity_type_id` int(11) DEFAULT NULL,
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `nationalities`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `nationalities` ADD INDEX(`identity_type_id`);

ALTER TABLE `nationalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  

INSERT INTO `nationalities` (
`id`, 
`name`, 
`identity_type_id`, 
`order`, 
`visible`, 
`editable`, 
`default`, 
`international_code`, 
`national_code`, 
`modified_user_id`, 
`modified`, 
`created_user_id`, 
`created`
)
SELECT 
`id`,
`name`,
`identity_type_id`,
`order`,
`visible`,
1, 
`default`,
`international_code`,
`national_code`,
`modified_user_id`,
`modified`,
`created_user_id`,
`created`
FROM countries 
WHERE EXISTS (
    SELECT * FROM user_nationalities WHERE `country_id` = `countries`.`id`
);


-- Fix: re-create the user_nationalities table with the correct columns instead of altering table as it might take a long time 
-- backing up
RENAME TABLE user_nationalities TO z_2714_user_nationalities;

-- new table user_nationalities START
CREATE TABLE IF NOT EXISTS `user_nationalities` (
  `id` int(11) NOT NULL,
  `nationality_id` int(11) NOT NULL,
  `comments` text,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `user_nationalities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nationality_id` (`nationality_id`),
  ADD KEY `security_user_id` (`security_user_id`);

ALTER TABLE `user_nationalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
-- new table user_nationalities END

INSERT INTO `user_nationalities` (
    `id`,
    `nationality_id`,
    `comments`,
    `security_user_id`,
    `modified_user_id`,
    `modified`,
    `created_user_id`,
    `created`
) SELECT 
    `id`,
    `country_id`,
    `comments`,
    `security_user_id`,
    `modified_user_id`,
    `modified`,
    `created_user_id`,
    `created` 
    FROM z_2714_user_nationalities;


-- NEED TO DROP THE IDENTITY_TYPE COLUMN FOR COUNTRIES
ALTER TABLE `countries` DROP `identity_type_id`;
