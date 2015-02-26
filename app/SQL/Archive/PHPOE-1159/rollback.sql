UPDATE security_functions SET _view = CONCAT(_view , '|InstitutionSiteStudent.index') WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students' AND _view NOT LIKE '%InstitutionSiteStudent.index%';
UPDATE security_functions SET _add = CONCAT(_add , '|InstitutionSiteStudent.add') WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students' AND _add NOT LIKE '%InstitutionSiteStudent.add%';
-- SELECT * FROM security_functions WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students';

UPDATE security_functions SET _view = CONCAT(_view , '|InstitutionSiteStaff.index') WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff' AND _view NOT LIKE '%InstitutionSiteStaff.index%';
UPDATE security_functions SET _add = CONCAT(_add , '|InstitutionSiteStaff.add') WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff' AND _add NOT LIKE '%InstitutionSiteStaff.add%';
-- SELECT * FROM security_functions WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff';



DELETE FROM security_functions WHERE name = 'Student' AND controller = 'Students' AND module = 'Institutions';
DELETE FROM security_functions WHERE name = 'Staff' AND controller = 'Staff' AND module = 'Institutions';


SET @lastDetailOrderNo := 0;
SELECT MAX(security_functions.order) INTO @lastDetailOrderNo FROM `security_functions` WHERE `category` = 'Details' AND controller = 'InstitutionSites' AND name <> 'Staff - Academic' AND name <> 'Students - Academic';
UPDATE security_functions SET security_functions.order = security_functions.order +2 WHERE security_functions.order > @lastDetailOrderNo;
