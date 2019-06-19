<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;

class AutomateReportCardGenerationShell extends Shell
{
    CONST SLEEP_TIME = 5; // Default 30 seconds
    CONST EXPIRY_TIME = 5; // 10 for maldives & others it would be 30 
    CONST DEFAULT_ITERATION_TO_RUN = 40;  //  125 means 1hr for maldives for others 500 , this will run for 4 hours and each fault it finds, it resets back to 4 hours.
    CONST DEBUG = TRUE;
    CONST ACADEMIC_PERIOD_ID = 18;

    public function initialize()
    {
        if (self::DEBUG) {
            $this->out('initializing AutomateReportCardGenerationShell..' . ' (' . Time::now() .')');
        }
        parent::initialize();
        $this->loadModel('Institution.ReportCardStatuses');
        $this->loadModel('ReportCard.ReportCardProcesses');
    }

    public function main()
    {
        $this->_checkArgs();

        $iterationsToRun = $this->args[0];

        if ($iterationsToRun > 0 && count($this->_totalRunningSystemProcesses()) <= $this->ReportCardStatuses::MAX_PROCESSES) {
            $recordToProcess = $this->_getRecordToProcess();

            if (!empty($recordToProcess)) {
                $this->_executeAllReportCardsShell($recordToProcess);
                $this->_recursiveCall(self::DEFAULT_ITERATION_TO_RUN);
            }
        }
        $this->_recursiveCall($iterationsToRun - 1);
    }

    private function _checkArgs()
    {
        if (!isset($this->args[0])) {
            $this->_recursiveCall(self::DEFAULT_ITERATION_TO_RUN);
            exit();
        } else if ($this->args[0] <= 0) {
            exit();
        }
    }

    private function _recursiveCall($iterations)
    {
        sleep(self::SLEEP_TIME);
        if (self::DEBUG) {
            $this->out('recursive calling self.. Iteration: ' . $iterations);
        }
        $cmd = ROOT . DS . 'bin' . DS . 'cake AutomateReportCardGeneration ' . $iterations;
        $logs = ROOT . DS . 'logs' . DS . 'AutomateReportCardGeneration.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $ex) {
            $this->out('error : ' . __METHOD__ . ' exception when _recursiveCall : '. $ex);
        }
        exit();
    }

    private function _totalRunningSystemProcesses()
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->ReportCardStatuses->registryAlias());

        foreach ($runningProcess as $key => $processData) {
            $systemProcessId = $processData['id'];
            $pId = !empty($processData['process_id']) ? $processData['process_id'] : 0;
            $createdDate = $processData['created'];

            $expiryDate = clone($createdDate);
            $expiryDate->addMinutes(self::EXPIRY_TIME); 
            $today = Time::now();

            if ($expiryDate < $today) {
                $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::COMPLETED);
                $SystemProcesses->killProcess($pId);
            }
        }
        if (self::DEBUG) {
            $this->out('running process: ' . count($runningProcess));
        }
        return $runningProcess;
    }

    private function _getRecordToProcess()
    {
        $recordToProcess = $this->ReportCardProcesses->find()
            ->select([
                $this->ReportCardProcesses->aliasField('report_card_id'),
                $this->ReportCardProcesses->aliasField('institution_class_id'),
                $this->ReportCardProcesses->aliasField('student_id'),
                $this->ReportCardProcesses->aliasField('institution_id'),
                $this->ReportCardProcesses->aliasField('education_grade_id'),
                $this->ReportCardProcesses->aliasField('academic_period_id')
            ])
            ->where([
                $this->ReportCardProcesses->aliasField('status') => $this->ReportCardProcesses::NEW_PROCESS
            ])
            ->order([
                $this->ReportCardProcesses->aliasField('created'),
                $this->ReportCardProcesses->aliasField('student_id')
            ])
            ->hydrate(false)
            ->first();

        return $recordToProcess;
    }

    private function _executeAllReportCardsShell($recordToProcess)
    {
        if (self::DEBUG) {
            $this->out('executing report card shell..');
        }

        $args[0] = $this->ReportCardStatuses->registryAlias();

        $passArray = [
            'institution_id' => $recordToProcess['institution_id'],
            'institution_class_id' => $recordToProcess['institution_class_id'],
            'report_card_id' => $recordToProcess['report_card_id']
        ];

        if (!is_null($recordToProcess['student_id'])) {
            $passArray['student_id'] = $recordToProcess['student_id'];
        }

        $args[1] = json_encode($passArray);

        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllReportCards '.$args[0] . " " . $args[1];
        $logs = ROOT . DS . 'logs' . DS . 'GenerateAllReportCards.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $ex) {
            $this->out('error : ' . __METHOD__ . ' exception when _executeAllReportCardsShell : '. $ex);
        }
    }
}
