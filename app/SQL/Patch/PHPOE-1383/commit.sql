UPDATE `navigations` SET `visible` = 0 WHERE `module` = 'Administration' AND `plugin` = 'Datawarehouse' AND `controller` = 'Datawarehouse' AND `header` = 'Data Processing';
UPDATE `navigations` SET `visible` = 0 WHERE `module` = 'Administration' AND `plugin` = 'DataProcessing' AND `controller` = 'DataProcessing' AND `header` = 'Data Processing';

UPDATE `security_functions` SET `visible` = 0 WHERE `name` = 'Build' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
UPDATE `security_functions` SET `visible` = 0 WHERE `name` = 'Generate' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
UPDATE `security_functions` SET `visible` = 0 WHERE `name` = 'Export' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
UPDATE `security_functions` SET `visible` = 0 WHERE `name` = 'Processes' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';