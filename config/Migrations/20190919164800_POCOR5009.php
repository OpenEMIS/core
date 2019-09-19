<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Hash;

class POCOR5009 extends AbstractMigration
{
    public function up()
    {
    	// backup 
        $this->execute('CREATE TABLE `z_5009_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_5009_security_users` SELECT * FROM `security_users`');

        // alter
	$this->execute("ALTER TABLE `security_users` DROP INDEX `openemis_no`,   ADD UNIQUE INDEX `openemis_no_UNIQUE` (`openemis_no`) ;");
        $this->execute("ALTER TABLE `security_users` DROP INDEX `username`, ADD UNIQUE INDEX `username_UNIQUE` (`username`);");
    }

    public function down()
    {
	$this->execute('DROP TABLE security_users');
        $this->table('z_5009_security_users')->rename('security_users');
    }
}
