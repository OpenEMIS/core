<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Hash;

class POCOR4980 extends AbstractMigration
{
    public function up()
    {
    	// backup 
        $this->execute('CREATE TABLE `z_4980_security_users` LIKE `security_users`');
				
		$query = $this->fetchAll('show index from `security_users` where Column_name = "identity_number"');
		
		// alter
		// If identity_number is not indexing then below condition will be running
		
		if(count($query) <= 0){
			$this->execute("ALTER TABLE `security_users` ADD INDEX `identity_number` (`identity_number`);");
		}
				
    }	
	
	public function down()
       {
	    	$this->execute('DROP TABLE IF EXISTS `security_users`');
            $this->execute('RENAME TABLE `z_4980_security_users` TO `security_users`');
       }

}
