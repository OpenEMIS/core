--
-- 1. restore the original table census_graduates
--

DROP TABLE IF EXISTS `census_graduates`;

RENAME TABLE `census_graduates_bak` TO `census_graduates` ;

--
-- 2. restore the original table census_behaviours
--

DROP TABLE IF EXISTS `census_behaviours`;

RENAME TABLE `census_behaviours_bak` TO `census_behaviours` ;

--
-- 3. restore the original table census_teacher_fte, census_teacher_training, census_teachers
--

DROP TABLE IF EXISTS `census_teacher_fte`;
RENAME TABLE `census_teacher_fte_bak` TO `census_teacher_fte` ;

DROP TABLE IF EXISTS `census_teacher_training`;
RENAME TABLE `census_teacher_training_bak` TO `census_teacher_training` ;

DROP TABLE IF EXISTS `census_teachers`;
RENAME TABLE `census_teachers_bak` TO `census_teachers` ;

DROP TABLE IF EXISTS `census_teacher_grades`;
RENAME TABLE `census_teacher_grades_bak` TO `census_teacher_grades` ;

--
-- 4. remove field option sanitation gender
--

SET @sanitationGenderOptionId := 0;
SELECT `id` INTO @sanitationGenderOptionId FROM `field_options` WHERE `code` LIKE 'SanitationGender';

DELETE FROM `field_options` WHERE `id` = @sanitationGenderOptionId;
DELETE FROM `field_option_values` WHERE `field_option_id` = @sanitationGenderOptionId;

SET @sanitationOrder := 0;
SELECT `order` INTO @sanitationOrder FROM `field_options` WHERE `code` LIKE 'InfrastructureSanitation';

UPDATE `field_options` SET `order` = `order`-1 WHERE `order` > @sanitationOrder AND `order` > `id`;

--
-- 4. restore the original table census_sanitations
--

DROP TABLE IF EXISTS `census_sanitations`;

RENAME TABLE `census_sanitations_bak` TO `census_sanitations` ;
