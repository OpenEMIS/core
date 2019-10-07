<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Hash;

class POCOR5009a extends AbstractMigration
{
    public function up()
    {
		$openemisTemps = $this->table('openemis_temps',['collation' => 'utf8mb4_unicode_ci']);
		$openemisTemps->addColumn('openemis_no', 'string',[
				'limit' => 150,
                'null' => false])
              ->addColumn('ip_address', 'string',[
				'limit' => 40,
                'null' => false])
              ->create();
			  
		$this->execute('CREATE EVENT delete_openemis_temps_at_midnight ON SCHEDULE EVERY 1 DAY STARTS CURDATE() + INTERVAL 1 DAY DO TRUNCATE openemis_temps');
		 
		$this->execute('SET GLOBAL event_scheduler=ON');		 
		 
    }	
		
	public function down()
    {
	    $this->execute('DROP TABLE IF EXISTS `openemis_temps`');
		$this->execute('DROP EVENT IF EXISTS delete_openemis_temps_at_midnight');		
    }

}
