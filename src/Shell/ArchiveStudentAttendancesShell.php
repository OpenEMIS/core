<?php

namespace App\Shell;

use Cake\Database\Schema\Table;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;


/**
 * Class ArchiveStudentAttendanceShell
 * POCOR-7521-KHINDOL
 * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
 * @package App\Shell
 * Archive following tables
 * institution_class_attendance_records
 * institution_student_absences
 * institution_student_absence_details
 * student_attendance_marked_records
 * student_attendance_mark_types
 * uses RemoteArchiveShell
 */
class ArchiveStudentAttendancesShell extends Shell
{
    public $pid;
    public $processName;
    public $featureName;
    public $systemProcessId;
    public $recordsToArchive;
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
        $this->recordsToArchive = $recordsToArchive;
        $this->out("academic period id: $academicPeriodId, process id : $pid, recordsToArchive: $recordsToArchive, recordsInArchive: $recordsInArchive" );

        $tablesToArchive = [
            'institution_class_attendance_records',
            'institution_student_absences',
            'institution_student_absence_details',
            'student_attendance_marked_records',
            'student_attendance_mark_types',
        ];
        $processName = "ArchiveStudentAttendances";
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
        $this->featureName = "Student Attendances";
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
                throw $e;
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