<?php

use Phinx\Migration\AbstractMigration;

class POCOR4440 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4440_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `z_4440_institutions` SELECT * FROM `institutions`');

        $this->execute('UPDATE `institutions` SET `area_administrative_id` = null 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `area_administratives` 
                        WHERE `area_administratives`.`id` = `institutions`.`area_administrative_id`
                     )');

        $this->execute('CREATE TABLE `z_4440_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_4440_security_users` SELECT * FROM `security_users`');

        $this->execute('UPDATE `security_users` SET `address_area_id` = null 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `area_administratives` 
                        WHERE `area_administratives`.`id` = `security_users`.`address_area_id`
                    )');

        $this->execute('UPDATE `security_users` SET `birthplace_area_id` = null 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `area_administratives` 
                        WHERE `area_administratives`.`id` = `security_users`.`birthplace_area_id`
                    )');
        
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institutions`');
        $this->execute('RENAME TABLE `z_4440_institutions` TO `institutions`');
    
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_4440_security_users` TO `security_users`');
    }
}
