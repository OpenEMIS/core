-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3632', NOW());

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` > 5010 AND `order` < 6000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('5058', 'Assessment Periods', 'Assessments', 'Administration', 'Assessments', '5000', 'AssessmentPeriods.index|AssessmentPeriods.view', 'AssessmentPeriods.edit', 'AssessmentPeriods.add', 'AssessmentPeriods.remove', NULL, '5011', '1', NULL, NULL, NULL, '1', '2015-12-19 02:41:00');