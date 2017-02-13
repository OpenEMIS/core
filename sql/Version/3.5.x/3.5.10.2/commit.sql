-- POCOR-3219
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3219', NOW());

CREATE TABLE `z_3219_report_progress` (
  `id` char(36) NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_3219_report_progress`
SELECT `id`, `expiry_date`
FROM `report_progress`
WHERE `status` = 1;

UPDATE `report_progress`
SET `expiry_date` = NULL
WHERE `status` = 1;


-- 3.5.10.2
UPDATE config_items SET value = '3.5.10.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
