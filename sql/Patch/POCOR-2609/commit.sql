-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2609', NOW());

-- procedures
DROP PROCEDURE IF EXISTS patchOrder;
DROP PROCEDURE IF EXISTS tmpRefTable;
DROP PROCEDURE IF EXISTS patchNoFilterOrder;
DELIMITER $$

CREATE PROCEDURE tmpRefTable(
	IN referenceTable varchar(50)
)
BEGIN
	DROP TABLE IF EXISTS `tmp_table`;
	CREATE TABLE `tmp_table` (
		`id` int(11) NOT NULL
	);
	SET @updateRecord = CONCAT('INSERT INTO `tmp_table` SELECT `id` FROM `', referenceTable, '`');
	PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
	DEALLOCATE PREPARE updateRecord;
END
$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE patchOrder(
	IN updateTblName varchar(50),
	IN updateTblColumn varchar(50)
)
BEGIN

	DECLARE flag INT DEFAULT 0;
	DECLARE filterId VARCHAR(250);
	DECLARE system_cursor CURSOR FOR SELECT id from tmp_table;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

	OPEN system_cursor;

	forloop : LOOP
		FETCH system_cursor INTO filterId;
		IF flag = 1 THEN
	      LEAVE forloop;
		END IF;
		SET @rank:=0;
		SET @updateRecord = CONCAT('UPDATE `', updateTblName,'` SET `order`=@rank:=@rank+1 WHERE `', updateTblColumn, '` = \'', filterId,'\' ORDER BY `order`');
		PREPARE updateRecord FROM @updateRecord;
		EXECUTE updateRecord;
		DEALLOCATE PREPARE updateRecord;
		END LOOP forloop;
		CLOSE system_cursor;
END
$$

DELIMITER ;

DELIMITER $$
CREATE PROCEDURE patchNoFilterOrder()
BEGIN
	DECLARE flag INT DEFAULT 0;
    DECLARE tblName VARCHAR(100);
	DECLARE tblName_cursor CURSOR FOR 
		SELECT TABLE_NAME 
		FROM information_schema.COLUMNS
		WHERE COLUMN_NAME = 'order'
		AND TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME NOT IN (
			'area_administratives',
			'areas',
			'assessment_grading_options',
			'bank_branches',
			'config_item_options',
			'contact_types',
			'custom_field_options',
			'custom_forms_fields',
			'custom_table_columns',
			'custom_table_rows',
			'education_cycles',
			'education_field_of_studies',
			'education_grades',
			'education_levels',
			'education_programmes',
			'field_option_values',
			'import_mapping',
			'infrastructure_custom_field_options',
			'infrastructure_custom_forms_fields',
			'infrastructure_custom_table_columns',
			'infrastructure_custom_table_rows',
			'infrastructure_types',
			'institution_custom_field_options',
			'institution_custom_forms_fields',
			'institution_custom_table_columns',
			'institution_custom_table_rows',
			'rubric_sections',
			'rubric_criterias',
			'rubric_template_options',
			'security_functions',
			'security_roles',
			'staff_custom_field_options',
			'staff_custom_forms_fields',
			'staff_custom_table_columns',
			'staff_custom_table_rows',
			'student_custom_field_options',
			'student_custom_forms_fields',
			'student_custom_table_columns',
			'student_custom_table_rows',
			'survey_forms_questions',
			'survey_question_choices',
			'survey_table_columns',
			'survey_table_rows'
		)
		AND TABLE_NAME NOT LIKE 'z_%';
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;
    
	OPEN tblName_cursor;
    
    forloop : LOOP
    FETCH tblName_cursor INTO tblName;

    IF flag = 1 THEN
      LEAVE forloop;
	END IF;
    
    SET @rank = 0;
    SET @updateRecord = CONCAT('UPDATE `', tblName,'` SET `order`=@rank:=@rank+1  ORDER BY `order`');
	PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
    DEALLOCATE PREPARE updateRecord;
    
    END LOOP forloop;
    CLOSE tblName_cursor;
END
$$

DELIMITER ;

-- patch all tables with order field but without filter
CALL patchNoFilterOrder();

-- area_administratives
CALL tmpRefTable('area_administratives');
CALL patchOrder('area_administratives', 'parent_id');

-- areas
CALL tmpRefTable('areas');
CALL patchOrder('areas', 'parent_id');

-- assessment_grading_options
CALL tmpRefTable('assessment_grading_types');
CALL patchOrder('assessment_grading_options', 'assessment_grading_type_id');

-- bank_branches
CALL tmpRefTable('banks');
CALL patchOrder('bank_branches', 'bank_id');

-- config_item_options
DROP TABLE IF EXISTS `tmp_table`;
CREATE TABLE `tmp_table` (
	`id` VARCHAR(200) NOT NULL
);
INSERT INTO `tmp_table` SELECT DISTINCT(option_type) FROM `config_item_options`;
CALL patchOrder('config_item_options', 'option_type');

-- contact_types
CALL tmpRefTable('contact_options');
CALL patchOrder('contact_types', 'contact_option_id');

-- custom_field_options
CALL tmpRefTable('custom_fields');
CALL patchOrder('custom_field_options', 'custom_field_id');

-- custom_table_columns
CALL patchOrder('custom_table_columns', 'custom_field_id');

-- custom_table_rows
CALL patchOrder('custom_table_rows', 'custom_field_id');

-- custom_forms_fields
CALL tmpRefTable('custom_forms');
CALL patchOrder('custom_forms_fields', 'custom_form_id');

-- education_cycles
CALL tmpRefTable('education_levels');
CALL patchOrder('education_cycles', 'education_level_id');

