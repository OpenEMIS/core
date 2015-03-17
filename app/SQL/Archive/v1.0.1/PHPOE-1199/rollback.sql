UPDATE navigations SET action = 'salaries', pattern = 'salaries' WHERE module = 'Staff' AND plugin = 'Staff' AND controller = 'Staff' AND header = 'Details' AND title = 'Salary';
-- SELECT * FROM `navigations` WHERE `title` LIKE 'Salary'

ALTER TABLE `staff_salary_additions` CHANGE `amount` `addition_amount`  DECIMAL(11,2) NULL DEFAULT NULL;
ALTER TABLE `staff_salary_deductions` CHANGE `amount` `deduction_amount`  DECIMAL(11,2) NULL DEFAULT NULL;


UPDATE security_functions SET _view = 'salaries|salariesView'
, _edit = '_view:salariesEdit'
, _add = '_view:salariesAdd'
, _delete = '_view:salariesDelete' 
WHERE controller = 'Salary'
AND module = 'Staff'
AND category = 'Staff'
AND name = 'Details';
