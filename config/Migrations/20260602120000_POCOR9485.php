<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9485 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();

        // Add next_programme_option_id to education_programmes:
        //   1 = Show One Grade  (current behaviour, default)
        //   0 = Show All Grades (cross-cycle direct promotion)
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute("ALTER TABLE `education_programmes` ADD COLUMN `next_programme_option_id` TINYINT(1) NOT NULL DEFAULT 1 AFTER `same_grade_promotion`");
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * WARNING — rollback restores `education_programmes` from the snapshot taken at
     * up() time. Any programme rows created/modified AFTER the migration ran are
     * DELETED on rollback. Records that FK-reference education_programmes
     * (education_grades, institution_classes, institution_class_students, student
     * admissions, etc.) are NOT auto-cleaned and will become orphans pointing to
     * non-existent programme IDs.
     *
     * Before running rollback in any environment where real writes may have
     * happened, dump the post-migration rows you care about OR delete dependent
     * orphans manually. See the release doc's Rollback section for the recipe.
     */
    public function down()
    {
        $this->restoreTable();
    }

    private function backupTables()
    {
        $tables = [
            'education_programmes',
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
