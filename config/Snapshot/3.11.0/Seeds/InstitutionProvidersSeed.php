<?php
use Migrations\AbstractSeed;

/**
 * InstitutionProviders seed.
 */
class InstitutionProvidersSeed extends AbstractSeed
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
                'name' => 'Private',
                'order' => '2',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'institution_sector_id' => '2',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 09:03:39',
            ],
            [
                'id' => '2',
                'name' => 'Government',
                'order' => '1',
                'visible' => '1',
                'editable' => '1',
                'default' => '1',
                'institution_sector_id' => '1',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 09:03:47',
            ],
        ];

        $table = $this->table('institution_providers');
        $table->insert($data)->save();
    }
}
