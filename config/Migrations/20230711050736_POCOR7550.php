<?php
use Migrations\AbstractMigration;

class POCOR7550 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_7550_assessment_periods` LIKE `assessment_periods`');
        $this->execute('INSERT INTO `zz_7550_assessment_periods` SELECT * FROM `assessment_periods`');
        $this->execute("ALTER TABLE `assessment_periods` ADD COLUMN editable_student_statuses DEFAULT '0' COMMENT '1=yes, 0=no' INT(11) AFTER assessment_id ");
        
    }
    public function down()
    { 
        // Restore table
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `assessment_periods`');
        $this->execute('RENAME TABLE `zz_7550_assessment_periods` TO `assessment_periods`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
