<?php
use Migrations\AbstractSeed;

/**
 * InstitutionLocalities seed.
 */
class InstitutionLocalitiesSeed extends AbstractSeed
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
                'name' => 'Urban',
                'order' => '1',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:51:01',
            ],
            [
                'id' => '2',
                'name' => 'Rural',
                'order' => '2',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => '',
                'national_code' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '2',
                'created' => '2016-04-26 08:51:05',
            ],
        ];

        $table = $this->table('institution_localities');
        $table->insert($data)->save();
    }
}
