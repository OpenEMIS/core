<?php
use Migrations\AbstractMigration;

class POCOR5939Update extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5939Update_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_5939Update_config_items` SELECT * FROM `config_items`');
        $this->execute("UPDATE `config_items` SET `type` = 'Institution Completeness'   WHERE `type` = 'Institution Profile'");
        $this->execute("UPDATE `config_items` SET `type` = 'User Completeness'   WHERE `type` = 'User Profile'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_5939Update_config_items` TO `config_items`');
    }
}
