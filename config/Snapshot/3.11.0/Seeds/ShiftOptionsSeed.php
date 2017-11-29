<?php
use Migrations\AbstractSeed;

/**
 * ShiftOptions seed.
 */
class ShiftOptionsSeed extends AbstractSeed
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
                'name' => 'First Shift',
                'start_time' => '07:00:00',
                'end_time' => '11:00:00',
                'order' => '1',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2016-06-21 00:00:00',
            ],
            [
                'id' => '2',
                'name' => 'Second Shift',
                'start_time' => '11:00:00',
                'end_time' => '15:00:00',
                'order' => '2',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2016-06-21 00:00:00',
            ],
            [
                'id' => '3',
                'name' => 'Third Shift',
                'start_time' => '15:00:00',
                'end_time' => '19:00:00',
                'order' => '3',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2016-06-21 00:00:00',
            ],
            [
                'id' => '4',
                'name' => 'Fourth Shift',
                'start_time' => '19:00:00',
                'end_time' => '23:00:00',
                'order' => '4',
                'visible' => '1',
                'editable' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2016-06-21 00:00:00',
            ],
        ];

        $table = $this->table('shift_options');
        $table->insert($data)->save();
    }
}
