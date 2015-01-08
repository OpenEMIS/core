ALTER TABLE `student_behaviours` ADD `student_action_category_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER `student_behaviour_category_id` ;

UPDATE `security_functions` SET `_execute` = NULL WHERE `name` = 'Positions' AND `controller` = 'InstitutionSites' AND `category` = 'Details';
UPDATE `security_functions` SET `_execute` = NULL WHERE `name` = 'Programmes' AND `controller` = 'InstitutionSites' AND `category` = 'Details';

