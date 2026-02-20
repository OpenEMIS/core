<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9485 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();

        // Add next_programme_option_id to education_programmes
        // 1 = Show One Programme (current behaviour), 0 = Show All Programmes
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute("ALTER TABLE `education_programmes` ADD COLUMN `next_programme_option_id` TINYINT(1) NOT NULL DEFAULT 1 AFTER `same_grade_promotion`");

        // Add order to education_programmes_next_programmes
        $this->execute("ALTER TABLE `education_programmes_next_programmes` ADD COLUMN `order` INT(11) NOT NULL DEFAULT 0 AFTER `next_programme_id`");

        // Populate order for all existing rows using ROW_NUMBER()
        $this->execute("
            UPDATE education_programmes_next_programmes AS t
            JOIN (
                SELECT id,
                       ROW_NUMBER() OVER (
                           PARTITION BY education_programme_id ORDER BY id
                       ) AS row_num
                FROM education_programmes_next_programmes
            ) AS ranks ON t.id = ranks.id
            SET t.`order` = ranks.row_num
        ");

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        $this->restoreTable();
    }

    private function backupTables()
    {
        $tables = [
            'education_programmes',
            'education_programmes_next_programmes',
        ];

        foreach ($tables as $table) {
            $backup = 'z_9485_' . $table;
            if (!$this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `$backup` LIKE `$table`");
                $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreTable()
    {
        $tables = [
            'education_programmes',
            'education_programmes_next_programmes',
        ];

        foreach ($tables as $table) {
            $backup = 'z_9485_' . $table;
            if ($this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `$table`");
                $this->execute("RENAME TABLE `$backup` TO `$table`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}
