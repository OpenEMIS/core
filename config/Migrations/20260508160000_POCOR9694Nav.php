<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9694 — Async Services nav grouping
 *
 * Introduces a new {{Administration → Async Services}} navigation group that
 * collects every async/queue surface in one place. Builds the security
 * scaffolding (security_functions + super_admin role grants) so the new
 * group is gated by the standard role-permission flow.
 *
 * The CakePHP nav code in {{src/Controller/Component/NavigationComponent.php}}
 * (POCOR-9694 follow-up commit) reads {{category = 'Async Services'}} +
 * {{module = 'Administration'}} and renders the section once any role has at
 * least one matching {{_view = 1}} grant.
 *
 * Idempotent: re-running the migration is a no-op once the rows are present.
 *
 * @see api/storage/release-docs/POCOR-9694-README.md
 */
class POCOR9694Nav extends AbstractMigration
{
    private const TICKET = '9694';
    private const ADMINISTRATION_PARENT_ID = 5000;
    private const NAV_CATEGORY = 'Async Services';
    private const NAV_MODULE = 'Administration';

    private const BACKUP_TABLES = [
        'security_functions',
        'security_role_functions',
    ];

    /**
     * Each row defines one Async Services entry.
     *
     * - {{name}} is the human-facing label rendered in the sidebar.
     * - {{controller}} is the CakePHP controller key used by the nav array.
     * - {{view_actions}} are the {{action}} segments granted by {{_view = 1}}
     *   on the matching role; concatenated with {{|}} to form the legacy
     *   {{_view}} text column.
     * - {{order_offset}} keeps the entries in display order; the absolute
     *   {{`order`}} value is computed as {{ORDER_BASE + offset}} so the whole
     *   block stays contiguous and easy to renumber.
     */
    private const NAV_ROWS = [
        [
            'name'         => 'Overview',
            'controller'   => 'Systems',
            'view_actions' => ['AsyncServices.index', 'AsyncServices.view'],
            'order_offset' => 0,
        ],
        [
            'name'         => 'Processes',
            'controller'   => 'Systems',
            'view_actions' => ['SystemProcesses.index', 'SystemProcesses.view'],
            'order_offset' => 1,
        ],
        [
            'name'         => 'Failed Jobs',
            'controller'   => 'Systems',
            'view_actions' => ['FailedJobs.index', 'FailedJobs.view'],
            'order_offset' => 2,
        ],
        [
            'name'         => 'Stuck Processes',
            'controller'   => 'Systems',
            'view_actions' => ['StuckProcesses.index', 'StuckProcesses.view'],
            'order_offset' => 3,
        ],
        [
            'name'         => 'Webhook Failures',
            'controller'   => 'Systems',
            'view_actions' => ['WebhookFailures.index', 'WebhookFailures.view'],
            'order_offset' => 4,
        ],
        [
            'name'         => 'Queue Backlog',
            'controller'   => 'Systems',
            'view_actions' => ['QueueBacklog.index', 'QueueBacklog.view'],
            'order_offset' => 5,
        ],
    ];

    /** Order base — picked above existing Communications block (ends at 295). */
    private const ORDER_BASE = 600;

    /** super_admin role id — the canonical sole grant for new admin sections. */
    private const SUPER_ADMIN_ROLE_ID = 10;

    public function up(): void
    {
        $this->backupTables();
        $this->insertSecurityFunctions();
        $this->grantSuperAdmin();
    }

    public function down(): void
    {
        $this->revokeSuperAdmin();
        $this->deleteSecurityFunctions();
    }

    // -------------------------------------------------------------------------
    // Backup / Restore
    // -------------------------------------------------------------------------

    private function backupTables(): void
    {
        foreach (self::BACKUP_TABLES as $table) {
            $backup = sprintf('z_%s_nav_%s', self::TICKET, $table);
            if (!$this->hasTable($table) || $this->hasTable($backup)) {
                continue;
            }
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute("CREATE TABLE `$backup` LIKE `$table`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    // -------------------------------------------------------------------------
    // security_functions
    // -------------------------------------------------------------------------

    private function insertSecurityFunctions(): void
    {
        foreach (self::NAV_ROWS as $row) {
            if ($this->securityFunctionExists($row['name'])) {
                continue;
            }
            $this->execute(sprintf(
                "INSERT INTO `security_functions`
                    (name, controller, module, category, parent_id, _view, `order`, visible, created_user_id, created)
                 VALUES
                    (%s, %s, %s, %s, %d, %s, %d, 1, 1, NOW())",
                $this->quote($row['name']),
                $this->quote($row['controller']),
                $this->quote(self::NAV_MODULE),
                $this->quote(self::NAV_CATEGORY),
                self::ADMINISTRATION_PARENT_ID,
                $this->quote(implode('|', $row['view_actions'])),
                self::ORDER_BASE + (int)$row['order_offset']
            ));
        }
    }

    private function deleteSecurityFunctions(): void
    {
        $this->execute(sprintf(
            "DELETE FROM `security_functions`
             WHERE module = %s AND category = %s",
            $this->quote(self::NAV_MODULE),
            $this->quote(self::NAV_CATEGORY)
        ));
    }

    private function securityFunctionExists(string $name): bool
    {
        $stmt = $this->query(sprintf(
            "SELECT id FROM `security_functions`
             WHERE name = %s AND module = %s AND category = %s
             LIMIT 1",
            $this->quote($name),
            $this->quote(self::NAV_MODULE),
            $this->quote(self::NAV_CATEGORY)
        ));
        return $stmt->fetch() !== false;
    }

    // -------------------------------------------------------------------------
    // security_role_functions
    // -------------------------------------------------------------------------

    private function grantSuperAdmin(): void
    {
        $this->execute(sprintf(
            "INSERT INTO `security_role_functions`
                (security_role_id, security_function_id, _view, _edit, _add, _delete, _execute, created_user_id, created)
             SELECT %d, sf.id, 1, 0, 0, 0, 0, 1, NOW()
             FROM `security_functions` sf
             LEFT JOIN `security_role_functions` srf
                 ON srf.security_function_id = sf.id
                AND srf.security_role_id = %d
             WHERE sf.module = %s
               AND sf.category = %s
               AND srf.security_function_id IS NULL",
            self::SUPER_ADMIN_ROLE_ID,
            self::SUPER_ADMIN_ROLE_ID,
            $this->quote(self::NAV_MODULE),
            $this->quote(self::NAV_CATEGORY)
        ));
    }

    private function revokeSuperAdmin(): void
    {
        $this->execute(sprintf(
            "DELETE srf FROM `security_role_functions` srf
             JOIN `security_functions` sf ON sf.id = srf.security_function_id
             WHERE srf.security_role_id = %d
               AND sf.module = %s
               AND sf.category = %s",
            self::SUPER_ADMIN_ROLE_ID,
            $this->quote(self::NAV_MODULE),
            $this->quote(self::NAV_CATEGORY)
        ));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function quote(string $value): string
    {
        return $this->getAdapter()->getConnection()->quote($value);
    }
}
