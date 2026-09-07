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
    const MAX_FAILURES_PER_RUN = 100;

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

            // POCOR-9770: cron calls this shell directly, in parallel with
            // StudentStatusUpdatesTable::triggerUpdateStudentStatusShell()'s own trigger from
            // afterSave() - re-check and clear out any stale/expired process record here too
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
            $this->out('cron update withdrawal Status');

            $systemProcessId = $this->SystemProcesses->addProcess('UpdateStudentStatus', getmypid(), $this->args[0]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            $processedCount = 0;
            $failedIds = [];

            while (!$exit) {
                $recordToProcess = $StudentStatusUpdates->getStudentWithdrawalRecords(true, $failedIds);
                $this->out($recordToProcess);
                if (!empty($recordToProcess)) {
                    try {
                        $this->out('Dispatching event to update student withdrawal records for '.$recordToProcess['security_user_id']);
                        $event = $this->StudentWithdraw->dispatchEvent('Shell.StudentWithdraw.updateStudentStatusId', [$recordToProcess]);
                        $this->out('End Update for Student Withdrawal Status '.$recordToProcess['security_user_id'].' ('. Time::now() .')');
                        $processedCount++;
                    } catch (\Exception $e) {
                        $this->out('Error Update Student Status ' . $recordToProcess['security_user_id']);
                        $this->out($e->getMessage());
                        // POCOR-9770: skip this specific record on the next loop iteration (its stored execution_status
                        // stays Not Executed so it remains retriable on the next scheduled run) instead of aborting the whole run
                        $failedIds[] = $recordToProcess['id'];
                        if (count($failedIds) >= self::MAX_FAILURES_PER_RUN) {
                            $this->out('Too many failures this run (' . count($failedIds) . '), stopping (' . Time::now() . ')');
                            $exit = true;
                        }
                    }
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            }
            $this->out('End Update for Student Withdrawal Status ('.Time::now().')');
            if (!empty($failedIds)) {
                $this->out('Completed with ' . count($failedIds) . ' failed record(s), still Not Executed and retriable: ' . implode(', ', $failedIds));
            }
            $event = $StudentStatusUpdates->dispatchEvent('Shell.StudentWithdraw.writeLastExecutedDateToFile');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED, $processedCount);
        }
    }
}
