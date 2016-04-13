-- 
-- POCOR-1694
-- 

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1694', NOW());

-- patch institution_subjects, recreate table to rebuild index

ALTER TABLE `institution_classes` RENAME `z_1694_institution_classes`;

DROP TABLE IF EXISTS `institution_subjects`;
CREATE TABLE IF NOT EXISTS `institution_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `no_of_seats` int(3) DEFAULT NULL,
  `institution_id` int(11) NOT NULL,
  `education_subject_id` int(11) DEFAULT NULL,
  `academic_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_subjects`
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_subjects` SELECT * FROM `z_1694_institution_classes`;

-- end institution_subjects


-- patch institution_subject_staff, recreate table to rebuild index

ALTER TABLE `institution_class_staff` RENAME `z_1694_institution_class_staff`;

DROP TABLE IF EXISTS `institution_subject_staff`;
CREATE TABLE `institution_subject_staff` (
  `id` CHAR(36) NOT NULL,
  `status` int(1) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_subject_staff`
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`);

INSERT INTO `institution_subject_staff` SELECT * FROM `z_1694_institution_class_staff`;
UPDATE institution_subject_staff SET `id` = uuid();

-- end institution_subject_staff


-- patch institution_subject_students, recreate table to rebuild index

ALTER TABLE `institution_class_students` RENAME `z_1694_institution_class_students`;

