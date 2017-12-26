<?php
namespace Schedule\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use ArrayObject;
use Cake\Console\ShellDispatcher;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class ScheduleJobsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Jobs', ['className' => 'Schedule.Jobs']);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $scheduledTime = new Time($entity->scheduled_time);
        $entity->scheduled_time = $scheduledTime;
        if ($entity->offsetExists('start_shell') && $entity->start_shell) {
            $hour = $scheduledTime->format('H');
            $minute = $scheduledTime->format('i');
            $interval = $entity->interval;
            $log = ROOT . DS . 'logs' . DS . 'ScheduledJobs.log';
            $consoleDir = ROOT . DS . 'bin' . DS . 'cake';
            $job = TableRegistry::get('Schedule.Jobs')->get($entity->job_id);
            $cmd = sprintf($consoleDir . ' schedule %s %s %s %s', Inflector::camelize($job->code), $hour, $minute, $interval);
            return shell_exec("$cmd >>$log");
        }
    }
}
