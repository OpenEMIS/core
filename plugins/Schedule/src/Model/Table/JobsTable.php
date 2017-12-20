<?php
namespace Schedule\Model\Table;

use App\Model\Table\AppTable;

class JobsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasOne('ScheduleJobs', ['className' => 'Schedule.ScheduleJobs']);
    }
}
