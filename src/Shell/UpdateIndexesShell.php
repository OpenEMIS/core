<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;

class UpdateIndexesShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionStudentIndexes');
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
        $indexId = !empty($this->args[2]) ? $this->args[2] : 0;
        $academicPeriodId = !empty($this->args[3]) ? $this->args[3] : 0;

        // $indexesCriteriaData = $this->Indexes->getCriteriasData(); // all the criteria
        $indexesCriteriaData = $this->IndexesCriterias->getCriteriaKey($indexId);


        if (!empty($indexesCriteriaData)) {
            foreach ($indexesCriteriaData as $key => $obj) {
                $criteriaData = $this->Indexes->getCriteriasDetails($key);
                $this->autoUpdateIndexes($key, $criteriaData['model'], $institutionId, $userId, $academicPeriodId);
            }

            // update the generated_by and generated_on in indexes table
            $today = Time::now();
            $this->Indexes->query()
                ->update()
                ->set([
                    'generated_by' => $userId,
                    'generated_on' => $today
                ])
                ->execute();
        }
    }

    public function autoUpdateIndexes($key, $model, $institutionId=0, $userId=0, $academicPeriodId=0)
    {
        $today = Time::now();
        $CriteriaModel = TableRegistry::get($model);

        if (!empty($institutionId)) {
            $institutionStudentsResults = $this->Students->find()
                ->where([
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'student_status_id' => 1 //enrolled status
                ])
                ->all();

            // list of enrolled student in the institution in academic period
            $institutionStudentsList = [];
            foreach ($institutionStudentsResults as $institutionStudentsResultsKey => $institutionStudentsResultsObj) {
                $institutionStudentsList[] = $institutionStudentsResultsObj->student_id;
            }

            switch ($key) {
                case 'Absences': // no academic_period_id (within start and end date of the academic period)
                    $academicPeriodDetails = $this->AcademicPeriods->get($academicPeriodId);
                    $academicPeriodStartDate = $academicPeriodDetails->start_date;
                    $academicPeriodEndDate = $academicPeriodDetails->end_date;

                    $condition = [
                        $CriteriaModel->aliasField('institution_id') => $institutionId,
                        $CriteriaModel->aliasField('start_date') . ' >= ' => $academicPeriodStartDate,
                        $CriteriaModel->aliasField('end_date') . ' <= ' => $academicPeriodEndDate
                    ];
                    break;

                case 'Genders': // no institution_id
                    $condition = [$CriteriaModel->aliasField('id') . ' IN ' => $institutionStudentsList];
                    break;

                case 'Guardians': // no institution_id
                    $condition = [$CriteriaModel->aliasField('student_id') . ' IN ' => $institutionStudentsList];
                    break;

                case 'Special Needs': // no institution_id
                    $condition = [$CriteriaModel->aliasField('security_user_id') . ' IN ' => $institutionStudentsList];
                    break;

                default: // have institution_id and academic_period_id in the model table
                    $condition = [
                        $CriteriaModel->aliasField('institution_id') => $institutionId,
                        $CriteriaModel->aliasField('academic_period_id') => $academicPeriodId
                    ];
                    break;
            }
        } else {
            // dont have institution Id (from Administrator > Indexes > generate)
            $condition = [];
        }

        $criteriaModelResults = $CriteriaModel->find()
            ->where([$condition])
            ->all();

        foreach ($criteriaModelResults as $criteriaModelResultsKey => $criteriaModelEntity) {
            $criteriaModelEntityId = $criteriaModelEntity->id;

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
