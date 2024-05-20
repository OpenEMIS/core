<?php
use Migrations\AbstractMigration;

class POCOR7528 extends AbstractMigration
{
    public function up()
    {
        $this->execute('DROP TABLE IF EXISTS `zz_7528_staff_licenses`');
        $this->execute('CREATE TABLE `zz_7528_staff_licenses` LIKE `staff_licenses`');
        $staffLicences= $this->table('staff_licenses');
        $staffLicences->renameColumn('staff_id', 'security_user_id');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_licenses`');
        $this->execute('RENAME TABLE `zz_7528_staff_licenses` TO `staff_licenses`');
    }
}
