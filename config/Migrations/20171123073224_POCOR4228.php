<?php

use Phinx\Migration\AbstractMigration;

class POCOR4228 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('institution_subjects');
        $table
        ->addColumn('total_male_students', 'integer', [
            'default' => 0,
            'limit' => 5,
            'null' => false,
            'after' => 'no_of_seats'
        ])
        ->addColumn('total_female_students', 'integer', [
            'default' => 0,
            'limit' => 5,
            'null' => false,
            'after' => 'total_male_students'
        ])
        ->addIndex('total_male_students')
        ->addIndex('total_female_students')
        ->save();

        $update = '
            UPDATE institution_subjects
            SET total_male_students = (
                SELECT COUNT(1)
                FROM institution_subject_students
                INNER JOIN security_users ON security_users.id = institution_subject_students.student_id
                WHERE institution_subject_students.institution_subject_id = institution_subjects.id
                AND security_users.gender_id = 1
            ),
            total_female_students = (
                SELECT COUNT(1)
                FROM institution_subject_students
                INNER JOIN security_users ON security_users.id = institution_subject_students.student_id
                WHERE institution_subject_students.institution_subject_id = institution_subjects.id
                AND security_users.gender_id = 2
            )';

        $this->execute($update);

        $update = '
            UPDATE institution_classes
            SET total_male_students = (
                SELECT COUNT(1)
                FROM institution_class_students
                INNER JOIN security_users ON security_users.id = institution_class_students.student_id
                WHERE institution_class_students.institution_class_id = institution_classes.id
                AND security_users.gender_id = 1
            ),
            total_female_students = (
                SELECT COUNT(1)
                FROM institution_class_students
                INNER JOIN security_users ON security_users.id = institution_class_students.student_id
                WHERE institution_class_students.institution_class_id = institution_classes.id
                AND security_users.gender_id = 2
            )';

        $this->execute($update);

        $data = [
            [
                'id' => 'e4020d84-d028-11e7-a675-436637e1c535',
                'module' => 'InstitutionClasses',
                'field' => 'total_male_students',
                'module_name' => 'Institutions -> Classes',
                'field_name' => 'Male Students',
                'code' => NULL,
                'name' => NULL,
                'visible' => 1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ], [
                'id' => 'e991a4f8-d028-11e7-a675-436637e1c535',
                'module' => 'InstitutionClasses',
                'field' => 'total_female_students',
                'module_name' => 'Institutions -> Classes',
                'field_name' => 'Female Students',
                'code' => NULL,
                'name' => NULL,
                'visible' => 1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ], [
                'id' => 'f4be9d04-d028-11e7-a675-436637e1c535',
                'module' => 'InstitutionSubjects',
                'field' => 'total_male_students',
                'module_name' => 'Institutions -> Subjects',
                'field_name' => 'Male Students',
                'code' => NULL,
                'name' => NULL,
                'visible' => 1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ], [
                'id' => 'f98fd46a-d028-11e7-a675-436637e1c535',
                'module' => 'InstitutionSubjects',
                'field' => 'total_female_students',
                'module_name' => 'Institutions -> Subjects',
                'field_name' => 'Female Students',
                'code' => NULL,
                'name' => NULL,
                'visible' => 1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $table = $this->table('labels');
        $table->insert($data);
        $table->saveData();
    }

    public function down()
    {
        $table = $this->table('institution_subjects');
        $table
            ->removeColumn('total_male_students')
            ->removeColumn('total_female_students')
            ->save();

        $this->execute("DELETE FROM `labels` WHERE `id` = 'e4020d84-d028-11e7-a675-436637e1c535'");
        $this->execute("DELETE FROM `labels` WHERE `id` = 'e991a4f8-d028-11e7-a675-436637e1c535'");
        $this->execute("DELETE FROM `labels` WHERE `id` = 'f4be9d04-d028-11e7-a675-436637e1c535'");
        $this->execute("DELETE FROM `labels` WHERE `id` = 'f98fd46a-d028-11e7-a675-436637e1c535'");
    }
}
