-- examination_centres_examinations
DROP TABLE IF EXISTS `examination_centres_examinations`;

-- examination_centres
DROP TABLE IF EXISTS `examination_centres`;
RENAME TABLE `z_3690_examination_centres` TO `examination_centres`;

-- examination_centre_special_needs
DROP TABLE IF EXISTS `examination_centre_special_needs`;
RENAME TABLE `z_3690_examination_centre_special_needs` TO `examination_centre_special_needs`;

-- examination_centres_examinations_institutions
DROP TABLE IF EXISTS `examination_centres_examinations_institutions`;
RENAME TABLE `z_3690_examination_centres_institutions` TO `examination_centres_institutions`;

-- examination_centres_examinations_invigilators
DROP TABLE IF EXISTS `examination_centres_examinations_invigilators`;
RENAME TABLE `z_3690_examination_centres_invigilators` TO `examination_centres_invigilators`;

-- examination_centres_examinations_subjects
DROP TABLE IF EXISTS `examination_centres_examinations_subjects`;
RENAME TABLE `z_3690_examination_centre_subjects` TO `examination_centre_subjects`;

-- examination_centres_examinations_subjects_students
DROP TABLE IF EXISTS `examination_centres_examinations_subjects_students`;

-- examination_centres_examinations_students
DROP TABLE IF EXISTS `examination_centres_examinations_students`;
RENAME TABLE `z_3690_examination_centre_students` TO `examination_centre_students`;

-- examination_centre_rooms_examinations
DROP TABLE IF EXISTS `examination_centre_rooms_examinations`;

-- examination_centre_rooms
DROP TABLE IF EXISTS `examination_centre_rooms`;
RENAME TABLE `z_3690_examination_centre_rooms` TO `examination_centre_rooms`;

-- examination_centre_rooms_examinations_invigilators
DROP TABLE IF EXISTS `examination_centre_rooms_examinations_invigilators`;
RENAME TABLE `z_3690_examination_centre_rooms_invigilators` TO `examination_centre_rooms_invigilators`;

-- examination_centre_rooms_examinations_students
DROP TABLE IF EXISTS `examination_centre_rooms_examinations_students`;
RENAME TABLE `z_3690_examination_centre_room_students` TO `examination_centre_room_students`;

-- examination_item_results
DROP TABLE IF EXISTS `examination_item_results`;
RENAME TABLE `z_3690_examination_item_results` TO `examination_item_results`;

-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationCentreRooms';
INSERT INTO `import_mapping` SELECT * FROM `z_3690_import_mapping`;
DROP TABLE `z_3690_import_mapping`;

-- security_functions
DELETE FROM `security_functions`
WHERE `controller` = 'Examinations' AND `name` IN ('Exam Centre Exams', 'Exam Centre Subjects', 'Exam Centre Invigilators', 'Exam Centre Linked Institutions');

UPDATE `security_functions`
SET `_edit` = 'ExamCentres.edit'
WHERE `controller` = 'Examinations' AND `name` = 'Exam Centres';

UPDATE `security_functions`
SET `_add` = 'LinkedInstitutionAddStudents.add', `_delete` = 'ExamCentreStudents.remove', `_edit` = NULL, `order` = 5050
WHERE `controller` = 'Examinations' AND `name` = 'Exam Centre Students';

UPDATE `security_functions`
SET `order` = 5051
WHERE `controller` = 'Examinations' AND `name` = 'Exam Centre Rooms';

UPDATE `security_functions`
SET `order` = `order` - 4
WHERE `order` >= 5056 AND `order` <= 5071;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3690';
