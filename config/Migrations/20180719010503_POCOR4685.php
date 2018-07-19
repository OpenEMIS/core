<?php

use Phinx\Migration\AbstractMigration;

class POCOR4685 extends AbstractMigration
{
    public function up()
    {
        $this->table('institution_class_students')->rename('z_4685_institution_class_students');
        $this->execute('CREATE TABLE `institution_class_students` LIKE `z_4685_institution_class_students`');
        $this->table('institution_class_students')
            ->addColumn('next_institution_class_id', 'integer', [
                'after' => 'academic_period_id',
                'null' => true
            ])
            ->save();
        $this->execute('
            INSERT INTO `institution_class_students` (`id`, `student_id`, `institution_class_id`, `education_grade_id`, `academic_period_id`, `next_institution_class_id`, `institution_id`, `student_status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            SELECT `id`, `student_id`, `institution_class_id`, `education_grade_id`, `academic_period_id`, NULL, `institution_id`, `student_status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
            FROM `z_4685_institution_class_students`
        ');
    }

    public function down()
    {
        $this->dropTable('institution_class_students');
        $this->table('z_4685_institution_class_students')->rename('institution_class_students');
    }
}
