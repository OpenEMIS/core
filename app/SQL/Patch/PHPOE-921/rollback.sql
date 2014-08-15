--
-- 1. restore the original table census_graduates
--

DROP TABLE IF EXISTS `census_graduates`;

RENAME TABLE `census_graduates_bak` TO `census_graduates` ;

