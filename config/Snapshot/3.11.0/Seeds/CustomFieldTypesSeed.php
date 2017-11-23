<?php
use Migrations\AbstractSeed;

/**
 * CustomFieldTypes seed.
 */
class CustomFieldTypesSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => '1',
                'code' => 'TEXT',
                'name' => 'Text',
                'value' => 'text_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '1',
                'visible' => '1',
            ],
            [
                'id' => '2',
                'code' => 'NUMBER',
                'name' => 'Number',
                'value' => 'number_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '1',
                'visible' => '1',
            ],
            [
                'id' => '3',
                'code' => 'DECIMAL',
                'name' => 'Decimal',
                'value' => 'decimal_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '4',
                'code' => 'TEXTAREA',
                'name' => 'Textarea',
                'value' => 'textarea_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '5',
                'code' => 'DROPDOWN',
                'name' => 'Dropdown',
                'value' => 'number_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '6',
                'code' => 'CHECKBOX',
                'name' => 'Checkbox',
                'value' => 'number_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '0',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '7',
                'code' => 'TABLE',
                'name' => 'Table',
                'value' => 'text_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '0',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '8',
                'code' => 'DATE',
                'name' => 'Date',
                'value' => 'date_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '9',
                'code' => 'TIME',
                'name' => 'Time',
                'value' => 'time_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '10',
                'code' => 'STUDENT_LIST',
                'name' => 'Student List',
                'value' => 'text_value',
                'description' => '',
                'format' => 'OpenEMIS_Institution',
                'is_mandatory' => '0',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '11',
                'code' => 'COORDINATES',
                'name' => 'Coordinates',
                'value' => 'text_value',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '1',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '12',
                'code' => 'FILE',
                'name' => 'File',
                'value' => 'file',
                'description' => '',
                'format' => 'OpenEMIS',
                'is_mandatory' => '0',
                'is_unique' => '0',
                'visible' => '1',
            ],
            [
                'id' => '13',
                'code' => 'REPEATER',
                'name' => 'Repeater',
                'value' => 'text_value',
                'description' => '',
                'format' => 'OpenEMIS_Institution',
                'is_mandatory' => '0',
                'is_unique' => '0',
                'visible' => '1',
            ],
        ];

        $table = $this->table('custom_field_types');
        $table->insert($data)->save();
    }
}
