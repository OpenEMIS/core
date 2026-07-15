<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR3573 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();

        $this->execute("ALTER TABLE `examinations` ADD COLUMN `release_results_date` DATE NULL DEFAULT NULL AFTER `registration_end_date`");
    }

    public function down()
    {
        $this->restoreTables();
    }

    private function backupTables()
    {
        $tables = ['examinations'];
        foreach ($tables as $t) {
            $backup = 'z_3573_' . $t;
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
        $tables = ['examinations'];
        foreach ($tables as $t) {
            $backup = 'z_3573_' . $t;
            if ($this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `{$t}`");
                $this->execute("RENAME TABLE `{$backup}` TO `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}
