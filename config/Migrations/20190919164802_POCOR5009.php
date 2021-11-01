<?php
use Migrations\AbstractMigration;

class POCOR5009 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6299_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_6299_security_users` SELECT * FROM `security_users`');
        
        $this->execute('CREATE TABLE IF NOT EXISTS `security_users_20200827` LIKE `security_users`');
        $this->execute('TRUNCATE TABLE `security_users_20200827`');
        // alter
        
        $this->execute('INSERT INTO `security_users_20200827` SELECT security_users.* FROM security_users WHERE username != openemis_no AND username IN (SELECT subq.username from (SELECT count(security_users.id) dups, security_users.id, security_users.username FROM `security_users` GROUP BY security_users.username) AS subq WHERE subq.dups > 1) ORDER BY username ASC');

        $this->execute('UPDATE security_users_20200827 SET username = openemis_no');

        $this->execute('UPDATE security_users INNER JOIN security_users_20200827 ON security_users_20200827.id = security_users.id SET security_users.username = security_users_20200827.username');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_users_20200827`');
        
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_6299_security_users` TO `security_users`');
    }
}
