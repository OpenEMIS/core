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
