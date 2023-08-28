<?php
use Migrations\AbstractMigration;

class POCOR7648 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `z_7648_field_options` LIKE `field_options`');
        $this->execute('INSERT INTO `z_7648_field_options` SELECT * FROM `field_options`');
        $this->execute("DELETE FROM `field_options` WHERE `field_options`.`table_name` = 'extracurricular_types'");
    }
    public function down()
    { 
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `field_options`');
        $this->execute('RENAME TABLE `z_7648_field_options` TO `field_options`');
    }
}
