-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2601', NOW());

-- security_users

CREATE TABLE `z_2601_security_users` LIKE `security_users`;

INSERT IGNORE INTO `z_2601_security_users`
SELECT * FROM `security_users`
WHERE `security_users`.`address_area_id` NOT IN (SELECT id FROM area_administratives);

INSERT IGNORE INTO `z_2601_security_users`
SELECT * FROM `security_users` 
WHERE `security_users`.`birthplace_area_id` NOT IN (SELECT id FROM area_administratives);

UPDATE `security_users`
SET `address_area_id` = NULL
WHERE `security_users`.`address_area_id` NOT IN (SELECT id FROM area_administratives);

UPDATE `security_users`
SET `address_area_id` = NULL
WHERE `security_users`.`birthplace_area_id` NOT IN (SELECT id FROM area_administratives);

-- institutions

CREATE TABLE `z_2601_institutions` LIKE institutions;

INSERT IGNORE INTO `z_2601_institutions`
SELECT * FROM institutions WHERE area_id NOT IN (SELECT id FROM areas);

INSERT IGNORE INTO `z_2601_institutions`
SELECT * FROM institutions WHERE area_administrative_id NOT IN (SELECT id FROM area_administratives);

UPDATE `institutions`
SET area_id = (SELECT id FROM areas WHERE parent_id = -1 LIMIT 1)
WHERE area_id NOT IN (SELECT id FROM areas);

UPDATE `institutions`
SET area_administrative_id = NULL
WHERE area_administrative_id NOT IN (SELECT id FROM area_administratives);

-- security_group_areas
CREATE TABLE `z_2601_security_group_areas` LIKE `security_group_areas`;

INSERT INTO `z_2601_security_group_areas`
SELECT * FROM `security_group_areas` WHERE area_id NOT IN (SELECT id FROM areas);

UPDATE `security_group_areas`
SET area_id = (SELECT id FROM areas WHERE parent_id = -1 LIMIT 1)
WHERE area_id NOT IN (SELECT id FROM areas);

