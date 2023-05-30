<?php
use Migrations\AbstractMigration;

class POCOR7363 extends AbstractMigration
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
        $this->execute("INSERT INTO `field_options` ( `name`, `category`, `table_name`, `order`, `modified_by`, `modified`, `created_by`, `created`) VALUES
        ( 'Industries', 'Others', 'industries', 134, NULL, NULL, 1, '2023-05-30 12:00:00'),
        (136, 'Food Type', 'Meals', 'meal_benefits', 136, NULL, NULL, 1, '2023-05-30 12:00:00'),
      
        ");
}
