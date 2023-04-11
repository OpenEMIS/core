<?php
use Migrations\AbstractMigration;

class POCOR7347 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    // commit
    public function up()
    {
        // security_users
        $this->execute('CREATE TABLE `z_7347_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_7347_security_users` SELECT * FROM `security_users`');

        // update `external_reference` column null for all `security_users`
        $this->execute("UPDATE `security_users` SET `external_reference` = 'NULL'");
    }

    // rollback
    public function down()
    {
        // security_users
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_7347_security_users` TO `security_users`');
    }
}
