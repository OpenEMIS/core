<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9693 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        /** Backup */
        $this->execute('CREATE TABLE `zz_9693_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_9693_security_functions` SELECT * FROM `security_functions`');
        /** Update Staff Behaviour Attachments permission mapping */
        $this->execute("
            UPDATE `security_functions`
            SET
                `controller` = 'Institutions',
                '_view'            => 'StaffBehaviourAttachments.index|StaffBehaviourAttachments.view',
                '_edit'            => 'StaffBehaviourAttachments.edit',
                '_add'             => 'StaffBehaviourAttachments.add',
                '_delete'          => 'StaffBehaviourAttachments.delete',
            WHERE `name` = 'Staff Behaviour Attachments Old'
              AND `module` = 'Institutions'
              AND `category` = 'Staff'
        ");
    }

    /**
     * Rollback Method
     *
     * Restores original security_functions table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_9693_security_functions` TO `security_functions`');
    }
}
