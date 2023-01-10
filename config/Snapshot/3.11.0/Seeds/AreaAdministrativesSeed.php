<?php
use Migrations\AbstractSeed;

/**
 * AreaAdministratives seed.
 */
class AreaAdministrativesSeed extends AbstractSeed
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
                'code' => 'World',
                'name' => 'World',
                'is_main_country' => '0',
                'parent_id' => null,
                'lft' => '1',
                'rght' => '2',
                'area_administrative_level_id' => '1',
                'order' => '1',
                'visible' => '1',
                'modified_user_id' => '0',
                'modified' => '1990-01-01 00:00:00',
                'created_user_id' => '1',
                'created' => '2015-01-01 00:00:00',
            ],
        ];
        $table = $this->table('area_administratives');
        $table->insert($data)->save();
    }
}
