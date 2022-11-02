<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Log\Log;

class UpdateRisksShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionStudentRisks');
        $this->loadModel('Institution.InstitutionRisks');
        $this->loadModel('Institution.StudentRisksCriterias');
        $this->loadModel('Risk.RiskCriterias');
        $this->loadModel('Risk.Risks');
        $this->loadModel('Institution.Students');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
    }

    public function main()
    {
        $institutionId = !empty($this->args[0]) ? $this->args[0] : 0;
        $userId = !empty($this->args[1]) ? $this->args[1] : 0;
        $riskId = !empty($this->args[2]) ? $this->args[2] : 0;
        $academicPeriodId = !empty($this->args[3]) ? $this->args[3] : 0;

        $riskCriteriaData = $this->RiskCriterias->getCriteriaKey($riskId);

        if (!empty($riskCriteriaData)) {
            foreach ($riskCriteriaData as $key => $obj) {
                $criteriaData = $this->Risks->getCriteriasDetails($key);

                // for cli-debug.log to see still updating
                // Log::write('debug', 'Criteria: '. $key);
                // end debug                
                $this->autoUpdateRisks($key, $criteriaData['model'], $institutionId, $userId, $academicPeriodId);
            }
        }
        
        // update the generated_by and generated_on in indexes table
        $this->InstitutionRisks->updateAll(
            [
                'generated_by' => $userId,
                'generated_on' => new Time(),
                'pid' => null,
                'status' => 3 // completed
            ],
            ['risk_id' => $riskId, 'institution_id' => $institutionId]
        );
    }

    public function autoUpdateRisks($key, $model, $institutionId, $userId, $academicPeriodId)
    {
        $today = Time::now();
        $CriteriaModel = TableRegistry::get($model);

        // get the list of enrolled student in the institution in academic period
        $institutionStudentsResults = $this->Students->find()
            ->where([
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                // 'student_status_id' => 1 //enrolled status
            ])
            ->all();

        $institutionStudentsList = [];
        foreach ($institutionStudentsResults as $institutionStudentsResultsKey => $institutionStudentsResultsObj) {
            $institutionStudentsList[] = $institutionStudentsResultsObj->student_id;
        }
        // end get list

        switch ($key) {
            case 'AbsencesExcused': // no academic_period_id (within start and end date of the academic period)
                $academicPeriodDetails = $this->AcademicPeriods->get($academicPeriodId);
                $academicPeriodStartDate = $academicPeriodDetails->start_date;
                $academicPeriodEndDate = $academicPeriodDetails->end_date;

                $condition = [
                    $CriteriaModel->aliasField('institution_id') => $institutionId,
                    $CriteriaModel->aliasField('date') . ' >= ' => $academicPeriodStartDate,
                    $CriteriaModel->aliasField('date') . ' <= ' => $academicPeriodEndDate,
                    $CriteriaModel->aliasField('absence_type_id') => 1 // excused
                ];
                break;

            case 'AbsencesUnexcused': // no academic_period_id (within start and end date of the academic period)
                $academicPeriodDetails = $this->AcademicPeriods->get($academicPeriodId);
                $academicPeriodStartDate = $academicPeriodDetails->start_date;
                $academicPeriodEndDate = $academicPeriodDetails->end_date;

                $condition = [
                    $CriteriaModel->aliasField('institution_id') => $institutionId,
                    $CriteriaModel->aliasField('date') . ' >= ' => $academicPeriodStartDate,
                    $CriteriaModel->aliasField('date') . ' <= ' => $academicPeriodEndDate,
                    $CriteriaModel->aliasField('absence_type_id') => 2 // unexcused
                ];
                break;

            case 'AbsencesLate': // no academic_period_id (within start and end date of the academic period)
                $academicPeriodDetails = $this->AcademicPeriods->get($academicPeriodId);
                $academicPeriodStartDate = $academicPeriodDetails->start_date;
                $academicPeriodEndDate = $academicPeriodDetails->end_date;

                $condition = [
                    $CriteriaModel->aliasField('institution_id') => $institutionId,
                    $CriteriaModel->aliasField('date') . ' >= ' => $academicPeriodStartDate,
                    $CriteriaModel->aliasField('date') . ' <= ' => $academicPeriodEndDate,
                    $CriteriaModel->aliasField('absence_type_id') => 3 // late
                ];
                break;

            case 'SpecialNeedsAssessments': // no institution_id, no academic_period_id
                $condition = [$CriteriaModel->aliasField('security_user_id') . ' IN ' => $institutionStudentsList];
                break;

            default: // have institution_id and academic_period_id in the model table
                $condition = [
                    $CriteriaModel->aliasField('institution_id') => $institutionId,
                    $CriteriaModel->aliasField('academic_period_id') => $academicPeriodId
                ];
                break;
        }
        
        $criteriaModelResults = $CriteriaModel->find()
            ->where([$condition])
            ->all();
        
        foreach ($criteriaModelResults as $criteriaModelEntity) {
            $criteriaModelEntityId = $criteriaModelEntity->id;

            // for cli-debug.log to see still updating
            $studentId = $criteriaModelEntity->student_id;
            if ($key == 'SpecialNeedsAssessments') {
                $studentId = $criteriaModelEntity->security_user_id;
                $criteriaModelEntity['institution_id'] = $institutionId;
            }
            // Log::write('debug', 'Student id: '. $studentId);
            // end debug

            // will triggered the aftersave of the model (indexes behavior)
            $criteriaModelEntity->dirty('modified_user_id', true);
            $criteriaModelEntity->trigger_from = 'shell';
            $CriteriaModel->save($criteriaModelEntity);

            // update the institution student index
            $this->InstitutionStudentRisks->query()
                ->update()
                ->set([
                    'created_user_id' => $userId,
                    'created' => $today
                ])
                ->execute();

            // update the student indexes criteria
            $this->StudentRisksCriterias->query()
                ->update()
                ->set([
                    'created_user_id' => $userId,
                    'created' => $today
                ])
                ->execute();
        }
    }
}
