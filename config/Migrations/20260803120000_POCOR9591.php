<?php

use Migrations\AbstractMigration;

class POCOR9591 extends AbstractMigration
{
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
        //POCOR-9591: document valid status values on the column
        $this->execute("ALTER TABLE `security_users` MODIFY COLUMN `status` INT NOT NULL DEFAULT 1 COMMENT '0=Inactive, 1=Active, 2=Locked'");
    }

    public function down()
    {
        $this->restoreTable();
    }
}
