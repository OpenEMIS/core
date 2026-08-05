<?php

use Migrations\AbstractMigration;

//POCOR-9355
/**
 * Adds the 'multiple_institutions_student_program_enrollment' configuration item.
 *
 * name 'Multiple Institutions Student Program Enrollment',
 * code 'multiple_institutions_student_program_enrollment',
 * type 'Student Settings'
 * label 'Allow users to enrol students to multiple programmes'
 * default value 0
 */
class POCOR9355 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_multiprogenrol_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_multiprogenrol_config_items` SELECT * FROM `config_items`');
        $this->execute("INSERT IGNORE INTO `config_items` (
            name,
            code,
            type,
            label,
            value,
            value_selection,
            default_value,
            editable,
            visible,
            field_type,
            option_type,
            modified_user_id,
            modified,
            created_user_id,
            created
        ) VALUES (
            'Allow users to enrol students to multiple programmes',
            'multiple_institutions_student_program_enrollment',
            'Student Settings',
            'Allow users to enrol students to multiple programmes',
            '0',
            '',
            '0',
            1,
            1,
            'Dropdown',
            'yes_no',
            null,
            null,
            1,
            NOW()
        )");
    }

    public function down(): void
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_multiprogenrol_config_items` TO `config_items`');
    }
}
