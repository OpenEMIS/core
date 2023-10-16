<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\ResultSet;

class AppraisalScoresTable extends ControllerActionTable
{
    const SCORE_TYPE_CODE = 'SCORE';
    const SLIDER_TYPE_CODE = 'SLIDER';
    const IS_FINAL_SCORE = 1;

    private $validationPass = true;


    private $stepsOptions = [
        // '0' => '-- Select --',
        'SUM' => 'Sum',
        'AVG' => 'Average'
    ];

    public function initialize(array $config)
    {
        $this->table('appraisal_forms');
        parent::initialize($config);

        $this->hasMany('AppraisalPeriods', [
            'className' => 'StaffAppraisal.AppraisalPeriods',
            'foreignKey' => 'appraisal_form_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('StaffAppraisals', [
            'className' => 'Institution.StaffAppraisals',
            'foreignKey' => 'appraisal_form_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('AppraisalFormsCriterias', [
            'className' => 'StaffAppraisal.AppraisalFormsCriterias',
            'foreignKey' => ['appraisal_form_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->matching('AppraisalFormsCriterias.AppraisalCriterias.FieldTypes', function($q) {
                return $q->where(['FieldTypes.code' => self::SCORE_TYPE_CODE]);
            })
            ->group([
                $this->aliasField('id')
            ]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $scoreType = self::SCORE_TYPE_CODE;
        $query
            ->contain([
                'AppraisalFormsCriterias' => function ($q) use ($scoreType) {
                    return $q->innerJoinWith('AppraisalCriterias.FieldTypes', function($fieldQuery) use ($scoreType) {
                        return $fieldQuery->where(['FieldTypes.code' => $scoreType]);
                    });
                }
            ])
            ->contain([
                'AppraisalFormsCriterias.AppraisalCriterias',
                'AppraisalFormsCriterias.AppraisalFormsCriteriasScores.AppraisalFormsCriteriasScoresLinks'
            ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupFields();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
    {
        $this->setupFields($entity);

        $formId = $entity->id;

        if (!$this->isAppraisalScoreAnswersEditable($formId)) {
            unset($extra['toolbarButtons']['edit']);
            $this->Alert->info('Staff.Appraisal.isNotEditable');
        }
    }

    public function editAfterAction(Event $event, Entity $entity) 
    {
        $this->setupFields($entity);

        $formId = $entity->id;

        if (!$this->isAppraisalScoreAnswersEditable($formId)) {
            $this->Alert->info('Staff.Appraisal.isNotEditable');
            $event->stopPropagation();
            return $this->controller->redirect($this->url('view'));
        }
    }

    public function editOnAddField(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        if (!empty($requestData) && isset($requestData[$this->alias()]) && isset($requestData[$this->alias()]['selected_score'])) {
            $selectedCriteria = $requestData[$this->alias()]['selected_score'];

            // Get the critiera type fields based on their ID
            $appraisalCriterias = TableRegistry::get('StaffAppraisal.AppraisalCriterias');

            if (!empty($requestData[$this->alias()]['appraisal_forms_criterias_score'])) {
                $scoreDependencyArrayField = $requestData[$this->alias()]['appraisal_forms_criterias_score'];
            }


            // Next get the criteria field type out den see if it is score or not
            foreach ($selectedCriteria as $key => $criteriaId) {
                if (!empty($criteriaId)) {
                    $criteriaEntity = $appraisalCriterias
                        ->find()
                        ->where([$appraisalCriterias->aliasField('id') => $criteriaId])
                        ->contain(['FieldTypes'])
                        ->first();

                    $scoreField = $criteriaEntity->field_type->code;
                    // Selected score fields only
                    if ($scoreField == self::SCORE_TYPE_CODE && !empty($scoreDependencyArrayField)) {
                        $mainScoreId = $key;
                        $selectedScore = $criteriaEntity->id;
                        $this->populateScoreDependency($scoreDependencyArrayField, $mainScoreId, $selectedScore);

                        if (!$this->validationPass) {
                            $requestData[$this->alias()]['selected_score'][$key] = null;
                            $this->Alert->clear();
                            $this->Alert->error('Staff.Appraisal.circular_dependency');
                        }
                    }
                }
            }
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $appraisalFormId = $entity->id;
        $criteriaScores = $entity->appraisal_forms_criterias;
        $finalScore = $requestData[$this->alias()]['final_score'];
        $criteriaIds = [];

        if (!isset($requestData[$this->alias()]['appraisal_forms_criterias'])) {
            $requestData[$this->alias()]['appraisal_forms_criterias'] = [];
        }

        if (!isset($requestData[$this->alias()]['appraisal_forms_criterias_score'])) {
            $requestData[$this->alias()]['appraisal_forms_criterias_score'] = [];
        }

        if (isset($requestData[$this->alias()]['selected_score'])) {
            foreach ($requestData[$this->alias()]['selected_score'] as $criteriaId => $value) {
                $criteriaIds[] = $criteriaId;
            }
        }

        foreach ($criteriaIds as $criteriaId) {
            if (!isset($requestData[$this->alias()]['appraisal_forms_criterias_score'][$criteriaId])) {
                $requestData[$this->alias()]['appraisal_forms_criterias_score'][$criteriaId] = [];
            }

            if (!isset($requestData[$this->alias()]['steps'][$criteriaId])) {
                $requestData[$this->alias()]['steps'][$criteriaId] = [];
            }            
        }

        $scoreField = $requestData[$this->alias()]['appraisal_forms_criterias_score'];
        $stepsList = $requestData[$this->alias()]['steps'];

        foreach ($scoreField as $criteriaId => $data) {
            $stepData = $stepsList[$criteriaId];
            $params = [];

            if (!is_null($stepData)) {
                if($stepData != '0') {
                    $params['formula'] = $stepData;
                }
            }
            $encodedParams = json_encode($params);
            $scoreData = [
                'appraisal_form_id' => $appraisalFormId,
                'appraisal_criteria_id' => $criteriaId,
                'params' => $encodedParams,
                'appraisal_forms_criterias_scores_links' => $data
            ];
            $scoreData['final_score'] = ($criteriaId == $finalScore) ? 1 : 0;
            $requestData[$this->alias()]['appraisal_forms_criterias'][] = [
                'appraisal_form_id' => $appraisalFormId,
                'appraisal_criteria_id' => $criteriaId,
                'appraisal_forms_criterias_score' => $scoreData
            ];
        }

        $newOptions = [];
        $newOptions['associated'] = [
            'AppraisalFormsCriterias',
            'AppraisalFormsCriterias.AppraisalFormsCriteriasScores',
            'AppraisalFormsCriterias.AppraisalFormsCriteriasScores.AppraisalFormsCriteriasScoresLinks'
        ];

        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);
    }

    public function onGetFinalScore(Event $event, Entity $entity)
    {
        $formId = $entity->id;
        $result = $this->find()
            ->matching('AppraisalFormsCriterias.AppraisalCriterias.FieldTypes', function($q) {
                return $q
                    ->where([
                        'FieldTypes.code' => self::SCORE_TYPE_CODE
                    ]);
            })
            ->matching('AppraisalFormsCriterias.AppraisalFormsCriteriasScores', function($q) use ($formId) {
                return $q
                    ->where([
                        'AppraisalFormsCriteriasScores.final_score' => self::IS_FINAL_SCORE,
                        'AppraisalFormsCriteriasScores.appraisal_form_id' => $formId
                    ]);
            })
            ->all();

        if (!$result->isEmpty()) {
            $finalScoreEntity = $result->first();

            if (!empty($finalScoreEntity->_matchingData['AppraisalCriterias']['code']) && 
                !empty($finalScoreEntity->_matchingData['AppraisalCriterias']['name'])
            ) {
                $finalQnsCode = $finalScoreEntity->_matchingData['AppraisalCriterias']['code'];
                $finalQnsName = $finalScoreEntity->_matchingData['AppraisalCriterias']['name'];
                return ($finalQnsCode.' - '.$finalQnsName);
            }
        }

        return "<i class='fa fa-minus'></i>";
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        if (!is_null($entity)) {
            if ($entity->has('code') && $entity->has('name')) {
                return ($entity->code . ' - ' . $entity->name);
            }
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'code') {
            return __('Form');
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }


    public function onGetScoreFieldsElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'view') {
            $scoreEntity = $attr['entity'];
            $criteriaId = $scoreEntity->appraisal_criteria_id;
            $criteriaCode = $scoreEntity->appraisal_criteria->code;
            $criteriaName = $scoreEntity->appraisal_criteria->name;
            $label = $criteriaCode . ' - ' . $criteriaName;
            $formId = $scoreEntity->appraisal_form_id;

            $form = $event->subject()->Form;
            $form->unlockField($this->alias() . '.appraisal_forms_criterias_score.' . $criteriaId);

            $arrayFields = [];
            $arrayFields = $this->getSelectedCriteriaList($scoreEntity);

            $cellCount = 0;
            $tableHeaders = [__('Criteria')];
            $tableCells = [];

            foreach ($arrayFields as $obj) {
                $fieldPrefix = $attr['model'] . '.appraisal_forms_criterias_score.' . $criteriaId . '.' . $cellCount++;
                $joinDataPrefix = $fieldPrefix . '._joinData';

                $cellData = '';
                $cellData .= $form->hidden($fieldPrefix . '.appraisal_criteria_linked_id', ['value' => $obj['appraisal_criteria_linked_id']]);
                $cellData .= $form->hidden($fieldPrefix . '.appraisal_form_id', ['value' => $formId]);
                $cellData .= $form->hidden($fieldPrefix . '.appraisal_criteria_id', ['value' => $criteriaId]);
                $cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $obj['name']]);
                $cellData .= $form->hidden($joinDataPrefix.".code", ['value' => $obj['code']]);
                $cellData .= $form->hidden($joinDataPrefix.".field_type", ['value' => $obj['field_type']]);

                $criteriaLabel = $obj['code'] . ' - ' . $obj['name'];

                $rowData = [];
                $rowData[] = $criteriaLabel . $cellData;
                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;

            $attr['label'] = $label;
            $attr['field'] = 'selected_score.' . $criteriaId;
            $attr['default'] = '';

            $attr['add_field'] = __('Criterias');

            // Display the steps
            $attr2 = [];
            $attr2['model'] = $this->alias();
            $attr2['field'] = 'steps.'.$criteriaId;
            $attr2['add_steps_field'] = __('Steps');

            // Let the steps to auto select
            $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');

            $appraisalFormsCriteriasScoresEntity = $appraisalFormsCriteriasScores
                ->find()
                ->where([
                    $appraisalFormsCriteriasScores->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriteriasScores->aliasField('appraisal_criteria_id') => $criteriaId
                ])
                ->first();

            $attr2['attr']['value'] = "<i class='fa fa-minus'></i>";


            if (!is_null($appraisalFormsCriteriasScoresEntity) && !is_null($appraisalFormsCriteriasScoresEntity->params)) {
                $scoresParamsJSON = json_decode($appraisalFormsCriteriasScoresEntity->params);

                if (!is_null($scoresParamsJSON)) {
                    if(!is_null($scoresParamsJSON) && array_key_exists('formula', $scoresParamsJSON) && !is_null($scoresParamsJSON->formula)) {
                        $attr2['attr']['value'] = $this->translateStepToReadableWords($scoresParamsJSON->formula);
                    }
                }
            }

            return $event->subject()->renderElement('CustomField.form_criterias_score_fields', ['attr' => $attr, 'attr2' => $attr2]);
        } elseif ($action == 'edit') {

            $scoreEntity = $attr['entity'];
            $criteriaId = $scoreEntity->appraisal_criteria_id;

            $criteriaCode = $scoreEntity->appraisal_criteria->code;
            $criteriaName = $scoreEntity->appraisal_criteria->name;
            $label = $criteriaCode . ' - ' . $criteriaName;
            $formId = $entity->id;


            $form = $event->subject()->Form;
            $form->unlockField($this->alias() . '.appraisal_forms_criterias_score.' . $criteriaId);

            // Generate the question for the dropdownlist
            $validCriteriaTypes = [self::SCORE_TYPE_CODE, self::SLIDER_TYPE_CODE];

            $customFieldOptions = $this->AppraisalFormsCriterias
                ->find('list', [
                    'keyField' => 'appraisal_criteria_id',
                    'valueField' => function ($row) {
                        // pr($row);
                        if($row->has('appraisal_criteria') && !empty($row->appraisal_criteria)) {
                            if($row->appraisal_criteria->has('code') && !empty($row->appraisal_criteria->code)) {
                                return $row->appraisal_criteria->code.' - '.$row->appraisal_criteria->name;
                            }
                        }
                    }
                ])
                ->contain(['AppraisalCriterias.FieldTypes'])
                ->where([
                    $this->AppraisalFormsCriterias->aliasField('appraisal_form_id') => $formId,
                    $this->AppraisalFormsCriterias->AppraisalCriterias->FieldTypes->aliasField('code IN ') => $validCriteriaTypes,
                    $this->AppraisalFormsCriterias->aliasField('appraisal_criteria_id !=') => $criteriaId,

                ])
                ->toArray();

            $requestData = $this->request->data;
            $arrayFields = [];

            $arrayFields = $this->getSelectedCriteriaList($scoreEntity, $requestData);

            $cellCount = 0;
            $tableHeaders = [__('Criteria'), ['' => ['style' => 'width: 100px']]];
            $tableCells = [];


            foreach ($arrayFields as $obj) {
                $fieldPrefix = $attr['model'] . '.appraisal_forms_criterias_score.' . $criteriaId . '.' . $cellCount++;
                $joinDataPrefix = $fieldPrefix . '._joinData';

                $cellData = '';
                $cellData .= $form->hidden($fieldPrefix . '.appraisal_criteria_linked_id', ['value' => $obj['appraisal_criteria_linked_id']]);
                $cellData .= $form->hidden($fieldPrefix . '.appraisal_form_id', ['value' => $formId]);
                $cellData .= $form->hidden($fieldPrefix . '.appraisal_criteria_id', ['value' => $criteriaId]);
                $cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $obj['name']]);
                $cellData .= $form->hidden($joinDataPrefix.".code", ['value' => $obj['code']]);

                $cellData .= $form->hidden($joinDataPrefix.".field_type", ['value' => $obj['field_type']]);

                $criteriaLabel = $obj['code'] . ' - ' . $obj['name'];


                $rowData = [];
                $rowData[] = $criteriaLabel . $cellData;
                $rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

                //Remove away the selected criterias from the dropdownlist
                unset($customFieldOptions[$obj['appraisal_criteria_linked_id']]);
                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;

            $selectedCustomField = '';
            $customFieldOptions = ['' => '-- '. __('Add') .' --'] + $customFieldOptions;
            ksort($customFieldOptions);
            $this->advancedSelectOptions($customFieldOptions, $selectedCustomField);

            $attr['label'] = $label;
            $attr['field'] = 'selected_score.' . $criteriaId;
            $attr['default'] = '';
            $attr['options'] = $customFieldOptions;
            $attr['add_field'] = __('Criterias');

            // Display the steps
            // To be refactor
            $attr2 = [];
            $attr2['model'] = $this->alias();
            $attr2['field'] = 'steps.'.$criteriaId;
            $attr2['add_steps_field'] = __('Steps');

            $attr2['options'] = $this->translateSteps();

            if ($this->request->is(['post', 'put']) && isset($requestData['AppraisalScores']['steps'])) {
                $attr2['attr']['value'] = $requestData['AppraisalScores']['steps'][$criteriaId];
            }else {
                $attr2['attr']['value'] = "<i class='fa fa-minus'></i>";
            }

            // Let the steps to auto select
            $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');

            $appraisalFormsCriteriasScoresEntity = $appraisalFormsCriteriasScores
                ->find()
                ->where([
                    $appraisalFormsCriteriasScores->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriteriasScores->aliasField('appraisal_criteria_id') => $criteriaId
                ])
                ->first();



            if (!is_null($appraisalFormsCriteriasScoresEntity) && !is_null($appraisalFormsCriteriasScoresEntity->params)) {
                $scoresParamsJSON = json_decode($appraisalFormsCriteriasScoresEntity->params);
                if (!is_null($scoresParamsJSON)) {
                    if(!is_null($scoresParamsJSON) && array_key_exists('formula', $scoresParamsJSON) && !is_null($scoresParamsJSON->formula)) {
                        $attr2['attr']['value'] = $scoresParamsJSON->formula;
                    }
                }
            }

            return $event->subject()->renderElement('CustomField.form_criterias_score_fields', ['attr' => $attr, 'attr2' => $attr2]);
        }
    }

    public function onUpdateFieldFinalScore(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['attr']['entity'];
            $scoreCriteriasOptions = [];
            $selectedFinal = '';            

            if ($entity->has('appraisal_forms_criterias')) {
                $criteriaList = $entity->appraisal_forms_criterias;
                foreach ($criteriaList as $scoreEntity) {
                    if (!is_null($scoreEntity->appraisal_forms_criterias_score)) {
                        if ($scoreEntity->appraisal_forms_criterias_score->final_score == self::IS_FINAL_SCORE) {
                            $selectedFinal = $scoreEntity->appraisal_forms_criterias_score->appraisal_criteria_id;
                        }
                        $name = $scoreEntity->appraisal_criteria->name;
                        $code = $scoreEntity->appraisal_criteria->code;
                        $id = $scoreEntity->appraisal_criteria->id;
                        $scoreCriteriasOptions[$id] = $code . ' - ' . $name;
                    }
                }
            }

            if ($request->is(['get'])) {
                $attr['value'] = $selectedFinal;
                $attr['attr']['value'] = $selectedFinal;
            }

            $attr['options'] = $scoreCriteriasOptions;
            return $attr;
        }
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            if (array_key_exists('attr', $attr) && array_key_exists('entity', $attr['attr'])) {
                $entity = $attr['attr']['entity'];
                if ($entity->has('code') && $entity->has('name')) {
                    $attr['attr']['value'] = $entity->code . ' - ' . $entity->name;
                    return $attr;
                }
            }

        }
    }

