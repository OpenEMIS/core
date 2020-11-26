<?php

use Migrations\AbstractMigration;

class POCOR5336 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5336_api_securities` LIKE `api_securities`');
		$this->execute('INSERT INTO `z_5336_api_securities` SELECT * FROM `api_securities`');	


        //insert api security for InstitutionClassSubjects and StudentAttendanceTypes

        $stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];
        
        $apiSecuritiesData = [
            [
                'id' => $uniqueId + 1,
                'name' => 'Subjects',
                'model' => 'Institution.InstitutionClassSubjects',
                'index' => 1,
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 0,
                'execute' => 0 
            ]
        ];

        $apiSecuritiesTable = $this->table('api_securities');
        $apiSecuritiesTable->insert($apiSecuritiesData);
        $apiSecuritiesTable->saveData();

        $apiSecuritiesData = [
            [
                'id' => $uniqueId + 2,
                'name' => 'StudentAttendanceTypes',
                'model' => 'Attendance.StudentAttendanceTypes',
                'index' => 1,
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 0,
                'execute' => 0 
            ]
        ];

        $apiSecuritiesTable = $this->table('api_securities');
        $apiSecuritiesTable->insert($apiSecuritiesData);
        $apiSecuritiesTable->saveData();

        // Add subject column to student_attendance_marked_records and institution_student_absence_details table
        
        $this->execute('ALTER TABLE `student_attendance_marked_records` ADD COLUMN `subject_id` int(11) NOT NULL DEFAULT 0 AFTER `period`');
        $this->execute("ALTER TABLE `student_attendance_marked_records` DROP PRIMARY KEY, ADD primary key (`institution_id`,`academic_period_id`,`institution_class_id`,`date`,`period`,`subject_id`)");
        $this->execute('ALTER TABLE `institution_student_absence_details` ADD COLUMN `subject_id` int(11) NOT NULL DEFAULT 0 AFTER `student_absence_reason_id`');
        $this->execute("ALTER TABLE `institution_student_absence_details` DROP PRIMARY KEY, ADD primary key (`student_id`,`institution_id`,`academic_period_id`,`institution_class_id`,`date`,`period`,`subject_id`)");  
        $this->execute('ALTER TABLE `student_attendance_per_day_periods` ADD COLUMN `period` int(11) AFTER `academic_period_id`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_5336_api_securities` TO `api_securities`');
        $this->execute('ALTER TABLE `student_attendance_marked_records` DROP COLUMN `subject_id`');
        $this->execute('ALTER TABLE `institution_student_absence_details` DROP COLUMN `subject_id`');
        $this->execute('ALTER TABLE `student_attendance_per_day_periods` DROP COLUMN `period`');
    }
}
