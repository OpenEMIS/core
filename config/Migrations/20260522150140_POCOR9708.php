<?php
use Migrations\AbstractMigration;

class POCOR9708 extends AbstractMigration
{
    public function up()
    {
        // Backup security_functions table
        $this->execute('CREATE TABLE `zz_9708_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_9708_security_functions` SELECT * FROM `security_functions`');

        // Update permissions
        $this->execute("
            UPDATE security_functions
            SET 
                `_edit` = 'Registrations.edit',
                `_add` = 'Registrations.add'
            WHERE `name` = 'Registrations'
              AND `controller` = 'Institutions'
        ");
        $this->execute("
            UPDATE security_functions
            SET 
                `_edit` = 'Accreditations.edit',
                `_add` = 'Accreditations.add'
            WHERE `name` = 'Accreditations'
              AND `controller` = 'Institutions'
        ");
    }

    public function down()
    {
        // Restore backup
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_9708_security_functions` TO `security_functions`');
    }
}