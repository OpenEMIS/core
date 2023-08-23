<?php

namespace App\Shell;

use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Class RemoteArchiveShell
 * @package App\Shell
 * Archive Common Functions.
 * If called from CLI creates/returns archive table name for a table name
 */
class CommonArchiveShell extends Shell
{

    public function initialize()
    {
        //POCOR-7339-HINDOL cleaned the code
        parent::initialize();
        $this->loadModel('Archive.DataManagementCollection');
    }

    public function main()
    {
        $args = $this->args;
        $table_name = !empty($args[0]) ? strval(trim($args[0])) : "";
        $this->out("table to check: $table");
        if ($table_name === "") return;
        $targetTableNameAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
        $targetTableName = $targetTableNameAndConnection[0];
        $targetTableConnection = $targetTableNameAndConnection[1];
        return $targetTableName . ":" . $targetTableConnection;

    }

    public static
    function setTransferLogsBatch($caller, $step = 1, $proc = "", $baseCount = 0, $baseCountStr="")
    {
        $recordsInArchive = number_format($caller->recordsInArchive, 0, '', ' ');
        $recordsToArchive = number_format($caller->recordsToArchive, 0, '', ' ');
        $recordsMoved = $baseCount - $caller->recordsToArchive;
        $recordsMovedStr = number_format($recordsMoved, 0, '', ' ');
        $featureName = $caller->featureName;
        $pid = $caller->pid;
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $transferlog = $TransferLogs
            ->find('all')
            ->where(['p_id' => $pid])->first();
        $moved = "{$featureName}. {$recordsToArchive} / {$recordsInArchive}. {$proc} {$step}.";
        $caller->out($moved);
        Log::write('debug', $moved);
        $moved = "{$featureName}. {$recordsMovedStr} / {$baseCountStr}.";
        $transferlog->features = $moved;
        Log::write('debug', $moved);
        try {
            $TransferLogs->save($transferlog);
        } catch (\Exception $e) {
            Log::write('error', 'Error executing batch: ' . $e->getMessage());
            throw $e;
        }

    }

    /**
     * @param $academicPeriodId
     * @param $table_name
     * @param $caller
     * @return bool|int
     * @throws \Exception
     */

    public static
    function moveRecordsToArchive($academicPeriodId, $table_name, $caller)
    {
        //POCOR-7339-HINDOL
        $records_count = 0;

        $pid = $caller->pid;
        $processName = $caller->processName;
        $systemProcessId = $caller->systemProcessId;
        $sourceTable = TableRegistry::get($table_name);
        $targetTableNameAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
        $targetTableName = $targetTableNameAndConnection[0];
        $targetTableConnection = $targetTableNameAndConnection[1];
        if ($targetTableName === "") {
            return -1;
        }
//        Log::write('debug', "targetTableName: $targetTableName");
        $remoteConnection = ConnectionManager::get($targetTableConnection);
        $targetTable = TableRegistry::get($targetTableName, ['connection' => $remoteConnection]);
        try {
            // Start a database transaction
            $whereCondition = ['academic_period_id' => $academicPeriodId];
            return self::moveRecords($sourceTable, $targetTable, $whereCondition,
                $table_name, $targetTableName, $targetTableConnection,
                $caller);
        } catch (\Exception $e) {
            $caller->out("Error in $processName");
            $caller->out($e->getMessage());
            $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
            $caller->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $caller->out("System process failed $processName:  $processedDateTime");
            throw $e;
        }
    }


