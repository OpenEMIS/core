<?php
use Migrations\AbstractSeed;

/**
 * StaffChangeTypes seed.
 */
class StaffChangeTypesSeed extends AbstractSeed
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
                'code' => 'END_OF_ASSIGNMENT',
                'name' => 'End of Assignment',
            ],
            [
                'id' => '2',
                'code' => 'CHANGE_IN_FTE',
                'name' => 'Change in FTE',
            ],
            [
                'id' => '3',
                'code' => 'CHANGE_IN_STAFF_TYPE',
                'name' => 'Change in Staff Type',
            ],
            [
                'id' => '4',
                'code' => 'CHANGE_OF_START_DATE',
                'name' => 'Change of Start Date',
            ],
        ];

        $table = $this->table('staff_change_types');
        $table->insert($data)->save();
    }
}
