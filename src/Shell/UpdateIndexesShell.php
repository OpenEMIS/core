<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Log\Log;

class UpdateIndexesShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionStudentIndexes');
        $this->loadModel('Institution.InstitutionIndexes');
        $this->loadModel('Institution.StudentIndexesCriterias');
        $this->loadModel('Indexes.IndexesCriterias');
        $this->loadModel('Indexes.Indexes');
        $this->loadModel('Institution.Students');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
    }

    public function main()
    {
        $institutionId = !empty($this->args[0]) ? $this->args[0] : 0;
        $userId = !empty($this->args[1]) ? $this->args[1] : 0;
        $indexesId = !empty($this->args[2]) ? $this->args[2] : 0;
        $academicPeriodId = !empty($this->args[3]) ? $this->args[3] : 0;

        $indexesCriteriaData = $this->IndexesCriterias->getCriteriaKey($indexesId);

        if (!empty($indexesCriteriaData)) {
            foreach ($indexesCriteriaData as $key => $obj) {
                $criteriaData = $this->Indexes->getCriteriasDetails($key);

                // for cli-debug.log to see still updating
                Log::write('debug', 'Criteria: '. $key);
                // end debug

                $this->autoUpdateIndexes($key, $criteriaData['model'], $institutionId, $userId, $academicPeriodId, $indexesId);
            }
        }

        // update the generated_by and generated_on in indexes table
        $this->InstitutionIndexes->updateAll(
            [
                'generated_by' => $userId,
                'generated_on' => new Time(),
                'pid' => NULL,
                'status' => 3 // completed
            ],
            ['index_id' => $indexesId, 'institution_id' => $institutionId]
        );
    }

    public function autoUpdateIndexes($key, $model, $institutionId, $userId, $academicPeriodId, $indexesId)
    {
        $today = Time::now();
        $CriteriaModel = TableRegistry::get($model);

        // get the list of enrolled student in the institution in academic period
        $institutionStudentsResults = $this->Students->find()
            ->where([
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'student_status_id' => 1 //enrolled status
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
                    $CriteriaModel->aliasField('start_date') . ' >= ' => $academicPeriodStartDate,
                    $CriteriaModel->aliasField('end_date') . ' <= ' => $academicPeriodEndDate,
                    $CriteriaModel->aliasField('absence_type_id') => 1 // excused
                ];
                break;

            case 'AbsencesUnexcused': // no academic_period_id (within start and end date of the academic period)
                $academicPeriodDetails = $this->AcademicPeriods->get($academicPeriodId);
                $academicPeriodStartDate = $academicPeriodDetails->start_date;
                $academicPeriodEndDate = $academicPeriodDetails->end_date;

                $condition = [
                    $CriteriaModel->aliasField('institution_id') => $institutionId,
                    $CriteriaModel->aliasField('start_date') . ' >= ' => $academicPeriodStartDate,
                    $CriteriaModel->aliasField('end_date') . ' <= ' => $academicPeriodEndDate,
                    $CriteriaModel->aliasField('absence_type_id') => 2 // unexcused
                ];
                break;

            case 'AbsencesLate': // no academic_period_id (within start and end date of the academic period)
                $academicPeriodDetails = $this->AcademicPeriods->get($academicPeriodId);
                $academicPeriodStartDate = $academicPeriodDetails->start_date;
                $academicPeriodEndDate = $academicPeriodDetails->end_date;

                $condition = [
                    $CriteriaModel->aliasField('institution_id') => $institutionId,
                    $CriteriaModel->aliasField('start_date') . ' >= ' => $academicPeriodStartDate,
                    $CriteriaModel->aliasField('end_date') . ' <= ' => $academicPeriodEndDate,
                    $CriteriaModel->aliasField('absence_type_id') => 3 // late
                ];
                break;

            case 'SpecialNeeds': // no institution_id, no academic_period_id
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
            Log::write('debug', 'Student id: '. $studentId);
            // end debug

            // will triggered the aftersave of the model (indexes behavior)
            $criteriaModelEntity->dirty('modified_user_id', true);
            $CriteriaModel->save($criteriaModelEntity);

            // update the institution student index
            $this->InstitutionStudentIndexes->query()
                ->update()
                ->set([
                    'created_user_id' => $userId,
                    'created' => $today
                ])
                ->execute();

            // update the student indexes criteria
            $this->StudentIndexesCriterias->query()
                ->update()
                ->set([
                    'created_user_id' => $userId,
                    'created' => $today
                ])
                ->execute();
        }
    }
}
