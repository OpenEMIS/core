<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;

class GenerateAllReportCardsShell extends Shell
{
    private $sleepTime = 5;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('CustomExcel.ReportCards');
        $this->loadModel('ReportCard.ReportCardProcesses');
        $this->loadModel('Institution.ReportCardStatuses');
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        if (!empty($this->args[0])) {
            $pid = getmypid();
            $systemProcessId = !empty($this->args[0]) ? $this->args[0] : 0;
            $this->SystemProcesses->updatePid($systemProcessId, $pid);

            $maxProcesses = $this->ReportCardStatuses::MAX_PROCESSES;

            $this->out('Initialize Generate All Report Cards ('.Time::now().')');

            $exit = false;
            while (!$exit) {
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

                if (!empty($recordToProcess)) {
                    $runningCount = $this->ReportCardProcesses->find()
                        ->where([$this->ReportCardProcesses->aliasField('status') => $this->ReportCardProcesses::RUNNING])
                        ->count();

                    if ($runningCount < $maxProcesses) {
                        $this->out('Generating report card for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');
                        $this->ReportCardProcesses->updateAll(['status' => $this->ReportCardProcesses::RUNNING], [
                            'report_card_id' => $recordToProcess['report_card_id'],
                            'institution_class_id' => $recordToProcess['institution_class_id'],
                            'student_id' => $recordToProcess['student_id']
                        ]);

                        $excelParams = new ArrayObject([]);
                        $excelParams['className'] = 'CustomExcel.ReportCards';
                        $excelParams['requestQuery'] = $recordToProcess;

                        try {
                            $this->ReportCards->renderExcelTemplate($excelParams);
                        } catch (\Exception $e) {
                            $this->out('Error generating Report Card for Student ' . $recordToProcess['student_id']);
                            $this->out($e->getMessage());
                        }

                        $this->out('End generating report card for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');
                    } else {
                        sleep($this->sleepTime);
                    }
                } else {
                    $exit = true;
                    $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
                }
            }

            $this->out('End Generate All Report Cards ('.Time::now().')');
        }
    }
}
