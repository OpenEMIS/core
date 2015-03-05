ALTER TABLE `countries` DROP `identity_type_id`;

ALTER TABLE `student_identities` CHANGE `issue_date` `issue_date` DATE NOT NULL;
ALTER TABLE `student_identities` CHANGE `expiry_date` `expiry_date` DATE NOT NULL;
ALTER TABLE `student_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `staff_identities` CHANGE `issue_date` `issue_date` DATE NOT NULL;
ALTER TABLE `staff_identities` CHANGE `expiry_date` `expiry_date` DATE NOT NULL;
ALTER TABLE `staff_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;