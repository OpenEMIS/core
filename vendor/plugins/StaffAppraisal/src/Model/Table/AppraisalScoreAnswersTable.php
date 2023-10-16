<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use StaffAppraisal\Model\Table\AppraisalAnswersTable;
use Cake\ORM\TableRegistry;

class AppraisalScoreAnswersTable extends AppraisalAnswersTable
{
    // Added
    const FIELD_TYPE_SCORE = "SCORE";
    const FIELD_TYPE_SLIDER = "SLIDER";
    const FORMULA_SUM = "SUM";
    const FORMULA_AVG = "AVG";

    public function implementedEvents()
   	{
        $events = parent::implementedEvents();
        $events['Model.Appraisal.edit.beforePatch'] = 'appraisalEditBeforePatch';
        return $events;
   	}

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function appraisalEditBeforePatch(Event $event, ArrayObject $requestData, $alias)
    {
        // pr($requestData);die;
        $formId = $requestData[$alias]['appraisal_form_id'];
        $institutionStaffAppraisalId = $requestData[$alias]['id'];
        $criteriaScoreIds = [];
        $count = 0;

        // Get all the appraisal score criteria id and store to an array
        $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');
        $appraisalCriteriaScoreEntities = $appraisalFormsCriteriasScores->find()
            ->where([
                $appraisalFormsCriteriasScores->aliasField('appraisal_form_id') => $formId
            ])
            ->contain([
                'AppraisalFormsCriteriasScoresLinks'
            ]);

        $requestData[$alias]['appraisal_score_answers'] = [];

        foreach ($appraisalCriteriaScoreEntities as $criteriaScore) {
            if (!empty($criteriaScore->appraisal_forms_criterias_scores_links)) {
                $requestData[$alias]['appraisal_score_answers'][] = [
                    'answer' => null,
                    'appraisal_form_id' => $formId,
                    'appraisal_criteria_id' => $criteriaScore['appraisal_criteria_id'],
                ];

                $criteriaScoreIds[] = $criteriaScore['appraisal_criteria_id'];
            }
         }

        $noOfCriteriaScoreIds = count($criteriaScoreIds);

        $appraisalFormsCriteriasScoresLinks = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScoresLinks');


        // Those criteria score that has already calculated
        $proccessedCriteriaScore = [];

        while (!empty($criteriaScoreIds)) {
            foreach ($criteriaScoreIds as $criteriaScoreId) {
                $appraisalFormsCriteriasScoresLinksEntity = $appraisalFormsCriteriasScoresLinks->find()
                    ->contain([
                     'AppraisalFormsCriteriasLinks.AppraisalCriterias.FieldTypes',
                     'AppraisalFormsCriteriasLinks.AppraisalSliderAnswers',
                        'AppraisalFormsCriterias.AppraisalFormsCriteriasScores'
                    ])
                    ->where([
                     $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_form_id') => $formId,
                     $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_criteria_id') => $criteriaScoreId
                    ]);

                    // pr($criteriaScoreId);
                    // pr($appraisalFormsCriteriasScoresLinksEntity->toArray());

                if ($appraisalFormsCriteriasScoresLinksEntity->count() > 0) {
                    $hasNoLinkageToScore = true;
                    $totalScore = 0;
                    $noOfChildInTheScoreEntity = 0;
                    $appraisalFormsCriteriasScoresLinksEntityIndex = null;


                    foreach ($appraisalFormsCriteriasScoresLinksEntity as $indexKey => $scoreEntity) {

                        // pr($scoreEntity);die;
                        $scoreEntityId = $scoreEntity->appraisal_criteria_id;
                        $scoreEntityLinkId = $scoreEntity->appraisal_criteria_linked_id;
                        $scoreEntityFieldType = $scoreEntity->appraisal_forms_criterias_link->appraisal_criteria->field_type->code;

                        if ($scoreEntityFieldType == self::FIELD_TYPE_SLIDER) {
                            // Should i here
                            if ($scoreEntity->appraisal_forms_criterias_link->has('appraisal_slider_answers')) {
                                $appraisalSliderAnswers = $scoreEntity->appraisal_forms_criterias_link->appraisal_slider_answers;

                                foreach ($appraisalSliderAnswers as $scoreAnswer) {
                                    if ($scoreAnswer->institution_staff_appraisal_id == $institutionStaffAppraisalId) {
                                        $totalScore += $scoreAnswer->answer;
                                        $noOfChildInTheScoreEntity++;
                                    }
                                }
                                $appraisalFormsCriteriasScoresLinksEntityIndex = $indexKey;
                            }
                        } elseif ($scoreEntityFieldType == self::FIELD_TYPE_SCORE) {
                            // If it is a score type den check if that score is already proccessed(calculated)
                            if (array_key_exists($scoreEntityLinkId, $proccessedCriteriaScore)) {
                                // Do the calculation
                                $totalScore += $proccessedCriteriaScore[$scoreEntityLinkId];
                                $noOfChildInTheScoreEntity++;
                                $appraisalFormsCriteriasScoresLinksEntityIndex = $indexKey;

                            }else {
                                // Has score type but is not processed
                                $hasNoLinkageToScore = false;
                                array_shift($criteriaScoreIds);
                                array_push($criteriaScoreIds,$criteriaScoreId);
                                break;
                            }

                        }
                    }

                    if ($hasNoLinkageToScore) {
                        $scoreEntity = $appraisalFormsCriteriasScoresLinksEntity->toArray()[$appraisalFormsCriteriasScoresLinksEntityIndex];
                        $scoreEntityParams = $scoreEntity->appraisal_forms_criteria->appraisal_forms_criterias_score->params;
                        $scoreEntityParams = json_decode($scoreEntityParams, true);

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
                            }


                            array_shift($criteriaScoreIds);
                            $proccessedCriteriaScore[$criteriaScoreId] = $totalScore;
                            ++$count;

                        }else {
                            // If the score does not exists a step (SUM / AVG) den i will ignore the whole linkage for the score and set the total score to 0.
                            $totalScore = 0;
                            array_shift($criteriaScoreIds);
                            $proccessedCriteriaScore[$criteriaScoreId] = $totalScore;
                            ++$count;
                        }
                    }
                }else {
                    array_shift($criteriaScoreIds);
                } 
            }
        }





   //  	if (!empty($requestData[$alias]['appraisal_score_answers'])) {

   //  		$formId = $requestData[$alias]['appraisal_form_id'];
   //          $institutionStaffAppraisalId = $requestData[$alias]['id'];
   //  		$criteriaScoreIds = [];

   //  		// Get all the appraisal score criteria id and store to an array
   //  		$appraisalCriteriaScoreType = $requestData[$alias]['appraisal_score_answers'];
   //  		foreach ($appraisalCriteriaScoreType as $criteriaScore) {
   //  			$criteriaScoreIds[] = $criteriaScore['appraisal_criteria_id'];
   //  		}
   //  	}
   //      // pr($criteriaScoreIds);die;

   //  	$noOfCriteriaScoreIds = count($criteriaScoreIds);
   //  	$count = 0;

   //      $appraisalFormsCriteriasScoresLinks = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScoresLinks');

   //      // Those criteria score that has already calculated
   //      $proccessedCriteriaScore = [];
   //      while (!empty($criteriaScoreIds)) {
			// foreach ($criteriaScoreIds as $criteriaScoreId) {
	  //       	$appraisalFormsCriteriasScoresLinksEntity = $appraisalFormsCriteriasScoresLinks->find()
			//         ->contain([
			//         	'AppraisalFormsCriteriasLinks.AppraisalCriterias.FieldTypes',
			//         	'AppraisalFormsCriteriasLinks.AppraisalSliderAnswers',
   //                      'AppraisalFormsCriterias.AppraisalFormsCriteriasScores'
			//         ])
			//         ->where([
			//         	$appraisalFormsCriteriasScoresLinks->aliasField('appraisal_form_id') => $formId,
			//         	$appraisalFormsCriteriasScoresLinks->aliasField('appraisal_criteria_id') => $criteriaScoreId
			//         ]);

   //              if ($appraisalFormsCriteriasScoresLinksEntity->count() > 0) {
   //                  $hasNoLinkageToScore = true;
   //                  $totalScore = 0;
   //                  $noOfChildInTheScoreEntity = 0;
   //                  $appraisalFormsCriteriasScoresLinksEntityIndex = null;


   //                  foreach ($appraisalFormsCriteriasScoresLinksEntity as $indexKey => $scoreEntity) {
   //                      $scoreEntityId = $scoreEntity->appraisal_criteria_id;
   //                      $scoreEntityLinkId = $scoreEntity->appraisal_criteria_linked_id;
   //                      $scoreEntityFieldType = $scoreEntity->appraisal_forms_criterias_link->appraisal_criteria->field_type->code;

   //                      if ($scoreEntityFieldType == self::FIELD_TYPE_SLIDER) {
   //                          if ($scoreEntity->appraisal_forms_criterias_link->has('appraisal_slider_answers')) {
   //                              $appraisalSliderAnswers = $scoreEntity->appraisal_forms_criterias_link->appraisal_slider_answers;

   //                              foreach ($appraisalSliderAnswers as $scoreAnswer) {
   //                                  if ($scoreAnswer->institution_staff_appraisal_id == $institutionStaffAppraisalId) {
   //                                      $totalScore += $scoreAnswer->answer;
   //                                      $noOfChildInTheScoreEntity++;
   //                                  }
   //                              }
   //                              $appraisalFormsCriteriasScoresLinksEntityIndex = $indexKey;
   //                          }
   //                      } elseif ($scoreEntityFieldType == self::FIELD_TYPE_SCORE) {
   //                          // If it is a score type den check if that score is already proccessed(calculated)
   //                          if (array_key_exists($scoreEntityLinkId, $proccessedCriteriaScore)) {
   //                              // Do the calculation
   //                              $totalScore += $proccessedCriteriaScore[$scoreEntityLinkId];
   //                              $noOfChildInTheScoreEntity++;
   //                              $appraisalFormsCriteriasScoresLinksEntityIndex = $indexKey;

   //                          }else {
   //                              // Has score type but is not processed
   //                              $hasNoLinkageToScore = false;
   //                              array_shift($criteriaScoreIds);
   //                              array_push($criteriaScoreIds,$criteriaScoreId);
   //                              break;
   //                          }

   //                      }
   //                  }

   //                  if ($hasNoLinkageToScore) {
   //                      $scoreEntity = $appraisalFormsCriteriasScoresLinksEntity->toArray()[$appraisalFormsCriteriasScoresLinksEntityIndex];
   //                      $scoreEntityParams = $scoreEntity->appraisal_forms_criteria->appraisal_forms_criterias_score->params;
   //                      $scoreEntityParams = json_decode($scoreEntityParams, true);

   //                      if (!is_null($scoreEntityParams) && array_key_exists('formula', $scoreEntityParams)) {
   //                          $formula = $scoreEntityParams['formula'];
                            
   //                          switch ($formula) {
   //                              case self::FORMULA_AVG:
   //                                  if ($noOfChildInTheScoreEntity != 0) {
   //                                      $totalScore = $totalScore/$noOfChildInTheScoreEntity;
   //                                  } else {
   //                                      $totalScore = 0;
   //                                  }
   //                                  break;
   //                              case self::FORMULA_SUM:
   //                                  $totalScore = $totalScore;
   //                                  break;
   //                          }


   //                          array_shift($criteriaScoreIds);
   //                          $proccessedCriteriaScore[$criteriaScoreId] = $totalScore;
   //                          ++$count;

   //                      }else {
   //                          // If the score does not exists a step (SUM / AVG) den i will ignore the whole linkage for the score and set the total score to 0.
   //                          $totalScore = 0;
   //                          array_shift($criteriaScoreIds);
   //                          $proccessedCriteriaScore[$criteriaScoreId] = $totalScore;
   //                          ++$count;
   //                      }
   //                  }
   //              }else {
   //                  array_shift($criteriaScoreIds);
   //              } 
	  //       }
   //      }

   //      $this->updateScoreAnswerBackToScoreCriteria($requestData, $alias, $proccessedCriteriaScore);
    }




    // public function appraisalEditBeforePatch(Event $event, ArrayObject $requestData, $alias)
    // {
    //     if (!empty($requestData[$alias]['appraisal_score_answers'])) {

    //         $formId = $requestData[$alias]['appraisal_form_id'];
    //         $institutionStaffAppraisalId = $requestData[$alias]['id'];
    //         $criteriaScoreIds = [];

    //         // Get all the appraisal score criteria id and store to an array
    //         $appraisalCriteriaScoreType = $requestData[$alias]['appraisal_score_answers'];
    //         foreach ($appraisalCriteriaScoreType as $criteriaScore) {
    //             $criteriaScoreIds[] = $criteriaScore['appraisal_criteria_id'];
    //         }
    //     }

    //     $noOfCriteriaScoreIds = count($criteriaScoreIds);
    //     $count = 0;

    //     $appraisalFormsCriteriasScoresLinks = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScoresLinks');

    //     // Those criteria score that has already calculated
    //     $proccessedCriteriaScore = [];
    //     while (!empty($criteriaScoreIds)) {
    //         foreach ($criteriaScoreIds as $criteriaScoreId) {
    //             $appraisalFormsCriteriasScoresLinksEntity = $appraisalFormsCriteriasScoresLinks->find()
    //                 ->contain([
    //                     'AppraisalFormsCriteriasLinks.AppraisalCriterias.FieldTypes',
    //                     'AppraisalFormsCriteriasLinks.AppraisalSliderAnswers',
    //                     'AppraisalFormsCriterias.AppraisalFormsCriteriasScores'
    //                 ])
    //                 ->where([
    //                     $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_form_id') => $formId,
    //                     $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_criteria_id') => $criteriaScoreId
    //                 ]);

    //             $hasNoLinkageToScore = true;
    //             $totalScore = 0;
    //             $noOfChildInTheScoreEntity = 0;
    //             $appraisalFormsCriteriasScoresLinksEntityIndex = null;


    //             foreach ($appraisalFormsCriteriasScoresLinksEntity as $indexKey => $scoreEntity) {
    //                 $scoreEntityId = $scoreEntity->appraisal_criteria_id;
    //                 $scoreEntityLinkId = $scoreEntity->appraisal_criteria_linked_id;
    //                 $scoreEntityFieldType = $scoreEntity->appraisal_forms_criterias_link->appraisal_criteria->field_type->code;

    //                 if ($scoreEntityFieldType == self::FIELD_TYPE_SLIDER) {
    //                     if ($scoreEntity->appraisal_forms_criterias_link->has('appraisal_slider_answers')) {
    //                         $appraisalSliderAnswers = $scoreEntity->appraisal_forms_criterias_link->appraisal_slider_answers;

    //                         foreach ($appraisalSliderAnswers as $scoreAnswer) {
    //                             if ($scoreAnswer->institution_staff_appraisal_id == $institutionStaffAppraisalId) {
    //                                 $totalScore += $scoreAnswer->answer;
    //                                 $noOfChildInTheScoreEntity++;
    //                             }
    //                         }
    //                         $appraisalFormsCriteriasScoresLinksEntityIndex = $indexKey;
    //                     }
    //                 } elseif ($scoreEntityFieldType == self::FIELD_TYPE_SCORE) {
    //                     // If it is a score type den check if that score is already proccessed(calculated)
    //                     if (array_key_exists($scoreEntityLinkId, $proccessedCriteriaScore)) {
    //                         // Do the calculation
    //                         $totalScore += $proccessedCriteriaScore[$scoreEntityLinkId];
    //                         $noOfChildInTheScoreEntity++;
    //                         $appraisalFormsCriteriasScoresLinksEntityIndex = $indexKey;

    //                     }else {
    //                         // Has score type but is not processed
    //                         $hasNoLinkageToScore = false;
    //                         array_shift($criteriaScoreIds);
    //                         array_push($criteriaScoreIds,$criteriaScoreId);
    //                         break;
    //                     }

    //                 }
    //             }

    //             if ($hasNoLinkageToScore) {
    //                 $scoreEntity = $appraisalFormsCriteriasScoresLinksEntity->toArray()[$appraisalFormsCriteriasScoresLinksEntityIndex];
    //                 $scoreEntityParams = $scoreEntity->appraisal_forms_criteria->appraisal_forms_criterias_score->params;
    //                 $scoreEntityParams = json_decode($scoreEntityParams, true);

    //                 if (!is_null($scoreEntityParams) && array_key_exists('formula', $scoreEntityParams)) {
    //                     $formula = $scoreEntityParams['formula'];
                        
    //                     switch ($formula) {
    //                         case self::FORMULA_AVG:
    //                             if ($noOfChildInTheScoreEntity != 0) {
    //                                 $totalScore = $totalScore/$noOfChildInTheScoreEntity;
    //                             } else {
    //                                 $totalScore = 0;
    //                             }
    //                             break;
    //                         case self::FORMULA_SUM:
    //                             $totalScore = $totalScore;
    //                             break;
    //                     }


    //                     array_shift($criteriaScoreIds);
    //                     $proccessedCriteriaScore[$criteriaScoreId] = $totalScore;
    //                     ++$count;

    //                 }else {
    //                     // If the score does not exists a step (SUM / AVG) den i will ignore the whole linkage for the score and set the total score to 0.
    //                     $totalScore = 0;
    //                     array_shift($criteriaScoreIds);
    //                     $proccessedCriteriaScore[$criteriaScoreId] = $totalScore;
    //                     ++$count;
    //                 }
    //             }
    //         }
    //     }

    //     $this->updateScoreAnswerBackToScoreCriteria($requestData, $alias, $proccessedCriteriaScore);
    // }

    private function updateScoreAnswerBackToScoreCriteria(ArrayObject $requestData, $alias, $proccessedCriteriaScore)
    {
    	if (array_key_exists('appraisal_score_answers', $requestData[$alias])) {
    		foreach ($requestData[$alias]['appraisal_score_answers'] as $key => $scoreAnswersEntity) {
    			foreach ($proccessedCriteriaScore as $indexKey => $scoreAnswer) {
    				if ($scoreAnswersEntity['appraisal_criteria_id'] == $indexKey) {
    					$requestData[$alias]['appraisal_score_answers'][$key]['answer'] = $scoreAnswer;
    				}
    			}
    		}
    	}
    }
}
