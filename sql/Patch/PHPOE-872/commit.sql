-- 22nd July 2015

--
-- For Workflow (Administration)
--

RENAME TABLE `workflow_step_roles` TO `workflow_steps_roles`;
RENAME TABLE `workflow_submodels` TO `workflows_filters`;

ALTER TABLE `workflow_models` CHANGE `submodel` `filter` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `workflows_filters` CHANGE `submodel_reference` `filter_id` INT(11) NOT NULL;

--
-- For Student Transfer
--

-- 23rd July 2015

-- security_functions
INSERT INTO `security_functions`
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1019, 'Transfer Request', 'Institutions', 'Institutions', 'Students', 1000, NULL, NULL, NULL, NULL, 'Transfers.add', 1019, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1020, 'Transfer Approval', 'Dashboard', 'Institutions', 'Students', 1000, NULL, NULL, NULL, NULL, 'Transfers.edit', 1020, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- New table - student_statuses
DROP TABLE IF EXISTS `student_statuses`;
CREATE TABLE IF NOT EXISTS `student_statuses` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_statuses`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `student_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

TRUNCATE TABLE `student_statuses`;
INSERT INTO `student_statuses` (`id`, `code`, `name`) VALUES
(1, 'CURRENT', 'Current'),
(2, 'PENDING_TRANSFER', 'Pending Transfer'),
(3, 'TRANSFERRED', 'Transferred'),
(4, 'DROPOUT', 'Dropout'),
(5, 'EXPELLED', 'Expelled'),
(6, 'GRADUATED', 'Graduated');

-- New table - institution_student_transfers
DROP TABLE IF EXISTS `institution_student_transfers`;
CREATE TABLE IF NOT EXISTS `institution_student_transfers` (
  `id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject',
  `institution_id` int(11) NOT NULL,
  `education_programme_id` int(11) NOT NULL,
  `previous_institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_transfers`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_student_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- patch institution_site_students
DELIMITER $$

DROP PROCEDURE IF EXISTS student_transfer
$$
CREATE PROCEDURE student_transfer()
BEGIN
	DECLARE done INT DEFAULT FALSE;
	DECLARE statusId, newStatusId INT(3);
	DECLARE fov CURSOR FOR 
		SELECT `FieldOptionValues`.`id`, `StudentStatuses`.`id` AS `newId`
		FROM `field_option_values` AS `FieldOptionValues`
		INNER JOIN `field_options` AS `FieldOptions` ON `FieldOptions`.`id` = `FieldOptionValues`.`field_option_id`
		INNER JOIN `student_statuses` AS `StudentStatuses` ON `StudentStatuses`.`name` = `FieldOptionValues`.`name`
		WHERE `FieldOptions`.`code` = 'StudentStatuses';
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

	OPEN fov;

	read_loop: LOOP
	FETCH fov INTO statusId, newStatusId;
	IF done THEN
		LEAVE read_loop;
	END IF;

		UPDATE 	`institution_site_students` AS `Students`
		SET 	`Students`.`student_status_id` = newStatusId
		WHERE	`Students`.`student_status_id` = statusId;

	END LOOP read_loop;

	CLOSE fov;
END
$$

CALL student_transfer
$$

DROP PROCEDURE IF EXISTS student_transfer
$$

DELIMITER ;
