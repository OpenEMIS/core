<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9697 — Relax user_activities.security_user_id FK so the audit
 * trail survives a hard-delete of the parent user.
 *
 * Before:
 *   security_user_id INT NOT NULL,
 *   CONSTRAINT user_activ_fk_secur_user_id
 *     FOREIGN KEY (security_user_id) REFERENCES security_users(id)
 *     ON DELETE RESTRICT ON UPDATE RESTRICT
 *
 * After:
 *   security_user_id INT NULL,
 *   CONSTRAINT user_activ_fk_secur_user_id
 *     FOREIGN KEY (security_user_id) REFERENCES security_users(id)
 *     ON DELETE SET NULL ON UPDATE RESTRICT
 *
 * model_reference (INT NOT NULL, no FK) keeps the original
 * security_users.id forever, so "everything that ever happened to user X"
 * remains queryable after the parent row is gone — the v6 dashboard query
 * can do `WHERE model_reference = ? AND model = 'Users'`.
 *
 * This activates the per-field delete-snapshot logic shipped with this
 * same ticket (UserActivityLog::logDeleteSnapshot +
 * TrackActivityBehavior::afterDelete) on security_users specifically.
 * Before this migration the FK rejected the snapshot INSERTs.
 */
class POCOR9697 extends AbstractMigration
{
    private const TICKET = '9697';

    private const BACKUP_TABLES = [
        'user_activities',
    ];

    private const FK_NAME = 'user_activ_fk_secur_user_id';

    public function up(): void
    {
        $this->backupTables();
        $this->relaxSecurityUserIdFk();
    }

    public function down(): void
    {
        $this->restoreTables();
    }

    // -------------------------------------------------------------------------
    // Backup / Restore
    // -------------------------------------------------------------------------

    private function backupTables(): void
    {
        foreach (self::BACKUP_TABLES as $table) {
            if (!$this->hasTable($table)) {
                continue;
            }
            $backup = 'z_' . self::TICKET . '_' . $table;
            if ($this->hasTable($backup)) {
                continue;
            }
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute("CREATE TABLE `$backup` LIKE `$table`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function restoreTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        foreach (self::BACKUP_TABLES as $table) {
            $backup = 'z_' . self::TICKET . '_' . $table;
            if ($this->hasTable($backup)) {
                $this->execute("DROP TABLE IF EXISTS `$table`");
                $this->execute("RENAME TABLE `$backup` TO `$table`");
            }
        }
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    // -------------------------------------------------------------------------
    // Schema change
    // -------------------------------------------------------------------------

    private function relaxSecurityUserIdFk(): void
    {
        if (!$this->hasTable('user_activities')) {
            return;
        }

        // Drop the existing FK only if it is still there (idempotent).
        $db = $this->getAdapter()->getOption('name');
        $fk = self::FK_NAME;
        $exists = $this->fetchRow(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = '$db'
               AND TABLE_NAME = 'user_activities'
               AND CONSTRAINT_NAME = '$fk'"
        );
        if ($exists) {
            $this->execute("ALTER TABLE `user_activities` DROP FOREIGN KEY `$fk`");
        }

        // Make the column nullable so SET NULL is legal.
        $this->execute(
            "ALTER TABLE `user_activities`
             MODIFY `security_user_id` INT NULL COMMENT 'links to security_users.id'"
        );

        // Re-add the FK with ON DELETE SET NULL so the audit trail survives
        // a parent hard-delete. ON UPDATE stays RESTRICT — id changes on
        // security_users would be a different kind of incident.
        $this->execute(
            "ALTER TABLE `user_activities`
             ADD CONSTRAINT `$fk`
             FOREIGN KEY (`security_user_id`) REFERENCES `security_users` (`id`)
             ON DELETE SET NULL ON UPDATE RESTRICT"
        );
    }
}
