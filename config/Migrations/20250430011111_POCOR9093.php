<?php

use Migrations\AbstractMigration;

class POCOR9093 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        // Comment: Backup table is not created because no data was changed, removed, or added during migration.
        // Add indexes to the summary_student_attendances table to optimize query performance
        $this->table('summary_student_attendances')
            ->addIndex(['institution_id'], ['name' => 'idx_institution_id'])
            ->addIndex(['academic_period_id'], ['name' => 'idx_academic_period_id'])
            ->addIndex(['education_grade_id'], ['name' => 'idx_education_grade_id'])
            ->addIndex(['attendance_date'], ['name' => 'idx_attendance_date'])
            ->addIndex(['class_id'], ['name' => 'idx_class_id'])
            ->addIndex(['period_id'], ['name' => 'idx_period_id'])
            ->addIndex(['subject_id'], ['name' => 'idx_subject_id'])
            ->update();

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Remove indexes from the summary_student_attendances table
        $this->table('summary_student_attendances')
            ->removeIndex(['institution_id'])
            ->removeIndex(['academic_period_id'])
            ->removeIndex(['education_grade_id'])
            ->removeIndex(['attendance_date'])
            ->removeIndex(['class_id'])
            ->removeIndex(['period_id'])
            ->removeIndex(['subject_id'])
            ->update();

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
