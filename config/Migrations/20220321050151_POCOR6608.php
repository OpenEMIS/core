<?php
use Migrations\AbstractMigration;

class POCOR6608 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_6608_meal_institution_programmes` LIKE `meal_institution_programmes`');
        $this->execute('INSERT INTO `z_6608_meal_institution_programmes` SELECT * FROM `meal_institution_programmes`');

        $this->execute('ALTER TABLE `meal_institution_programmes` ADD COLUMN `area_id` int(11) AFTER `institution_id`');
    }

    // rollback
    public function down()
    {
       // meal_programmes
       $this->execute('DROP TABLE IF EXISTS `meal_institution_programmes`');
       $this->execute('RENAME TABLE `z_6608_meal_institution_programmes` TO `meal_institution_programmes`');
    }
}
