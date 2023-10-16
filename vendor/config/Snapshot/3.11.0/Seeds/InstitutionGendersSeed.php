<?php
use Migrations\AbstractSeed;

/**
 * InstitutionGenders seed.
 */
class InstitutionGendersSeed extends AbstractSeed
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
                'name' => 'Mixed',
                'code' => 'X',
                'order' => '1',
                'created_user_id' => '1',
                'created' => '2017-04-13 00:00:00',
            ],
            [
                'id' => '2',
                'name' => 'Male',
                'code' => 'M',
                'order' => '2',
                'created_user_id' => '1',
                'created' => '2017-04-13 00:00:00',
            ],
            [
                'id' => '3',
                'name' => 'Female',
                'code' => 'F',
                'order' => '3',
                'created_user_id' => '1',
                'created' => '2017-04-13 00:00:00',
            ],
        ];

        $table = $this->table('institution_genders');
        $table->insert($data)->save();
    }
}
