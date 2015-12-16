-- `user_activities`
DROP TABLE `user_activities`;

-- student_activities
ALTER TABLE `z_2193_student_activities` 
RENAME TO  `student_activities` ;

-- staff_activites
ALTER TABLE `z_2193_staff_activities` 
RENAME TO  `staff_activities` ;

-- guardian_activities
ALTER TABLE `z_2193_guardian_activities` 
RENAME TO  `guardian_activities` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2193';