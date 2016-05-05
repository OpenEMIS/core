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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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


ALTER TABLE `user_nationalities` CHANGE `country_id` `nationality_id` INT(11) NOT NULL;

-- NEED TO DROP THE IDENTITY_TYPE COLUMN FOR COUNTRIES
ALTER TABLE `countries` DROP `identity_type_id`;
