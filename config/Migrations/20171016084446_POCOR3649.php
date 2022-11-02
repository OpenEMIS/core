<?php

use Phinx\Migration\AbstractMigration;

class POCOR3649 extends AbstractMigration
{
     // commit
    public function up()
    {
        // backup the table
        $this->execute('RENAME TABLE `institution_student_admission` TO `z_3649_institution_student_admission`');

        // institution_student_admission
        $this->execute('DROP TABLE IF EXISTS `institution_student_admission`');
        $table = $this->table('institution_student_admission', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains student admission records'
            ]);
        $table
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('requested_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('status', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false,
                'comment' => '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('new_education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('institution_class_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('previous_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('student_transfer_reason_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_transfer_reasons.id'
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('type', 'integer', [
                'default' => 2,
                'limit' => 1,
                'null' => false,
                'comment' => '1 -> Admission, 2 -> Transfer'
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
            ->addIndex('education_grade_id')
            ->addIndex('new_education_grade_id')
            ->addIndex('institution_class_id')
            ->addIndex('previous_institution_id')
            ->addIndex('student_transfer_reason_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
            // end of institution_student_admission

            // insert data to institution_student_admission, Requested_date same as created_date
            $this->execute('
                INSERT INTO `institution_student_admission` (`id`, `start_date`, `end_date`, `requested_date`, `student_id`, `status`, `institution_id`, `academic_period_id`, `education_grade_id`, `new_education_grade_id`, `institution_class_id`, `previous_institution_id`, `student_transfer_reason_id`, `comment`, `type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
                SELECT `id`, `start_date`, `end_date`, date(`created`), `student_id`, `status`, `institution_id`, `academic_period_id`, `education_grade_id`, `new_education_grade_id`, `institution_class_id`, `previous_institution_id`, `student_transfer_reason_id`, `comment`, `type`, `modified_user_id`, `modified`, `created_user_id`, `created`
                FROM `z_3649_institution_student_admission`
            ');
            // end insert data to institution_student_admission
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_student_admission`');
        $this->execute('RENAME TABLE `z_3649_institution_student_admission` TO `institution_student_admission`');
    }
}
