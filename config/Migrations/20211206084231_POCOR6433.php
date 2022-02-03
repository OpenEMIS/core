<?php
use Migrations\AbstractMigration;

class POCOR6433 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // security_functions table backup
        $this->execute('CREATE TABLE `zz_6433_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6433_security_functions` SELECT * FROM `security_functions`');

        // security_functions table changes for "Leave Tab"
        $this->execute("UPDATE `security_functions` SET `_edit` = 'StaffLeave.edit', `_add` = 'StaffLeave.add', `_delete` = 'StaffLeave.remove' WHERE `category` = 'Staff - Career' AND `name` = 'Leave' AND `module` = 'Personal'");

        // security_functions table changes for "Statuses"
        $this->execute("UPDATE `security_functions` SET `_edit` = 'StaffEmploymentStatuses.edit', `_add` = 'StaffEmploymentStatuses.add', `_delete` = 'StaffEmploymentStatuses.remove' WHERE `category` = 'Staff - Career' AND `name` = 'Statuses' AND `module` = 'Personal'");
    }

    //rollback
    public function down()  
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6433_security_functions` TO `security_functions`');
    }
}
