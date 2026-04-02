<?php

namespace App\Service;

use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;

//POCOR-8898: start - ArchiveService: plain PHP service class replacing CommonArchiveShell static utilities
class ArchiveService
{

    public static function moveRecordsToArchive($academicPeriodId, $table_name, $caller)
    {
        //POCOR-8898: port from CommonArchiveShell::moveRecordsToArchive
        $records_count = 0;

        $pid = $caller->pid;
        $processName = $caller->processName;
        $systemProcessId = $caller->systemProcessId;
        $sourceTable = TableRegistry::getTableLocator()->get($table_name); //POCOR-8898: replaced TableRegistry::get()
        $targetTableNameAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
        $targetTableName = $targetTableNameAndConnection[0];
        $targetTableConnection = $targetTableNameAndConnection[1];
        if ($targetTableName === "") {
            return -1;
        }
        $remoteConnection = ConnectionManager::get($targetTableConnection);
        $targetTable = TableRegistry::getTableLocator()->get($targetTableName, ['connection' => $remoteConnection]); //POCOR-8898: replaced TableRegistry::get()
        try {
            $whereCondition = ['academic_period_id' => $academicPeriodId];
            return self::moveRecords($sourceTable, $targetTable, $whereCondition,
                $table_name, $targetTableName, $targetTableConnection,
                $caller);
        } catch (\Exception $e) {
            $caller->out("Error in $processName");
            $caller->out($e->getMessage());
            $processedDateTime = self::setTransferLogsFailed($pid);
            $caller->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = self::setSystemProcessFailed($systemProcessId);
            $caller->out("System process failed $processName:  $processedDateTime");
            exit(1); //POCOR-7895
        }
    }

    public static function moveRecords($sourceTable, $targetTable,
                                       $whereCondition, $table_name,
                                       $targetTableName, $targetTableConnection,
                                       $caller): bool
    {
        //POCOR-8898: port from CommonArchiveShell::moveRecords
        $pid = $caller->pid;
        $processName = $caller->processName;
        $systemProcessId = $caller->systemProcessId;
        $academic_period_id = $whereCondition['academic_period_id'];
        $totalRecords = self::getTableRecordsCountForAcademicPeriod($table_name, $academic_period_id);
        try {
            $connection = ConnectionManager::get($targetTableConnection);
            $batchSize = intval(($totalRecords / 100) + 1);
            $connection->execute("SET FOREIGN_KEY_CHECKS = 0");
            $connection->execute("ALTER TABLE $targetTableName DISABLE KEYS");

            $i = 1;
            $baseCount = intval($caller->recordsToArchiveTotal);
            $baseCountStr = number_format($baseCount, 0, '', ' ');
            for ($offset = 0; $offset < $totalRecords; $offset += $batchSize) {
                $sql = "INSERT IGNORE INTO $targetTableName SELECT * FROM $table_name where academic_period_id = $academic_period_id LIMIT $batchSize OFFSET $offset";
                $affectedRecordsCount = $connection->execute($sql)->rowCount();
                $caller->recordsInArchive = $caller->recordsInArchive + $affectedRecordsCount;
                $proc = "Copy step:";
                self::setTransferLogsBatch($caller, $i, $proc, $baseCount, $baseCountStr);
                $i++;
            }

            $connection->execute("ALTER TABLE $targetTableName ENABLE KEYS");
            $connection->execute("SET FOREIGN_KEY_CHECKS = 1"); //POCOR-8898
            $sourceConnection = ConnectionManager::get('default');
            $i = 1;

            for ($offset = 0; $offset < $totalRecords; $offset += $batchSize) {
                $sql = "DELETE FROM $table_name where academic_period_id = $academic_period_id LIMIT $batchSize";
                $affectedBatchRows = $sourceConnection->execute($sql)->rowCount();
                $caller->recordsToArchive = $caller->recordsToArchive - $affectedBatchRows;
                $proc = "Delete step:";
                self::setTransferLogsBatch($caller, $i, $proc, $baseCount, $baseCountStr);
                $i++;
            }

            $sourceTable->deleteAll($whereCondition);
            return true;
        } catch (\Exception $e) {
            Log::write('error', 'I have BAD exception in move records: ' . $e->getMessage());
            $caller->out("Error in $processName");
            $caller->out($e->getMessage());
            $processedDateTime = self::setTransferLogsFailed($pid);
            $caller->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = self::setSystemProcessFailed($systemProcessId);
            $caller->out("System process failed $processName:  $processedDateTime");
            $connection->execute("ALTER TABLE $targetTableName ENABLE KEYS");
            $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
            return false;
        }
    }

