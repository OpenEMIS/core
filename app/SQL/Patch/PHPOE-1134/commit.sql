-- add delete to nav
UPDATE navigations SET pattern = CONCAT(pattern, '|delete')  WHERE controller = 'FieldOption';
-- SELECT * FROM `navigations` WHERE `controller` LIKE 'FieldOption'

-- deactivate student gender
UPDATE field_options SET visible = 0 WHERE code = 'Gender' AND name = 'Gender' AND parent = 'Student';

ALTER TABLE `institution_site_history` ADD `institution_site_sector_id` INT NOT NULL AFTER `institution_site_status_id`;
ALTER TABLE `institution_site_history` ADD `institution_site_provider_id` INT NOT NULL AFTER `institution_site_sector_id`;
ALTER TABLE `institution_site_history` ADD `institution_site_gender_id` INT NOT NULL AFTER `institution_site_provider_id`;

-- need to add plugin column
ALTER TABLE `field_options` ADD `plugin` VARCHAR(50) NULL DEFAULT NULL AFTER `id`;
UPDATE field_options SET plugin = 'Students' WHERE parent = 'Student';
UPDATE field_options SET plugin = 'Students' WHERE parent = 'Guardian';
UPDATE field_options SET plugin = 'Staff' WHERE parent = 'Staff';
UPDATE field_options SET plugin = 'Staff' WHERE parent = 'Position';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'QualificationLevel';
UPDATE field_options SET plugin = 'Training' WHERE code = 'QualificationSpecialisation';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'EmploymentType';

-- training
UPDATE field_options SET plugin = 'Staff' WHERE code = 'TrainingAchievementType';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'TrainingNeedCategory';
UPDATE field_options SET plugin = 'Training' WHERE code = 'TrainingResultType';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingCourseType';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingFieldStudy';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingLevel';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingModeDelivery';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingPriority';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingProvider';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingRequirement';
UPDATE field_options SET plugin = NULL WHERE code = 'TrainingStatus';


-- reinsert country
SELECT MAX(field_options.order) INTO @highestOrder FROM field_options;
INSERT INTO `field_options` (`code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Country', 'Countries', 'Others', '{"model":"Country"}', @highestOrder+1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

