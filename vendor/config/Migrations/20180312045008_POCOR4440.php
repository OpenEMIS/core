<?php

use Phinx\Migration\AbstractMigration;

class POCOR4440 extends AbstractMigration
{
    public function up()
    {   
        $TrainingSessions = $this->table('training_sessions');
        $TrainingSessions->addIndex('area_id')
                         ->save();
        
        $this->execute('CREATE TABLE `z_4440_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `z_4440_institutions` SELECT * FROM `institutions`
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `area_administratives` 
                        WHERE `area_administratives`.`id` = `institutions`.`area_administrative_id`
                     ) OR
                        NOT EXISTS(
                            SELECT 1 FROM `areas` 
                            WHERE `areas`.`id` = `institutions`.`area_id`
                         )');

        $this->execute('UPDATE `institutions` SET `area_administrative_id` = null 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `area_administratives` 
                        WHERE `area_administratives`.`id` = `institutions`.`area_administrative_id`
                     )');

        $this->execute('UPDATE `institutions` SET `area_id` = 0 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `areas` 
                        WHERE `areas`.`id` = `institutions`.`area_id`
                     )');


        $this->execute('CREATE TABLE `z_4440_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_4440_security_users` SELECT * FROM `security_users`
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `area_administratives` 
                        WHERE `area_administratives`.`id` = `security_users`.`address_area_id`
                     ) OR
                    NOT EXISTS(
                        SELECT 1 FROM `area_administratives` 
                        WHERE `area_administratives`.`id` = `security_users`.`birthplace_area_id`
                     )');

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


        $this->execute('CREATE TABLE `z_4440_examination_centres` LIKE `examination_centres`');
        $this->execute('INSERT INTO `z_4440_examination_centres` SELECT * FROM `examination_centres` 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `areas` 
                        WHERE `areas`.`id` = `examination_centres`.`area_id`
                    )');
        $this->execute('UPDATE `examination_centres` SET `area_id` = 0 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `areas` 
                        WHERE `areas`.`id` = `examination_centres`.`area_id`
                     )');


        $this->execute('CREATE TABLE `z_4440_training_sessions` LIKE `training_sessions`');
        $this->execute('INSERT INTO `z_4440_training_sessions` SELECT * FROM `training_sessions` 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `areas` 
                        WHERE `areas`.`id` = `training_sessions`.`area_id`
                    )');
        $this->execute('UPDATE `training_sessions` SET `area_id` = 0 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `areas` 
                        WHERE `areas`.`id` = `training_sessions`.`area_id`
                     )');


        $this->execute('CREATE TABLE `z_4440_security_group_areas` LIKE `security_group_areas`');
        $this->execute('INSERT INTO `z_4440_security_group_areas` SELECT * FROM `security_group_areas` 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `areas` 
                        WHERE `areas`.`id` = `security_group_areas`.`area_id`
                    )');
        $this->execute('DELETE FROM `security_group_areas` WHERE NOT EXISTS(
                                SELECT 1 FROM `areas` 
                                WHERE `areas`.`id` = `security_group_areas`.`area_id`
                            )');

    }

    public function down()
    {
        $TrainingSessions = $this->table('training_sessions');
        $TrainingSessions->removeIndex('area_id');
            
        $this->execute('UPDATE `institutions` 
                    INNER JOIN `z_4440_institutions` ON institutions.id = z_4440_institutions.id
                    SET institutions.area_id = z_4440_institutions.area_id , institutions.area_administrative_id = z_4440_institutions.area_administrative_id');
        
        $this->execute('DROP TABLE IF EXISTS `z_4440_institutions`');

        $this->execute('UPDATE `security_users` 
                    INNER JOIN `z_4440_security_users` ON security_users.id = z_4440_security_users.id
                    SET security_users.address_area_id = z_4440_security_users.address_area_id , security_users.birthplace_area_id = z_4440_security_users.birthplace_area_id');
        
        $this->execute('DROP TABLE IF EXISTS `z_4440_security_users`');

        $this->execute('UPDATE `examination_centres` 
                    INNER JOIN `z_4440_examination_centres` ON examination_centres.id = z_4440_examination_centres.id
                    SET examination_centres.area_id = z_4440_examination_centres.area_id');
        
        $this->execute('DROP TABLE IF EXISTS `z_4440_examination_centres`');

        $this->execute('UPDATE `training_sessions` 
                    INNER JOIN `z_4440_training_sessions` ON training_sessions.id = z_4440_training_sessions.id
                    SET training_sessions.area_id = z_4440_training_sessions.area_id');
        
        $this->execute('DROP TABLE IF EXISTS `z_4440_training_sessions`');

        $this->execute('INSERT INTO `security_group_areas` SELECT * FROM `z_4440_security_group_areas`');
        $this->execute('DROP TABLE IF EXISTS `z_4440_security_group_areas`');

    }
}
