-- Restore tables
DROP TABLE IF EXISTS `excel_templates`;

-- labels
DELETE FROM `labels` WHERE `id` = 'ad8fa33a-c0d8-11e6-90e8-525400b263eb';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5059;

-- assessment_item_results
DROP TABLE IF EXISTS `assessment_item_results`;
RENAME TABLE `z_3576_assessment_item_results` TO `assessment_item_results`;

-- assessment_items_grading_types
ALTER TABLE `assessment_items_grading_types`
	DROP INDEX (`assessment_grading_type_id`),
	DROP INDEX (`assessment_id`),
	DROP INDEX (`education_subject_id`),
	DROP INDEX (`assessment_period_id`);

-- examination_centres_institutions
ALTER TABLE `examination_centres_institutions`
	DROP INDEX (`examination_centre_id`),
	DROP INDEX (`institution_id`);

-- examination_centres_invigilators
ALTER TABLE `examination_centres_invigilators`
	DROP INDEX (`examination_centre_id`),
	DROP INDEX (`invigilator_id`);

-- examination_centre_rooms_invigilators
ALTER TABLE `examination_centre_rooms_invigilators`
	DROP INDEX (`examination_centre_room_id`),
	DROP INDEX (`invigilator_id`);

-- examination_centre_special_needs
ALTER TABLE `examination_centre_special_needs`
	DROP INDEX (`examination_centre_id`),
	DROP INDEX (`special_need_type_id`);

-- examination_centre_students
ALTER TABLE `examination_centre_students`
	DROP INDEX (`examination_centre_id`),
	DROP INDEX (`student_id`),
	DROP INDEX (`education_subject_id`);

-- examination_centre_subjects
ALTER TABLE `examination_centre_subjects`
	DROP INDEX (`examination_centre_id`),
	DROP INDEX (`education_subject_id`);

-- examination_items
ALTER TABLE `examination_items`
	DROP INDEX (`examination_id`),
	DROP INDEX (`education_subject_id`);

-- examination_item_results
ALTER TABLE `examination_item_results`
	DROP INDEX (`academic_period_id`),
	DROP INDEX (`examination_id`),
	DROP INDEX (`education_subject_id`),
	DROP INDEX (`student_id`);

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3576';
