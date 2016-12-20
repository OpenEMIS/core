-- POCOR-3633
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3633', NOW());

-- security_function
UPDATE `security_functions` SET `_edit`='StudentUser.edit|StudentUser.pull' WHERE `id`='2000';


-- POCOR-3623
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3623', NOW());

-- backup user_identities
DROP TABLE IF EXISTS `z_3623_user_identities`;
CREATE TABLE `z_3623_user_identities` LIKE `user_identities`;

INSERT INTO `z_3623_user_identities`
SELECT * FROM `user_identities` UI
WHERE NOT EXISTS (
        SELECT 1
    FROM `security_users` SU
    WHERE UI.`security_user_id` = SU.`id`
);

-- delete user_identities
DELETE FROM `user_identities`
WHERE NOT EXISTS (
        SELECT 1
    FROM `security_users`
    WHERE `user_identities`.`security_user_id` = `security_users`.`id`
);


-- 3.8.3
UPDATE config_items SET value = '3.8.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
