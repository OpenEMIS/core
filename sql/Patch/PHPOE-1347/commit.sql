UPDATE security_functions SET _execute = 'Students.excel' WHERE security_functions.id = 1012;
UPDATE security_functions SET _execute = 'Staff.excel' WHERE security_functions.id = 1016;

-- select * from security_functions WHERE security_functions.name = 'Staff' and security_functions.controller = 'Institutions' and security_functions.module = 'Institutions' and security_functions.category = 'Staff'