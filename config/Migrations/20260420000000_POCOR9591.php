<?php

use Migrations\AbstractMigration;

class POCOR9591 extends AbstractMigration
{
    //POCOR-9591: Adds semantic support for status=2 (Locked) on security_users.
    //No schema change needed — status is already INT with no CHECK constraint.
    //Backs up security_users so the change is reversible.

    private function backupTables()
    {
        $tables = ['security_users'];
        foreach ($tables as $t) {
            $b = 'z_9591_' . $t;
            if (!$this->hasTable($b)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `$b` LIKE `$t`");
                $this->execute("INSERT INTO `$b` SELECT * FROM `$t`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreTable()
    {
        $tables = ['security_users'];
        foreach ($tables as $t) {
            $b = 'z_9591_' . $t;
            if ($this->hasTable($b)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `$t`");
                $this->execute("RENAME TABLE `$b` TO `$t`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    public function up()
    {
        $this->backupTables();
        //POCOR-9591: status=2 (Locked) is now a valid value set by the system
        //when a user exceeds the maximum login attempts configured in config_items.login_attempts.
        //status=0 (Inactive) remains admin-triggered only.
        //No column alteration needed; INT already accepts value 2.
    }

    public function down()
    {
        $this->restoreTable();
    }
}
