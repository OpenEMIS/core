<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class UpdateStudentStatusShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.StudentWithdraw');
        $this->loadModel('Institution.Students');
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        if (!empty($this->args[0])) {
            $exit = false;
            $StudentStatusUpdates = TableRegistry::get('Institution.StudentStatusUpdates');
            $this->out('Initializing Update of Student Withdrawal Status ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('UpdateStudentStatus', getmypid(), $this->args[0]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            while (!$exit) {
                $recordToProcess = $StudentStatusUpdates->getStudentWithdrawalRecords(true);
                $this->out($recordToProcess);
                if (!empty($recordToProcess)) {
                    try {
                        $this->out('Dispatching event to update student withdrawal records for '.$recordToProcess[' security_user_id']);
                        $event = $this->StudentWithdraw->dispatchEvent('Shell.StudentWithdraw.updateStudentStatusId', [$recordToProcess]);
                        $this->out('End Update for Student Withdrawal Status '.$recordToProcess['security_user_id'].' ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->out('Error Update Student Status ' . $recordToProcess['security_user_id']);
                        $this->out($e->getMessage());
                        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
                    }
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            }
            $this->out('End Update for Student Withdrawal Status ('.Time::now().')');
            $event = $StudentStatusUpdates->dispatchEvent('Shell.StudentWithdraw.writeLastExecutedDateToFile');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
        }
    }
}