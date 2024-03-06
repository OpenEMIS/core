<?php
use Migrations\AbstractMigration;

class POCOR7970 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7970_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `zz_7970_security_users` SELECT * FROM `security_users`');


        // ALTER TABLE
        $this->execute("ALTER TABLE `security_users` ADD `failed_logins` INT NOT NULL DEFAULT '0' AFTER `last_login`");
    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `zz_7970_security_users` TO `security_users`');

    }
}
