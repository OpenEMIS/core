-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4081', NOW());

ALTER TABLE `deleted_records`
RENAME TO `z_4081_deleted_records`;

CREATE TABLE `deleted_records` (
  `id` bigint(20) unsigned NOT NULL,
  `reference_table` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_date` int(8) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`deleted_date`),
  KEY `reference_table` (`reference_table`),
  KEY `deleted_date` (`deleted_date`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains data of previously deleted records'
PARTITION BY HASH (deleted_date)
PARTITIONS 101;

INSERT INTO `deleted_records`
SELECT `id`, `reference_table`, `reference_key`, `data`, date_format(created, '%Y%m%d') as `deleted_date`, `created_user_id`, `created`
FROM `z_4081_deleted_records`;
