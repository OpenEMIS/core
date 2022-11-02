<?php

use Phinx\Migration\AbstractMigration;

class POCOR4573 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('custom_field_types');
        $data = [
            'code' => 'NOTE',
            'name' => 'Note',
            'value' => 'textarea_value',
            'description' => '',
            'format' => 'OpenEMIS',
            'is_mandatory' => 0,
            'is_unique' => 0,
            'visible' => 1
        ];
        $table->insert($data);
        $table->saveData();

        $this->execute('ALTER TABLE `custom_fields` ADD COLUMN `description` TEXT NULL AFTER `name`');
        $this->execute('ALTER TABLE `infrastructure_custom_fields` ADD COLUMN `description` TEXT NULL AFTER `name`');
        $this->execute('ALTER TABLE `institution_custom_fields` ADD COLUMN `description` TEXT NULL AFTER `name`');
        $this->execute('ALTER TABLE `staff_custom_fields` ADD COLUMN `description` TEXT NULL AFTER `name`');
        $this->execute('ALTER TABLE `student_custom_fields` ADD COLUMN `description` TEXT NULL AFTER `name`');
        $this->execute('ALTER TABLE `survey_questions` ADD COLUMN `description` TEXT NULL AFTER `name`');
    }

    public function down()
    {
        $this->execute("DELETE FROM `custom_field_types` WHERE `code` = 'NOTE'");

        $this->execute('ALTER TABLE `custom_fields` DROP COLUMN `description`');
        $this->execute('ALTER TABLE `infrastructure_custom_fields` DROP COLUMN `description`');
        $this->execute('ALTER TABLE `institution_custom_fields` DROP COLUMN `description`');
        $this->execute('ALTER TABLE `staff_custom_fields` DROP COLUMN `description`');
        $this->execute('ALTER TABLE `student_custom_fields` DROP COLUMN `description`');
        $this->execute('ALTER TABLE `survey_questions` DROP COLUMN `description`');
    }
}
