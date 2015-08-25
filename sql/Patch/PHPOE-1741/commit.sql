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

UPDATE labels set module_name = 'Institutions -> Visits' where module = 'InstitutionQualityVisits';
UPDATE labels set module_name = 'Institutions -> Rubrics' where module = 'InstitutionRubrics';

UPDATE labels set module_name = 'Institutions -> History' where module = 'InstitutionSiteActivities';
UPDATE labels set module_name = 'Institutions -> Attachments' where module = 'InstitutionSiteAttachments';
UPDATE labels set module_name = 'Institutions -> Subjects' where module = 'InstitutionSiteClasses';
UPDATE labels set module_name = 'Institutions -> Positions' where module = 'InstitutionSitePositions';
UPDATE labels set module_name = 'Institutions -> Programmes' where module = 'InstitutionSiteProgrammes';

UPDATE labels set module_name = 'Institutions -> Classes' where module = 'InstitutionSiteSections';
UPDATE labels set module_name = 'Institutions -> Shifts' where module = 'InstitutionSiteShifts';
UPDATE labels set module_name = 'Institutions -> Staff' where module = 'InstitutionSiteStaff';
UPDATE labels set module_name = 'Institutions -> Students -> Attendance' where module = 'InstitutionSiteStudentAbsences';

UPDATE labels set module_name = 'Special Needs' where module = 'SpecialNeeds';
UPDATE labels set module_name = 'Staff -> Absences' where module = 'StaffAbsences';
UPDATE labels set module_name = 'Staff -> History' where module = 'StaffActivities';
UPDATE labels set module_name = 'Staff -> Attendance' where module = 'StaffAttendances';
UPDATE labels set module_name = 'Staff -> Behaviour' where module = 'StaffBehaviours';
UPDATE labels set module_name = 'Staff -> Subjects' where module = 'StaffClasses';
UPDATE labels set module_name = 'Staff -> Positions' where module = 'StaffPositions';

UPDATE labels set module_name = 'Student -> History' where module = 'StudentActivities';
UPDATE labels set module_name = 'Student -> Attendance' where module = 'StudentAttendances';
UPDATE labels set module_name = 'Student -> Behaviour' where module = 'StudentBehaviours';
UPDATE labels set module_name = 'Student -> Subjects' where module = 'StudentClasses';
UPDATE labels set module_name = 'Student -> Fees' where module = 'StudentFees';
UPDATE labels set module_name = 'Institutions -> Promotion' where module = 'StudentPromotion';
UPDATE labels set module_name = 'Student -> Classes' where module = 'StudentSections';

UPDATE labels set module_name = 'Staff -> Behaviour' where module = 'Behaviours' and field = 'staff_behaviour_category_id';
UPDATE labels set module_name = 'Student -> Behaviour' where module = 'Behaviours' and field = 'student_behaviour_category_id';

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'labels', 'module_name', 'labels', 'Module Name', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'labels', 'field_name', 'labels', 'Field Name', 1, NOW());


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'Academic_period_id', 'Institution -> Shifts', 'Academic Period', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'start_time', 'Institution -> Shifts', 'Start Time', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'end_time', 'Institution -> Shifts', 'End Time', 1, NOW());


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'StaffBehaviours', 'date_of_behaviour', 'Staff -> Behaviour', 'Date', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'StaffBehaviours', 'time_of_behaviour', 'Staff -> Behaviour', 'Time', 1, NOW());

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteStaff', 'fte', 'Institutions -> Staff', 'FTE', 1, NOW());

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'SurveyTemplates', 'survey_module_id', 'Survey -> Templates', 'Module', 1, NOW());

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'openemis_no', 'General', 'OpenEMIS ID', 0, 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'created_user_id', 'General', 'Created By', 0, 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'created', 'General', 'Created On', 0, 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'modified_user_id', 'General', 'Modified By', 0, 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'modified', 'General', 'Modified On', 0, 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
VALUES (uuid(), 'General', 'order', 'General', 'Order', 0, 1, NOW());
