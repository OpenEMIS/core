<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9590 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();

        //POCOR-9590: 0=Local (no preferred external identity), 1=Synced, 2=Not Synced (drifted after edit)
        $this->table('security_users')
            ->addColumn('sync_status', 'integer', [
                'default' => 0,
                'null' => false,
                'limit' => 1,
                'after' => 'external_reference',
                'comment' => '0=Local, 1=Synced, 2=Not Synced',
            ])
            ->update();
    }

    public function down()
    {
        $this->restoreTables();
    }

    private function backupTables()
    {
        $tables = ['security_users'];
        foreach ($tables as $t) {
            $backup = 'z_9590_' . $t;
            if (!$this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `{$backup}` LIKE `{$t}`");
                $this->execute("INSERT INTO `{$backup}` SELECT * FROM `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreTables()
    {
        $tables = ['security_users'];
        foreach ($tables as $t) {
            $backup = 'z_9590_' . $t;
            if ($this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `{$t}`");
                $this->execute("RENAME TABLE `{$backup}` TO `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}
