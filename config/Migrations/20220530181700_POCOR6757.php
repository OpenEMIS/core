<?php
use Migrations\AbstractMigration;

class POCOR6757 extends AbstractMigration
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
        /** BACKUP OpenEMIS Core report_queries */
        $this->execute('DROP TABLE IF EXISTS `z_6757_report_queries`');
        $this->execute('CREATE TABLE `z_6757_report_queries` LIKE `report_queries`');

        /** UPDATE OpenEMIS Core report_queries */
        $this->execute('UPDATE report_queries SET frequency = `week` WHERE name IN (`report_student_assessment_summary_truncate`,`report_student_assessment_summary_insert`)');
        
    }

    //rollback
    public function down()
    {
        /** RESTORE OpenEMIS Core report_queries */
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('CREATE TABLE `report_queries` LIKE `z_6757_report_queries`');
        $this->execute('DROP TABLE IF EXISTS `z_6757_report_queries`');
    }
}