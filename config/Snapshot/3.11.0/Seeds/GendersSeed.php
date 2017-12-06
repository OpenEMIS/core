<?php
use Migrations\AbstractSeed;

/**
 * Genders seed.
 */
class GendersSeed extends AbstractSeed
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
                'name' => 'Male',
                'code' => 'M',
                'order' => '1',
                'created_user_id' => '1',
                'created' => '2015-04-09 02:46:40',
            ],
            [
                'id' => '2',
                'name' => 'Female',
                'code' => 'F',
                'order' => '2',
                'created_user_id' => '1',
                'created' => '2015-04-09 02:46:40',
            ],
        ];

        $table = $this->table('genders');
        $table->insert($data)->save();
    }
}
