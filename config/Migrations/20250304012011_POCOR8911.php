<?php

use Migrations\AbstractMigration;

class POCOR8911 extends AbstractMigration
{

    public function up()
    {
        // Create a backup table
        $this->execute('CREATE TABLE `zz_8911_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `zz_8911_security_users` SELECT * FROM `security_users`');

        // Alter email and phone columns to be unique
        $this->execute('ALTER TABLE `security_users` ADD UNIQUE (`email`)');
        $this->execute('ALTER TABLE `security_users` ADD UNIQUE (`mobile_number`)');
    }

    public function down()
    {
        // Drop modified table and restore from backup
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `zz_8911_security_users` TO `security_users`');
    }
}