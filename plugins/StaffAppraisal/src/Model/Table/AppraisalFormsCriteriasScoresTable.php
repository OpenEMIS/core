<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Datasource\EntityInterface;
use Cake\Log\Log;

class AppraisalFormsCriteriasScoresTable extends AppTable
{
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Appraisal.add.afterSave'] = 'updateAppraisalScore';
        $events['Model.Appraisal.edit.beforeSave'] = 'updateAppraisalScore';
        $events['Model.InstitutionStaffAppraisal.addAfterSave'] = 'calculateScore';
        $events['Model.InstitutionStaffAppraisal.editAfterSave'] = 'calculateScore';
        return $events;
    }

    public function updateAppraisalScore(Event $event, Entity $entity, ArrayObject $requestData, $alias)
    {
        // Form ID
        $requestData[$this->alias()]['id'] = $entity->id;

        $this->createAppraisalFormsCriteriasScoresRecord($requestData);
    }

    // All the slider criteria has been save to DB already, when it come until here therefore now "retrieve" all the question from DB and all the "SCORE" type and calculate then save back to db for the score fields.
    public function calculateScore(Event $event, Entity $entity)
    {
        $formId = $entity->appraisal_form_id;
        $institutionStaffAppraisalId = $entity->id;
        // To store all the criterias that have a criteria record
        $criteriaScoreEntities = [];
        // To store all the criteria score that is been processed, "scoreId" => "calculatedScore"
        $proccessedCriteriaScore = new ArrayObject([]);

        // $appraisalCriteriaScoreEntities = $this->find()
        $result = $this->find()
            ->where([
                $this->aliasField('appraisal_form_id') => $formId,
            ])
            ->contain([
                'AppraisalFormsCriterias.AppraisalCriterias.FieldTypes',
                'AppraisalFormsCriteriasScoresLinks.AppraisalFormsCriteriasLinks.AppraisalCriterias.FieldTypes',
                'AppraisalFormsCriteriasScoresLinks.AppraisalFormsCriteriasLinks.AppraisalSliderAnswers' => function ($q) use ($institutionStaffAppraisalId) {
                    return $q->where([
                        'AppraisalSliderAnswers.institution_staff_appraisal_id' => $institutionStaffAppraisalId
                    ]);
                }
            ])
            ->all();

        if (!$result->isEmpty()) {
            $appraisalCriteriaScoreEntities = $result->toArray();

            // Retriving all the score ID that has a linkage to $criteriaScoreEntities[]
            foreach ($appraisalCriteriaScoreEntities as $criteriaScore) {
                $criteriaScoreId = $criteriaScore->appraisal_criteria_id;
                if (!empty($criteriaScore->appraisal_forms_criterias_scores_links)) {
                    $criteriaScoreEntities[] = $criteriaScore;
                } else { // When score doesn't have criteria, treated as processed
                    $proccessedCriteriaScore[$criteriaScoreId] = 0;
                }
            }
        }

        $processCount = 0;
        $currentIndex = 0;

        while (count($criteriaScoreEntities)) {
            if ($processCount++ > 10000) {
                Log::write('error', $this->alias() . ': Infinite loop when calculating scores');
                Log::write('error', $this->alias() . ': Entity');
                Log::write('error', $criteriaScoreEntities);
                throw new Exception('Unable to compute the scores');
            }

            $currentCriteria = $criteriaScoreEntities[$currentIndex];
            $canEvaluate = true;

            // Determine canEvaluate
            foreach ($currentCriteria->appraisal_forms_criterias_scores_links as $childCriteria) {
                // Check if there is a score
                $isScoreType = ($childCriteria->appraisal_forms_criterias_link->appraisal_criteria->field_type->code == self::FIELD_TYPE_SCORE);
                if ($isScoreType && !array_key_exists($childCriteria->appraisal_criteria_linked_id, $proccessedCriteriaScore)) {
                    // If the currentCriteria have all slider and no score
                    $canEvaluate = false;
                    break;
                }
            }

            if($canEvaluate) {
                // Evaluate and throw into processed array
                $this->evaluateScore($currentCriteria, $proccessedCriteriaScore);

                // Remove the criteria from criteriaScoreIds since its already successfully calculated the score
                array_splice($criteriaScoreEntities, $currentIndex, 1);
            }

            // Increment currentIndex by 1 (circular)
            if (count($criteriaScoreEntities) > 0) {
                $currentIndex = ++$currentIndex % count($criteriaScoreEntities);
            }
         }

        // Save score to database (appraisal_score_answers table)
        $params = new ArrayObject([
            'form_id' => $formId,
            'institution_staff_appraisal_id' => $institutionStaffAppraisalId,
        ]);
        $this->saveCriteriaScoreAnswers($proccessedCriteriaScore, $params);
    }

    private function evaluateScore($currentCriteria, ArrayObject $proccessedCriteriaScore)
    {   
        $criteriaCountLink = count($currentCriteria->appraisal_forms_criterias_scores_links);

        if ($criteriaCountLink > 0) {
            $totalScore = 0;
            foreach ($currentCriteria->appraisal_forms_criterias_scores_links as $childCriteria) {
                $childCriteriaLink = $childCriteria->appraisal_forms_criterias_link;
                if ($childCriteriaLink->has('appraisal_slider_answers') && count($childCriteriaLink->appraisal_slider_answers)) {
                    $childChildCriteria = $childCriteriaLink->appraisal_slider_answers[0];
                    $totalScore += $childChildCriteria->answer;
                } else { //score
                    //take the id and check with processed
                    if (isset($proccessedCriteriaScore[$childCriteriaLink->appraisal_criteria_id])) {
                        $totalScore += $proccessedCriteriaScore[$childCriteriaLink->appraisal_criteria_id];
                    } 
                }
            }

            // Calculate score based on the criteria params.
            if ($currentCriteria->has('params') && !empty($currentCriteria->params)) {                             
                $scoreEntityParams = json_decode($currentCriteria->params, true);
                if (!is_null($scoreEntityParams) && array_key_exists('formula', $scoreEntityParams)) {
                    $formula = $scoreEntityParams['formula'];
                    switch ($formula) {
                        case self::FORMULA_AVG:
                            $totalScore = $totalScore / $criteriaCountLink;
                        break;
                        case self::FORMULA_SUM:
                            break;
                        default:
                            $totalScore = 0;
                            // Write a log error also
                            // Remove the select thing.
                        break;
                    }
                }
            }
            $proccessedCriteriaScore[$currentCriteria->appraisal_criteria_id] = $totalScore;
        }
    }

    private function saveCriteriaScoreAnswers(ArrayObject $proccessedCriteriaScore, ArrayObject $params)
    {
        $AppraisalScoreAnswers = TableRegistry::get('StaffAppraisal.AppraisalScoreAnswers');
        $data = [];
        
        // Calculated all the score fields, time to save to DB
        foreach ($proccessedCriteriaScore as $criteriaScoreId => $answer) {
            $data[] = [
                'appraisal_form_id' => $params['form_id'],
                'institution_staff_appraisal_id' => $params['institution_staff_appraisal_id'],
                'appraisal_criteria_id' => $criteriaScoreId,
                'answer' => $answer
            ];
        }

        if (!empty($data)) {
            $newEntities = $AppraisalScoreAnswers->newEntities($data);
            $AppraisalScoreAnswers->connection()->transactional(function () use ($AppraisalScoreAnswers, $newEntities) {
                foreach ($newEntities as $entity) {
                    $AppraisalScoreAnswers->save($entity);
                }
            });
        }

    }

    private function createAppraisalFormsCriteriasScoresRecord($requestData) 
    {
        $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');

        if (array_key_exists("appraisal_criterias", $requestData['AppraisalForms']) && !empty($requestData['AppraisalForms']['appraisal_criterias'])) {


            // To remove the record that is un-link to the form.
            $this->deleteAppraisalFormsCriteriasScoreRecord($requestData);
            $actualDataToBeSave = [];
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

            if (!is_null($actualDataToBeSave)) {
                // Process all the entities as a single transaction
                $newEntities = $appraisalFormsCriteriasScores->newEntities($actualDataToBeSave);
                $appraisalFormsCriteriasScores->connection()->transactional(function () use ($appraisalFormsCriteriasScores, $newEntities) {
                foreach ($newEntities as $entity) {
                    $appraisalFormsCriteriasScores->save($entity, ['atomic' => false]);
                    }
                });
            }
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
