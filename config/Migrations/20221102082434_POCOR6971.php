<?php
use Migrations\AbstractMigration;

class POCOR6971 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6971_institution_staff_attendances` LIKE `institution_staff_attendances`');
        $this->execute('INSERT INTO `zz_6971_institution_staff_attendances` SELECT * FROM `institution_staff_attendances`');
        // End

        $this->execute("ALTER TABLE `institution_staff_attendances` ADD `shift_id` INT NOT NULL DEFAULT 1 AFTER `academic_period_id`");

        $this->execute("ALTER TABLE institution_staff_attendances DROP PRIMARY KEY");

        $this->execute("ALTER TABLE institution_staff_attendances ADD PRIMARY KEY(staff_id,institution_id,academic_period_id,shift_id,`date`)");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff_attendances`');
        $this->execute('RENAME TABLE `zz_6971_institution_staff_attendances` TO `institution_staff_attendances`');
    }
}
