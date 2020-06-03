<?php
use Phinx\Migration\AbstractMigration;

class POCOR5316 extends AbstractMigration
{
	public function up()
	{
			// config_items
	        $this->execute('CREATE TABLE `z_5316_config_items` LIKE `config_items`');
	        $this->execute('INSERT INTO `z_5316_config_items` SELECT * FROM `config_items`');
			$this->execute('UPDATE `config_items` SET `value`= "support@openemis.org" WHERE `code` = "version_support_emails"');
	}

	public function down()
	{
			// config_items
	        $this->execute('DROP TABLE IF EXISTS `config_items`');
	        $this->execute('RENAME TABLE `z_5316_config_items` TO `config_items`');
	}
}