<?php
use Migrations\AbstractMigration;

class POCOR6241 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6241_institution_staff_transfers` LIKE `institution_staff_transfers`');
        $this->execute('INSERT INTO `zz_6241_institution_staff_transfers` SELECT * FROM `institution_staff_transfers`');

        $this->execute("ALTER TABLE `institution_staff_transfers` CHANGE `previous_end_date` `previous_end_date` DATE NULL DEFAULT NULL");
    }

     //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff_transfers`');
        $this->execute('RENAME TABLE `zz_6241_institution_staff_transfers` TO `institution_staff_transfers`');
    }
}
