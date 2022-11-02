-- staff_leave_attachments
DROP TABLE IF EXISTS `staff_leave_attachments`;
CREATE TABLE IF NOT EXISTS `staff_leave_attachments` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` varchar(250) NOT NULL,
  `file_name` varchar(250) NOT NULL,
  `file_content` longblob NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `staff_leave_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_leave_attachments`
  ADD PRIMARY KEY (`id`), ADD KEY `staff_leave_id` (`staff_leave_id`);


ALTER TABLE `staff_leave_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- staff_leaves
ALTER TABLE `staff_leaves`
  DROP `file_name`,
  DROP `file_content`;

-- labels
DELETE FROM `labels` WHERE `module` = 'Leaves' AND `field` = 'file_content';

-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` = 3016;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2103';
