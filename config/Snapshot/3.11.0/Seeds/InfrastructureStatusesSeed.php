<?php
use Migrations\AbstractSeed;

/**
 * InfrastructureStatuses seed.
 */
class InfrastructureStatusesSeed extends AbstractSeed
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
                'code' => 'IN_USE',
                'name' => 'In Use',
            ],
            [
                'id' => '2',
                'code' => 'END_OF_USAGE',
                'name' => 'End of Usage',
            ],
            [
                'id' => '3',
                'code' => 'CHANGE_IN_TYPE',
                'name' => 'Change in Type',
            ],
        ];

        $table = $this->table('infrastructure_statuses');
        $table->insert($data)->save();
    }
}
