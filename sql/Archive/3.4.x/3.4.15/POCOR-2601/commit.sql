-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2601', NOW());

-- security_users

UPDATE `security_users`
SET `address_area_id` = NULL
WHERE `security_users`.`address_area_id` NOT IN (SELECT id FROM area_administratives);

UPDATE `security_users`
SET `birthplace_area_id` = NULL
WHERE `security_users`.`birthplace_area_id` NOT IN (SELECT id FROM area_administratives);

-- institutions

UPDATE `institutions`
SET area_id = (SELECT id FROM areas WHERE parent_id = -1 LIMIT 1)
WHERE area_id NOT IN (SELECT id FROM areas);

UPDATE `institutions`
SET area_administrative_id = NULL
WHERE area_administrative_id NOT IN (SELECT id FROM area_administratives);

-- security_group_areas
DELETE FROM `security_group_areas`
WHERE area_id NOT IN (SELECT id FROM areas);