    /**
     * @param $sourceTable
     * @param $targetTable
     * @param $whereCondition
     * @param $table_name
     * @param $targetTableName
     * @param $targetTableConnection
     * @param $caller
     * @return bool
     * @throws \Exception
     */
    public static function moveRecords($sourceTable, $targetTable,
                                       $whereCondition, $table_name,
                                       $targetTableName, $targetTableConnection,
                                       $caller): bool
    {
        $pid = $caller->pid;
        $processName = $caller->processName;
        $systemProcessId = $caller->systemProcessId;
        $academic_period_id = $whereCondition['academic_period_id'];
        $totalRecords = self::getTableRecordsCountForAcademicPeriod($table_name, $academic_period_id);
        try {
            $connection = ConnectionManager::get($targetTableConnection);
            $batchSize = intval(($totalRecords / 100) + 1);
            // Disable foreign key checks
            $connection->execute("SET FOREIGN_KEY_CHECKS = 0");

            // Disable keys on target table
            $connection->execute("ALTER TABLE $targetTableName DISABLE KEYS");

            $i = 1;
            $baseCount = intval($caller->recordsToArchiveTotal);
            $baseCountStr = number_format($baseCount, 0, '', ' ');
            for ($offset = 0; $offset < $totalRecords; $offset += $batchSize) {
                // Build and execute batch insert query
                $sql = "INSERT IGNORE INTO $targetTableName SELECT * FROM $table_name where academic_period_id = $academic_period_id LIMIT $batchSize OFFSET $offset";
                $affectedRecordsCount = $connection->execute($sql)->rowCount();
                $caller->recordsInArchive = $caller->recordsInArchive + $affectedRecordsCount;
                // Commit transaction
                // Update affected records count and log progress;
                $proc = "Copy step:";
                self::setTransferLogsBatch($caller,
                    $i, $proc, $baseCount, $baseCountStr);
                $i++;
            }

            // Enable keys on target table
            $connection->execute("ALTER TABLE $targetTableName ENABLE KEYS");

            // Enable foreign key checks
            $i = 1;

            for ($offset = 0; $offset < $totalRecords; $offset += $batchSize) {
                $sql = "DELETE FROM $table_name where academic_period_id = $academic_period_id LIMIT $batchSize";
                $affectedBatchRows = $connection->execute($sql)->rowCount();
                $caller->recordsToArchive = $caller->recordsToArchive - $affectedBatchRows;
                $proc = "Delete step:";
                self::setTransferLogsBatch($caller,
                    $i, $proc, $baseCount, $baseCountStr);
                $i++;
            }
            $sourceTable->deleteAll($whereCondition);
            $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
            return true;
        } catch (\Exception $e) {
            Log::write('error', 'I have BAD exception in move records: ' . $e->getMessage());
            $caller->out("Error in $processName");
            $caller->out($e->getMessage());
            $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
            $caller->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $caller->out("System process failed $processName:  $processedDateTime");
            // Enable keys on target table
            $connection->execute("ALTER TABLE $targetTableName ENABLE KEYS");
            // Enable foreign key checks
            $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
            throw $e;
            return false;
        }
        return false;
    }


    /**
     * @param $academicPeriodId
     * @param $mypid
     * @return array
     */
    public static
    function startArchiveTransferSystemProcess($academicPeriodId, $mypid, $name, $pid)
    {
        $param = [
            'academicPeriodId' => $academicPeriodId,
            'pid' => $pid,
        ];
        $model = TableRegistry::get('Archive.TransferLogs');
        $eventName = '';
        $processModel = $model->registryAlias();
        $param = json_encode($param);
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $systemProcessId = $SystemProcesses->addProcess($name, $mypid, $processModel, $eventName, $param);
        return $systemProcessId;
    }

    /**
     * @param $systemProcessId
     */
    public static
    function setSystemProcessRunning($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::RUNNING, 1);
        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
    }

    /**
     * @param $systemProcessId
     */
    public static
    function setSystemProcessCompleted($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::COMPLETED);
        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
    }

    /**
     * @param $systemProcessId
     */
    public static
    function setTransferLogsCompleted($pid)
    {
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $processInfo = date('Y-m-d H:i:s');
        $transferlog = $TransferLogs
            ->find('all')
            ->where(['p_id' => $pid])->first();
        $moved = $transferlog->features;
        $moved = trim($moved) . ' Finished at: ' . $processInfo;
        $transferlog->features = $moved;
        $transferlog->process_status = $TransferLogs::DONE;
        $TransferLogs->save($transferlog);
        return $processInfo;
    }


    /**
     * @param $systemProcessId
     */
    public static
    function setTransferLogsFailed($pid)
    {
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $processInfo = date('Y-m-d H:i:s');
        $transferlog = $TransferLogs
            ->find('all')
            ->where(['p_id' => $pid])->first();
        $moved = $transferlog->features;
        $moved = trim($moved) . ' Stopped at: ' . $processInfo;
        $transferlog->features = $moved;
        $transferlog->process_status = $TransferLogs::ERROR;
        return $processInfo;
    }

    /**
     * @param $systemProcessId
     */
    public static
    function setSystemProcessFailed($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
    }

    /**
     * @param $table_name
     * @param $academic_period_id
     * @return int
     * POCOR-7521-KHINDOL
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * cleaner code
     */
    private static function getTableRecordsCountForAcademicPeriod($table_name, $academic_period_id)
    {
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
        $connection = ConnectionManager::get($connectionName);
        $sql = "SELECT count(*) as count FROM $tableName WHERE $fieldName = :fieldValue";
        $result = $connection->execute($sql, ['fieldValue' => $fieldValue])->fetch('assoc');
        return intval($result['count']);
    }


}