    public static function setTransferLogsBatch($caller, $step = 1, $proc = "", $baseCount = 0, $baseCountStr = "")
    {
        //POCOR-8898: port from CommonArchiveShell::setTransferLogsBatch
        $recordsInArchive = number_format($caller->recordsInArchive, 0, '', ',');
        $recordsToArchive = number_format($caller->recordsToArchive, 0, '', ',');
        $featureName = $caller->featureName;
        $pid = $caller->pid;
        $TransferLogs = TableRegistry::getTableLocator()->get('Archive.TransferLogs'); //POCOR-8898: replaced TableRegistry::get()
        $transferlog = $TransferLogs
            ->find('all')
            ->where(['p_id' => $pid])->first();
        $moved = "{$featureName}: {$recordsToArchive} / {$recordsInArchive}. {$proc} {$step}."; // POCOR-7957
        $caller->out($moved);
        Log::write('debug', $moved);
        $moved = "{$featureName}: {$recordsToArchive} / {$recordsInArchive}"; // POCOR-7957
        $transferlog->features = $moved;
        try {
            $TransferLogs->save($transferlog);
        } catch (\Exception $e) {
            Log::write('error', 'Error executing batch: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function startArchiveTransferSystemProcess($academicPeriodId, $mypid, $name, $pid)
    {
        //POCOR-8898: port from CommonArchiveShell::startArchiveTransferSystemProcess
        $param = [
            'academicPeriodId' => $academicPeriodId,
            'pid' => $pid,
        ];
        $model = TableRegistry::getTableLocator()->get('Archive.TransferLogs'); //POCOR-8898: replaced TableRegistry::get()
        $eventName = '';
        $processModel = $model->getRegistryAlias();
        $param = json_encode($param);
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses'); //POCOR-8898: replaced TableRegistry::get()
        $systemProcessId = $SystemProcesses->addProcess($name, $mypid, $processModel, $eventName, $param);
        return $systemProcessId;
    }

    public static function setSystemProcessRunning($systemProcessId)
    {
        //POCOR-8898: port from CommonArchiveShell::setSystemProcessRunning; replaced Time::now() with new \DateTime()
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses'); //POCOR-8898: replaced TableRegistry::get()
        $SystemProcesses->updateProcess($systemProcessId, new \DateTime(), $SystemProcesses::RUNNING, 1); //POCOR-8898: replaced Time::now()
        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
    }

    public static function setSystemProcessCompleted($systemProcessId)
    {
        //POCOR-8898: port from CommonArchiveShell::setSystemProcessCompleted; replaced Time::now() with new \DateTime()
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses'); //POCOR-8898: replaced TableRegistry::get()
        $SystemProcesses->updateProcess($systemProcessId, new \DateTime(), $SystemProcesses::COMPLETED); //POCOR-8898: replaced Time::now()
        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
    }

    public static function setTransferLogsCompleted($pid)
    {
        //POCOR-8898: port from CommonArchiveShell::setTransferLogsCompleted
        $TransferLogs = TableRegistry::getTableLocator()->get('Archive.TransferLogs'); //POCOR-8898: replaced TableRegistry::get()
        $processInfo = date('Y-m-d H:i:s');
        $completed = $TransferLogs->updateAll(
            ['process_status' => $TransferLogs::DONE, 'completed_on' => $processInfo],
            ['p_id' => $pid]
        );
        return $processInfo;
    }

    public static function setTransferLogsFailed($pid)
    {
        //POCOR-8898: port from CommonArchiveShell::setTransferLogsFailed
        $TransferLogs = TableRegistry::getTableLocator()->get('Archive.TransferLogs'); //POCOR-8898: replaced TableRegistry::get()
        $processInfo = date('Y-m-d H:i:s');
        $completed = $TransferLogs->updateAll(
            ['process_status' => $TransferLogs::ERROR, 'completed_on' => $processInfo],
            ['p_id' => $pid]
        );
        return $processInfo;
    }

    public static function setSystemProcessFailed($systemProcessId)
    {
        //POCOR-8898: port from CommonArchiveShell::setSystemProcessFailed; replaced Time::now() with new \DateTime()
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses'); //POCOR-8898: replaced TableRegistry::get()
        $SystemProcesses->updateProcess($systemProcessId, new \DateTime(), $SystemProcesses::ERROR); //POCOR-8898: replaced Time::now()
    }

    private static function getTableRecordsCountForAcademicPeriod($table_name, $academic_period_id)
    {
        //POCOR-8898: port from CommonArchiveShell::getTableRecordsCountForAcademicPeriod
        $connectionName = 'default';
        $fieldName = 'academic_period_id';
        $RecordsCount = self::getSimpleCount(
            $table_name,
            $connectionName,
            $fieldName,
            $academic_period_id);
        return intval($RecordsCount);
    }

    private static function getSimpleCount($tableName, $connectionName, $fieldName, $fieldValue)
    {
        //POCOR-8898: port from CommonArchiveShell::getSimpleCount
        $connection = ConnectionManager::get($connectionName);
        $sql = "SELECT count(*) as count FROM $tableName WHERE $fieldName = :fieldValue";
        $result = $connection->execute($sql, ['fieldValue' => $fieldValue])->fetch('assoc');
        return intval($result['count']);
    }

    //POCOR-8898: Check if archiving will make year non-editable + return alert message
    public static function checkArchiveCompletionStatus($academicPeriodId, $archiveTypeName)
    {
        /**
         * Returns:
         * ['willBecomeReadOnly' => false, 'alert' => null] — normal archive
         * ['willBecomeReadOnly' => true, 'alert' => 'Archiving X will make year YYYY read-only (all 4 types archived)']
         *
         * Checks if ALL 4 archive types have records for this academic period.
         * No institution filtering — archive completion is per academic period globally.
         *
         * The 4 archive types checked:
         * 1. Student Report Cards (InstitutionStudentsReportCardsArchived)
         * 2. Student Assessments (ArchivedAssessments)
         * 3. Student Attendances (StudentAttendanceMarkedRecordsArchived)
         * 4. Staff Attendances (StaffAttendancesArchived)
         */

        $archiveStatusChecks = [
            'Institution.InstitutionStudentsReportCardsArchived' => true,
            'Student.ArchivedAssessments' => true,
            'Attendance.StudentAttendanceMarkedRecordsArchived' => true,
            'Institution.StaffAttendancesArchived' => true,
        ];

        $archivedCount = 0;
        $archiveNames = [];

        foreach ($archiveStatusChecks as $tableName => $checkRequired) {
            if (!$checkRequired) continue;

            $ArchiveTable = TableRegistry::getTableLocator()->get($tableName);
            $hasArchiveRecords = $ArchiveTable->find()
                ->select(['academic_period_id'])
                ->where(['academic_period_id' => $academicPeriodId])
                ->disableHydration()
                ->first() !== null;

            if ($hasArchiveRecords) {
                $archivedCount++;
                // Extract friendly name from table name
                $friendlyName = match($tableName) {
                    'Institution.InstitutionStudentsReportCardsArchived' => 'Student Report Cards',
                    'Student.ArchivedAssessments' => 'Student Assessments',
                    'Attendance.StudentAttendanceMarkedRecordsArchived' => 'Student Attendances',
                    'Institution.StaffAttendancesArchived' => 'Staff Attendances',
                    default => $tableName
                };
                $archiveNames[] = $friendlyName;
            }
        }

        // Year becomes READ-ONLY ONLY when ALL 4 types are archived (4/4)
        // But we warn when 3+ are archived (so operators know it's coming)
        $willBecomeReadOnly = ($archivedCount === 4);
        $hasWarning = ($archivedCount === 3); // 3/4 archived — warn before final archive locks it

        $alert = null;
        if ($hasWarning) {
            // Alert when 3 are done and 4th type is about to fully lock the year
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $periodRecord = $AcademicPeriods->get($academicPeriodId);
            $yearLabel = $periodRecord->name ?? "Period $academicPeriodId";
            $alert = "⚠️ WARNING: Archiving this will make year $yearLabel READ-ONLY (all 4 archive types will be completed):\n"
                . "- Already archived: " . implode(', ', $archiveNames) . "\n"
                . "- Now archiving: $archiveTypeName";
        }

        return [
            'willBecomeReadOnly' => $willBecomeReadOnly,
            'hasWarning' => $hasWarning,
            'alert' => $alert,
            'archivedCount' => $archivedCount
        ];
    }

}
//POCOR-8898: end
