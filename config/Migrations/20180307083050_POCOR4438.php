<?php

use Phinx\Migration\AbstractMigration;

class POCOR4438 extends AbstractMigration
{
    public function up()
    {
        $institutionAppraisalsSql = "UPDATE security_functions
            SET `_view` = 'StaffAppraisals.index|StaffAppraisals.view|StaffAppraisals.download',
            `_edit` = 'StaffAppraisals.edit',
            `_add` = 'StaffAppraisals.add',
            `_delete` = 'StaffAppraisals.remove',
            `_execute` = null
            WHERE `id` = 3037";
        $this->execute($institutionAppraisalsSql);
    }

    public function down()
    {
        $institutionAppraisalsSql = "UPDATE security_functions
            SET `_view` = 'InstitutionStaffAppraisals.index|InstitutionStaffAppraisals.view|InstitutionStaffAppraisals.download',
            `_edit` = 'InstitutionStaffAppraisals.edit',
            `_add` = 'InstitutionStaffAppraisals.add',
            `_delete` = 'InstitutionStaffAppraisals.remove',
            `_execute` = null
            WHERE `id` = 3037";
        $this->execute($institutionAppraisalsSql);
    }
}
