<?php
use Migrations\AbstractMigration;

class POCOR6913 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6913_institution_staff_position_profiles` LIKE `institution_staff_position_profiles`');
        $this->execute('INSERT INTO `zz_6913_institution_staff_position_profiles` SELECT * FROM `institution_staff_position_profiles`');
        // End

        $this->execute('ALTER TABLE `institution_staff_position_profiles` CHANGE `end_date` `end_date` DATE NOT NULL');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff_position_profiles`');
        $this->execute('RENAME TABLE `zz_6913_institution_staff_position_profiles` TO `institution_staff_position_profiles`');
    }
}
