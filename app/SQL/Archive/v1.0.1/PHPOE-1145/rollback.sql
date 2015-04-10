--
-- 1. Restore
--

DROP TABLE areas;
DROP TABLE area_levels;
DROP TABLE area_administratives;
DROP TABLE area_administrative_levels;

RENAME TABLE 1145_areas TO areas;
RENAME TABLE 1145_area_levels TO area_levels;
RENAME TABLE 1145_area_educations TO area_educations;
RENAME TABLE 1145_area_education_levels TO area_education_levels;

--
-- 3. Rename Columns in other tables
--

ALTER TABLE `institution_sites` CHANGE `area_administrative_id` `area_education_id` INT(11) NULL DEFAULT NULL;