<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Hash;

class POCOR4976 extends AbstractMigration
{
    public function up()
    {
    	// backup 
        $this->execute('CREATE TABLE `z_4976_special_need_types` LIKE `special_need_types`');
        $this->execute('INSERT INTO `z_4976_special_need_types` SELECT * FROM `special_need_types`');

        // alter
        $this->execute("ALTER TABLE `special_need_types` ADD `type` TINYINT NOT NULL DEFAULT 1 COMMENT '1- Special need type , 2- special need assessment type' AFTER `international_code`");
    }

    public function down()
    {
    }
}
