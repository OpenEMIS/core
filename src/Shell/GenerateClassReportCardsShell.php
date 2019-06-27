<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;

class GenerateClassReportCardsShell extends Shell
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
            
            $institutionId = !empty($this->args[0]) ? $this->args[0] : 0;
            $institutionClassId = !empty($this->args[1]) ? $this->args[1] : 0;
            $this->out('Initialize Generate All Report Cards of Class ('.Time::now().')');
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
                        $this->ReportCardProcesses->aliasField('status') => $this->ReportCardProcesses::NEW_PROCESS,
                        $this->ReportCardProcesses->aliasField('institution_id') => $institutionId,
                        $this->ReportCardProcesses->aliasField('institution_class_id') => $institutionClassId
                    ])
                    ->order([
                        $this->ReportCardProcesses->aliasField('created'),
                        $this->ReportCardProcesses->aliasField('status')
                    ])
                    ->hydrate(false)
                    ->first();

                if (!empty($recordToProcess)) {
                    $this->out('Generating report card of Class for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');
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
                        $this->out('Error generating Report Card of Class for Student ' . $recordToProcess['student_id']);
                        $this->out($e->getMessage());
                    }

                    $this->out('End generating report card of Class for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');
                } else {
                    $exit = true;
                }
            }

            $this->out('End Generate All Report Cards of Class ('.Time::now().')');
        }
    }
}
