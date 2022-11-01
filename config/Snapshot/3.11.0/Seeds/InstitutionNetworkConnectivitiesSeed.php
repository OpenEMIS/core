<?php
use Migrations\AbstractSeed;

/**
 * InstitutionNetworkConnectivities seed.
 */
class InstitutionNetworkConnectivitiesSeed extends AbstractSeed
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
                'name' => 'Narrowband Internet',
                'order' => '4',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:46:21',
            ],
            [
                'id' => '2',
                'name' => 'Internet-assisted Instruction',
                'order' => '3',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:46:32',
            ],
            [
                'id' => '3',
                'name' => 'Fixed Broadband internet',
                'order' => '2',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:46:41',
            ],
            [
                'id' => '4',
                'name' => 'Wireless Broadband Internet',
                'order' => '5',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:46:53',
            ],
            [
                'id' => '5',
                'name' => 'None',
                'order' => '1',
                'visible' => '1',
                'editable' => '1',
                'default' => '1',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:47:01',
            ],
        ];

        $table = $this->table('institution_network_connectivities');
        $table->insert($data)->save();
    }
}
