-- Set field options for StudentCategories to be visible
UPDATE `field_options`
SET `field_options`.`visible` = 1
WHERE `field_options`.`code` = 'StudentCategories';

-- Remove the Promoted option from the student categories
DELETE FROM `field_option_values`
WHERE `field_option_values`.`field_option_id` = 
  ( SELECT `field_options`.`id` 
    FROM `field_options` 
    WHERE `field_options`.`code` = 'StudentCategories')
  AND `field_option_values`.`name` = 'Promoted';

-- Revert the institution site section table
UPDATE `institution_site_section_students` 
LEFT JOIN `z_1716_institution_site_section_students` ON `institution_site_section_students`.`id` = `z_1716_institution_site_section_students`.`id`
  SET `institution_site_section_students`.`student_category_id` = `z_1716_institution_site_section_students`.`student_category_id`
  WHERE `institution_site_section_students`.`id` = `z_1716_institution_site_section_students`.`id`;
DROP TABLE `z_1716_institution_site_section_students`;

-- Revert the field options values table
UPDATE `field_option_values`
LEFT JOIN `z_1716_field_option_values` ON `field_option_values`.`id` = `z_1716_field_option_values`.`id`
  SET `field_option_values`.`name` = `z_1716_field_option_values`.`name`,
    `field_option_values`.`visible` = `z_1716_field_option_values`.`visible`,
    `field_option_values`.`editable` = `z_1716_field_option_values`.`editable`,
    `field_option_values`.`default` = `z_1716_field_option_values`.`default`
  WHERE `field_option_values`.`id` = `z_1716_field_option_values`.`id`;
DROP TABLE `z_1716_field_option_values`;