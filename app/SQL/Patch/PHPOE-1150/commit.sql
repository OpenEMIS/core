ALTER TABLE `student_behaviours` DROP `student_action_category_id` ;

UPDATE `security_functions` SET `_execute` = 'InstitutionSitePosition.excel' WHERE `name` = 'Positions' AND `controller` = 'InstitutionSites' AND `category` = 'Details';
UPDATE `security_functions` SET `_execute` = 'InstitutionSiteProgramme.excel' WHERE `name` = 'Programmes' AND `controller` = 'InstitutionSites' AND `category` = 'Details';

