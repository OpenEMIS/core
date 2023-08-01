<?php

namespace App\Shell;

use Cake\Database\Schema\Collection;
use Cake\Database\Schema\Table;
use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\Utility\Security;
use PDOException;

/**
 * Class ArchiveStaffAttendancesShell
 * @package App\Shell
 * Archive following tables
 * institution_staff_attendances
 * institution_staff_leave
 * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
 */
class ArchiveStaffAttendancesShell extends Shell
{

    /**
     * POCOR-7521-KHINDOL
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
        $this->out("academic period id: $academicPeriodId, process id : $pid");
        $tablesToArchive = [
            'institution_staff_attendances',
            'institution_staff_leave'

        ];
        $processName = "Archive Staff Attendances";
        if ($academicPeriodId === 0) {
            $this->out('No valid academic period given');
            return;
        }
        $processInfo = date('d-m-Y H:i:s');
        $this->out("Initializing $processName:  $processInfo");
        $mypid = getmypid();
        $systemProcessId = CommonArchiveShell::startArchiveTransferSystemProcess($academicPeriodId, $mypid, $processName);
        $processInfo = CommonArchiveShell::setSystemProcessRunning($systemProcessId);
        $this->out($processInfo . ' - Running System PID:' . $systemProcessId);
//        $countOfArchivedRecords = 1;
        $recordsToArchive = 0;
        $tableRecordsCount = 0;
        foreach ($tablesToArchive as $tableToArchive) {
            $tableRecordsCount =
                CommonArchiveShell::moveRecordsToArchive($academicPeriodId, $tableToArchive);
            $this->out("Count of archived records for $tableToArchive: $tableRecordsCount");
            $recordsToArchive = $recordsToArchive + $tableRecordsCount;
            $tableRecordsCount = 0;
        }
        $this->out("Count of archived records for $processName: $recordsToArchive");

        if ($recordsToArchive >= 0) {
            try {
                $processInfo = CommonArchiveShell::setTransferLogsCompleted($pid);
                $this->out("Transfer completed $processName:  $processInfo");

                $processInfo = CommonArchiveShell::setSystemProcessCompleted($systemProcessId);
                $this->out("System process completed $processName:  $processInfo");
            } catch (\Exception $e) {
                $this->out("Error in $processName");
                $this->out($e->getMessage());
                $processInfo = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                $this->out("System process failed $processName:  $processInfo");
                $processInfo = CommonArchiveShell::setTransferLogsFailed($pid);
                $this->out("Transfer failed $processName:  $processInfo");
            }
        }

        if ($recordsToArchive < 0) {
            $processInfo = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $this->out("System process failed $processName:  $processInfo");
            $processInfo = CommonArchiveShell::setTransferLogsFailed($pid);
            $this->out("Transfer failed $processName:  $processInfo");
            $this->out("No records to update ");
        }

        $this->out("Ended $processName:  $processInfo");

    }

}