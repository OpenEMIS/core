<?php
use Migrations\AbstractMigration;

class POCOR6434 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6434_meal_programmes` LIKE `meal_programmes`');
        $this->execute('INSERT INTO `z_6434_meal_programmes` SELECT * FROM `meal_programmes`');

        $this->execute('ALTER TABLE `meal_programmes` DROP COLUMN `area_id`');
        $this->execute('ALTER TABLE `meal_programmes` DROP COLUMN `institution_id`');
        // Create tables
        $this->execute("CREATE TABLE IF NOT EXISTS `meal_institution_programmes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `meal_programme_id` int(11),
            `institution_id` int(11) ,
            `created_user_id` int(11) NOT NULL,
            `created` datetime  DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          )");
    }

    // rollback
    public function down()
    {
       // meal_programmes
       $this->execute('DROP TABLE IF EXISTS `meal_programmes`');
        $this->execute('RENAME TABLE `z_6434_meal_programmes` TO `meal_programmes`');
    }
}
