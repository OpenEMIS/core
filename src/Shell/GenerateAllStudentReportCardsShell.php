<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\FrozenTime;
use Cake\Console\Shell;

class GenerateAllStudentReportCardsShell extends Shell
{
    private $sleepTime = 5;

    public function initialize(): void
    {
        parent::initialize();
        $this->StudentReportCards = $this->fetchTable('CustomExcel.StudentReportCards');
        $this->StudentReportCardProcesses = $this->fetchTable('ReportCard.StudentReportCardProcesses');
        $this->SystemProcesses = $this->fetchTable('SystemProcesses');
    }

    public function main()
    {

        if (!empty($this->args[0]) && !empty($this->args[1])) {
            $systemProcessId = $this->SystemProcesses->addProcess('GenerateAllStudentReportCards', getmypid(), $this->args[0], '', $this->args[1]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);
            $recordToProcess = $this->StudentReportCardProcesses->find()
                ->select([
                    $this->StudentReportCardProcesses->aliasField('student_profile_template_id'),
                    $this->StudentReportCardProcesses->aliasField('student_id'),
                    $this->StudentReportCardProcesses->aliasField('institution_id'),
                    $this->StudentReportCardProcesses->aliasField('education_grade_id'),
                    $this->StudentReportCardProcesses->aliasField('academic_period_id')
                ])
                ->where([
                    $this->StudentReportCardProcesses->aliasField('status') => $this->StudentReportCardProcesses::NEW_PROCESS
                ])
                ->order([
                    $this->StudentReportCardProcesses->aliasField('created'),
                ])
                ->enableHydration(false)
                ->first();
            if (!empty($recordToProcess)) {
                $this->out('Generating report card for student '.$recordToProcess['student_id'].' ('. FrozenTime::now() .')');
                $this->StudentReportCardProcesses->updateAll(['status' => $this->StudentReportCardProcesses::RUNNING], [
                    'student_profile_template_id' => $recordToProcess['student_profile_template_id'],
                    'institution_id' => $recordToProcess['institution_id'],
                    'student_id' => $recordToProcess['student_id'],
                    'education_grade_id' => $recordToProcess['education_grade_id'],
                ]);

                $excelParams = new ArrayObject([]);
                $excelParams['className'] = 'CustomExcel.StudentReportCards';
                $excelParams['requestQuery'] = $recordToProcess;

                try {
                    $this->StudentReportCards->renderExcelTemplate($excelParams);
                } catch (\Exception $e) {
                    $this->out('Error generating Report Card for student ' . $recordToProcess['student_id']);
                    $this->out($e->getMessage());
                }

                $this->out('End generating report card for student '.$recordToProcess['student_id'].' ('. FrozenTime::now() .')');
                $this->SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $this->SystemProcesses::COMPLETED);
                $this->recursiveCallToMyself($this->args);
            } else {
                $this->SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $this->SystemProcesses::COMPLETED);
            }
        }

        try {
            $pid = getmypid();
            if (function_exists('posix_kill')) {
                posix_kill($pid, 9);
            } else {
                exec("kill -15 $pid"); // Works on Unix-like systems
            }
        } catch (\Exception $exception) {
            $this->out($exception->getMessage());
        }
    }

    private function recursiveCallToMyself($args)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllStudentReportCards '.$args[0] . " " . $args[1];
        $logs = ROOT . DS . 'logs' . DS . 'GenerateAllStudentReportCards.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $ex) {
            $this->out('error : ' . __METHOD__ . ' exception when recursiveCallToMyself : '. $ex);
        }
    }
}
