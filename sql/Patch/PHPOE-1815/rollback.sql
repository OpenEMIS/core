-- field_options
DELETE FROM `field_options` WHERE `plugin` = 'Students' AND `code` = 'StudentTransferReasons';

-- institution_student_transfers
ALTER TABLE `institution_student_transfers` DROP `student_transfer_reason_id`;
ALTER TABLE `institution_student_transfers` DROP `comment`;

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Transfers.add' WHERE `id` = 1020;
UPDATE `security_functions` SET `_execute` = 'Transfers.edit' WHERE `id` = 1021;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1815';
