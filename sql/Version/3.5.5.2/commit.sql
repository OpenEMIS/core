-- POCOR-3006
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3006', NOW());

-- create new backup table
CREATE TABLE IF NOT EXISTS `z_3006_institution_positions` (
  `id` int(11) NOT NULL,
  `position_no` varchar(30) NOT NULL
)ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

-- Indexes for table `z_3006_institution_positions`
--
ALTER TABLE `z_3006_institution_positions`
  ADD PRIMARY KEY (`id`);

-- insert backup from main table

INSERT INTO `z_3006_institution_positions`
SELECT
  `institution_positions`.`id`,
  `institution_positions`.`position_no`
FROM `institution_positions`;

-- patch to remove blank space

UPDATE `institution_positions`
SET `position_no` = REPLACE(`position_no`, ' ', '');


-- POCOR-2992
-- db_patches
INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2992', NOW());

-- labels
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'institution_id';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'previous_institution_id';
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'institution_id', 'Institution -> Staff Transfer Approvals', 'Requested Institution', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'previous_institution_id', 'Institution -> Staff Transfer Approvals', 'Current Institution', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'institution_position_id', 'Institution -> Staff Transfer Approvals', 'Requested Institution Position', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'start_date', 'Institution -> Staff Transfer Approvals', 'Requested Start Date', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'FTE', 'Institution -> Staff Transfer Approvals', 'Requested FTE', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'staff_type_id', 'Institution -> Staff Transfer Approvals', 'Requested Staff Type', 1, 1, NOW());


-- 3.5.5.2
UPDATE config_items SET value = '3.5.5.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
