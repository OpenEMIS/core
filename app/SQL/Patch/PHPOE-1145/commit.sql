--
-- 1. Backup
--

CREATE TABLE IF NOT EXISTS 1145_areas LIKE areas;
CREATE TABLE IF NOT EXISTS 1145_area_levels LIKE area_levels;
CREATE TABLE IF NOT EXISTS 1145_area_educations LIKE area_educations;
CREATE TABLE IF NOT EXISTS 1145_area_education_levels LIKE area_education_levels;

INSERT 1145_areas SELECT * FROM areas WHERE NOT EXISTS (SELECT * FROM 1145_areas);
INSERT 1145_area_levels SELECT * FROM area_levels WHERE NOT EXISTS (SELECT * FROM 1145_area_levels);
INSERT 1145_area_educations SELECT * FROM area_educations WHERE NOT EXISTS (SELECT * FROM 1145_area_educations);
INSERT 1145_area_education_levels SELECT * FROM area_education_levels WHERE NOT EXISTS (SELECT * FROM 1145_area_education_levels);

--
-- 2. Duplicate area_educations from areas, rename Areas Tables & Columns
--

DROP TABLE area_educations;
DROP TABLE area_education_levels;

CREATE TABLE IF NOT EXISTS area_administratives LIKE areas;
CREATE TABLE IF NOT EXISTS area_administrative_levels LIKE area_levels;

INSERT area_administratives SELECT * FROM areas WHERE NOT EXISTS (SELECT * FROM area_administratives);
INSERT area_administrative_levels SELECT * FROM area_levels WHERE NOT EXISTS (SELECT * FROM area_administrative_levels);

ALTER TABLE `area_administratives` CHANGE `area_level_id` `area_administrative_level_id` INT(11) NOT NULL;
ALTER TABLE `area_administrative_levels` ADD `area_administrative_id` INT(11) NOT NULL AFTER `level`;

--
-- 3. Rename Columns in other tables
--

ALTER TABLE `institution_sites` CHANGE `area_education_id` `area_administrative_id` INT(11) NULL DEFAULT NULL;

--
-- 4. Update area_administrative_levels table
--

SET @idOfCurrentCountry := 0;
SELECT `id` INTO @idOfCurrentCountry FROM `area_administratives` WHERE `parent_id` = -1;

UPDATE `area_administrative_levels` SET `area_administrative_id` = @idOfCurrentCountry WHERE `level` <> 1;

UPDATE `area_administrative_levels` SET `level` = `level` + 1;
INSERT INTO `area_administrative_levels` (
`name`,
`level`,
`area_administrative_id`,
`created_user_id`,
`created`
) VALUES (
'World', '1', 0, '1', '0000-00-00 00:00:00'
);

--
-- 5. Update area_administratives table
--

SET @levelIdOfWorld := 0;
SELECT `id` INTO @levelIdOfWorld FROM `area_administrative_levels` WHERE `level` = 1;

INSERT INTO `area_administratives` (
`code`,
`name`,
`parent_id`,
`area_administrative_level_id`,
`order`,
`visible`,
`created_user_id`,
`created`
) VALUES (
'World', 'World', '-1', @levelIdOfWorld, '1', '1', '1', '0000-00-00 00:00:00'
);

SET @parentIdOfWorld := 0;
SELECT `id` INTO @parentIdOfWorld FROM `area_administratives` WHERE `name` LIKE 'World' AND `parent_id` = -1;

UPDATE `area_administratives` SET `parent_id` = @parentIdOfWorld WHERE `parent_id` = '-1' AND `name` <> 'World';