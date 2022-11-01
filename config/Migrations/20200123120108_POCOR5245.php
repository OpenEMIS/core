<?php

use Phinx\Migration\AbstractMigration;

class POCOR5245 extends AbstractMigration
{
    public function up()
    {
		// Backup Table
		$this->execute('CREATE TABLE `z_5245_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5245_security_functions` SELECT * FROM `security_functions`');
		
		//Update security functions for Absence delete permission
		$this->execute(
						'UPDATE `security_functions` SET `_delete` = "Absences.remove" 
						WHERE `name` = "Absence" AND `controller` = "Students"
						AND `module` = "Institutions" AND `category` = "Students - Academic"
						AND `_view` = "Absences.index|Absences.view"'
					);		
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_5245_security_functions` TO `security_functions`');
        
    }
}
