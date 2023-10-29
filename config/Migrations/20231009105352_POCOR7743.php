<?php
use Migrations\AbstractMigration;

class POCOR7743 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // custom field types
        $this->execute('CREATE TABLE `zz_7743_custom_field_types` LIKE `custom_field_types`');
        $this->execute('INSERT INTO `zz_7743_custom_field_types` SELECT * FROM `custom_field_types`');
        $data = [
            [
                'code' => 'PLACEHOLDER_GENDER',
                'name' => 'Placeholder - Gender',
                'value' => 'text_value',
                'description' => "",
                'format' => 'OpenEMIS',
                'is_mandatory' => 0,
                'is_unique' => 0,
                'visible' => 1
            ],
            [
                'code' => 'PLACEHOLDER_DOB',
                'name' => 'Placeholder - Date Of Birth',
                'value' => 'date_value',
                'description' => "",
                'format' => 'OpenEMIS',
                'is_mandatory' => 0,
                'is_unique' => 0,
                'visible' => 1
            ]
        ];
        $this->insert('custom_field_types', $data);
       
    }
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `zz_7743_custom_field_types`');
        $this->execute('RENAME TABLE `zz_7743_custom_field_types` TO `custom_field_types`');
    }
}
