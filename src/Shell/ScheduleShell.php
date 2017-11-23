<?php
namespace App\Shell;

use Cake\I18n\Time;
use Cake\Console\Shell;

/**
 * Schedule shell command.
 */
class ScheduleShell extends Shell
{
    /**
     * Manage the available sub-commands along with their arguments and help
     *
     * @see http://book.cakephp.org/3.0/en/console-and-shells.html#configuring-options-and-generating-help
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser
            ->addArgument('taskName', [
                'help' => 'Task name'
            ])
            ->addArgument('timeHour', [
                'help' => 'Hour of the time to execute (0 - 23)'
            ])
            ->addArgument('timeMinute', [
                'help' => 'Minute of the time to execute (0 - 59)'
            ])
            ->addArgument('interval', [
                'help' => 'Interval of the execution in seconds'
            ])
            ->description('Kord IT Scheduler for execution of model functions.')
        ;
        return $parser;
    }

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main($taskName, $timeHour, $timeMinute, $interval)
    {
        $this->tasks = [$taskName];
        $this->loadTasks();

        $timeToStart = Time::now();
        $timeToStart->hour($timeHour);
        $timeToStart->minute($timeMinute);
        $timeToStart = (int)$timeToStart->toUnixString();
        $interval = (int) $interval;
        // $this->loadModel('Schedules');
        // Fetch schedule record
        // id, name, task_name, description, pid, status (running, scheduled, stop), modified, modified_user_id, created, created_user_id
        do {
            // Patch schedule record with the PID and status to set to running
            if ($interval) {
                while ($timeToStart < (int)(Time::now())->toUnixString() + 1) {
                    $timeToStart += $interval;
                }
                time_sleep_until($timeToStart);
                $timeToStart += $interval;
            }
            $this->{$taskName}->main();
            if ($interval) {
                // Patch schedule record with the PID and status to set to scheduled
            } else {
                // Patch schedule record with the PID and status to set to stopped
            }
        } while ($interval);
    }
}