DROP TABLE IF EXISTS `institution_subject_students`;
CREATE TABLE IF NOT EXISTS `institution_subject_students` (
  `id` CHAR(36) NOT NULL,
  `status` int(1) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_subject_students`
  ADD KEY `student_id` (`student_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `institution_class_id` (`institution_class_id`);

INSERT INTO `institution_subject_students` SELECT * FROM `z_1694_institution_class_students`;
DELETE FROM `institution_subject_students` WHERE NOT EXISTS (
	SELECT 1 FROM `security_users` WHERE `security_users`.`id` = `institution_subject_students`.`student_id`
);
UPDATE institution_subject_students SET `id` = uuid();

-- end institution_subject_students

-- patch institution_class_subjects, recreate table to rebuild index

ALTER TABLE `institution_section_classes` RENAME `z_1694_institution_section_classes`;

DROP TABLE IF EXISTS `institution_class_subjects`;
CREATE TABLE `institution_class_subjects` (
  `id` CHAR(36) NOT NULL,
  `status` int(1) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_class_subjects`
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`);

INSERT INTO `institution_class_subjects` SELECT * FROM `z_1694_institution_section_classes`;
UPDATE institution_class_subjects SET `id` = uuid();

-- end institution_class_subjects


-- patch institution_classes, recreate table to rebuild index

ALTER TABLE `institution_sections` RENAME `z_1694_institution_sections`;

DROP TABLE IF EXISTS `institution_classes`;
CREATE TABLE `institution_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `class_number` int(11) DEFAULT NULL COMMENT 'This column is being used to determine whether this class is a multi-grade or single-grade.',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_shift_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_classes`
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `institution_shift_id` (`institution_shift_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_classes` SELECT * FROM `z_1694_institution_sections`;

-- end institution_classes


-- patch institution_class_grades, recreate table to rebuild index

ALTER TABLE `institution_section_grades` RENAME `z_1694_institution_section_grades`;

DROP TABLE IF EXISTS `institution_class_grades`;
CREATE TABLE `institution_class_grades` (
  `id` CHAR(36) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_class_grades`
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `education_grade_id` (`education_grade_id`);

INSERT INTO `institution_class_grades`
SELECT
uuid(),
institution_section_id,
education_grade_id,
modified_user_id,
modified,
created_user_id,
created
FROM `z_1694_institution_section_grades`;

-- end institution_class_grades


-- patch institution_class_students, recreate table to rebuild index

ALTER TABLE `institution_section_students` RENAME `z_1694_institution_section_students`;

DROP TABLE IF EXISTS `institution_class_students`;
CREATE TABLE `institution_class_students` (
  `id` char(36) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_class_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_class_students`
  ADD KEY `student_id` (`student_id`),
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `student_status_id` (`student_status_id`);

INSERT INTO `institution_class_students` SELECT * FROM `z_1694_institution_section_students`;

-- end institution_class_students


-- patch institution_quality_rubrics, recreate table to rebuild index
ALTER TABLE `institution_quality_rubrics` RENAME `z_1694_institution_quality_rubrics`;

DROP TABLE IF EXISTS `institution_quality_rubrics`;
CREATE TABLE `institution_quality_rubrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed',
  `comment` text,
  `rubric_template_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_subject_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_quality_rubrics`
  ADD KEY `rubric_template_id` (`rubric_template_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_quality_rubrics` SELECT * FROM `z_1694_institution_quality_rubrics`;

-- end institution_quality_rubrics


-- patch institution_quality_visits, recreate table to rebuild index
ALTER TABLE `institution_quality_visits` RENAME `z_1694_institution_quality_visits`;

DROP TABLE IF EXISTS `institution_quality_visits`;
CREATE TABLE `institution_quality_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `comment` text,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `quality_visit_type_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `institution_subject_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_quality_visits`
  ADD KEY `quality_visit_type_id` (`quality_visit_type_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_quality_visits` SELECT * FROM `z_1694_institution_quality_visits`;
-- end institution_quality_visits


UPDATE `labels` SET `field`='subjects' WHERE `module`='InstitutionSections' AND `field`='classes';
UPDATE `labels` SET `field`='institution_subject_id'
WHERE 
 	`module` IN (
 		'Absences',
 		'StaffClasses',
 		'StaffAbsences',
 		'StudentClasses',
 		'InstitutionRubrics',
 		'InstitutionQualityVisits',
 		'InstitutionStudentAbsences'
 	) AND `field`='institution_class_id';
UPDATE `labels` SET `field`='institution_class_id'
WHERE 
 	`module` IN (
		'Absences',
		'StaffAbsences',
		'StudentClasses',
		'StudentSections',
		'InstitutionRubrics',
		'InstitutionStudentAbsences'
 	) AND `field`='institution_section_id';
UPDATE `labels` SET `field`='class'
WHERE 
 	`module` IN (
		'InstitutionStudentAbsences',
		'StudentBehaviours',
		'Students'
	) AND `field`='section';
UPDATE `labels` SET `field`='institution_classes_code' WHERE `module`='Imports' AND `field`='institution_sections_code';
UPDATE `labels` SET `field`='InstitutionClasses' WHERE `module`='Imports' AND `field`='InstitutionSections';
UPDATE `labels` SET `field`='number_of_classes' WHERE `module`='InstitutionSections' AND `field`='number_of_sections';
UPDATE `labels` SET `field`='institution_class' WHERE `module`='StaffClasses' AND `field`='institution_section';
UPDATE `labels` SET `field`='select_class' WHERE `module`='Absences' AND `field`='select_section';
UPDATE `labels` SET `module`='InstitutionSubjects' WHERE `module`='InstitutionClasses';
UPDATE `labels` SET `module`='StaffSubjects' WHERE `module`='StaffClasses';
UPDATE `labels` SET `module`='StudentSubjects' WHERE `module`='StudentClasses';
UPDATE `labels` SET `module`='InstitutionClasses' WHERE `module`='InstitutionSections';
UPDATE `labels` SET `module`='StudentClasses' WHERE `module`='StudentSections';

UPDATE `import_mapping` SET `lookup_model`='InstitutionClasses' WHERE `id`=66;

UPDATE `security_functions` SET `_view`='AllClasses.index|AllClasses.view|Classes.index|Classes.view', `_edit`='AllClasses.edit|Classes.edit', `_add`='Classes.add', `_delete`='Classes.remove', `_execute`=NULL WHERE `id`='1007';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`='Classes.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1008';
UPDATE `security_functions` SET `_view`='AllSubjects.index|AllSubjects.view|Subjects.index|Subjects.view', `_edit`='AllSubjects.edit|Subjects.edit', `_add`='Subjects.add', `_delete`='Subjects.remove', `_execute`=NULL WHERE `id`='1009';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`='Subjects.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1010';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2012';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2013';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3013';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3014';
UPDATE `security_functions` SET `_view`='StudentClasses.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7011';
UPDATE `security_functions` SET `_view`='StaffClasses.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7022';
UPDATE `security_functions` SET `_view`='StaffSubjects.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7023';

-- 
-- END POCOR-1694
-- 
