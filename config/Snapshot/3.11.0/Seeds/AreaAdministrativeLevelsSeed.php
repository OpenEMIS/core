<?php
use Migrations\AbstractSeed;

/**
 * AreaAdministrativeLevels seed.
 */
class AreaAdministrativeLevelsSeed extends AbstractSeed
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
                'name' => 'World',
                'level' => '-1',
                'area_administrative_id' => '0',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-06-20 00:00:00',
            ],
            [
                'id' => '2',
                'name' => 'Country',
                'level' => '0',
                'area_administrative_id' => '1',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
        ];

        $table = $this->table('area_administrative_levels');
        $table->insert($data)->save();
    }
}
