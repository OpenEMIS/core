<?php
use Migrations\AbstractMigration;

class POCOR5754 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5754_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5754_import_mapping` SELECT * FROM `import_mapping`');

        //import_mapping
        $data = [
            [
                'model' => 'Institution.StudentAbsencesPeriodDetails',
                'column_name' => 'student_attendance_types',
                'description' => 'Code',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Attendance',
                'lookup_model' => 'StudentAttendanceTypes',
                'lookup_column' => 'Code'
            ],
            [
                'model' => 'Institution.StudentAbsencesPeriodDetails',
                'column_name' => 'subject_id',
                'description' => 'Name',
                'order' => 4,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => NULL,
                'lookup_model' => 'subject',
                'lookup_column' => 'id'
            ]
        ];

        $this->insert('import_mapping', $data);  

        $this->execute("UPDATE `import_mapping` SET `order` = 3 WHERE `model`='Institution.StudentAbsencesPeriodDetails' AND `column_name` = 'period' ");
        $this->execute("UPDATE `import_mapping` SET `order` = 6 WHERE `model`='Institution.StudentAbsencesPeriodDetails' AND `column_name` = 'absence_type_id' ");
        $this->execute("UPDATE `import_mapping` SET `order` = 7 WHERE `model`='Institution.StudentAbsencesPeriodDetails' AND `column_name` = 'student_absence_reason_id' ");
        $this->execute("UPDATE `import_mapping` SET `order` = 5 WHERE `model`='Institution.StudentAbsencesPeriodDetails' AND `column_name` = 'student_id' ");
        $this->execute("UPDATE `import_mapping` SET `order` = 8 WHERE `model`='Institution.StudentAbsencesPeriodDetails' AND `column_name` = 'comment' ");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5754_import_mapping` TO `import_mapping`');
    }
}

