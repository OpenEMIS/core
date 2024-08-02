<?php
use Migrations\AbstractMigration;

class POCOR8116 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_8116_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8116_config_items` SELECT * FROM `config_items`');

        $this->execute("DELETE FROM config_items WHERE `type` = 'LDAP Configuration'");
        $this->execute("DELETE FROM config_items WHERE `type` = 'Where\'s My School Config'");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8116_config_items` TO `config_items`');

    }
}
