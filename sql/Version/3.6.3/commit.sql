-- POCOR-3338
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3338', NOW());

-- workflow_actions
CREATE TABLE `z_3338_workflow_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_key` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_3338_workflow_actions`(`id`, `event_key`)
SELECT `id`, `event_key`
FROM `workflow_actions`
WHERE `event_key` = 'Workflow.onDeleteRecord';

UPDATE `workflow_actions`
SET `event_key` = NULL
WHERE `event_key` = 'Workflow.onDeleteRecord';


-- 3.6.3
UPDATE config_items SET value = '3.6.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
