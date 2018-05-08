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


        $this->execute('ALTER TABLE `survey_questions` ADD COLUMN `description` TEXT NULL AFTER `name`');
    }

    public function down()
    {
        $this->execute("DELETE FROM `custom_field_types` WHERE `code` = 'NOTE'");

        $this->execute('ALTER TABLE `survey_questions` DROP COLUMN `description`');
    }
}
