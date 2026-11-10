<?php
use Migrations\AbstractMigration;

class POCOR9887 extends AbstractMigration
{
    // POCOR-9887: Institutions > Students - Guardians > "Guardian Profile" did not grant access to the
    // GuardianProfile page (View Profile button), which was gated by a separate "Student Guardian Profile"
    // function added in POCOR-9811. Merge that function into "Guardian Profile" and remove the duplicate.
    public function up()
    {
        // Backup
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9887_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `zz_9887_security_functions` SELECT * FROM `security_functions`');
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9887_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT IGNORE INTO `zz_9887_security_role_functions` SELECT * FROM `security_role_functions`');

        $guardianProfile = $this->fetchRow("
            SELECT `id` FROM `security_functions`
            WHERE `name` = 'Guardian Profile'
              AND `controller` = 'Students'
              AND `module` = 'Institutions'
              AND `category` = 'Students - Guardians'
        ");
        if (empty($guardianProfile)) {
            return;
        }
        $guardianProfileId = $guardianProfile['id'];

        // Guarded per column: a site that was patched by hand may already carry one but not the other.
        $this->execute("
            UPDATE `security_functions`
            SET `_view` = CONCAT_WS('|', NULLIF(`_view`, ''), 'GuardianProfile.index|GuardianProfile.view')
            WHERE `id` = $guardianProfileId
              AND (`_view` IS NULL OR `_view` NOT LIKE '%GuardianProfile.view%')
        ");

        $this->execute("
            UPDATE `security_functions`
            SET `_edit` = CONCAT_WS('|', NULLIF(`_edit`, ''), 'GuardianProfile.edit')
            WHERE `id` = $guardianProfileId
              AND (`_edit` IS NULL OR `_edit` NOT LIKE '%GuardianProfile.edit%')
        ");

        $duplicate = $this->fetchRow("
            SELECT `id` FROM `security_functions`
            WHERE `name` = 'Student Guardian Profile'
              AND `controller` = 'Students'
              AND `module` = 'Institutions'
              AND `category` = 'Students - Guardians'
        ");
        if (!empty($duplicate)) {
            $duplicateId = $duplicate['id'];

            // Carry over any role permissions already granted on the duplicate
            $this->execute("
                INSERT INTO `security_role_functions`
                    (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `created_user_id`, `created`)
                SELECT `src`.`_view`, `src`.`_edit`, 0, 0, 0, `src`.`security_role_id`, $guardianProfileId, 1, NOW()
                FROM `security_role_functions` AS `src`
                WHERE `src`.`security_function_id` = $duplicateId
                ON DUPLICATE KEY UPDATE
                    `_view` = GREATEST(IFNULL(`security_role_functions`.`_view`, 0), IFNULL(VALUES(`_view`), 0)),
                    `_edit` = GREATEST(IFNULL(`security_role_functions`.`_edit`, 0), IFNULL(VALUES(`_edit`), 0)),
                    `modified` = NOW(),
                    `modified_user_id` = 1
            ");

            $this->execute("DELETE FROM `security_role_functions` WHERE `security_function_id` = $duplicateId");
            $this->execute("DELETE FROM `security_functions` WHERE `id` = $duplicateId");
        }

        // AccessControlComponent::buildPermissions() caches the permission tree in the session and
        // only rebuilds it when isChanged() sees a security_role_functions row newer than
        // Permissions.lastModified. Widening _view/_edit on security_functions alone leaves that
        // cache stale, so every already-logged-in user would keep missing the View Profile button
        // until they log out. Touch the grant rows so the rebuild is triggered on their next request.
        $this->execute("
            UPDATE `security_role_functions`
            SET `modified` = NOW(),
                `modified_user_id` = 1
            WHERE `security_function_id` = $guardianProfileId
        ");
    }

    // Restore only the rows touched by up(); dropping/renaming security_functions is blocked by the
    // security_role_functions foreign key and would discard permission changes made since.
    public function down()
    {
        $functionIds = $this->fetchAll("
            SELECT `id` FROM `zz_9887_security_functions`
            WHERE `name` IN ('Guardian Profile', 'Student Guardian Profile')
              AND `controller` = 'Students'
              AND `module` = 'Institutions'
              AND `category` = 'Students - Guardians'
        ");
        $functionIds = implode(',', array_map('intval', array_column($functionIds, 'id')));

        if (!empty($functionIds)) {
            // "Guardian Profile" _view/_edit
            $this->execute("
                UPDATE `security_functions` AS `sf`
                INNER JOIN `zz_9887_security_functions` AS `bk` ON `bk`.`id` = `sf`.`id`
                SET `sf`.`_view` = `bk`.`_view`,
                    `sf`.`_edit` = `bk`.`_edit`
                WHERE `sf`.`id` IN ($functionIds)
            ");

            // "Student Guardian Profile" row
            $this->execute("
                INSERT IGNORE INTO `security_functions`
                SELECT * FROM `zz_9887_security_functions`
                WHERE `id` IN ($functionIds)
            ");

            // Role permissions on both functions
            $this->execute("DELETE FROM `security_role_functions` WHERE `security_function_id` IN ($functionIds)");
            $this->execute("
                INSERT INTO `security_role_functions`
                SELECT * FROM `zz_9887_security_role_functions`
                WHERE `security_function_id` IN ($functionIds)
            ");
        }

        $this->execute('DROP TABLE IF EXISTS `zz_9887_security_functions`');
        $this->execute('DROP TABLE IF EXISTS `zz_9887_security_role_functions`');
    }
}
