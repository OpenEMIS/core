<?php
use Migrations\AbstractMigration;

class POCOR7652 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_7652_meal_institution_programmes` LIKE `meal_institution_programmes`');
        $this->execute('INSERT INTO `z_7652_meal_institution_programmes` SELECT * FROM `meal_institution_programmes`');
        $this->execute("ALTER TABLE tst_core_dmo.meal_institution_programmes DROP FOREIGN KEY meal_insti_progr_fk_are_id");
    }
    public function down()
    { 
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `meal_institution_programmes`');
        $this->execute('RENAME TABLE `z_7652_meal_institution_programmes` TO `meal_institution_programmes`');
    }
}
