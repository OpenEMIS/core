<?php
use Migrations\AbstractMigration;

class POCOR7594 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7594_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7594_config_items` SELECT * FROM `config_items`');
        $this->execute("UPDATE config_items SET type='Student Settings' WHERE code='multiple_institutions_student_enrollment'");
    }
    public function down()
    { 
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7594_config_items` TO `config_items`');
    }
}
