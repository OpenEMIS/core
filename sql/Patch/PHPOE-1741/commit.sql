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


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'labels', 'module_name', 'labels', 'Module Name', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'labels', 'field_name', 'labels', 'Field Name', 1, NOW());


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'Academic_period_id', 'InstitutionSiteShifts', 'Academic Period', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'start_time', 'InstitutionSiteShifts', 'Start Time', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteShifts', 'end_time', 'InstitutionSiteShifts', 'End Time', 1, NOW());


INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'StaffBehaviours', 'date_of_behaviour', 'StaffBehaviours', 'Date', 1, NOW());
INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'StaffBehaviours', 'time_of_behaviour', 'StaffBehaviours', 'Time', 1, NOW());

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'InstitutionSiteStaff', 'fte', 'InstitutionSiteStaff', 'FTE', 1, NOW());

INSERT INTO labels (id, module, field, module_name, field_name, created_user_id, created) 
VALUES (uuid(), 'SurveyTemplates', 'survey_module_id', 'SurveyTemplates', 'Module', 1, NOW());

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
