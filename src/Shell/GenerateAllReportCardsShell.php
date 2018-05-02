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
    private $maxProcesses = 1;
    private $sleepTime = 5;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionClassStudents');
        $this->loadModel('CustomExcel.ReportCards');
        $this->loadModel('ReportCard.ReportCardProcesses');
    }

    public function main()
    {
        if (!empty($this->args[0])) {
            $pid = getmypid();
            $institutionId = !empty($this->args[0]) ? $this->args[0] : 0;
            $institutionClassId = !empty($this->args[1]) ? $this->args[1] : 0;
            $reportCardId = !empty($this->args[2]) ? $this->args[2] : 0;

            $this->out($pid.': Initialize Generate All Report Cards '.$reportCardId.' for Class '.$institutionClassId.' ('.Time::now().')');

            $classStudents = $this->InstitutionClassStudents->find()
                ->where([$this->InstitutionClassStudents->aliasField('institution_class_id') => $institutionClassId])
                ->toArray();

            foreach ($classStudents as $student) {
                $idKeys = [
                    'report_card_id' => $reportCardId,
                    'institution_class_id' => $student->institution_class_id,
                    'student_id' => $student->student_id
                ];

                if (!$this->ReportCardProcesses->exists($idKeys)) {
                    $data = [
                        'status' => $this->ReportCardProcesses::NEW_PROCESS,
                        'institution_id' => $student->institution_id,
                        'education_grade_id' => $student->education_grade_id,
                        'academic_period_id' => $student->academic_period_id,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    $obj = array_merge($idKeys, $data);
                    $newEntity = $this->ReportCardProcesses->newEntity($obj);
                    $this->ReportCardProcesses->save($newEntity);
                }
            }

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
                        $this->ReportCardProcesses->aliasField('institution_class_id') => $institutionClassId,
                        $this->ReportCardProcesses->aliasField('report_card_id') => $reportCardId,
                        $this->ReportCardProcesses->aliasField('status') => $this->ReportCardProcesses::NEW_PROCESS
                    ])
                    ->hydrate(false)
                    ->first();

                if (!empty($recordToProcess)) {
                    $runningCount = $this->ReportCardProcesses->find()
                        ->where([$this->ReportCardProcesses->aliasField('status') => $this->ReportCardProcesses::RUNNING])
                        ->count();

                    if ($runningCount < $this->maxProcesses)    {
                        $this->out($pid.': Generating report card for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');
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
                            $this->out($pid.': Error generating Report Card for Student ' . $recordToProcess['student_id']);
                            $this->out($e->getMessage());
                        }

                        $this->out($pid.': End generating report card for Student '.$recordToProcess['student_id'].' ('. Time::now() .')');
                    } else {
                        sleep($this->sleepTime);
                    }
                } else {
                    $exit = true;
                }
            }

            $this->out($pid.': End Generate All Report Cards '.$reportCardId.' for Class '.$institutionClassId.' ('.Time::now().')');
        }
    }
}
