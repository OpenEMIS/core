-- db_patches
INSERT INTO db_patches VALUES ('PHPOE-2500', NOW());

-- institution_section_students
CREATE TABLE z_2500_institution_section_students LIKE institution_section_students;

INSERT INTO z_2500_institution_section_students
SELECT * FROM institution_section_students WHERE id = '';

UPDATE institution_section_students SET id = uuid() WHERE id = '';

-- For patching institution system groups
CREATE TABLE z_2500_security_groups LIKE security_groups;

INSERT INTO `z_2500_security_groups`
SELECT * FROM `security_groups` 
WHERE `security_groups`.`id` IN (
	SELECT `security_group_id` FROM `institutions`
);

UPDATE `security_groups` 
INNER JOIN `institutions` 
ON `institutions`.`security_group_id` = `security_groups`.`id`
SET `security_groups`.`name` = CONCAT(`institutions`.`code`, ' - ', `institutions`.`name`);