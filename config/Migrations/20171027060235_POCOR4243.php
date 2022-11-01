<?php

use Phinx\Migration\AbstractMigration;

class POCOR4243 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `reports` CHANGE `query` `query` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

        // ALTER TABLE `institution_classes` ADD `total_male_students` INT(11) NOT NULL DEFAULT 0 AFTER `class_number`, ADD INDEX (`total_male_students`);
        // ALTER TABLE `institution_classes` ADD `total_female_students` INT(11) NOT NULL DEFAULT 0 AFTER `total_male_students`, ADD INDEX (`total_female_students`);

        $table = $this->table('institution_classes');
        $table
        ->addColumn('total_male_students', 'integer', [
            'default' => 0,
            'limit' => 5,
            'null' => false,
            'after' => 'class_number'
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
    }

    public function down()
    {
        // ALTER TABLE `institution_classes` DROP `total_male_students`;
        // ALTER TABLE `institution_classes` DROP `total_female_students`;

        $table = $this->table('institution_classes');
        $table
            ->removeColumn('total_male_students')
            ->removeColumn('total_female_students')
            ->save();
    }
}
