<?php

use Phinx\Migration\AbstractMigration;

class POCOR4891 extends AbstractMigration
{
    public function up()
    {
        // institution_schedule_terms
		$this->execute('CREATE TABLE `z_4891_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4891_import_mapping` SELECT * FROM `import_mapping`');
        $this->execute("Insert into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Staff.InstitutionStaffAttendances','openemis_no','Openemis ID','1','0','2','Security','Users','openemis_no')");
		$this->execute("Insert into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Staff.InstitutionStaffAttendances','academic_period_id','Code','2','0','2','AcademicPeriod','AcademicPeriods','code')");
		$this->execute("Insert into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Staff.InstitutionStaffAttendances','date','( DD/MM/YYYY )','3','0','0',NULL,NULL,NULL)");
		$this->execute("Insert into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Staff.InstitutionStaffAttendances','time_in','(HH:MM AM/PM)','4','0','0',NULL,NULL,NULL)");
		$this->execute("Insert into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Staff.InstitutionStaffAttendances','time_out','(HH:MM AM/PM)','5','0','0',NULL,NULL,NULL)");
		$this->execute("Insert into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Staff.InstitutionStaffAttendances','comment',NULL,'6','0','0',NULL,NULL,NULL)");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_4891_import_mapping` TO `import_mapping`');
        
    }
}
