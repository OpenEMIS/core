-- Backup institution site section students
Drop Table IF EXISTS `z_1716_institution_site_section_students`;

CREATE TABLE `z_1716_institution_site_section_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3334 DEFAULT CHARSET=utf8;

INSERT INTO `z_1716_institution_site_section_students` 
SELECT `id`, `student_category_id`
FROM `institution_site_section_students`;

-- Backup field option values
Drop Table IF EXISTS `z_1716_field_option_values`;

CREATE TABLE `z_1716_field_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=244 DEFAULT CHARSET=utf8;

INSERT INTO `z_1716_field_option_values` 
SELECT `id`, `name`, `visible`, `editable`, `default`
FROM `field_option_values`;

-- Set field options for StudentCategories to be not visible
UPDATE `field_options`
SET `field_options`.`visible` = 0
WHERE `field_options`.`code` = 'StudentCategories';

-- Set visibility of all record to not visible
UPDATE `field_option_values`
SET `field_option_values`.`visible`=0, `field_option_values`.`default`=0
WHERE `field_option_values`.`field_option_id` = 
  ( SELECT `field_options`.`id`
    FROM `field_options`
    WHERE `field_options`.`code` = 'StudentCategories');

-- Add Promoted Options into field option value
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`)
VALUES ('Promoted', '0', '1', '0', '1', 
  (SELECT `field_options`.`id` FROM field_options WHERE `field_options`.`code` = 'StudentCategories')
    , '1', NOW());

-- Set visibility for Promoted and Visible
UPDATE `field_option_values`
SET `field_option_values`.`visible`=1, `field_option_values`.`editable`=0
WHERE 
  `field_option_values`.`field_option_id` = 
    ( SELECT `field_options`.`id`
      FROM field_options 
      WHERE `field_options`.`code` = 'StudentCategories')
  AND `field_option_values`.`name` = 'Promoted'
  OR `field_option_values`.`name` = 'Repeated';

-- Set the orphan record
UPDATE institution_site_section_students
SET student_category_id = (
	SELECT `field_option_values`.`id`
    FROM `field_option_values`, `field_options`
	WHERE `field_option_values`.`field_option_id` = `field_options`.`id`
		AND `field_options`.`code` = 'StudentCategories'
    AND `field_option_values`.`default` = 1
);