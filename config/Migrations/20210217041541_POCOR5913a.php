<?php
use Migrations\AbstractMigration;

class POCOR5913a extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_5913a_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5913a_import_mapping` SELECT * FROM `import_mapping`');

        // End
        $this->execute("UPDATE `import_mapping` SET `description` = 'OpenEMIS ID (Student)' WHERE `model` = 'Student.StudentGuardians' AND `column_name` = 'student_id'");

        $this->execute("UPDATE `import_mapping` SET `description` = 'Relation Code (Guardian)' WHERE `model` = 'Student.StudentGuardians' AND `column_name` = 'guardian_relation_id'");

        $this->execute("UPDATE `import_mapping` SET `description` = 'OpenEMIS ID (Guardian)', `lookup_column` = 'openemis_no' WHERE `model` = 'Student.StudentGuardians' AND `column_name` = 'guardian_id' AND `order` = '3'");

        $rows = [
            [
                'model' => 'Student.StudentGuardians',
                'column_name' => 'guardian_id',
                'description' => 'Identity Number (Guardian)',
                'order' => 4,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Security',
                'lookup_model' => 'Users',
                'lookup_column' => 'number',
            ]
        ];
        $this->table('import_mapping')->insert($rows)->save();
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5913a_import_mapping` TO `import_mapping`');
    }
}
