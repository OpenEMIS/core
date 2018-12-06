<?php

use Phinx\Migration\AbstractMigration;

class POCOR4908 extends AbstractMigration
{
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_4908_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4908_import_mapping` SELECT * FROM `import_mapping`');
        // end backup

        //Delete
        $this->execute('DELETE FROM import_mapping WHERE `id` = 57');
        $this->execute('UPDATE `import_mapping` SET `column_name` = "date" WHERE `id` = 56');

        $excelRowData = [
            'id' => 57,
            'column_name' => 'period',
            'model' => 'Institution.InstitutionStudentAbsences',
            'description' => '',
            'order' => 2,
            'is_optional' => 0,
            'foreign_key' => 3,
            'lookup_plugin' => NULL,
            'lookup_model' => 'StudentAttendanceMarkTypes',
            'lookup_column' => 'attendance_per_day'
        ];

        $this->insert('import_mapping', $excelRowData);
    }

    public function down()
    {
        //Restore backups
        $this->execute('DROP TABLE import_mapping');
        $this->table('z_4908_import_mapping')->rename('import_mapping');
    }
}
