<?php

use Migrations\AbstractMigration;

class POCOR5267a extends AbstractMigration
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
		// backup 
        $this->execute('CREATE TABLE `z_5267a_api_securities` LIKE `api_securities`');
		$this->execute('INSERT INTO `z_5267a_api_securities` SELECT * FROM `api_securities`');
		
		$this->execute('UPDATE `api_securities` SET `index` = 1, `view` = 1, `add` = 1, `edit` = 1 WHERE `model` = "Institution.Institutions"');
		$this->execute('UPDATE `api_securities` SET `index` = 1, `view` = 1, `add` = 1, `edit` = 1 WHERE `model` = "User.Users"');
		$this->execute('UPDATE `api_securities` SET `index` = 1, `view` = 1, `add` = 0, `edit` = 0 WHERE `model` = "Institution.InstitutionClasses"');
		$this->execute('UPDATE `api_securities` SET `index` = 1, `view` = 1, `add` = 1, `edit` = 1 WHERE `model` = "Institution.StudentAdmission"');
		
        // end 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_5267a_api_securities` TO `api_securities`');
    }
}
