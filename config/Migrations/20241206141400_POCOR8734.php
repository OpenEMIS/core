<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8734 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
        public function up()
        {
            //Backup `education_grades_subjects` table 
            $this->execute('SET FOREIGN_KEY_CHECKS = 0');
            $this->execute('CREATE TABLE IF NOT EXISTS `z_8734_education_grades_subjects` LIKE `education_grades_subjects`');
            $this->execute('INSERT INTO `z_8734_education_grades_subjects` SELECT * FROM `education_grades_subjects`');
            $this->execute("ALTER TABLE `education_grades_subjects` CHANGE COLUMN `result_type` `result_type` VARCHAR(255) DEFAULT 'Assessments'");
            $this->execute("UPDATE `education_grades_subjects` SET `result_type` = 'Assessments' WHERE `result_type` IS NULL OR `result_type` = ''");
            $this->execute('SET FOREIGN_KEY_CHECKS = 1');
        }

        public function down()
        {
            //Restore `education_grades_subjects` table 
            $this->execute('SET FOREIGN_KEY_CHECKS = 0');
            $this->execute('DROP TABLE IF EXISTS `education_grades_subjects`');
            $this->execute('RENAME TABLE `z_8734_education_grades_subjects` TO `education_grades_subjects`');
            $this->execute('SET FOREIGN_KEY_CHECKS = 1');
        }

}
