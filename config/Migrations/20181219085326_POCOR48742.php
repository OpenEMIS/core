<?php

use Phinx\Migration\AbstractMigration;

class POCOR48742 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4874_2_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4874_2_import_mapping` SELECT * FROM `import_mapping`');

        $this->execute('UPDATE `import_mapping` SET `description` = "Student OpenEMIS ID" WHERE `model` = "Student.StudentGuardians" AND `column_name` = "student_id"');

        $this->execute('UPDATE `import_mapping` SET `description` = "Code" WHERE `model` = "Student.StudentGuardians" AND `column_name` = "guardian_relation_id"');

        $this->execute('UPDATE `import_mapping` SET `description` = "Guardian OpenEMIS ID" WHERE `model` = "Student.StudentGuardians" AND `column_name` = "guardian_id"');
    }

    public function down()
    {
        $this->execute('DROP TABLE import_mapping');
        $this->table('z_4874_2_import_mapping')->rename('import_mapping');
    }
}
