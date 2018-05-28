<?php

use Phinx\Migration\AbstractMigration;

class POCOR4607 extends AbstractMigration
{
    public function up()
    {
        // institution_student_absences
        $this->execute('CREATE TABLE `z_4607_institution_student_absences` LIKE `institution_student_absences`');
        $this->execute('INSERT INTO `z_4607_institution_student_absences` SELECT * FROM `institution_student_absences`');
        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');

        $StudentAbsences = $this->table('institution_student_absences', [
            'collation' => 'utf8mb4_unicode_ci',
            'comments' => 'This table contains absence records of students',
        ]);

        $StudentAbsences
            ->addColumn('start_date', 'date', [
                'null' => false,
                'default' => null
            ])
            ->addColumn('end_date', 'date', [
                'null' => false,
                'default' => null
            ])
            ->addColumn('full_day', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => null
            ])
            ->addColumn('start_time', 'time', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('end_time', 'time', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('comment', 'text', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('student_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_class_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('absence_type_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to absence_types.id'
            ])
            ->addColumn('student_absence_reason_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_absence_reasons.id'
            ])
            ->addColumn('institution_student_absence_day_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_student_absence_days.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('institution_class_id')
            ->addIndex('absence_type_id')
            ->addIndex('student_absence_reason_id')
            ->addIndex('institution_student_absence_day_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();


        $absenceData = $this->fetchAll('SELECT * FROM `z_4607_institution_student_absences`');
        $data = [];

        // $AcademicPeriods = $this->table('academic_periods');
        // $InstitutionClasses = $this->table('institution_classes');

        foreach ($absenceData as $record) {
            $startDate = $record['start_date'];
            $institutionId = $record['institution_id'];

            $academicData = $this->fetchRow('
                SELECT `id` FROM `academic_periods`
                WHERE `parent_id` <> 0
                AND `visible` = 1
                AND `start_date` <= ' . $start_date . '
                AND `end_date` >= ' . $startDate . '
            ');

            $academicId = $academicData['id'];
        }

        if (!empty($data)) {
            $this->insert('institution_student_absences', $data);
        }
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        $this->execute('RENAME TABLE `z_4607_institution_student_absences` TO `institution_student_absences`');
    }
}
