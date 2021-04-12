<?php
use Migrations\AbstractMigration;

class POCOR5948 extends AbstractMigration
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
		$this->execute('CREATE TABLE `zz_5948_ institution_grades` LIKE ` institution_grades`');
		$this->execute('INSERT INTO `zz_5948_ institution_grades` SELECT * FROM ` institution_grades`');
		$this->execute('ALTER TABLE `institution_grades` CHANGE `start_date` `start_date` date NULL DEFAULT NULL');		
		$this->execute('ALTER TABLE `institution_grades` CHANGE `start_year` `start_year` int(4) NULL DEFAULT NULL');		
    }

    // rollback
    public function down()
    {
		// rollback of  institution_grades
		$this->execute('DROP TABLE IF EXISTS ` institution_grades`');
		$this->execute('RENAME TABLE `zz_5948_ institution_grades` TO ` institution_grades`');
    }
}
