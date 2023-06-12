<?php
namespace App\Shell;

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

class StudentAttendanceShell extends Shell
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
        $this->out('Initializing Archiving Student Attendance of data (' . Time::now() . ')');
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

    
    public function moveRecordsToArchive($academicPeriodId, $pid){
        //POCOR-7474-HINDOL get rid of unused connection to backup table and old comments
        $connection = ConnectionManager::get('default');
        $count = 0;
        $sourceTable = TableRegistry::get('institution_class_attendance_records');
        $targetTableExists = $this->hasArchiveTable($sourceTable);

        if ($targetTableExists) {
            $adding = $connection->execute("INSERT IGNORE INTO `institution_class_attendance_records_archived` SELECT * FROM `institution_class_attendance_records` WHERE academic_period_id = $academicPeriodId");
            $connection->execute("DELETE FROM institution_class_attendance_records WHERE academic_period_id = $academicPeriodId");
            $count = $count + $adding->rowCount();
        }
        $sourceTable = null;

        $sourceTable = TableRegistry::get('institution_student_absences');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        if ($targetTableExists) {
            $adding = $connection->execute("INSERT IGNORE INTO `institution_student_absences_archived` SELECT * FROM `institution_student_absences` WHERE academic_period_id = $academicPeriodId");
            $connection->execute("DELETE FROM institution_student_absences WHERE academic_period_id = $academicPeriodId");
            $count = $count + $adding->rowCount();
        }
        $sourceTable = null;

        $sourceTable = TableRegistry::get('institution_student_absence_details');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        if ($targetTableExists) {
            $adding = $connection->execute("INSERT IGNORE INTO `institution_student_absence_details_archived` SELECT * FROM `institution_student_absence_details` WHERE academic_period_id = $academicPeriodId");
            $connection->execute("DELETE FROM institution_student_absence_details WHERE academic_period_id = $academicPeriodId");
            $count = $count + $adding->rowCount();
        }
        $sourceTable = null;

        $sourceTable = TableRegistry::get('student_attendance_marked_records');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        if ($targetTableExists) {
            $adding = $connection->execute("INSERT IGNORE INTO `student_attendance_marked_records_archived` SELECT * FROM `student_attendance_marked_records` WHERE academic_period_id = $academicPeriodId");
            $connection->execute("DELETE FROM student_attendance_marked_records WHERE academic_period_id = $academicPeriodId");
            $count = $count + $adding->rowCount();
        }
        $sourceTable = null;

        $sourceTable = TableRegistry::get('student_attendance_mark_types');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        if ($targetTableExists) {
            $adding = $connection->execute("INSERT IGNORE INTO `student_attendance_mark_types_archived` SELECT * FROM `student_attendance_mark_types` WHERE academic_period_id = $academicPeriodId");
            $connection->execute("DELETE FROM student_attendance_mark_types WHERE academic_period_id = $academicPeriodId");
            $count = $count + $adding->rowCount();
        }
        $sourceTable = null;

        return $count;
    }

    public function hasArchiveTable($sourceTable)
    {
        $sourceTableName = $sourceTable->table();
        $targetTableName = $sourceTableName . '_archived';
        $connection = ConnectionManager::get('default');
        $schemaCollection = new \Cake\Database\Schema\Collection($connection);
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
        $randomString = $this->generateRandomString();
        // Copy the indexes from the source table to the target table
        foreach ($sourceTableSchema->indexes() as $index) {
            $indexDefinition = $sourceTableSchema->index($index);
            $targetTableSchema->addIndex($index . $randomString, $indexDefinition);
        }

        // Copy the constraints from the source table to the target table
        // FIX for random FK name

        foreach ($sourceTableSchema->constraints() as $constraint) {
            $constraintDefinition = $sourceTableSchema->constraint($constraint);
            $targetTableSchema->addConstraint($constraint . $randomString, $constraintDefinition);
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

    private function generateRandomString($length = 4) {
        $bytes = random_bytes($length);
        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
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
        $name = 'Archive Student Attendances';
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