<?php
use Migrations\AbstractMigration;

class POCOR5357 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5357_custom_field_types` LIKE `custom_field_types`');
        $this->execute('INSERT INTO `z_5357_custom_field_types` SELECT * FROM `custom_field_types`');

        //rename module name profile to personal

        $this->execute("UPDATE custom_field_types SET is_mandatory = 1 WHERE code = 'CHECKBOX'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `custom_field_types`');
        $this->execute('RENAME TABLE `z_5357_custom_field_types` TO `custom_field_types`');
    }
}
