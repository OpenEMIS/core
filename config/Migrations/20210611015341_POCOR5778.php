<?php
use Migrations\AbstractMigration;

class POCOR5778 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_5778_import_mapping`');
        $this->execute('CREATE TABLE `zz_5778_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5778_import_mapping` SELECT * FROM `import_mapping`');

          $this->execute("UPDATE `import_mapping` SET `foreign_key` = 2 WHERE `model`='Institution.StudentAbsencesPeriodDetails' AND `column_name` = 'subject_id' ");
          $this->execute("UPDATE `import_mapping` SET `lookup_model` = 'InstitutionSubjects' WHERE `model`='Institution.StudentAbsencesPeriodDetails' AND `column_name` = 'subject_id' ");
    }
     // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5778_import_mapping` TO `import_mapping`');
    }
}
