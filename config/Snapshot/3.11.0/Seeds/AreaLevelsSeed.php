<?php
use Migrations\AbstractSeed;

/**
 * AreaLevels seed.
 */
class AreaLevelsSeed extends AbstractSeed
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
                'name' => 'Country',
                'level' => '1',
                'modified_user_id' => '0',
                'modified' => '1990-01-01 00:00:00',
                'created_user_id' => '0',
                'created' => '1990-01-01 00:00:00',
            ],
        ];

        $table = $this->table('area_levels');
        $table->insert($data)->save();
    }
}
