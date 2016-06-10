-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2604', NOW());

-- student_statuses
CREATE TABLE z_2604_student_statuses LIKE student_statuses;

INSERT INTO z_2604_student_statuses
SELECT * FROM student_statuses;

DELETE FROM student_statuses WHERE code = 'PENDING_TRANSFER' OR code = 'PENDING_ADMISSION' OR code = 'PENDING_DROPOUT';