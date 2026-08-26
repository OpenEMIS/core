<?php
use Migrations\AbstractMigration;

// POCOR-9768: Duties feature enhancements.
//
//   1. Schema changes — staff_duties.security_role_id, institution_staff_duties.status,
//      institution_staff_duties.security_group_user_id.
//   2. One-time data cleanup — the status column above backfills every existing row to
//      'active'; any duty belonging to a staff member whose institution assignment had
//      already ended before this migration ran must be corrected to 'inactive', with any
//      linked security-role grant removed unless another still-active duty at that
//      institution shares the same grant (POCOR-9768's "don't duplicate / only remove
//      when nothing else needs it" rule).
//
// down() restores only the specific rows the cleanup step touched (tracked in the
// zz_9768_affected_* tables), not a full-table snapshot restore — security_group_users in
// particular is a hot, actively-written table, and wholesale-restoring it on rollback would
// discard any unrelated grants created after this migration ran. zz_9768_staff_duties and
// zz_9768_institution_staff_duties are kept purely as an audit/manual-recovery snapshot of the
// pre-alter schema (down() reverses the ALTERs directly, it doesn't restore from them).
class POCOR9768 extends AbstractMigration
{
    public function up()
    {
        // Backups — snapshot of both tables before any change below.
        $this->execute('CREATE TABLE `zz_9768_staff_duties` LIKE `staff_duties`');
        $this->execute('INSERT INTO `zz_9768_staff_duties` SELECT * FROM `staff_duties`');
        $this->execute('CREATE TABLE `zz_9768_institution_staff_duties` LIKE `institution_staff_duties`');
        $this->execute('INSERT INTO `zz_9768_institution_staff_duties` SELECT * FROM `institution_staff_duties`');

        // staff_duties: optional link to a security role (POCOR-9768)
        $this->execute("ALTER TABLE `staff_duties` ADD COLUMN `security_role_id` int(11) DEFAULT NULL COMMENT 'links to security_roles.id' AFTER `national_code`");
        $this->execute('ALTER TABLE `staff_duties` ADD CONSTRAINT `staff_dutie_fk_sec_role_id` FOREIGN KEY (`security_role_id`) REFERENCES `security_roles`(`id`)');

        // institution_staff_duties: activation status + the security_group_users row granted for it, if any (POCOR-9768)
        $this->execute("ALTER TABLE `institution_staff_duties` ADD COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `comment`");
        $this->execute("ALTER TABLE `institution_staff_duties` ADD COLUMN `security_group_user_id` char(36) DEFAULT NULL COMMENT 'links to security_group_users.id when the duty type carries a security role' AFTER `status`");

        // --- One-time cleanup: the ALTER above backfilled every existing row to 'active'. ---
        //
        // No separate full-table backup is taken here before the cleanup: unlike the pre-alter
        // backups above, a full copy at this point would be an exact-in-time duplicate of what
        // zz_9768_affected_duties/zz_9768_affected_group_users already capture precisely for the
        // rows this step actually changes — and security_group_users especially is a hot,
        // actively-written table, so copying it wholesale here for zero extra recovery benefit
        // is pure cost.
        $assignedStatusId = $this->fetchRow("SELECT id FROM `staff_statuses` WHERE code = 'ASSIGNED'")['id'];

        // Track exactly which rows the cleanup below is about to change, before changing them.
        $this->execute("
            CREATE TABLE `zz_9768_affected_duties` AS
            SELECT isd.id AS id, isd.status AS prev_status, isd.security_group_user_id AS prev_security_group_user_id
            FROM `institution_staff_duties` isd
            LEFT JOIN `institution_staff` ins
                ON ins.staff_id = isd.staff_id
                AND ins.institution_id = isd.institution_id
                AND ins.staff_status_id = {$assignedStatusId}
            WHERE isd.status = 'active'
                AND ins.id IS NULL
        ");

        $this->execute("
            CREATE TABLE `zz_9768_affected_group_users` AS
            SELECT sgu.*
            FROM `security_group_users` sgu
            INNER JOIN (
                SELECT DISTINCT prev_security_group_user_id AS security_group_user_id
                FROM `zz_9768_affected_duties`
                WHERE prev_security_group_user_id IS NOT NULL
            ) affected ON affected.security_group_user_id = sgu.id
        ");

        // Duties belonging to staff with no currently-active (staff_status_id = ASSIGNED)
        // institution_staff record at that institution — covers both "assignment ended" and
        // "staff record removed outright" — are corrected to inactive. Anything with a matching
        // active assignment is untouched, so it correctly stays Active.
        $this->execute("
            UPDATE `institution_staff_duties` isd
            INNER JOIN `zz_9768_affected_duties` affected ON affected.id = isd.id
            SET isd.status = 'inactive', isd.security_group_user_id = NULL
        ");

        // Remove each affected grant only if no remaining ACTIVE duty still references it
        // (i.e. no other duty for the same staff/institution shares that security role's grant).
        $this->execute("
            DELETE sgu FROM `security_group_users` sgu
            INNER JOIN `zz_9768_affected_group_users` affected ON affected.id = sgu.id
            WHERE NOT EXISTS (
                SELECT 1 FROM `institution_staff_duties` remaining
                WHERE remaining.security_group_user_id = sgu.id
                    AND remaining.status = 'active'
            )
        ");
    }

    public function down()
    {
        // Restore only the specific duty rows the cleanup step changed.
        $this->execute("
            UPDATE `institution_staff_duties` isd
            INNER JOIN `zz_9768_affected_duties` affected ON affected.id = isd.id
            SET isd.status = affected.prev_status, isd.security_group_user_id = affected.prev_security_group_user_id
        ");

        // Restore only the specific security_group_users rows the cleanup step deleted (skip any
        // id that already exists again, in case something else re-created it since).
        $this->execute("
            INSERT INTO `security_group_users`
            SELECT affected.* FROM `zz_9768_affected_group_users` affected
            WHERE NOT EXISTS (
                SELECT 1 FROM `security_group_users` sgu WHERE sgu.id = affected.id
            )
        ");

        $this->execute('DROP TABLE IF EXISTS `zz_9768_affected_duties`');
        $this->execute('DROP TABLE IF EXISTS `zz_9768_affected_group_users`');

        $this->execute('ALTER TABLE `staff_duties` DROP FOREIGN KEY `staff_dutie_fk_sec_role_id`');
        $this->execute('ALTER TABLE `staff_duties` DROP COLUMN `security_role_id`');

        $this->execute('ALTER TABLE `institution_staff_duties` DROP COLUMN `status`');
        $this->execute('ALTER TABLE `institution_staff_duties` DROP COLUMN `security_group_user_id`');

        $this->execute('DROP TABLE IF EXISTS `zz_9768_staff_duties`');
        $this->execute('DROP TABLE IF EXISTS `zz_9768_institution_staff_duties`');
    }
}
