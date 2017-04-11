SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'Nationalities';
UPDATE field_options SET field_options.order = field_options.order-1 WHERE field_options.order >= @fieldOptionOrder;
DELETE FROM field_options WHERE code = 'Nationalities';

-- Fix: re-create the user_nationalities table with the correct columns instead of altering table as it might take a long time 
-- ALTER TABLE `user_nationalities` CHANGE `nationality_id` `country_id` INT(11) NOT NULL;
DROP TABLE user_nationalities;
RENAME TABLE z_2714_user_nationalities TO user_nationalities;

DROP TABLE `nationalities`;

DROP TABLE `countries`;
RENAME TABLE z_2714_countries TO countries;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2714';