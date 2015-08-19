INSERT INTO `student_statuses` (`id`, `code`, `name`)
VALUES (9, 'PENDING_ADMISSION', 'Pending Admission');

INSERT INTO `student_statuses` (`id`, `code`, `name`)
VALUES (10, 'REJECTED', 'Rejected');

ALTER TABLE `institution_student_transfers` 
ADD COLUMN `type` VARCHAR(100) NULL AFTER `comment`, 
RENAME TO  `institution_student_admission` ;