ALTER TABLE `countries` ADD `identity_type_id` INT NULL AFTER `name`;

ALTER TABLE `student_identities` CHANGE `issue_date` `issue_date` DATE NULL;
ALTER TABLE `student_identities` CHANGE `expiry_date` `expiry_date` DATE NULL;
ALTER TABLE `student_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

ALTER TABLE `staff_identities` CHANGE `issue_date` `issue_date` DATE NULL;
ALTER TABLE `staff_identities` CHANGE `expiry_date` `expiry_date` DATE NULL;
ALTER TABLE `staff_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;