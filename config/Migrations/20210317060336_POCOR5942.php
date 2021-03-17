<?php
use Migrations\AbstractMigration;

class POCOR5942 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5942_meal_received` LIKE `meal_received`');
        $this->execute('INSERT INTO `z_5942_meal_received` SELECT * FROM `meal_received`');

        $this->execute('UPDATE `meal_received` SET `code` = "Received", `name` = "Received"  WHERE `id` = 1' );
        $this->execute('UPDATE `meal_received` SET `code` = "NotReceived", `name` = "Not Received"  WHERE `id` = 2' );
        $this->execute('UPDATE `meal_received` SET `code` = "None", `name` = "None"  WHERE `id` = 3' );


        $this->execute('ALTER TABLE `meal_programme_types` ADD `default` INT(1) NULL DEFAULT 0 AFTER `visible`');
        $this->execute('ALTER TABLE `meal_target_types` ADD `default` INT(1) NULL DEFAULT 0 AFTER `visible`');
        $this->execute('ALTER TABLE `meal_nutritions` ADD `default` INT(1) NULL DEFAULT 0 AFTER `visible`');
        $this->execute('ALTER TABLE `meal_implementers` ADD `default` INT(1) NULL DEFAULT 0 AFTER `visible`');
        $this->execute('ALTER TABLE `meal_benefits` ADD `default` INT(1) NULL DEFAULT 0 AFTER `visible`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `meal_received`');
        $this->execute('RENAME TABLE `z_5942_meal_received` TO `meal_received`');  
    }
}