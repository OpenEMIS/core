<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8201 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE `z_8201_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_8201_import_mapping` SELECT * FROM `import_mapping`');

        $row = $this->fetchRow("SELECT `id` FROM `import_mapping` order by id desc limit 1");
        $idOne = $row[0]+1;
        $idTwo = $row[0]+2;
        $idThree = $row[0]+3;
        $this->execute("INSERT into `import_mapping` (id, `model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values($idOne, 'Examination.ExaminationStudentSubjectResults','','','3','0','3','User','Users','openemis_no')");
        $this->execute("INSERT into `import_mapping` (id, `model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values($idTwo, 'Examination.ExaminationStudentSubjectResults','','Marks (Leave as blank for Grades type)','2','0','2','Examination','ExaminationGradingOptions','max')");
        $this->execute("INSERT into `import_mapping` (id, `model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values($idThree, 'Examination.ExaminationStudentSubjectResults','','','1','0','1','Examination','Examinations','id')");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_8201_import_mapping` TO `import_mapping`');
    }
}
