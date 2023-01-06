<?php
use Migrations\AbstractSeed;

/**
 * TextbookStatuses seed.
 */
class TextbookStatusesSeed extends AbstractSeed
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
                'code' => 'AVAILABLE',
                'name' => 'Available',
            ],
            [
                'id' => '2',
                'code' => 'NOT_AVAILABLE',
                'name' => 'Not Available',
            ],
        ];

        $table = $this->table('textbook_statuses');
        $table->insert($data)->save();
    }
}
