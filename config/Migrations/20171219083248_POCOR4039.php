<?php
use Migrations\AbstractMigration;

class POCOR4039 extends AbstractMigration
{
    public function up()
    {
        $this->table('schedule_jobs')
            ->addColumn('job_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to jobs.id',
            ])
            ->addColumn('pid', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null
            ])
            ->addColumn('scheduled_time', 'time', [
                'null' => false
            ])
            ->addColumn('interval', 'integer', [
                'default' => 0,
                'null' => false
            ])
            ->addColumn('last_ran', 'datetime', [
                'null' => true
            ])
            ->addColumn('status', 'integer', [
                'null' => false,
                'comment' => '1 => scheduled, 2 => running, 3 => stopped',
            ])
            ->insert([
                [
                    'id' => '1',
                    'job_id' => '1',
                    'pid' => null,
                    'scheduled_time' => '00:00:00',
                    'interval' => 86400,
                    'last_ran' => null,
                    'status' => 1
                ]
            ])
            ->save();

        $this->table('jobs')
            ->addColumn('code', 'string', [
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'limit' => 250,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'null' => true
            ])
            ->insert([
                [
                    'id' => '1',
                    'code' => 'institution_status',
                    'name' => 'Institution Statuses',
                    'description' => null
                ]
            ])
            ->save();
    }

    public function down()
    {
        $this->dropTable('schedule_jobs');
        $this->dropTable('jobs');
    }
}
