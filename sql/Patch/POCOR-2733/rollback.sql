-- Restore table
DROP TABLE IF EXISTS `staff_leaves`;
RENAME TABLE `z_2733_staff_leaves` TO `staff_leaves`;

DROP TABLE IF EXISTS `institution_surveys`;
RENAME TABLE `z_2733_institution_surveys` TO `institution_surveys`;

DROP TABLE IF EXISTS `workflow_records`;
RENAME TABLE `z_2733_workflow_records` TO `workflow_records`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2733';
