<?php
use Migrations\AbstractMigration;

class POCOR6758 extends AbstractMigration
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
        /** BACKUP OpenEMIS Core report_queries */
        $this->execute('DROP TABLE IF EXISTS `z_6758_meal_programmes`');
        $this->execute('CREATE TABLE `z_6758_meal_programmes` LIKE `meal_programmes`');

        /** UPDATE OpenEMIS Core report_queries */
        $this->execute('ALTER TABLE `meal_programmes` CHANGE `amount` `amount` DECIMAL(10,2) NOT NULL;');
        
    }

    //rollback
    public function down()
    {
        /** RESTORE OpenEMIS Core report_queries */
        $this->execute('DROP TABLE IF EXISTS `meal_programmes`');
        $this->execute('CREATE TABLE `meal_programmes` LIKE `z_6758_meal_programmes`');
        $this->execute('DROP TABLE IF EXISTS `z_6758_meal_programmes`');
    }
}

