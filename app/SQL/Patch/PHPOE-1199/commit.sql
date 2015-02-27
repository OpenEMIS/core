UPDATE navigations SET action = 'StaffSalary', pattern = 'StaffSalary' WHERE module = 'Staff' AND plugin = 'Staff' AND controller = 'Staff' AND header = 'Details' AND title = 'Salary';
-- SELECT * FROM `navigations` WHERE `title` LIKE 'Salary'

ALTER TABLE `staff_salary_additions` CHANGE `addition_amount` `amount` DECIMAL(11,2) NULL DEFAULT NULL;
ALTER TABLE `staff_salary_deductions` CHANGE `deduction_amount` `amount` DECIMAL(11,2) NULL DEFAULT NULL;


-- run below for TST 
UPDATE security_functions SET 
_view = 'StaffSalary.index|StaffSalary.view'
, _edit = '_view:StaffSalary.edit'
, _add = '_view:StaffSalary.add'
, _delete = '_view:StaffSalary.remove'
WHERE controller = 'Salary'
AND module = 'Staff'
AND category = 'Staff'
AND name = 'Details';


ALTER TABLE `staff_salaries` CHANGE `gross_salary` `gross_salary` DECIMAL(20,2) NOT NULL;
ALTER TABLE `staff_salaries` CHANGE `additions` `additions` DECIMAL(20,2) NOT NULL;
ALTER TABLE `staff_salaries` CHANGE `deductions` `deductions` DECIMAL(20,2) NOT NULL;
ALTER TABLE `staff_salaries` CHANGE `net_salary` `net_salary` DECIMAL(20,2) NOT NULL;