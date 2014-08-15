--
-- 1. remove record from field_options and field_options regarding gender
--

SET @genderFieldOptionId = 0;
SELECT `id` INTO @genderFieldOptionId FROM `field_options` WHERE `code` LIKE 'Gender';

DELETE FROM `field_options` WHERE `code` LIKE 'Gender';
DELETE FROM `field_option_values` WHERE `field_option_id` = @genderFieldOptionId;

--
-- 2. restore the original table census_students
--

DROP TABLE IF EXISTS `census_students`;

ALTER TABLE `census_students_bak` 
RENAME TO  `census_students` ;

--
-- 3. restore the original table census_staff
--

DROP TABLE IF EXISTS `census_staff`;

RENAME TABLE `census_staff_bak` TO `census_staff` ;

--
-- 4. restore the original table census_graduates
--

DROP TABLE IF EXISTS `census_graduates`;

RENAME TABLE `census_graduates_bak` TO `census_graduates` ;

