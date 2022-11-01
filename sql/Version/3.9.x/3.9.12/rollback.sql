-- POCOR-3271
-- `institution_genders`
DROP TABLE IF EXISTS `institution_genders`;

UPDATE institutions
JOIN z_3271_institution_genders
    ON z_3271_institution_genders.national_code = institutions.institution_gender_id
SET institutions.institution_gender_id = z_3271_institution_genders.id;

RENAME TABLE `z_3271_institution_genders` TO `institution_genders`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3271';


-- POCOR-3690
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


-- POCOR-2879
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2879', NOW());

ALTER TABLE `institution_subjects`
DROP COLUMN `education_grade_id`;


-- POCOR-3516
-- custom_field_types
DROP TABLE IF EXISTS `custom_field_types`;
RENAME TABLE `z_3516_custom_field_types` TO `custom_field_types`;

-- custom_field_values
DROP TABLE IF EXISTS `custom_field_values`;
RENAME TABLE `z_3516_custom_field_values` TO `custom_field_values`;

-- institution_custom_field_values
DROP TABLE IF EXISTS `institution_custom_field_values`;
RENAME TABLE `z_3516_institution_custom_field_values` TO `institution_custom_field_values`;

-- infrastructure_custom_field_values
DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
RENAME TABLE `z_3516_infrastructure_custom_field_values` TO `infrastructure_custom_field_values`;

-- room_custom_field_values
DROP TABLE IF EXISTS `room_custom_field_values`;
RENAME TABLE `z_3516_room_custom_field_values` TO `room_custom_field_values`;

-- staff_custom_field_values
DROP TABLE IF EXISTS `staff_custom_field_values`;
RENAME TABLE `z_3516_staff_custom_field_values` TO `staff_custom_field_values`;

-- student_custom_field_values
DROP TABLE IF EXISTS `student_custom_field_values`;
RENAME TABLE `z_3516_student_custom_field_values` TO `student_custom_field_values`;

-- institution_survey_answers
DROP TABLE IF EXISTS `institution_survey_answers`;
RENAME TABLE `z_3516_institution_survey_answers` TO `institution_survey_answers`;

-- institution_student_survey_answers
DROP TABLE IF EXISTS `institution_student_survey_answers`;
RENAME TABLE `z_3516_institution_student_survey_answers` TO `institution_student_survey_answers`;

-- institution_repeater_survey_answers
DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
RENAME TABLE `z_3516_institution_repeater_survey_answers` TO `institution_repeater_survey_answers`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3516';


-- 3.9.11
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.11' WHERE code = 'db_version';
