<?php
use Cake\I18n\Date;
use Phinx\Migration\AbstractMigration;

class POCOR4942 extends AbstractMigration
{
    public function up()
    {
		
		// backup 
		
        $this->execute('CREATE TABLE `z_4942_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4942_import_mapping` SELECT * FROM `import_mapping`');
		
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','openemis_no',NULL,'1','1','0',NULL,NULL,NULL)");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','qualification_title_id','','2','1','2','FieldOption','QualificationTitles','id')");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','education_field_of_study_id','','3','1','2','Education','EducationFieldOfStudies','id')");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','qualification_specialisation_id',NULL,'4','1','2','FieldOption','QualificationSpecialisations','id')");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','education_subject_id',NULL,'5','1','2','Education','EducationSubjects','id')");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','qualification_country_id','','6','1','2','FieldOption','Countries','id')");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','qualification_institution','','7','1','0',NULL,NULL,NULL)");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','document_no','','8','1','0',NULL,NULL,NULL)");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','graduate_year','(YYYY)','9','1','0',NULL,NULL,NULL)");
		$this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES('Staff.Qualifications','gpa','','10','1','0',NULL,NULL,NULL)");

    }

    public function down()
    {
        $this->dropTable('import_mapping');
        $this->execute('RENAME TABLE `z_4942_import_mapping` TO `import_mapping`');
    }

}
