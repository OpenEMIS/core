<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class UpdateStudentStatusShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();
        $this->StudentWithdraw = $this->fetchTable('Institution.StudentWithdraw');
        $this->Students = $this->fetchTable('Institution.Students');
        $this->SystemProcesses = $this->fetchTable('SystemProcesses');
    }

    public function main(): void
    {
        if (!empty($this->args[0])) {
            $exit = false;
            $StudentStatusUpdates = TableRegistry::getTableLocator()->get('Institution.StudentStatusUpdates');

            // POCOR-9770: cron calls this shell directly, bypassing
            // StudentStatusUpdatesTable::triggerUpdateStudentStatusShell()'s own overlap
            // guard, so this shell needs its own check to avoid two runs stacking up.
            // Mirrors that method's own 30-minute staleness purge so a crashed/hung run
            // (e.g. a fatal error the try/catch below can't stop) doesn't permanently
            // block every future scheduled run.
            $runningProcesses = $this->SystemProcesses->getRunningProcesses($this->args[0]);
            foreach ($runningProcesses as $processData) {
                $expiryDate = clone($processData['created']);
                $expiryDate = $expiryDate->addMinutes(30);
                if ($expiryDate < Time::now()) {
                    $this->SystemProcesses->updateProcess($processData['id'], Time::now(), $this->SystemProcesses::COMPLETED);
                    $this->SystemProcesses->killProcess(!empty($processData['process_id']) ? $processData['process_id'] : 0);
                }
            }
            if (!empty($this->SystemProcesses->getRunningProcesses($this->args[0]))) {
                $this->out('A previous run of UpdateStudentStatus is still marked as running. Skipping this run ('.Time::now().')');
                return;
            }

            $this->out('Initializing Update of Student Withdrawal Status ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('UpdateStudentStatus', getmypid(), $this->args[0]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            while (!$exit) {
                $recordToProcess = $StudentStatusUpdates->getStudentWithdrawalRecords(true);
                $this->out($recordToProcess);
                if (!empty($recordToProcess)) {
                    try {
                        $this->out('Dispatching event to update student withdrawal records for '.$recordToProcess['security_user_id']);
                        $event = $this->StudentWithdraw->dispatchEvent('Shell.StudentWithdraw.updateStudentStatusId', [$recordToProcess]);
                        $this->out('End Update for Student Withdrawal Status '.$recordToProcess['security_user_id'].' ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->out('Error Update Student Status ' . $recordToProcess['security_user_id']);
                        $this->out($e->getMessage());
                        // POCOR-9770: stop instead of retrying the same failing record forever -
                        // also this previously referenced an undefined $SystemProcesses variable,
                        // so a real failure here fatally crashed before ever recording ERROR status.
                        $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::ERROR);
                        $exit = true;
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