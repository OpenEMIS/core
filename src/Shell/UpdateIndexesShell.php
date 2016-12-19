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

    }

    public function main()
    {
        $institutionId = !empty($this->args[0]) ? $this->args[0] : 0;
        $userId = !empty($this->args[1]) ? $this->args[1] : 0;
        $indexId = !empty($this->args[2]) ? $this->args[2] : 0;

        $today = Time::now();

        // update the generated_by and generated_on in indexes table
        $this->Indexes->query()
            ->update()
            ->set([
                'generated_by' => $userId,
                'generated_on' => $today
            ])
            ->execute();

        $criteriaKey = $this->Indexes->getCriteriasOptions();

        foreach ($criteriaKey as $key => $obj) {
            $this->autoUpdateIndexes($key, $obj, $institutionId, $userId);
        }
    }

    public function autoUpdateIndexes($key, $obj, $institutionId=0, $userId=0)
    {
        $today = Time::now();
        $CriteriaModel = TableRegistry::get($key);

        // $excludedModel = ['StudentUser', 'Guardians'];// doesnt have institution Id in the table.
// pr($excludedModel);die;
        // if (!empty($institutionId)) {
        //     if (!in_array($obj, $CriteriaModel)) {
        //         $condition = [$CriteriaModel->aliasField('institution_id') => $institutionId];
        //     } else {
        //         // model dont have institution Id. (security_user, student_guardians)
        //         $condition = [];
        //     }
        // } else {
            // dont have institution Id (from administrator > generate)
            $condition = [];
        // }

        $criteriaModelResults = $CriteriaModel->find()
            ->where([$condition])
            ->all();

        foreach ($criteriaModelResults as $key => $criteriaModelEntity) {
            $criteriaModelEntityId = $criteriaModelEntity->id;

            // will triggered the aftersave of the model (indexes behavior)
            $criteriaModelEntity->dirty('comment', true);
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
