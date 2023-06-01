<?php

namespace App\Shell;

//use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
//use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

//use Cake\I18n\Date;
//use Cake\Utility\Security;
//use PDOException;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\Table;

class StudentAssessmentsShell extends Shell
{
    public function initialize()
    {
        //POCOR-7339-HINDOL cleaned the code
        parent::initialize();
        $this->loadModel('SystemProcesses');
        $this->loadModel('Archive.TransferLogs');
    }

    public function main()
    {
        $args = $this->args;
        $academicPeriodId = !empty($args[0]) ? intval(trim($args[0])) : 0;
        $pid = !empty($args[1]) ? intval(trim($args[1])) : getmypid();
        $this->out("academic period id: $academicPeriodId, process id : $pid");

        if ($academicPeriodId === 0) {
            //academic period is not shown
            $this->out('No valid academic period given (' . Time::now() . ')');
            return;
        }
        $this->out('Initializing Archiving Student Assessments of data (' . Time::now() . ')');
        $mypid = getmypid();

        $systemProcessId = $this->startSystemProcess($academicPeriodId, $mypid);
        $this->setSystemProcessRunning($systemProcessId);
//        $countOfArchivedRecords = 1;
        $countOfArchivedRecords = $this->moveRecordsToArchive($academicPeriodId, $pid);
        $this->out("Count of archived records: $countOfArchivedRecords");

        if ($countOfArchivedRecords) {
            try {
                $this->setTransferLogsCompleted($pid);
                $this->out('Dispatching event to update Archiving Student Assessments');
                $this->out('End Update for Archiving Student Assessments (' . Time::now() . ')');
            } catch (\Exception $e) {
                $this->out('Error in Archiving Student Assessments');
                $this->out($e->getMessage());
                $this->setSystemProcessFailed($systemProcessId);
            }
        } else {
            $this->out('No records to update (' . Time::now() . ')');
        }
        $this->out('End Archiving Student Assessments Status (' . Time::now() . ')');

        $this->setSystemProcessCompleted($systemProcessId);
    }


    public
    function moveRecordsToArchive($academicPeriodId)
    {
        //POCOR-7339-HINDOL

        $sourceTable = TableRegistry::get('Institution.AssessmentItemResults');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        if (!$targetTableExists) {
            return 0;
        }
        $targetTable = TableRegistry::get('Institution.AssessmentItemResultsArchived');
        try {
            // Start a database transaction
            $whereCondition = ['academic_period_id' => $academicPeriodId];
            $records_count = $this->moveRecords($sourceTable, $targetTable, $whereCondition);
            $this->out("I have $records_count in moveRecordsToArchive");
            return $records_count;
        } catch (\Exception $e) {
            // An error occurred, rollback the transaction
            $this->out($e->getMessage());
            return 0;
        }
        return $records_count;
    }

    /**
     * @param $academicPeriodId
     * @param $mypid
     * @return array
     */

    public
    function startSystemProcess($academicPeriodId, $mypid)
    {
        $param = [
            'academicPeriodId' => $academicPeriodId,
//            'academicPeriodId' => $academicPeriodId,
        ];
        $name = 'Archive Student Assessments';
        $model = TableRegistry::get('Archive.TransferLogs');
        $eventName = '';
        $processModel = $model->registryAlias();
        $param = json_encode($param);
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $systemProcessId = $SystemProcesses->addProcess($name, $mypid, $processModel, $eventName, $param);
        $processInfo = date('d-m-Y H:i:s') . ' : ' . $name;
        $this->out($processInfo . ' - Start System PID:' . $systemProcessId);
        return $systemProcessId;
    }

