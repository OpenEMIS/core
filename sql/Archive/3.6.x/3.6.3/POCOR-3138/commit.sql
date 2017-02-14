-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3138', NOW());

-- security_user
ALTER TABLE `security_users` ADD `identity_number` VARCHAR(50) NULL DEFAULT NULL AFTER `date_of_death`, ADD INDEX (`identity_number`);

#update identity_number based on the current default identity type
UPDATE `security_users` S 
INNER JOIN (
    SELECT `security_user_id`, `number`
    FROM `user_identities` U1
    WHERE `created` = (
        SELECT MAX(U2.`created`)
        FROM `user_identities` U2
        WHERE U1.`security_user_id` = U2.`security_user_id`
        AND U2.`identity_type_id` = (
            SELECT `id` 
            FROM `identity_types`
            WHERE `default` = 1)
        GROUP BY U2.`security_user_id`)
    AND `number` <> '') U
ON S.`id` = U.`security_user_id`
SET S.`identity_number` = U.`number`;

-- translations
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT NULL, NULL, 'Please set other identity type as default before deleting the current one', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual
WHERE NOT EXISTS (SELECT * FROM `translations` WHERE `en` = 'Please set other identity type as default before deleting the current one');
