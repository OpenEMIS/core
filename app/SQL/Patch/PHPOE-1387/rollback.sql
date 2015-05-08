--
-- PHPOE-1387 rollback.sql
-- 

DELETE FROM `security_group_institution_sites`
WHERE `id` IN (SELECT CONCAT_WS('-', `id`, `security_group_id`) FROM `institution_sites`);

ALTER TABLE `security_group_institution_sites` DROP PRIMARY KEY, ADD PRIMARY KEY (`security_group_id`, `institution_site_id`);
ALTER TABLE `security_group_institution_sites` DROP `id`;

DELETE FROM `security_groups`
WHERE `id` IN (SELECT `security_group_id` FROM `institution_sites`);

ALTER TABLE `institution_sites` DROP `security_group_id`;
