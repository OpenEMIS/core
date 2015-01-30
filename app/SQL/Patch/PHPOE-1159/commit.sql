UPDATE security_functions SET _view = REPLACE(_view , '|InstitutionSiteStudent.index','') WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students';
UPDATE security_functions SET _add = REPLACE(_add , '|InstitutionSiteStudent.add','') WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students';
-- SELECT * FROM security_functions WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students';

UPDATE security_functions SET _view = REPLACE(_view , '|InstitutionSiteStaff.index','') WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff';
UPDATE security_functions SET _add = REPLACE(_add , '|InstitutionSiteStaff.add','') WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff';
-- SELECT * FROM security_functions WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff';

	
SET @lastDetailOrderNo := 0;
SELECT MAX(security_functions.order) INTO @lastDetailOrderNo FROM `security_functions` WHERE `category` = 'Details' AND controller = 'InstitutionSites' AND name <> 'Staff - Academic' AND name <> 'Students - Academic';
UPDATE security_functions SET security_functions.order = security_functions.order +2 WHERE security_functions.order > @lastDetailOrderNo;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (null , 'Student', 'Students', 'Institutions', 'Details', 8 , 'InstitutionSiteStudent|InstitutionSiteStudent.index', 'InstitutionSiteStudent.add', 'InstitutionSiteStudent.excel', @lastDetailOrderNo + 1 , 1, 1, NOW());

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (null , 'Staff', 'Staff', 'Institutions', 'Details', 8 , 'InstitutionSiteStaff|InstitutionSiteStaff.index', 'InstitutionSiteStaff.add', 'InstitutionSiteStaff.excel', @lastDetailOrderNo + 2 , 1, 1, NOW());

-- SELECT * FROM security_functions WHERE `category` = 'Details' AND name <> 'Staff - Academic' AND name <> 'Students - Academic';