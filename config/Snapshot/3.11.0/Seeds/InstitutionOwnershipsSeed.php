<?php
use Migrations\AbstractSeed;

/**
 * InstitutionOwnerships seed.
 */
class InstitutionOwnershipsSeed extends AbstractSeed
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
                'name' => 'Customary (Disputed)',
                'order' => '3',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:58:20',
            ],
            [
                'id' => '2',
                'name' => 'Customary (Non Disputed)',
                'order' => '4',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:58:29',
            ],
            [
                'id' => '3',
                'name' => 'Leasehold',
                'order' => '2',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:58:35',
            ],
            [
                'id' => '4',
                'name' => 'Freehold',
                'order' => '1',
                'visible' => '1',
                'editable' => '1',
                'default' => '1',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => '2',
                'modified' => '2016-04-26 08:59:10',
                'created_user_id' => '2',
                'created' => '2016-04-26 08:58:40',
            ],
        ];

        $table = $this->table('institution_ownerships');
        $table->insert($data)->save();
    }
}
