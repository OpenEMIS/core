<?php

use Phinx\Migration\AbstractMigration;

class POCOR6154 extends AbstractMigration
{
   	public function up() {
		
		$this->execute('CREATE TABLE `zz_6154_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6154_security_functions` SELECT * FROM `security_functions`');
		
		$this->execute("UPDATE `security_functions` SET `_execute` = 'StudentBehaviours.excel' WHERE `category`='Students' AND `name` = 'Behaviour' AND controller='Institutions' AND module = 'Institutions'");

	}
	
	public function down() {
		$this->execute('DROP TABLE IF EXISTS `security_functions`');
		$this->execute('RENAME TABLE `zz_6154_security_functions` TO `security_functions`');
	}
	
}


