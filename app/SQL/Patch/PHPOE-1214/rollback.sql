--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `institution_site_programmes`;
DROP TABLE IF EXISTS `institution_site_grades`;

--
-- 2. Restore
--

RENAME TABLE `1214_institution_site_programmes` TO `institution_site_programmes`;
