-- POCOR-3486
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3486', NOW());

DROP TABLE IF EXISTS `institution_students_tmp`;
CREATE TABLE IF NOT EXISTS `institution_students_tmp` (
  `id` char(36) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `start_date` date NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains information of all students in every institution';

ALTER TABLE `institution_students_tmp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

INSERT INTO `institution_students_tmp`
SELECT `id`, `student_id`, `start_date`, `created`
FROM `institution_students`;

UPDATE `institution_students` `A`
SET `A`.`previous_institution_student_id` = (
        SELECT `id`
        FROM `institution_students_tmp` `B`
        WHERE `A`.`student_id` = `B`.`student_id`
        AND `A`.`start_date` > `B`.`start_date`
        ORDER BY `start_date` DESC
        LIMIT 1
);


-- POCOR-3440
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3440', NOW());

-- label
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('23a54720-95de-11e6-8c88-525400b263eb', 'UndoStudentStatus', 'student_status_id', 'Institution -> Students -> Undo Student Status', 'Undo', NULL, NULL, '1', NULL, NULL, '1', '2016-10-19 00:00:00');

-- institution_student_admission
ALTER TABLE `institution_student_admission` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo';

-- institution_student_dropout
ALTER TABLE `institution_student_dropout` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo';


-- POCOR-3436
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3436', NOW());

-- security_functions
UPDATE `security_functions`
SET `_execute` = 'Promotion.index|Promotion.add|Promotion.reconfirm|IndividualPromotion.index|IndividualPromotion.add|IndividualPromotion.reconfirm'
WHERE `controller` = 'Institutions' AND `name` = 'Promotion';


-- 3.7.1.1
UPDATE config_items SET value = '3.7.1.1' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
