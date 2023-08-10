<?php

namespace App\Shell;

//use Exception;

use Cake\Console\Shell;


/**
 * Class ArchiveStudentAssessmentsShell
 * @package App\Shell
 * POCOR-7521-KHINDOL
 * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
 * table
 * assessment_item_results
 */
class ArchiveStudentAssessmentsShell extends Shell
{
    /**
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */

    public $pid;
    public $processName;
    public $systemProcessId;

    public function initialize()
    {
        //POCOR-7521-HINDOL cleaned the code even more
        parent::initialize();
        $this->loadModel('SystemProcesses');
        $this->loadModel('Archive.TransferLogs');
    }

    /**
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function main()
    {
        $args = $this->args;
        $academicPeriodId = !empty($args[0]) ? intval(trim($args[0])) : 0;
        $pid = !empty($args[1]) ? intval(trim($args[1])) : getmypid();
        $this->out("academic period id: $academicPeriodId, process id : $pid");
        $tablesToArchive = [
            'assessment_item_results',
        ];
        $processName = "Archive Student Assessments";
        if ($academicPeriodId === 0) {
            $this->out('No valid academic period given');
            return;
        }
        $processedDateTime = date('d-m-Y H:i:s');
        $this->out("Initializing $processName:  $processedDateTime");
        $mypid = getmypid();
        $systemProcessId = CommonArchiveShell::startArchiveTransferSystemProcess($academicPeriodId, $mypid, $processName);

        $processedDateTime = CommonArchiveShell::setSystemProcessRunning($systemProcessId);
        $this->out($processedDateTime . ' - Running System PID:' . $systemProcessId);
//        $countOfArchivedRecords = 1;
        $recordsToArchive = 0;
        $tableRecordsCount = 0;
        $this->pid = $pid;
        $this->processName = $processName;
        $this->systemProcessId = $systemProcessId;
        foreach ($tablesToArchive as $tableToArchive) {
            try {
                $tableRecordsCount =
                    CommonArchiveShell::moveRecordsToArchive($academicPeriodId, $tableToArchive, $this);
            } catch (\Exception $e) {
                $this->out("Error in $processName");
                $this->out($e->getMessage());
                $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
                $this->out("Transfer failed $processName:  $processedDateTime");
                $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                $this->out("System process failed $processName:  $processedDateTime");
            }
            $this->out("Count of archived records for $tableToArchive: $tableRecordsCount");
            $recordsToArchive = $recordsToArchive + $tableRecordsCount;
            $tableRecordsCount = 0;
        }
        $this->out("Count of archived records for $processName: $recordsToArchive");

        if ($recordsToArchive >= 0) {
            try {
                $processedDateTime = CommonArchiveShell::setTransferLogsCompleted($pid, $recordsToArchive);
                $this->out("Transfer completed $processName:  $processedDateTime");
                $processedDateTime = CommonArchiveShell::setSystemProcessCompleted($systemProcessId);
                $this->out("System process completed $processName:  $processedDateTime");
            } catch (\Exception $e) {
                $this->out("Error in $processName");
                $this->out($e->getMessage());
                $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
                $this->out("Transfer failed $processName:  $processedDateTime");
                $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                $this->out("System process failed $processName:  $processedDateTime");
            }
        }

        if ($recordsToArchive < 0) {
            $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
            $this->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $this->out("System process failed $processName:  $processedDateTime");
            $this->out("No records to update ");
        }
        $this->out("Ended $processName:  $processedDateTime");

    }
}