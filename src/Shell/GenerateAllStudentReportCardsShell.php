<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;

class GenerateAllStudentReportCardsShell extends Shell
{
    private $sleepTime = 5;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('CustomExcel.StudentReportCards');
        $this->loadModel('ReportCard.StudentReportCardProcesses');
        $this->loadModel('SystemProcesses');
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
                ->hydrate(false)
                ->first();

            if (!empty($recordToProcess)) {
                $this->out('Generating report card for student '.$recordToProcess['student_id'].' ('. Time::now() .')');
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

                $this->out('End generating report card for student '.$recordToProcess['student_id'].' ('. Time::now() .')');
                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
                $this->recursiveCallToMyself($this->args);
            } else {
                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
            }
        }
        posix_kill(getmypid(), SIGKILL);
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
