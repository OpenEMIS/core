<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Hash;

class POCOR3920 extends AbstractMigration
{
    public function up()
    {
    	// backup 
        $this->execute('CREATE TABLE `z_3920_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `z_3920_alerts` SELECT * FROM `alerts`');
        // alter
        $this->execute("ALTER TABLE `alerts` ADD `triggered_on` TIME NOT NULL DEFAULT '01:00:00' AFTER `name`");
        $this->execute("ALTER TABLE `alerts` ADD `next_triggered_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `triggered_on`");
    }

    public function down()
    {
         $this->execute('DROP TABLE IF EXISTS `alerts`');
         $this->execute('RENAME TABLE `z_3920_alerts` TO `alerts`');
    }
}
