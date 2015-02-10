--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `institution_site_programmes`;
DROP TABLE IF EXISTS `institution_site_grades`;
DROP TABLE IF EXISTS `institution_site_students`;

--
-- 2. Restore
--

RENAME TABLE `1214_institution_site_programmes` TO `institution_site_programmes`;
RENAME TABLE `1214_institution_site_students` TO `institution_site_students`;
