<?php
use Migrations\AbstractMigration;

class POCOR7318 extends AbstractMigration
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
       $this->execute('CREATE TABLE `z_7318_assessment_grading_options` LIKE `assessment_grading_options`');
       $this->execute('INSERT INTO `z_7318_assessment_grading_options` SELECT * FROM `assessment_grading_options`');
        
       $this->execute('ALTER TABLE `assessment_grading_options` ADD `gpa` int(11) NULL AFTER `max`');
    }

    // rollback
    public function down()
    {
        // Restore table
       $this->execute('DROP TABLE IF EXISTS `assessment_grading_options`');
       $this->execute('RENAME TABLE `zz_7318_assessment_grading_options` TO `assessment_grading_options`');
    }
}
