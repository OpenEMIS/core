<?php

use Migrations\AbstractMigration;

class POCOR9460 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $this->execute('CREATE TABLE `z_9460_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_9460_config_items` SELECT * FROM `config_items`');

        $sql = "UPDATE `config_items` 
                SET `value` = '/^\\+?[0-9]{7,15}$/'
                WHERE `code` = 'validate_contact_person_mobile_number'";

        $this->execute($sql);
          
    }

    public function down()
    {
            $this->execute('DROP TABLE IF EXISTS `config_items`');
            $this->execute('RENAME TABLE `z_9460_config_items` TO `config_items`');
    }
}
