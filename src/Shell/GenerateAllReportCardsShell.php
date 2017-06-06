<?php
namespace App\Shell;

use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;
use CustomExcel\Controller\CustomExcelsController;

class GenerateAllReportCardsShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionClassStudents');
        $this->CustomExcels = new CustomExcelsController();
    }

    public function main()
    {
        if (!empty($this->args[0])) {
            $pid = getmypid();
            $institutionId = !empty($this->args[0]) ? $this->args[0] : 0;
            $institutionClassId = !empty($this->args[1]) ? $this->args[1] : 0;
            $reportCardId = !empty($this->args[2]) ? $this->args[2] : 0;

            $executedCount = 0;
            $this->out($pid.': Initialize Generate All Report Cards ('. Time::now() .')');

            $studentObj = [];
            $studentObj['report_card_id'] = $reportCardId;
            $studentObj['institution_id'] = $institutionId;
            $studentObj['institution_class_id'] = $institutionClassId;

            $classStudents = $this->InstitutionClassStudents->find()
                ->where([
                    $this->InstitutionClassStudents->aliasField('institution_id') => $institutionId,
                    $this->InstitutionClassStudents->aliasField('institution_class_id') => $institutionClassId
                ])
                ->toArray();

            $this->out($pid.': Generating report cards for '. count($classStudents) .' students ('. Time::now() .')');

            $excelParams = [];
            $excelParams['className'] = 'CustomExcel.ReportCards';

            foreach ($classStudents as $student) {
                $studentObj['student_id'] = $student->student_id;
                $studentObj['education_grade_id'] = $student->education_grade_id;
                $studentObj['academic_period_id'] = $student->academic_period_id;

                $excelParams['requestQuery'] = $studentObj;

                try {
                    $this->CustomExcels->ExcelReport->renderExcel($excelParams);

                } catch (\Exception $e) {
                    $this->out($pid.': Error generating Report Card for student id ' . $student->student_id);
                    $this->out($e->getMessage());
                }
            }

            $this->out($pid.': End Generate All Report Cards ('. Time::now() .')');
        }
    }
}
