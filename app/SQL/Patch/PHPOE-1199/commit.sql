UPDATE navigations SET action = 'StaffSalary', pattern = 'StaffSalary' WHERE module = 'Staff' AND plugin = 'Staff' AND controller = 'Staff' AND header = 'Details' AND title = 'Salary';
-- SELECT * FROM `navigations` WHERE `title` LIKE 'Salary'

ALTER TABLE `staff_salary_additions` CHANGE `addition_amount` `amount` DECIMAL(11,2) NULL DEFAULT NULL;
ALTER TABLE `staff_salary_deductions` CHANGE `deduction_amount` `amount` DECIMAL(11,2) NULL DEFAULT NULL;