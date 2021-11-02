<?php
use Migrations\AbstractMigration;

class POCOR6056 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6056_institution_staff_transfers` LIKE `institution_staff_transfers`');
        $this->execute('INSERT INTO `zz_6056_institution_staff_transfers` SELECT * FROM `institution_staff_transfers`');

        $this->execute('UPDATE institution_staff_transfers SET previous_end_date =  DATE(institution_staff_transfers.created) WHERE previous_end_date is NULL');
        $this->execute('ALTER TABLE `institution_staff_transfers` CHANGE `previous_end_date` `previous_end_date` DATE NOT NULL');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff_transfers`');
        $this->execute('RENAME TABLE `zz_6056_institution_staff_transfers` TO `institution_staff_transfers`');
    }
}

