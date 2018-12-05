<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class UpdateWithdrawalStudentShell extends Shell
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
            $this->out('Initializing Update of Student Withdrawal Status ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('UpdateWithdrawalStudent', getmypid(), $this->args[0], '', $this->args[1]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            while (!$exit) {
                $recordToProcess = $this->getStudentWithdrawRecords();
                $this->out($recordToProcess);
                if (!empty($recordToProcess)) {
                    $this->out('Dispatching event to update student withdrawal records for '.$recordToProcess[' security_user_id']);
                    $event = $this->StudentWithdraw->dispatchEvent('Shell.StudentWithdraw.updateStudentStatusId', [$recordToProcess]);
                    $this->out('End Update for Student Withdrawal Status '.$recordToProcess['security_user_id'].' ('. Time::now() .')');
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            }
            $this->out('End Update for Student Withdrawal Status ('.Time::now().')');
            $event = $this->StudentWithdraw->dispatchEvent('Shell.StudentWithdraw.writeLastExecutedDateToFile');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
        }
    }

    private function getStudentWithdrawRecords()
    {
        $model = $this->StudentWithdraw->alias();
        $StudentStatusUpdates = TableRegistry::get('StudentStatusUpdates');
        $today = Time::now();

        $studentWithdrawRecords = $StudentStatusUpdates
            ->find()
            ->where([
                $StudentStatusUpdates->aliasField('effective_date <= ') => $today,
                $StudentStatusUpdates->aliasField('model') => $model,
            ])
            ->order(['created' => 'asc'])
            ->first();
        return $studentWithdrawRecords;
    }
}