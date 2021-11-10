<?php
use Migrations\AbstractMigration;

class POCOR6369 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6369_meal_programmes` LIKE `meal_programmes`');
        $this->execute('INSERT INTO `zz_6369_meal_programmes` SELECT * FROM `meal_programmes`');

        $this->execute("ALTER TABLE `meal_programmes` ADD `area_id` INT(11) NOT NULL AFTER `name`");
        $this->execute("ALTER TABLE `meal_programmes` ADD `institution_id` INT(11) NOT NULL AFTER `area_id`");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `meal_programmes`');
        $this->execute('RENAME TABLE `zz_6369_meal_programmes` TO `meal_programmes`');
    }
}
