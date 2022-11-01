<?php

use Phinx\Migration\AbstractMigration;

class POCOR5029 extends AbstractMigration
{
    public function up()
    {
		$this->execute('CREATE TABLE `z_config_items` LIKE `config_items`');
		$this->execute('INSERT INTO `z_config_items` SELECT * FROM `config_items`');
		$this->execute("Delete from config_items where code = 'adaptation'");
    }

    public function down()
    {
		$this->dropTable("config_items");
		$this->table("z_config_items")->rename("config_items");
    }   
}
