-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2979', NOW());

-- survey_responses
DROP TABLE IF EXISTS `survey_responses`;
CREATE TABLE IF NOT EXISTS `survey_responses` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response` longtext COLLATE utf8mb4_unicode_ci,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
