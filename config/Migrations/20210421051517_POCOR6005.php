<?php
use Migrations\AbstractMigration;

class POCOR6005 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6005_meal_programme_types` LIKE `meal_programme_types`');
        $this->execute('INSERT INTO `z_6005_meal_programme_types` SELECT * FROM `meal_programme_types`');

        $this->execute('ALTER TABLE `meal_implementers` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->execute('ALTER TABLE `meal_nutritions` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->execute('ALTER TABLE `meal_target_types` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->execute('ALTER TABLE `meal_programme_types` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `meal_programme_types`');
        $this->execute('RENAME TABLE `z_6005_meal_programme_types` TO `meal_programme_types`');
    }
}
