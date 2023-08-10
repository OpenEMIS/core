<?php

namespace App\Shell;

use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;

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

    /**
     * @param $academicPeriodId
     * @param $table_name
     * @return int Records moved | -1 if error
     *
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
            $records_count =
                self::moveRecords($sourceTable, $targetTable, $whereCondition,
                    $table_name, $targetTableName, $targetTableConnection,
                    $caller);
            return $records_count;
        } catch (\Exception $e) {
//            Log::write('error', $e->getMessage());
            $caller->out("Error in $processName");
            $caller->out($e->getMessage());
            $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
            $caller->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $caller->out("System process failed $processName:  $processedDateTime");
            throw $e;
//            return -1;
        }
        return $records_count;
    }


    public static function moveRecords($sourceTable, $targetTable,
                                       $whereCondition, $table_name,
                                       $targetTableName, $targetTableConnection,
                                       $caller)
    {
        $affectedRecordsCount = 0;
        $pid = $caller->pid;
        $processName = $caller->processName;
        $systemProcessId = $caller->systemProcessId;

//        Log::write('debug', "$table_name");
//        Log::write('debug', "$targetTableName");
//        $connection->transactional(function ($connection) use ($sourceTable, $targetTable, $whereCondition, &$affectedRecordsCount) {
        try {
            $countInArchive = 0;
            $sourceQuery = $sourceTable->find()->where($whereCondition);
            $countToArchive = $sourceQuery->count();
            $matchingRecords = $sourceQuery->all();
            if ($targetTableConnection != 'default') {
                foreach ($matchingRecords as $record) {
                    try {
                        $newRecord = $targetTable->newEntity($record->toArray());
                        $targetTable->save($newRecord);
                        $affectedRowsCount = $countInArchive + 1;
                    } catch (\Exception $e) {
                        Log::write('error', 'I have an exception: ' . $e->getMessage());
                        $caller->out("Error in $processName");
                        $caller->out($e->getMessage());
                        $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
                        $caller->out("Transfer failed $processName:  $processedDateTime");
                        $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                        $caller->out("System process failed $processName:  $processedDateTime");
                        throw $e;
                    }
                }
            } else {
                try {
                    $connection = ConnectionManager::get('default');
                    $academic_period_id = $whereCondition['academic_period_id'];
                    $sql = "INSERT INTO $targetTableName SELECT * FROM $table_name where academic_period_id = $academic_period_id";
                    $statement = $connection->execute($sql);
                    $affectedRowsCount = $statement->rowCount();
                } catch (\Exception $e) {
                    Log::write('error', 'I have an exception: ' . $e->getMessage());
                    $caller->out("Error in $processName");
                    $caller->out($e->getMessage());
                    $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
                    $caller->out("Transfer failed $processName:  $processedDateTime");
                    $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                    $caller->out("System process failed $processName:  $processedDateTime");
                    throw $e;
                }
            }
//            Log::write('debug', '$affectedRowsCount');
//            Log::write('debug', $affectedRowsCount);
            $countInArchive = $targetTable->find()->where($whereCondition)->count();
//            Log::write('debug', '$countToArchive');
//            Log::write('debug', $countToArchive);
//            Log::write('debug', '$countInArchive');
//            Log::write('debug', $countInArchive);
            if ($countInArchive >= $countToArchive) {
                $sourceTable->deleteAll($whereCondition);
                return $countToArchive;
            } else {
                $caller->out("Error in $processName");
                $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
                $caller->out("Transfer failed $processName:  $processedDateTime");
                $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                $caller->out("System process failed $processName:  $processedDateTime");
                return -1;
            }
        } catch (\Exception $e) {
            Log::write('error', 'I have BAD exception in move records: ' . $e->getMessage());
            $caller->out("Error in $processName");
            $caller->out($e->getMessage());
            $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
            $caller->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $caller->out("System process failed $processName:  $processedDateTime");
            throw $e;
//                return false;
        }
//        });

        return $affectedRecordsCount;
    }

    /**
     * @param $academicPeriodId
     * @param $mypid
     * @return array
     */
    public static
    function startArchiveTransferSystemProcess($academicPeriodId, $mypid, $name)
    {
        $param = [
            'academicPeriodId' => $academicPeriodId,
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
        $TransferLogs->updateAll(['process_status' => $TransferLogs::DONE],
            ['p_id' => $pid]
        );
        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
    }

    /**
     * @param $systemProcessId
     */
    public static
    function setTransferLogsFailed($pid)
    {
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $TransferLogs->updateAll(['process_status' => $TransferLogs::ERROR],
            ['p_id' => $pid]
        );
        $processInfo = date('Y-m-d H:i:s');
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

}