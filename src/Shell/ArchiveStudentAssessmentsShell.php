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
    public $featureName;
    public $systemProcessId;
    public $recordsToArchive;
    public $recordsToArchiveTotal;
    public $recordsInArchive;

    /**
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
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
        $recordsToArchive = !empty($args[2]) ? intval(trim($args[2])) : 0;
        $recordsInArchive = !empty($args[3]) ? intval(trim($args[3])) : 0;

        $this->recordsInArchive = $recordsInArchive;
        $this->recordsToArchiveTotal = $recordsToArchive;
        $this->recordsToArchive = $recordsToArchive;
        $this->out("academic period id: $academicPeriodId, process id : $pid, recordsToArchive: $recordsToArchive, recordsInArchive: $recordsInArchive" );

        $tablesToArchive = [
            'assessment_item_results',
        ];
        $processName = "ArchiveStudentAssessments";
        if ($academicPeriodId === 0) {
            $this->out('No valid academic period given');
            return;
        }
        if ($pid === 0) {
            $this->out('No valid pid given');
            return;
        }
        $processedDateTime = date('d-m-Y H:i:s');
        $this->out("Initializing $processName:  $processedDateTime");
        $mypid = getmypid();
        $systemProcessId = CommonArchiveShell::startArchiveTransferSystemProcess($academicPeriodId, $mypid, $processName, $pid);
        $processedDateTime = CommonArchiveShell::setSystemProcessRunning($systemProcessId);
        $this->out($processedDateTime . ' - Running System PID:' . $systemProcessId);

        $this->pid = $pid;
        $this->processName = $processName;
        $this->featureName = "Student Assessments";
        $this->systemProcessId = $systemProcessId;
        $tableMovedOK = true;
        foreach ($tablesToArchive as $tableToArchive) {
            try {
                $tableMovedOK = $tableMovedOK &&
                    CommonArchiveShell::moveRecordsToArchive($academicPeriodId, $tableToArchive, $this);
            } catch (\Exception $e) {
                $this->out("Error in $processName");
                $this->out($e->getMessage());
                $this->out("Transfer failed $processName:  $processedDateTime");
                $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                $this->out("System process failed $processName:  $processedDateTime");
                exit(1); //POCOR-7895
            }
            $this->out("Finished archiving records for $tableToArchive");
        }

        if ($tableMovedOK) {
            try {
                $processedDateTime = CommonArchiveShell::setTransferLogsCompleted($pid);
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
                throw $e; //POCOR-7895
            }
        }

        if (!$tableMovedOK) {
            $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
            $this->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $this->out("System process failed $processName:  $processedDateTime");
            $this->out("No records to update ");
        }
        $this->out("Ended $processName:  $processedDateTime");
    }
}