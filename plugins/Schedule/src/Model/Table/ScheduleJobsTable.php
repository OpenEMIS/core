<?php
namespace Schedule\Model\Table;

use App\Model\Table\AppTable;

class ScheduleJobsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Jobs', ['className' => 'Schedule.Jobs']);
    }
}
