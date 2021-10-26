<?php
use Migrations\AbstractMigration;

class POCOR6370 extends AbstractMigration
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
        /** START: meal_received table changes */
        $this->execute('CREATE TABLE `zz_6370_meal_received` LIKE `meal_received`');
        $this->execute('INSERT INTO `zz_6370_meal_received` SELECT * FROM `meal_received`');

        $this->execute("UPDATE `meal_received` SET `name` = 'Yes' WHERE `meal_received`.`code` = 'Received';");
        $this->execute("UPDATE `meal_received` SET `name` = 'No' WHERE `meal_received`.`code` = 'NotReceived';");
        /** END: meal_received table changes */
    }

    //rollback
    public function down()
    {
        /** START: meal_received table changes */
        $this->execute('DROP TABLE IF EXISTS `meal_received`');
        $this->execute('RENAME TABLE `zz_6370_meal_received` TO `meal_received`');
        /** END: meal_received table changes */
    }
}
