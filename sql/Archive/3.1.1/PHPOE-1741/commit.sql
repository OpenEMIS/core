INSERT INTO `db_patches` VALUES ('PHPOE-1741');

RENAME TABLE labels TO z_1741_labels;
CREATE TABLE IF NOT EXISTS labels (
  id char(36) NOT NULL,  
  module varchar(100) NOT NULL,
  field varchar(100) NOT NULL,
  module_name varchar(100) NOT NULL,
  field_name varchar(100) NOT NULL,
  code varchar(50) UNIQUE,
  name varchar(100) NULL,
  visible int(1) NOT NULL DEFAULT '1',
  modified_user_id int(11),
  modified datetime,
  created_user_id int(11) NOT NULL,
  created datetime NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO labels (id, module, field, module_name, field_name, code, modified_user_id, modified, created_user_id, created)
SELECT uuid(), module, field, module, en, code, modified_user_id, modified, created_user_id, created
FROM z_1741_labels;

UPDATE labels SET module_name = 'Institutions -> Visits' WHERE module = 'InstitutionQualityVisits';
UPDATE labels SET module_name = 'Institutions -> Rubrics' WHERE module = 'InstitutionRubrics';

UPDATE labels SET module_name = 'Institutions -> History' WHERE module = 'InstitutionSiteActivities';
UPDATE labels SET module_name = 'Institutions -> Attachments' WHERE module = 'InstitutionSiteAttachments';
UPDATE labels SET module_name = 'Institutions -> Subjects' WHERE module = 'InstitutionSiteClasses';
UPDATE labels SET module_name = 'Institutions -> Positions' WHERE module = 'InstitutionSitePositions';
UPDATE labels SET module_name = 'Institutions -> Programmes' WHERE module = 'InstitutionSiteProgrammes';

UPDATE labels SET module_name = 'Institutions -> Classes' WHERE module = 'InstitutionSiteSections';
UPDATE labels SET module_name = 'Institutions -> Shifts' WHERE module = 'InstitutionSiteShifts';
UPDATE labels SET module_name = 'Institutions -> Staff' WHERE module = 'InstitutionSiteStaff';
UPDATE labels SET module_name = 'Institutions -> Students -> Attendance' WHERE module = 'InstitutionSiteStudentAbsences';

UPDATE labels SET module_name = 'Special Needs' WHERE module = 'SpecialNeeds';
UPDATE labels SET module_name = 'Staff -> Absences' WHERE module = 'StaffAbsences';
UPDATE labels SET module_name = 'Staff -> History' WHERE module = 'StaffActivities';
UPDATE labels SET module_name = 'Staff -> Attendance' WHERE module = 'StaffAttendances';
UPDATE labels SET module_name = 'Staff -> Behaviour' WHERE module = 'StaffBehaviours';
UPDATE labels SET module_name = 'Staff -> Subjects' WHERE module = 'StaffClasses';
UPDATE labels SET module_name = 'Staff -> Positions' WHERE module = 'StaffPositions';

UPDATE labels SET module_name = 'Student -> History' WHERE module = 'StudentActivities';
UPDATE labels SET module_name = 'Student -> Attendance' WHERE module = 'StudentAttendances';
UPDATE labels SET module_name = 'Student -> Behaviour' WHERE module = 'StudentBehaviours';
UPDATE labels SET module_name = 'Student -> Subjects' WHERE module = 'StudentClasses';
UPDATE labels SET module_name = 'Student -> Fees' WHERE module = 'StudentFees';
UPDATE labels SET module_name = 'Institutions -> Promotion' WHERE module = 'StudentPromotion';
UPDATE labels SET module_name = 'Student -> Classes' WHERE module = 'StudentSections';

UPDATE labels SET module_name = 'Staff -> Behaviour' WHERE module = 'Behaviours' AND field = 'staff_behaviour_category_id';
UPDATE labels SET module_name = 'Student -> Behaviour' WHERE module = 'Behaviours' AND field = 'student_behaviour_category_id';

UPDATE labels SET visible = 0 WHERE module = 'Translations';

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'labels', 'module_name', 'labels', 'Module Name', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'labels', field_name = 'Module Name', visible = 0;

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'labels', 'field_name', 'labels', 'Field Name', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'labels', field_name = 'Field Name', visible = 0;

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'Academic_period_id', 'Institution -> Shifts', 'Academic Period', 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'Institution -> Shifts', field_name = 'Academic Period';


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'start_time', 'Institution -> Shifts', 'Start Time', 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'Institution -> Shifts', field_name = 'Start Time';

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'end_time', 'Institution -> Shifts', 'End Time', 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'Institution -> Shifts', field_name = 'End Time';


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'StaffBehaviours', 'date_of_behaviour', 'Staff -> Behaviour', 'Date', 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'Staff -> Behaviour', field_name = 'Date';


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'StaffBehaviours', 'time_of_behaviour', 'Staff -> Behaviour', 'Time', 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'Staff -> Behaviour', field_name = 'Time';

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteStaff', 'fte', 'Institutions -> Staff', 'Full Time Equivalent', 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'Institutions -> Staff', field_name = 'Full Time Equivalent';

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'SurveyTemplates', 'survey_module_id', 'Survey -> Templates', 'Module', 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'Survey -> Templates', field_name = 'Module';

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'openemis_no', 'General', 'OpenEMIS ID', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'General', field_name = 'OpenEMIS ID', visible = 0;


INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'created_user_id', 'General', 'Created By', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'General', field_name = 'Created By', visible = 0;

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'created', 'General', 'Created On', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'General', field_name = 'Created On', visible = 0;

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'modified_user_id', 'General', 'Modified By', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'General', field_name = 'Modified By', visible = 0;

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'modified', 'General', 'Modified On', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'General', field_name = 'Modified On', visible = 0;

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'order', 'General', 'Order', 0, 1, NOW())
ON DUPLICATE KEY UPDATE module_name = 'General', field_name = 'Order', visible = 0;
