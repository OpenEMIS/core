<?php
use Migrations\AbstractSeed;

/**
 * StaffStatuses seed.
 */
class StaffStatusesSeed extends AbstractSeed
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
                'code' => 'ASSIGNED',
                'name' => 'Assigned',
            ],
            [
                'id' => '2',
                'code' => 'END_OF_ASSIGNMENT',
                'name' => 'End of Assignment',
            ],
        ];

        $table = $this->table('staff_statuses');
        $table->insert($data)->save();
    }
}
