-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3527', NOW());

RENAME TABLE `db_patches` TO `system_patches`;

CREATE TABLE IF NOT EXISTS `system_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_released` date NOT NULL,
  `date_approved` date DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL COMMENT 'links to security_users.id',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '1 -> Pending, 2 -> Approved',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL DEFAULT 1,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `system_updates` ADD UNIQUE(`version`);

INSERT INTO `security_functions` (id, name, controller, module, category, parent_id, _view, _execute, `order`, `visible`, created_user_id, created) VALUES
(5060, 'Updates', 'Systems', 'Administration', 'Updates', 5000, 'Updates.index|Updates.view', 'Updates.updates', 5061, 1, 1, NOW());


INSERT INTO config_items (id, name, code, type, label, value, default_value, editable, visible, field_type, option_type, created_user_id, created) VALUES
(200, 'Version Support Emails', 'version_support_emails', 'System', 'Version Support Emails', 'support@openemis.org,shasanuddin@kordit.com', 'support@openemis.org', 0, 0, '', '', 1, NOW()),
(201, 'Version API Domain', 'version_api_domain', 'System', 'Version API Domain', 'https://demo.openemis.org/core', 'https://demo.openemis.org/core', 0, 0, '', '', 1, NOW());

TRUNCATE TABLE system_updates;
INSERT INTO system_updates
SELECT
NULL,
version,
created,
created,
1,
2,
null, null, 1, created
FROM system_patches
WHERE version IS NOT NULL
AND NOT EXISTS (
  SELECT 1 FROM system_updates WHERE system_updates.version = system_patches.version
)
GROUP BY version
ORDER BY created ASC, version ASC


-- Add below into version commit.sql at the end of the script

-- SET @maxId := 0;
-- SELECT max(id) + 1 INTO @maxId FROM system_updates;
-- INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
-- (
--   @maxId,
--   (SELECT value FROM config_items WHERE code = 'db_version'),
--   NOW(), NOW(), 1, 2, NOW()
-- );
