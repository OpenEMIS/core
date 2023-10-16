-- db_patches
INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2781', NOW());

CREATE TABLE z_2781_institution_subject_staff LIKE institution_subject_staff;
INSERT INTO z_2781_institution_subject_staff SELECT * FROM institution_subject_staff;

DELETE FROM institution_subject_staff where `institution_subject_staff`.`status` = 0;

ALTER TABLE `institution_subject_staff` DROP `status`;


-- SELECT * FROM `security_functions` WHERE `name` LIKE '%subject%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff subjects 3014
UPDATE security_functions SET `_add` = 'Subjects.add' WHERE id = 3014;

-- SELECT * FROM `security_functions` WHERE `name` LIKE '%classes%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff classes 3013
UPDATE security_functions SET `_add` = 'Classes.add' WHERE id = 3013;

