<?php
use Migrations\AbstractMigration;

class POCOR5175a extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5175a_import_mapping` LIKE `import_mapping`');
		$this->execute('INSERT INTO `z_5175a_import_mapping` SELECT * FROM `import_mapping`');
		
		$this->execute('UPDATE `import_mapping` SET `foreign_key` = 2, `lookup_plugin` = "AcademicPeriod", `lookup_model` = "AcademicPeriods", `lookup_column` = "code" WHERE `model` = "Student.Extracurriculars" and `column_name`="academic_period_id"');
		$this->execute('UPDATE `import_mapping` SET `foreign_key` = 1, `lookup_plugin` = "FieldOption", `lookup_model` = "ExtracurricularTypes", `lookup_column` = "code" WHERE `model` = "Student.Extracurriculars" and `column_name`="extracurricular_type_id"');
		
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_5175a_import_mapping` TO `import_mapping`');
    }
}
