<?php

use Phinx\Migration\AbstractMigration;

class POCOR4891 extends AbstractMigration
{
    public function up()
    {
        //backup the table
        $this->execute('CREATE TABLE `z_4891_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4891_import_mapping` SELECT * FROM `import_mapping`');
        // end backup


        //insert for import excel column for contact options and contact
        $this->execute("
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Institution.StaffAbsences','date_from','( DD/MM/YYYY )','1','0','0',NULL,NULL,NULL)
        ");

        $this->execute("
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Institution.StaffAbsences','date_to','( DD/MM/YYYY )','2','0','0',NULL,NULL,NULL);
        ");
        $this->execute("
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Institution.StaffAbsences','comment','','3','0','0',NULL,NULL,NULL)
        ");
        $this->execute("
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Institution.StaffAbsences','staff_id','OpenEMIS ID','4','0','2','Security','Users','openemis_no')
        ");
        $this->execute("
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Institution.StaffAbsences','staff_leave_type_id','','5','0','2','Staff','StaffLeaveTypes','id')
        ");
        $this->execute("
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Institution.StaffAbsences','status_id','','6','0','2','Workflow','WorkflowSteps','id')
        ");
        $this->execute("
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Institution.StaffAbsences','academic_period_id','Code','7','0','2','AcademicPeriod','AcademicPeriods','code')
        ");


    }

    public function down()
    {
        // restore the backup table
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_4891_import_mapping` TO `import_mapping`');
        // end restore

    }
}
