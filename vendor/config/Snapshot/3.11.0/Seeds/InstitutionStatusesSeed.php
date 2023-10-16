<?php
use Migrations\AbstractSeed;

/**
 * InstitutionStatuses seed.
 */
class InstitutionStatusesSeed extends AbstractSeed
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
                'code' => 'ACTIVE',
                'name' => 'Active',
            ],
            [
                'id' => '2',
                'code' => 'INACTIVE',
                'name' => 'Inactive',
            ],
        ];

        $table = $this->table('institution_statuses');
        $table->insert($data)->save();
    }
}
