<?php
use Migrations\AbstractSeed;

/**
 * AcademicPeriods seed.
 */
class AcademicPeriodsSeed extends AbstractSeed
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
                'code' => 'All',
                'name' => 'All Data',
                'start_date' => '1990-01-01',
                'start_year' => '1990',
                'end_date' => '2999-01-01',
                'end_year' => '2999',
                'school_days' => '0',
                'current' => '0',
                'editable' => '1',
                'parent_id' => '0',
                'lft' => '1',
                'rght' => '24',
                'academic_period_level_id' => '-1',
                'order' => '1',
                'visible' => '1',
                'modified_user_id' => null,
                'modified' => '2015-05-21 11:22:05',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
        ];

        $table = $this->table('academic_periods');
        $table->insert($data)->save();
    }
}