    /**
     * @param $systemProcessId
     */
    public
    function setSystemProcessRunning($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $processInfo = date('d-m-Y H:i:s');
        $this->out($processInfo . ' - Running System PID:' . $systemProcessId);
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::RUNNING, 1);
    }

    /**
     * @param $systemProcessId
     */
    public
    function setSystemProcessFailed($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $processInfo = date('d-m-Y H:i:s');
        $this->out($processInfo . ' - Error in System PID:' . $systemProcessId);
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
    }

    /**
     * @param $systemProcessId
     */
    public
    function setSystemProcessCompleted($systemProcessId)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $processInfo = date('d-m-Y H:i:s');
        $this->out($processInfo . ' - Completed System PID:' . $systemProcessId);
        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::COMPLETED);
    }

    /**
     * @param $systemProcessId
     */
    public
    function setTransferLogsCompleted($pid)
    {
        $TransferLogs = TableRegistry::get('Archive.TransferLogs');
        $processInfo = date('d-m-Y H:i:s');
        $this->out($processInfo . ' - set Transfer Logs Completed PID:' . $pid);
        $TransferLogs->updateAll(['process_status' => $TransferLogs::DONE], [
            'p_id' => $pid
        ]);

    }

    /**
     * Proc checks, if not - creates an archive table
     * @param $sourceTable
     * @return bool
     */


    public function hasArchiveTable($sourceTable)
    {
        $sourceTableName = $sourceTable->table();
        $targetTableName = $sourceTableName . '_archived';
        $connection = ConnectionManager::get('default');
        $schemaCollection = new Collection($connection);
        $existingTables = $schemaCollection->listTables();
        $tableExists = in_array($targetTableName, $existingTables);

        if ($tableExists) {
            return true;
        }

        $sourceTableSchema = $schemaCollection->describe($sourceTableName);

        // Create a new table schema for the target table
        $targetTableSchema = new Table($targetTableName);

        // Copy the columns from the source table to the target table
        foreach ($sourceTableSchema->columns() as $column) {
            $columnDefinition = $sourceTableSchema->column($column);
            $targetTableSchema->addColumn($column, $columnDefinition);
        }

        // Copy the indexes from the source table to the target table
        foreach ($sourceTableSchema->indexes() as $index) {
            $indexDefinition = $sourceTableSchema->index($index);
            $targetTableSchema->addIndex($index, $indexDefinition);
        }

        // Copy the constraints from the source table to the target table
        foreach ($sourceTableSchema->constraints() as $constraint) {
            $constraintDefinition = $sourceTableSchema->constraint($constraint);
            $targetTableSchema->addConstraint($constraint, $constraintDefinition);
        }

        // Generate the SQL statement to create the target table
        $createTableSql = $targetTableSchema->createSql($connection);

        // Execute the SQL statement to create the target table
        foreach ($createTableSql as $sql) {
            $connection->execute($sql);
        }

        // Check if the target table was created successfully
        $existingTables = $schemaCollection->listTables();
        $tableExists = in_array($targetTableName, $existingTables);
        if ($tableExists) {
            return true;
        }

        return false; // Return false if the table couldn't be created
    }


    public function moveRecords($sourceTable, $targetTable, $whereCondition)
    {
        $affectedRecordsCount = 0;
        $connection = ConnectionManager::get('default');
        $connection->transactional(function ($connection) use ($sourceTable, $targetTable, $whereCondition, &$affectedRecordsCount) {
            try {
                $sourceQuery = $sourceTable->find()->where($whereCondition);
                $sql = $sourceQuery->sql();
//                $this->out($sql);
                $count = $sourceQuery->count();
                $this->out("I found $count records to copy");
                $matchingRecords = $sourceQuery->all();
                foreach ($matchingRecords as $record) {
                    $newRecord = $targetTable->newEntity($record->toArray());
                    $targetTable->save($newRecord);
                }

                $newCount = $targetTable->find()->where($whereCondition)->count();
                $this->out("I found $newCount records copied");
                if ($count === $newCount) {
                    $this->out("OK $newCount copied");
                    $sourceTable->deleteAll($whereCondition);
                    $affectedRecordsCount = $newCount;
                    return true;
                } else {
                    $this->out("$count != $newCount");
                    return false;
                }
            } catch (\Exception $e) {
                $this->out('I have exception: ' . $e->getMessage());
                return false;
            }
        });

        return $affectedRecordsCount;
    }
}