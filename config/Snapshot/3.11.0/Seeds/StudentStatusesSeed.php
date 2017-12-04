<?php
use Migrations\AbstractSeed;

/**
 * StudentStatuses seed.
 */
class StudentStatusesSeed extends AbstractSeed
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
                'code' => 'CURRENT',
                'name' => 'Enrolled',
            ],
            [
                'id' => '3',
                'code' => 'TRANSFERRED',
                'name' => 'Transferred',
            ],
            [
                'id' => '4',
                'code' => 'WITHDRAWN',
                'name' => 'Withdrawn',
            ],
            [
                'id' => '6',
                'code' => 'GRADUATED',
                'name' => 'Graduated',
            ],
            [
                'id' => '7',
                'code' => 'PROMOTED',
                'name' => 'Promoted',
            ],
            [
                'id' => '8',
                'code' => 'REPEATED',
                'name' => 'Repeated',
            ],
        ];

        $table = $this->table('student_statuses');
        $table->insert($data)->save();
    }
}
