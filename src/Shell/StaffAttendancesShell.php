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

class StaffAttendancesShell extends Shell
{

    public function initialize()
    {
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
        $this->out('Initializing Archiving Staff Absences and Leaves of data (' . Time::now() . ')');
        $mypid = getmypid();

        $systemProcessId = $this->startSystemProcess($academicPeriodId, $mypid);
        $this->setSystemProcessRunning($systemProcessId);
//        $countOfArchivedRecords = 1;
        $archivedRecords = $this->moveRecordsToArchive($academicPeriodId, $pid);
        //['moved_leaves' => $moved_leaves, 'moved_attendances' => $moved_attendances];
        $moved_leaves = $archivedRecords['moved_leaves'];
        $moved_attendances = $archivedRecords['moved_attendances'];
        $this->out("Count of archived leaves: $moved_leaves");
        $this->out("Count of archived attendances: $moved_attendances");
        $this->log("Count of archived leaves: $moved_leaves", 'debug');
        $this->log("Count of archived attendances: $moved_attendances", 'debug');
        $countOfArchivedRecords = $moved_leaves + $moved_attendances;
        if ($countOfArchivedRecords >= 0) {
            try {
                $this->setTransferLogsCompleted($pid);
                $this->out('Dispatching event to update Archiving Staff Absences and Leaves');
                $this->out('End Update for Archiving Staff Absences and Leaves (' . Time::now() . ')');
            } catch (\Exception $e) {
                $this->out('Error in Archiving Staff Absences and Leaves');
                $this->out($e->getMessage());
                $this->setSystemProcessFailed($systemProcessId);
            }
        } else {
            $this->out('No records to update (' . Time::now() . ')');
        }
        $this->out('End Archiving Staff Absences and Leaves Status (' . Time::now() . ')');

        $this->setSystemProcessCompleted($systemProcessId);
    }


    public function moveRecordsToArchive($academicPeriodId, $pid)
    {
        //POCOR-7468-HINDOL a) removed code about remote backup db b)
        $connection = ConnectionManager::get('default');
        $sourceTable = TableRegistry::get('institution_staff_attendances');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        $sourceTable = null;
        $result = ['moved_leaves' => -1, 'moved_attendances' => -1];
        if (!$targetTableExists) {
            return $result;
        }
        $statement_a = $connection->execute("INSERT INTO `institution_staff_attendances_archive` SELECT * FROM `institution_staff_attendances` WHERE academic_period_id = $academicPeriodId");
        $moved_attendances = intval($statement_a->rowCount());
        $connection->execute("DELETE FROM institution_staff_attendances WHERE academic_period_id = $academicPeriodId");
        $this->log('staff attendances moved', 'debug');
        $result = ['moved_leaves' => -1, 'moved_attendances' => $moved_attendances];
        $sourceTable = TableRegistry::get('institution_staff_leave');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        $sourceTable = null;
        if (!$targetTableExists) {
            return $result;
        }
        $statement_l = $connection->execute("INSERT INTO `institution_staff_leave_archived` SELECT * FROM `institution_staff_leave` WHERE academic_period_id = $academicPeriodId");
        $moved_leaves = intval($statement_l->rowCount());
        $connection->execute("DELETE FROM institution_staff_leave WHERE academic_period_id = $academicPeriodId");
        //institution_staff_leave[END]
        return ['moved_leaves' => $moved_leaves, 'moved_attendances' => $moved_attendances];
    }

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
        $name = 'Archive Staff Absences and Leaves';
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


}