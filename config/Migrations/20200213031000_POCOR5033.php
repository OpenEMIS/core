<?php

use Phinx\Migration\AbstractMigration;

class POCOR5033 extends AbstractMigration
{
    // commit
    public function up()
    {
        $this->execute('CREATE TABLE `z_5033_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_5033_import_mapping` SELECT * FROM `import_mapping`');

        $this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Competency.CompetencyTemplates','code','','1','0','0',NULL,NULL,NULL)");
		$this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Competency.CompetencyTemplates','name','','2','0','0',NULL,NULL,NULL)");
		$this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Competency.CompetencyTemplates','description','','3','0','0',NULL,NULL,NULL)");
		$this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Competency.CompetencyTemplates','academic_period_id','Code','4','0','2','AcademicPeriod','AcademicPeriods','code')");
		$this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Competency.CompetencyTemplates','education_programme_id','Code','5','0','2','Education','EducationProgrammes','code')");
		$this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Competency.CompetencyTemplates','education_grade_id','Code','6','0','2','Education','EducationGrades','code')");		
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_5033_import_mapping` TO `import_mapping`');
    }
}
