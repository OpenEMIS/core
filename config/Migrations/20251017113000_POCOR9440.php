<?php

use Migrations\AbstractMigration;

class POCOR9440 extends AbstractMigration
{
    public function up()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Drop triggers
//        $this->execute("DROP TRIGGER IF EXISTS `trigger_institution_student_absence_details_insert`;");
//        $this->execute("DROP TRIGGER IF EXISTS `trigger_institution_student_absence_details_update`;");
//        $this->execute("DROP TRIGGER IF EXISTS `trigger_institution_student_absence_details_delete`;");

        // Backup original table
        $this->execute('DROP TABLE IF EXISTS `z_9440_institution_student_absence_details`');
        $this->execute('CREATE TABLE `z_9440_institution_student_absence_details` LIKE `institution_student_absence_details`');
        $this->execute('INSERT INTO `z_9440_institution_student_absence_details` SELECT * FROM `institution_student_absence_details`');

        // ➕ Add missing indexes
        if (!$this->table('institution_student_absence_details')->hasIndex(['date'])) {
            $this->table('institution_student_absence_details')
                ->addIndex(['date'], ['name' => 'idx_absence_date'])
                ->update();
        }

        if (!$this->table('institution_student_absence_details')->hasIndex(['period'])) {
            $this->table('institution_student_absence_details')
                ->addIndex(['period'], ['name' => 'idx_absence_period'])
                ->update();
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        if ($this->hasTable('z_9440_institution_student_absence_details')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');

            // Drop modified table
            $this->execute('DROP TABLE IF EXISTS `institution_student_absence_details`;');

            // Restore backup
            $this->execute('RENAME TABLE `z_9440_institution_student_absence_details` TO `institution_student_absence_details`;');

            // Recreate all triggers (no schema prefix)

            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
