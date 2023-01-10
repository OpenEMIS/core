<?php
use Phinx\Migration\AbstractMigration;

class POCOR6519 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6519_report_student_assessment_summary` LIKE `report_student_assessment_summary`');
        $this->execute('INSERT INTO `z_6519_report_student_assessment_summary` SELECT * FROM `report_student_assessment_summary`');

        $this->execute('ALTER TABLE `report_student_assessment_summary` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_student_assessment_summary`');
        $this->execute('RENAME TABLE `z_6519_report_student_assessment_summary` TO `report_student_assessment_summary`');
    }
}
