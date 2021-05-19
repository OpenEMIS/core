<?php
use Migrations\AbstractMigration;

class POCOR6039 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6039_staff_payslips` LIKE `staff_payslips`');
        $this->execute('INSERT INTO `zz_6039_staff_payslips` SELECT * FROM `staff_payslips`');

        $this->execute('ALTER TABLE `staff_payslips` ADD COLUMN `identity_number` varchar(50) COLLATE utf8_general_ci NULL AFTER `staff_id`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_payslips`');
        $this->execute('RENAME TABLE `zz_6039_staff_payslips` TO `staff_payslips`');
    }
}
