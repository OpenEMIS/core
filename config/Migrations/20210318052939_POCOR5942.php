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
        $this->execute('ALTER TABLE `student_meal_marked_records` ADD `meal_benefit_id` INT(11) NULL DEFAULT NULL AFTER `date`');
        $this->execute('ALTER TABLE `institution_meal_programmes` ADD `institution_id` INT(11) NULL DEFAULT NULL AFTER `meal_programmes_id`');

         $data = [
            [
                'name' => '100%',
                'order' => '1',
                'visible' => '1',
                'default' => '1',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->insert('meal_benefits', $data);


    }

    // rollback
    public function down()
    {
        $this->execute('ALTER TABLE `meal_programme_types` DROP COLUMN `default`');
        $this->execute('ALTER TABLE `meal_target_types` DROP COLUMN `default`');
        $this->execute('ALTER TABLE `meal_nutritions` DROP COLUMN `default`');
        $this->execute('ALTER TABLE `meal_implementers` DROP COLUMN `default`');
        $this->execute('ALTER TABLE `meal_benefits` DROP COLUMN `default`');
        $this->execute('DROP TABLE IF EXISTS `meal_received`');

        $this->execute('RENAME TABLE `z_5942_meal_received` TO `meal_received`');  
    }
}