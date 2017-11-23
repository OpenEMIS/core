<?php
use Migrations\AbstractSeed;

/**
 * AcademicPeriodLevels seed.
 */
class AcademicPeriodLevelsSeed extends AbstractSeed
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
                'name' => 'Year',
                'level' => '1',
                'editable' => '0',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2015-02-04 00:06:48',
            ],
        ];

        $table = $this->table('academic_period_levels');
        $table->insert($data)->save();
    }
}
