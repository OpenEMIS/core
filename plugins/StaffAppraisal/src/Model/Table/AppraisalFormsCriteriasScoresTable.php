<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Datasource\EntityInterface;

class AppraisalFormsCriteriasScoresTable extends AppTable
{
    // Added
    const FIELD_TYPE_SCORE = "SCORE";
    const FIELD_TYPE_SLIDER = "SLIDER";
    const FORMULA_SUM = "SUM";
    const FORMULA_AVG = "AVG";

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasOne('AppraisalFormsCriterias', [
            'className' => 'StaffAppraisal.AppraisalFormsCriterias',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('AppraisalFormsCriteriasScoresLinks', [
            'className' => 'StaffAppraisal.AppraisalFormsCriteriasScoresLinks',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsTo('AppraisalCriterias', [
            'className' => 'StaffAppraisal.AppraisalCriterias',
            'foreignKey' => [
                'appraisal_form_id', 
                'appraisal_criteria_id'
            ]
        ]);
    }

    // Added
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Appraisal.add.afterSave'] = 'appraisalScoreAddAfterSave';
        $events['Model.Appraisal.edit.beforeSave'] = 'appraisalScoreEditBeforeSave';
        $events['Model.Appraisal.edit.afterSave'] = 'appraisalEditAfterSave';

        return $events;
    }

    // Added
    public function appraisalScoreAddAfterSave(Event $event, Entity $entity, ArrayObject $requestData, $alias)
    {
        // Form ID
        $requestData[$this->alias()]['id'] = $entity->id;

        $this->createAppraisalFormsCriteriasScoresRecord($requestData);
    }

    public function appraisalScoreEditBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, $alias)
    {
        // Form ID
        $requestData[$this->alias()]['id'] = $entity->id;

        $this->createAppraisalFormsCriteriasScoresRecord($requestData);
    }

    public function appraisalEditAfterSave(Event $event, Entity $entity, $action = null)
    {
        switch ($action) {
            case "calculateScoreAfterSliderCriteriaIsSaved":
                $this->calculateScore($entity);
                break;
        }
    }

    private function calculateScore(Entity $entity)
    {
        pr("appraisalEditAfterSave - AppraisalFormsCriteriasScoresTable");
        // pr('die');die;
        // pr($entity);
        $formId = $entity->appraisal_form_id;
        $institutionStaffAppraisalId = $entity->id;
        $criteriaScoreIds = [];
        // $count = 0;
        $proccessedCriteriaScore = [];

         // Get all the appraisal score criteria id and store to an array
        $appraisalCriteriaScoreEntities = $this->find()
            ->where([
                $this->aliasField('appraisal_form_id') => $formId
            ])
            ->contain([
                'AppraisalFormsCriterias.AppraisalCriterias.FieldTypes',
                // 'AppraisalFormsCriteriasScoresLinks.AppraisalFormsCriteriasLinks.AppraisalCriterias.FieldTypes',
                'AppraisalFormsCriteriasScoresLinks.AppraisalFormsCriteriasLinks.AppraisalSliderAnswers',
                'AppraisalFormsCriteriasScoresLinks.AppraisalFormsCriteriasLinks.AppraisalScoreAnswers',
            ]);


        // Retriving all the score ID that has a linkage to $criteriaScoreIds[]
        foreach ($appraisalCriteriaScoreEntities as $criteriaScore) {
            $criteriaScoreId = $criteriaScore->appraisal_criteria_id;
            if (!empty($criteriaScore->appraisal_forms_criterias_scores_links)) {
                $criteriaScoreIds[] = $criteriaScore;
            } else {
                $proccessedCriteriaScore[$criteriaScoreId] = 0;
            }
         }
         // pr(count($criteriaScoreIds));
         // pr(count($proccessedCriteriaScore));die;
         $exit = 0;
         $currentIndex = 0;
         // pr($criteriaScoreIds);
         while (count($criteriaScoreIds) && $exit++ < 500) {
            $currentCriteria = $criteriaScoreIds[$currentIndex];
            $currAbleToEvaluate = true;

            foreach ($currentCriteria->appraisal_forms_criterias_scores_links as $childCriteria) {

                $childCriteriaLink = $childCriteria->appraisal_forms_criterias_link;

                if ($childCriteriaLink->has('appraisal_score_answers') && $currAbleToEvaluate) {
                    foreach($childCriteriaLink->appraisal_score_answers as $childChildCriteria) {
                        $scoreEntityLinkId = $childChildCriteria->appraisal_criteria_id;

                        if (!array_key_exists($scoreEntityLinkId, $proccessedCriteriaScore)) {
                            $currAbleToEvaluate = false;
                            break;
                        }

                    }
                }
            }



            if($currAbleToEvaluate) {
                //evaluate and throw into processed array
                $this->evaluateScore($currentCriteria, $proccessedCriteriaScore);
                        // pr($proccessedCriteriaScore);die;

                //remove the criteria from criteriaScoreIds since its already successfully calculated the score
                array_splice($criteriaScoreIds, $currentIndex, 1);
            }

                // if ($currentCriteria->appraisal_criteria_id == 12) {
                //     pr('before');
                //     pr($currentIndex + 1);
                //     pr(count($currentIndex));
                //     pr(($currentIndex + 1) % count($criteriaScoreIds));
                // }
            if (count($criteriaScoreIds) > 0) {
                $currentIndex = ($currentIndex + 1) % count($criteriaScoreIds);
            }

                // if ($currentCriteria->appraisal_criteria_id == 12) {
                //     pr('after');

                //     pr($currentIndex);
                //     pr($proccessedCriteriaScore);die;
                // }

         }
         pr($proccessedCriteriaScore);die;
        // Save score to database (appraisal_score_answers table)
        $this->saveCriteriasScoreAnswer($formId, $institutionStaffAppraisalId, $proccessedCriteriaScore);
    }

    private function evaluateScore($currentCriteria, &$proccessedCriteriaScore)
    {
        $noOfChildInTheScoreEntity = 0;
        $totalScore = 0;
        foreach ($currentCriteria->appraisal_forms_criterias_scores_links as $childCriteria) {
            $childCriteriaLink = $childCriteria->appraisal_forms_criterias_link;
            if ($childCriteriaLink->has('appraisal_slider_answers')) {
                foreach ($childCriteriaLink->appraisal_slider_answers as $childChildCriteria) {
                    $totalScore += $childChildCriteria->answer;
                    $noOfChildInTheScoreEntity++;
                }
            }
        }




        foreach ($currentCriteria->appraisal_forms_criterias_scores_links as $childCriteria) {
            $childCriteriaLink = $childCriteria->appraisal_forms_criterias_link;

            if ($childCriteriaLink->has('appraisal_score_answers')) {
                foreach ($childCriteriaLink->appraisal_score_answers as $childChildCriteria) {

                    //take the id and check with processed
                    if(isset($proccessedCriteriaScore[$childChildCriteria->appraisal_criteria_id])) {
                        $totalScore += $proccessedCriteriaScore[$childChildCriteria->appraisal_criteria_id];
                    } else {
                        pr($childChildCriteria->appraisal_criteria_id);
                        pr($proccessedCriteriaScore);
                        pr("holy sheet"); die;
                    }
  
                    $noOfChildInTheScoreEntity++;
                }
            }
        }


        // Calculate score
        if ($currentCriteria->has('params') && !empty($currentCriteria->params)) {                             
            $scoreEntityParams = json_decode($currentCriteria->params, true);
            if (!is_null($scoreEntityParams) && array_key_exists('formula', $scoreEntityParams)) {
                $formula = $scoreEntityParams['formula'];
                switch ($formula) {
                    case self::FORMULA_AVG:
                        if ($noOfChildInTheScoreEntity != 0) {
                            $totalScore = $totalScore/$noOfChildInTheScoreEntity;
                        } else {
                            $totalScore = 0;
                        }
                    break;
                    case self::FORMULA_SUM:
                        $totalScore = $totalScore;
                        break;
                    default:
                        $totalScore = 0;
                    break;
                }
            }
        }



        //throw into processed array
        $proccessedCriteriaScore[$currentCriteria->appraisal_criteria_id] = $totalScore;

        // pr($proccessedCriteriaScore);die;

    }

    private function saveCriteriasScoreAnswer($formId, $institutionStaffAppraisalId, $proccessedCriteriaScore)
    {
        $appraisalScoreAnswers = TableRegistry::get('StaffAppraisal.appraisal_score_answers');
        $appraisalScoreAnswersEntities = $appraisalScoreAnswers->find()
        ->where([
            $appraisalScoreAnswers->aliasField('appraisal_form_id') => $formId,
            $appraisalScoreAnswers->aliasField('institution_staff_appraisal_id') => $institutionStaffAppraisalId,

        ]);

        // pr($appraisalScoreAnswersEntities->toArray());die;

        // Calculated all the score fields, time to save to DB
        foreach ($proccessedCriteriaScore as $criteriaScoreId => $answer) {
            $data[] = [
                'appraisal_form_id' => $formId,
                'institution_staff_appraisal_id' => $institutionStaffAppraisalId,
                'appraisal_criteria_id' => $criteriaScoreId,
                'answer' => $answer
            ];
        }
        // pr($data);die;
        $newEntities = $appraisalScoreAnswers->newEntities($data);
        $appraisalScoreAnswers->connection()->transactional(function () use ($appraisalScoreAnswers, $newEntities) {
            foreach ($newEntities as $entity) {
                $appraisalScoreAnswers->save($entity, ['atomic' => false]);
            }
        });
    }

    private function createAppraisalFormsCriteriasScoresRecord($requestData) 
    {
        $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');

        if (array_key_exists("appraisal_criterias", $requestData['AppraisalForms']) && !empty($requestData['AppraisalForms']['appraisal_criterias'])) {


            // To remove the record that is un-link to the form.
            $this->deleteAppraisalFormsCriteriasScoreRecord($requestData);

            foreach ($requestData['AppraisalForms']['appraisal_criterias'] as $appraisalFormCriteriasEntity) {

                $criteriasFieldType = strtoupper($appraisalFormCriteriasEntity['_joinData']['field_type']);                

                switch ($criteriasFieldType) {
                    case self::FIELD_TYPE_SCORE:
                        // Create record at appraisal_forms_criterias_scores table
                        $formId = $requestData['AppraisalScores']['id'];
                        $criteriaId = $appraisalFormCriteriasEntity['id'];

                        $actualDataToBeSave[] = [
                            'appraisal_form_id' => $formId,
                            'appraisal_criteria_id' => $criteriaId,
                            'final_score' => 0
                        ];
                        break;
                }
            }

            // Process all the entities as a single transaction
            $newEntities = $appraisalFormsCriteriasScores->newEntities($actualDataToBeSave);

            $appraisalFormsCriteriasScores->connection()->transactional(function () use ($appraisalFormsCriteriasScores, $newEntities) {
                foreach ($newEntities as $entity) {
                    $appraisalFormsCriteriasScores->save($entity, ['atomic' => false]);
                }
            });
        }
    }

    private function deleteAppraisalFormsCriteriasScoreRecord($requestData)
    {
        $formId = $requestData['AppraisalForms']['id'];
        $criteriaIdFromTable = [];

        $appraisalFormsCriterias = TableRegistry::get('StaffAppraisal.AppraisalFormsCriterias');
        $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');
        $appraisalFormsCriteriasScoresLinks = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScoresLinks');

        // Get all the criterias score records
        $appraisalFormCriteriasEntity = $appraisalFormsCriterias
            ->find()
            ->where([
                $appraisalFormsCriterias->aliasField('appraisal_form_id') => $formId
            ]);


        // Check the array different den remove from the database table(To unlink)
        $appraisalFormCriteriasArray = $appraisalFormCriteriasEntity->toArray();
        foreach ($appraisalFormCriteriasArray as $obj) {
            $criteriaIdFromTable[] = $obj->appraisal_criteria_id;
        }

        $appraisalFormCriteriasFromRequestData = $requestData['AppraisalForms']['appraisal_criterias'];
        foreach ($appraisalFormCriteriasFromRequestData as $obj) {
            $criteriaIdFromRequestData[] = $obj['id'];
        }        

        $result = array_diff($criteriaIdFromTable,$criteriaIdFromRequestData);


        foreach ($result as $removeCriteriasId) {

            // Delete record from appraisal_forms_criterias table
            $appraisalFormCriteriasEntity = $appraisalFormsCriterias
                ->find()
                ->where([
                    $appraisalFormsCriterias->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriterias->aliasField('appraisal_criteria_id') => $removeCriteriasId
                ]);

            if($appraisalFormCriteriasEntity->count() > 0) {
                $appraisalFormsCriterias->delete($appraisalFormCriteriasEntity->first());
            }

            // Delete record from appraisal_forms_criterias_scores table
            $appraisalFormCriteriasScoresEntity = $appraisalFormsCriteriasScores
                ->find()
                ->where([
                    $appraisalFormsCriteriasScores->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriteriasScores->aliasField('appraisal_criteria_id') => $removeCriteriasId
                ]);

            if($appraisalFormCriteriasScoresEntity->count() > 0) {
                $appraisalFormsCriteriasScores->delete($appraisalFormCriteriasScoresEntity->first());
            }

            // Delete record from appraisal_forms_criterias_scores_links table
            $appraisalFormsCriteriasScoresLinksEntities = $appraisalFormsCriteriasScoresLinks
                ->find()
                ->where([
                    $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_criteria_id') => $removeCriteriasId
                ]);

            foreach ($appraisalFormsCriteriasScoresLinksEntities->toArray() as $appraisalFormsCriteriasScoresLinksEntity) {
                // There exists the linkage record therefore remove
                if(isset($appraisalFormsCriteriasScoresLinksEntity)) {
                    $appraisalFormsCriteriasScoresLinks->delete($appraisalFormsCriteriasScoresLinksEntity);
                }
            }

        }
    }
}
