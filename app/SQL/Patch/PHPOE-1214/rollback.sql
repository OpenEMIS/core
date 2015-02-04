--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `institution_site_programmes`;

--
-- 2. Restore
--

RENAME TABLE `1214_institution_site_programmes` TO `institution_site_programmes`;