    // Appraisals - Scores Page (Index)
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $formId = $entity->id;
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        // If there exists a staff appraisal form that has done before by any staff den the admin able to edit the score template else NO.
        if (!$this->isAppraisalScoreAnswersEditable($formId)) {
            unset($buttons['edit']);
        }

        return $buttons;
    }

    private function isAppraisalScoreAnswersEditable($formId)
    {
        $appraisalScoreAnswers = TableRegistry::get('StaffAppraisal.AppraisalScoreAnswers');

        $appraisalScoreAnswersEntities = $appraisalScoreAnswers->find()
            ->where([
                $appraisalScoreAnswers->aliasField('appraisal_form_id') => $formId
            ]);

        if ($appraisalScoreAnswersEntities->count() >= 1) {
            return false;
        }else {
            return true;
        }
    }

    private function getSelectedCriteriaList($scoreEntity, $requestData = null) 
    {
        $arrayFields = [];
        $appraisalCriteriaId = $scoreEntity->appraisal_criteria_id;
        $appraisalFormId = $scoreEntity->appraisal_form_id;
        $criteriaList = '';

        if ($this->request->is(['get'])) {
            if (isset($scoreEntity->id)) {
                $customFields = $this->getScoreCriteriaLinkedFields($appraisalFormId, $appraisalCriteriaId);
                foreach ($customFields as $key => $obj) {
                    $arrayFields[] = [
                        'name' => $obj->appraisal_forms_criterias_link->appraisal_criteria->name,
                        'code' => $obj->appraisal_forms_criterias_link->appraisal_criteria->code,
                        'appraisal_criteria_linked_id' => $obj->appraisal_forms_criterias_link->appraisal_criteria->id,
                        'appraisal_form_id' => $appraisalFormId,
                        'appraisal_criteria_id' => $appraisalCriteriaId,
                        'field_type' => $obj->appraisal_forms_criterias_link->appraisal_criteria->field_type->code

                    ];
                }
            }
        } elseif ($this->request->is(['post', 'put'])) {
            $requestAlias = $requestData[$this->alias()];
            if (array_key_exists('appraisal_forms_criterias_score', $requestAlias) &&
                array_key_exists($appraisalCriteriaId, $requestAlias['appraisal_forms_criterias_score'])
            ) {
                $criteriaList = $requestAlias['appraisal_forms_criterias_score'][$appraisalCriteriaId];

                foreach ($criteriaList as $obj) {
                    $linkedId = $obj['appraisal_criteria_linked_id'];
                    $arrayFields[] = [
                        'name' => $obj['_joinData']['name'],
                        'code' => $obj['_joinData']['code'],
                        'appraisal_criteria_linked_id' => $linkedId,
                        'appraisal_form_id' => $appraisalFormId,
                        'appraisal_criteria_id' => $appraisalCriteriaId,
                        'field_type' => $obj['_joinData']['field_type']
                    ];
                }
            }

            if (array_key_exists('selected_score', $requestData[$this->alias()]) &&
                array_key_exists($appraisalCriteriaId, $requestData[$this->alias()]['selected_score']) &&
                !is_null($requestData[$this->alias()]['selected_score'][$appraisalCriteriaId])
                ) {
                    $selectedCriteriaId = $requestData[$this->alias()]['selected_score'][$appraisalCriteriaId];
                    $appraisalCriterias = TableRegistry::get('StaffAppraisal.AppraisalCriterias');

                    $record = $appraisalCriterias
                        ->find()
                        ->where([$appraisalCriterias->aliasField('id') => $selectedCriteriaId])
                        ->contain(['FieldTypes'])
                        ->first();

                    if (!is_null($record)) {
                        $scoreField = $record->field_type->code;
                        $arrayFields[] = [
                            'field_type' => $scoreField,
                            'name' => $record->name,
                            'code' => $record->code,
                            'appraisal_criteria_linked_id' => $record->id,
                            'appraisal_form_id' => $appraisalFormId,
                            'appraisal_criteria_id' => $appraisalCriteriaId
                        ];
                    }
                }
        }
        return $arrayFields;
    }

    private function getScoreCriteriaLinkedFields($formId, $criteriaId)
    {
        $appraisalFormsCriteriasScoresLinks = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScoresLinks');

        return $appraisalFormsCriteriasScoresLinks
            ->find()
            ->where([
                $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_form_id') => $formId,
                $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_criteria_id') => $criteriaId
            ])
            ->contain([
                'AppraisalFormsCriteriasLinks.AppraisalCriterias.FieldTypes'
            ])
            ->toArray();
    }

    private function setupFields(Entity $entity = null)
    {
        $this->field('name', ['visible' => false]);

        $this->field('code', [
            'type' => 'disabled',
            'attr' => [
                'entity' => $entity
            ]
        ]);
        $this->field('final_score', [
            'type' => 'select',
            'attr' => [
                'entity' => $entity
            ]
        ]);

        $this->setFieldOrder(['code', 'final_score']);

        if (!is_null($entity)) {
            $criteriaList = $entity->appraisal_forms_criterias;
            foreach ($criteriaList as $scoreEntity) {
                $criteriaCode = $scoreEntity->appraisal_criteria->code;
                $criteriaName = $scoreEntity->appraisal_criteria->name;
                $criteriaId = $scoreEntity->appraisal_criteria->id;

                $this->field('appraisal_forms_criterias.' . $criteriaId, [
                    'override' => true,
                    'type' => 'score_fields',
                    'entity' => $scoreEntity,
                    'valueClass' => 'table-full-width'
                ]);
            }
        }
    }

    private function translateSteps()
    {
        $translatedSteps = [];
        foreach ($this->stepsOptions as $formulaKey => $formula) {
            $translatedSteps[$formulaKey] = __($formula);
        }

        return $translatedSteps;
    }

    private function translateStepToReadableWords($words)
    {
        switch ($words) {
            // case "0":
            //     return __("-- Select --");
            case "AVG":
                return __("Average");
            case "SUM":
                return __("Sum");
        }
    }

    private function populateScoreDependency($scoreDependencyArrayField, $mainScoreId, $selectedScore)
    {
        $scoreDependency = [];

        // Only add only score field type dependency
        foreach ($scoreDependencyArrayField as $scoreDependencyMainScoreId => $score) {
            foreach ($score as $dependency) {
                if ($dependency['_joinData']['field_type'] == self::SCORE_TYPE_CODE) {
                    $scoreDependency[$scoreDependencyMainScoreId][] = $dependency;
                }
            }
        }
        $this->validateCircularDependacy($mainScoreId, $selectedScore, $scoreDependency);
    }

    private function validateCircularDependacy($mainId, $traceId, $scoreDependency)
    {
        if (!is_null($scoreDependency)) {
            $this->recursiveFind($traceId, $mainId, $scoreDependency);
        }
    }

    /**
    *   This recursiveFind is to find if there any score dependency it will take the mainID and trace the linkage ID
    *   If there's exists one traceID that is link back to the mainID that mean there exists a score dependency and will return validationPass *   to false;
    *
    **/
    private function recursiveFind($traceId, $mainId, $scoreDependency) {
        if (array_key_exists($traceId, $scoreDependency)) {
            foreach ($scoreDependency[$traceId] as $childId) {
                if ($childId['_joinData']['field_type'] == self::SCORE_TYPE_CODE) {
                    if ($childId['appraisal_criteria_linked_id'] == $mainId) {
                        $this->validationPass = false;
                        return;
                    } else {
                        $this->recursiveFind($childId['appraisal_criteria_linked_id'], $mainId, $scoreDependency);
                    }
                }
            }
        } else {
            $this->validationPass = true;
        }
    }


}
