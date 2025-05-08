<?php

use Phinx\Migration\AbstractMigration;

class POCOR8942 extends AbstractMigration
{
    public function up()
    {
        // Backup the table
        $this->execute('CREATE TABLE `z_8942_education_grades_gpa` LIKE `education_grades_gpa`');
        $this->execute('INSERT INTO `z_8942_education_grades_gpa` SELECT * FROM `education_grades_gpa`');

        // Change column types from datetime to date
        $this->table('education_grades_gpa')
            ->changeColumn('start_date', 'date', ['default' => null, 'null' => true])
            ->changeColumn('end_date', 'date', ['default' => null, 'null' => true])
            ->update();
    }

    public function down()
    {
        // Drop the modified table (if it exists)
        $this->execute('DROP TABLE IF EXISTS `education_grades_gpa`');

        // Rename backup table back to original (if it exists)
        $this->execute('RENAME TABLE `z_8942_education_grades_gpa` TO `education_grades_gpa`');
    }
}
