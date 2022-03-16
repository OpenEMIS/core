<?php
use Migrations\AbstractMigration;

class POCOR6625 extends AbstractMigration
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
        /** Create OpenEMIS Core report_student_assessment_summary table */
        $this->execute('UPDATE `config_items` SET `value` = 1 WHERE `config_items`.`code` = "latitude_longitude"');
    }

    //rollback
    public function down()
    {
        $this->execute('UPDATE `config_items` SET `value` = 0 WHERE `config_items`.`code` = "latitude_longitude"');
    }
}
