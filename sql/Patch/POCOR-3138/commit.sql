-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3138', NOW());

-- security_user
ALTER TABLE `security_users` ADD `identity_number` VARCHAR(50) NULL DEFAULT NULL AFTER `date_of_death`, ADD INDEX (`identity_number`);

#update identity_number based on the current default identity type

UPDATE `security_users`
SET `identity_number` = NULL;

UPDATE `security_users` S
INNER JOIN (
    SELECT `security_user_id`, `number`
    FROM `user_identities` U1
    WHERE `created` = (
        		SELECT MAX(U2.`created`)
        		FROM `user_identities` U2
        		WHERE U1.`security_user_id` = U2.`security_user_id`)
    AND `identity_type_id` = (
        				SELECT `id` 
                        FROM `identity_types`
                        WHERE `default` = 1)
	AND `number` <> '') U
ON S.`id` = U.`security_user_id`
SET S.`identity_number` = U.`number`;