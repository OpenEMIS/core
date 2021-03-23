<?php
use Migrations\AbstractMigration;

class POCOR5184 extends AbstractMigration
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
        // Backup institution_students_report_cards table
        $this->execute('CREATE TABLE `z_5184_institution_students_report_cards` LIKE `institution_students_report_cards`');
        $this->execute('INSERT INTO `z_5184_institution_students_report_cards` SELECT * FROM `institution_students_report_cards`');
        // End	
		
        $this->execute('ALTER TABLE `institution_students_report_cards` ADD COLUMN `file_content_pdf` longblob AFTER `file_content`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_students_report_cards`');
        $this->execute('RENAME TABLE `z_5184_institution_students_report_cards` TO `institution_students_report_cards`');
    }
}