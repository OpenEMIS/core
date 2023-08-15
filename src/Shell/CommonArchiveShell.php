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

    public static
    function setTransferLogsBatch($pid, $movedRecordsCount = 0, $step = 0)
    {
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $transferlog = $TransferLogs
            ->find('all')
            ->where(['p_id' => $pid])->first();
        $moved = $transferlog->features;
        $movedRecordsCountStr = number_format($movedRecordsCount, 0, '', ' ');
        if (strpos($moved, 'Moved') === false) {
            // If "Moved Records" string doesn't exist, add it with the batch number
            $moved = trim($moved) . " Step: $step, Moved: $movedRecordsCount"; // Replace 5000 with the actual number
        } else {
            // If "Moved Records" string already exists, update the batch number
            $moved = preg_replace
            ('/Moved: (\d+)/', 'Moved: ' . $movedRecordsCount,
                $moved, 1);
            $moved = preg_replace
            ('/Step: (\d+)/', 'Step: ' . $step,
                $moved, 1);
        }
        $transferlog->features = $moved;
        try {
            $TransferLogs->save($transferlog);
        } catch (\Exception $e) {
            Log::write('error', 'Error executing batch: ' . $e->getMessage());
            throw $e;
        }

        $processInfo = date('Y-m-d H:i:s');
        return $processInfo;
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

        $totalRecords = $sourceTable->find()->where($whereCondition)->count();
        try {
            $connection = ConnectionManager::get('default');
            $academic_period_id = $whereCondition['academic_period_id'];
            $batchSize = intval(($totalRecords / 100) + 1);
            // Disable foreign key checks
            $connection->execute("SET FOREIGN_KEY_CHECKS = 0");

            // Disable keys on target table
            $connection->execute("ALTER TABLE $targetTableName DISABLE KEYS");

            $affectedRecordsCount = 0;
            $i = 1;
            for ($offset = 0; $offset < $totalRecords; $offset += $batchSize) {
                // Build and execute batch insert query
                $sql = "INSERT IGNORE INTO $targetTableName SELECT * FROM $table_name where academic_period_id = $academic_period_id LIMIT $batchSize OFFSET $offset";
                $affectedBatchRows = $connection->execute($sql)->rowCount();

                // Commit transaction
                $connection->commit();

                // Update affected records count and log progress
                $affectedRecordsCount += $affectedBatchRows;
                self::setTransferLogsBatch($pid, $affectedRecordsCount, $i);
                $i++;
            }

            // Enable keys on target table
            $connection->execute("ALTER TABLE $targetTableName ENABLE KEYS");

            // Enable foreign key checks
            $countInArchive = $targetTable->find()->where($whereCondition)->count();
            if ($countInArchive >= $totalRecords) {
                $i = 1;
                $left = $totalRecords;
                for ($offset = 0; $offset < $totalRecords; $offset += $batchSize) {

//                    $sourceTable->deleteAll($whereCondition)
//                        ->where($whereCondition)
//                        ->limit($batchSize)
//                        ->offset($offset - $batchSize)
//                        ->execute();
                    $sql = "DELETE FROM $table_name where academic_period_id = $academic_period_id LIMIT $batchSize";
                    $affectedBatchRows = $connection->execute($sql)->rowCount();
                    $left = $left - $affectedBatchRows;
                    $i++;
                    $caller->out("$i . Deleted $affectedBatchRows . Left $left");
                }
                $sourceTable->deleteAll($whereCondition);
                $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
                return $totalRecords;
            } else {
                $caller->out("Error in $processName");
                $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
                $caller->out("Transfer failed $processName:  $processedDateTime");
                $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
                $caller->out("System process failed $processName:  $processedDateTime");
                $connection->execute("ALTER TABLE $targetTableName ENABLE KEYS");
                $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
            }
        } catch (\Exception $e) {
            Log::write('error', 'I have BAD exception in move records: ' . $e->getMessage());
            $caller->out("Error in $processName");
            $caller->out($e->getMessage());
            $processedDateTime = CommonArchiveShell::setTransferLogsFailed($pid);
            $caller->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = CommonArchiveShell::setSystemProcessFailed($systemProcessId);
            $caller->out("System process failed $processName:  $processedDateTime");
            try{
                // Enable keys on target table
                $connection->execute("ALTER TABLE $targetTableName ENABLE KEYS");
                // Enable foreign key checks
                $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
            }catch (\Exception $xe){

            }
            throw $e;
        }
        return $affectedRecordsCount;
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
    function setTransferLogsCompleted($pid, $movedRecordsCount = 0)
    {
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $processInfo = date('Y-m-d H:i:s');
        $transferlog = $TransferLogs
            ->find('all')
            ->where(['p_id' => $pid])->first();
        $moved = $transferlog->features;
        if (strpos($moved, 'Moved') === false) {
            // If "Moved Records" string doesn't exist, add it with the batch number
            $moved = trim($moved) . '. Finally: ' . number_format($movedRecordsCount, 0, '', ' ') . ' at '. $processInfo;
        } else {
            // If "Moved Records" string already exists, update the batch number
            $moved = preg_replace
            ('/(Moved: \d+)/', 'Finally: ' . number_format($movedRecordsCount, 0, '', ' ') . ' at '. $processInfo,
                $moved, 1);
        }

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