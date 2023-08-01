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
    function moveRecordsToArchive($academicPeriodId, $table_name)
    {
        //POCOR-7339-HINDOL
        $records_count = 0;

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
            $records_count = self::moveRecords($sourceTable, $targetTable, $whereCondition);
            return $records_count;
        } catch (\Exception $e) {
            Log::write('error', $e->getMessage());
//            return -1;
        }
        return $records_count;
    }


    public static function moveRecords($sourceTable, $targetTable, $whereCondition)
    {
        $affectedRecordsCount = 0;
        $connection = ConnectionManager::get('default');
//        Log::write('debug', "sourceTable");
//        Log::write('debug', "targetTable");
//        $connection->transactional(function ($connection) use ($sourceTable, $targetTable, $whereCondition, &$affectedRecordsCount) {
            try {
                $countInArchive = 0;
                $sourceQuery = $sourceTable->find()->where($whereCondition);
                $countToArchive = $sourceQuery->count();
                $matchingRecords = $sourceQuery->all();
//                $matchingRecordsCount = count($matchingRecords);
//                Log::write('debug', '$matchingRecordsCount');
//                Log::write('debug', $matchingRecordsCount);
                foreach ($matchingRecords as $record) {
//                    try {
                        $newRecord = $targetTable->newEntity($record->toArray());
                        $targetTable->save($newRecord);
                    $countInArchive = $countInArchive + 1;
//                    } catch (\Exception $e) {
//                        Log::write('error', 'I have an exception: ' . $e->getMessage());
//                    }
                }

//                $countInArchive = $targetTable->find()->where($whereCondition)->count();
//                Log::write('debug', '$countToArchive');
//                Log::write('debug', $countToArchive);
//                Log::write('debug', '$countInArchive');
//                Log::write('debug', $countInArchive);
                if ($countInArchive >= $countToArchive) {
                    $sourceTable->deleteAll($whereCondition);
//                    $affectedRecordsCount = $countInArchive;
                    return $countToArchive;
                } else {
                    return -1;
                }
            } catch (\Exception $e) {
                Log::write('error', 'I have BAD exception: ' . $e->getMessage());
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