-- education_field_of_studies
CALL tmpRefTable('education_programme_orientations');
CALL patchOrder('education_field_of_studies', 'education_programme_orientation_id');

-- education_grades
CALL tmpRefTable('education_programmes');
CALL patchOrder('education_grades', 'education_programme_id');

-- education_levels
CALL tmpRefTable('education_systems');
CALL patchOrder('education_levels', 'education_system_id');

-- education_programmes
CALL tmpRefTable('education_cycles');
CALL patchOrder('education_programmes', 'education_cycle_id');

-- field_option_values
CALL tmpRefTable('field_options');
CALL patchOrder('field_option_values', 'field_option_id');

-- infrastructure_custom_field_options
CALL tmpRefTable('infrastructure_custom_fields');
CALL patchOrder('infrastructure_custom_field_options', 'infrastructure_custom_field_id');

-- infrastructure_custom_table_columns
CALL patchOrder('infrastructure_custom_table_columns', 'infrastructure_custom_field_id');

-- infrastructure_custom_table_rows
CALL patchOrder('infrastructure_custom_table_rows', 'infrastructure_custom_field_id');

-- infrastructure_custom_forms_fields
CALL tmpRefTable('infrastructure_custom_forms');
CALL patchOrder('infrastructure_custom_forms_fields', 'infrastructure_custom_form_id');

-- infrastructure_types
CALL tmpRefTable('infrastructure_levels');
CALL patchOrder('infrastructure_types', 'infrastructure_level_id');

-- institution_custom_field_options
CALL tmpRefTable('institution_custom_fields');
CALL patchOrder('institution_custom_field_options', 'institution_custom_field_id');

-- institution_custom_table_columns
CALL patchOrder('institution_custom_table_columns', 'institution_custom_field_id');

-- institution_custom_table_rows
CALL patchOrder('institution_custom_table_rows', 'institution_custom_field_id');

-- institution_custom_forms_fields
CALL tmpRefTable('institution_custom_forms');
CALL patchOrder('institution_custom_forms_fields', 'institution_custom_form_id');

-- rubric_criterias
CALL tmpRefTable('rubric_sections');
CALL patchOrder('rubric_criterias', 'rubric_section_id');

-- rubric_template_options
CALL tmpRefTable('rubric_templates');
CALL patchOrder('rubric_template_options', 'rubric_template_id');

-- rubric_sections
CALL patchOrder('rubric_sections', 'rubric_template_id');

-- security_roles
CALL tmpRefTable('security_groups');

DROP PROCEDURE IF EXISTS patchSecurityRoleOrder;
DELIMITER $$
CREATE PROCEDURE patchSecurityRoleOrder(
	IN updateTblName varchar(50),
	IN updateTblColumn varchar(50)
)
BEGIN

	DECLARE flag INT DEFAULT 0;
	DECLARE filterId VARCHAR(250);
	DECLARE system_cursor CURSOR FOR SELECT id from tmp_table;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

	OPEN system_cursor;

	forloop : LOOP
		FETCH system_cursor INTO filterId;
		IF flag = 1 THEN
	      LEAVE forloop;
		END IF;
		SET @rank:=0;
		SET @updateRecord = CONCAT('UPDATE `', updateTblName,'` SET `order`=@rank:=@rank+1 WHERE `', updateTblColumn, '` = \'', filterId,'\' ORDER BY `order`');
		PREPARE updateRecord FROM @updateRecord;
		EXECUTE updateRecord;
		DEALLOCATE PREPARE updateRecord;
		END LOOP forloop;
		CLOSE system_cursor;

	SET @rank:=0;
	SET @updateRecord = CONCAT('UPDATE `', updateTblName,'` SET `order`=@rank:=@rank+1 WHERE `', updateTblColumn, '` IN (-1, 0) ORDER BY `order`');
	PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
	DEALLOCATE PREPARE updateRecord;
END
$$

DELIMITER ;

CALL patchSecurityRoleOrder('security_roles', 'security_group_id');
DROP PROCEDURE IF EXISTS patchSecurityRoleOrder;

-- staff_custom_field_options
CALL tmpRefTable('staff_custom_fields');
CALL patchOrder('staff_custom_field_options', 'staff_custom_field_id');

-- staff_custom_table_columns
CALL patchOrder('staff_custom_table_columns', 'staff_custom_field_id');

-- staff_custom_table_rows
CALL patchOrder('staff_custom_table_rows', 'staff_custom_field_id');

-- staff_custom_forms_fields
CALL tmpRefTable('staff_custom_forms');
CALL patchOrder('staff_custom_forms_fields', 'staff_custom_form_id');

-- student_custom_field_options
CALL tmpRefTable('student_custom_fields');
CALL patchOrder('student_custom_field_options', 'student_custom_field_id');

-- staff_custom_table_columns
CALL patchOrder('student_custom_table_columns', 'student_custom_field_id');

-- staff_custom_table_rows
CALL patchOrder('student_custom_table_rows', 'student_custom_field_id');

-- staff_custom_forms_fields
CALL tmpRefTable('student_custom_forms');
CALL patchOrder('student_custom_forms_fields', 'student_custom_form_id');

-- survey_question_choices
CALL tmpRefTable('survey_questions');
CALL patchOrder('survey_question_choices', 'survey_question_id');

-- survey_table_columns
CALL patchOrder('survey_table_columns', 'survey_question_id');

-- survey_table_rows
CALL patchOrder('survey_table_rows', 'survey_question_id');

-- drop procedures and tmp table
DROP PROCEDURE IF EXISTS patchOrder;
DROP PROCEDURE IF EXISTS tmpRefTable;
DROP PROCEDURE IF EXISTS patchNoFilterOrder;
DROP TABLE IF EXISTS `tmp_table`;