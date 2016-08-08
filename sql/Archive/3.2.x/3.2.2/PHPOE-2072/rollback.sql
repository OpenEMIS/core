-- security_function
UPDATE `security_functions` SET `_view`=null, `_delete`=null WHERE `id`='1022';

-- labels
DELETE FROM `labels` WHERE `module`='TransferRequests' and `field`='created';

-- student_statuses
INSERT INTO `student_statuses` (`id`, `code`, `name`) VALUES (10, 'REJECTED', 'Rejected');

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2072';