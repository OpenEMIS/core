<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9863
 *
 * Notice visibility is now decided by notice_roles alone (enabled notices
 * assigned to one of the user's roles), so the "Notice Message" security
 * function no longer gates anything. Hide it so admins don't see a toggle
 * that has no effect.
 */
class POCOR9863 extends AbstractMigration
{
    private const WHERE = "`controller` = 'Systems' AND `name` = 'Notice Message'"
        . " AND `module` = 'Administration' AND `category` = 'Communications'";

    public function up(): void
    {
        $this->execute('UPDATE `security_functions` SET `visible` = 0 WHERE ' . self::WHERE);
    }

    public function down(): void
    {
        $this->execute('UPDATE `security_functions` SET `visible` = 1 WHERE ' . self::WHERE);
    }
}
