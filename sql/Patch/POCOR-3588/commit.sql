-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3588', NOW());

-- examination_items
ALTER TABLE `examination_items`
DROP PRIMARY KEY,
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
MODIFY `education_subject_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to education_subjects.id',
ADD `name` varchar(150) NOT NULL AFTER `id`,
ADD `code` varchar(20) NOT NULL AFTER `name`;
