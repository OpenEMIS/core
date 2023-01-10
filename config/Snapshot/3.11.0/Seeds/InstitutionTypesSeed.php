<?php
use Migrations\AbstractSeed;

/**
 * InstitutionTypes seed.
 */
class InstitutionTypesSeed extends AbstractSeed
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
                'name' => 'Pre-primary',
                'order' => '1',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 09:05:34',
            ],
            [
                'id' => '2',
                'name' => 'Primary',
                'order' => '2',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 09:05:39',
            ],
            [
                'id' => '3',
                'name' => 'Secondary',
                'order' => '3',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 09:05:43',
            ],
            [
                'id' => '4',
                'name' => 'Tertiary',
                'order' => '4',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 09:05:49',
            ],
        ];

        $table = $this->table('institution_types');
        $table->insert($data)->save();
    }
}
