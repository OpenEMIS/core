<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\Log\Log;
use Cake\Utility\Text;

class RenderRepeaterBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomRepeaterElement(Event $event, $action, $entity, $attr, $options=[]) {
        $CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
        $CustomFields = TableRegistry::get('Survey.SurveyQuestions');
        $CustomFormsFields = TableRegistry::get('Survey.SurveyFormsQuestions');
        $RepeaterSurveys = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
        $RepeaterSurveyAnswers = TableRegistry::get('InstitutionRepeater.RepeaterSurveyAnswers');

        $model = $this->_table;
        $session = $model->request->session();
        $registryAlias = $model->registryAlias();
        $debugInfo = $model->alias() . ' #'.$entity->id.' (Institution ID: ' . $entity->institution_id . ', Academic Period ID: ' . $entity->academic_period_id . ', Survey Form ID: ' . $entity->survey_form_id . ')';

        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);
        $customField = $attr['customField'];
        $fieldKey = $attr['attr']['fieldKey'];
        $formKey = $attr['attr']['formKey'];
        $fieldId = $customField->id;
        
        $form = $event->subject()->Form;
        $fieldPrefix = $attr['model'] . '.institution_repeater_surveys.' . $fieldId;

        $tableHeaders = [];
        $tableCells = [];
        $cellCount = 0;

        $formId = null;
        // Get Survey Form ID
        if ($customField->has('params') && !empty($customField->params)) {
            $params = json_decode($customField->params, true);
            if (array_key_exists('survey_form_id', $params)) {
                $formId = $params['survey_form_id'];
            }
        }
        // End

        if (!is_null($formId)) {
            $questions = $CustomFormsFields
                ->find('all')
                ->innerJoin([$CustomFields->alias() => $CustomFields->table()],
                    [
                        $CustomFields->aliasField('id = ') . $CustomFormsFields->aliasField($fieldKey),
                    ]
                )
                ->contain([
                    'CustomFields.CustomFieldOptions' => function($q) {
                        return $q
                            ->find('visible')
                            ->find('order');
                    }
                ])
                ->order([$CustomFormsFields->aliasField('order')])
                ->where([$CustomFormsFields->aliasField($formKey) => $formId])
                ->group([$CustomFormsFields->aliasField($fieldKey)])
                ->toArray();

            if (!empty($questions)) {
                $institutionId = $entity->institution_id;
                $periodId = $entity->academic_period_id;

                // Build table header
                $headerHtml = __('No.');
                $headerHtml .= $form->hidden("$fieldPrefix.$formKey", ['value' => $formId]);
                $tableHeaders[] = $headerHtml;
                $colOffset = 1; // 0 -> No.

                foreach ($questions as $colKey => $question) {
                    $questionName = !is_null($question->name) ? $question->name : $question->custom_field->name;
                    $tableHeaders[$colKey + $colOffset] = $questionName;
                }

                // remove button
                if ($action == 'edit') {
                    $tableHeaders[] = '';
                }
                // End

                $repeaters = [];
                if ($model->request->is(['get'])) {
                    $sessionKey = "$registryAlias.repeaters.$fieldId";
                    if ($session->check($sessionKey)) {
                        $repeaters = $session->read($sessionKey);
                        $session->delete($sessionKey);
                    }
                } else if ($model->request->is(['post', 'put'])) {
                    $requestData = $model->request->data;
                    $submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';

                    if ($submit == 'save') {
                        // do nothing
                    } else if ($submit == 'addRepeater') {
                        // from existing rows
                        if (array_key_exists($model->alias(), $requestData)) {
                            if (array_key_exists('institution_repeater_surveys', $requestData[$model->alias()])) {
                                if (array_key_exists($fieldId, $requestData[$model->alias()]['institution_repeater_surveys'])) {
                                    foreach ($requestData[$model->alias()]['institution_repeater_surveys'][$fieldId] as $repeaterKey => $repeaterObj) {
                                        if ($repeaterKey == 'survey_form_id') { continue; }
                                        $repeaters[] = $repeaterKey;
                                    }
                                }
                            }

                            // rely on repeater_question_id field added to InstitutionSurveys
                            if (array_key_exists('repeater_question_id', $requestData[$model->alias()])) {
                                $selectedFieldId = $requestData[$model->alias()]['repeater_question_id'];
                                if ($fieldId == $selectedFieldId) {
                                    // add one more rows
                                    $repeaters[] = Text::uuid();
                                }
                            }
                        }
                    }
                }

                if (!empty($repeaters)) {
                    $fieldTypes = $CustomFieldTypes
                        ->find('list', ['keyField' => 'code', 'valueField' => 'value'])
                        ->toArray();

                    $rowCount = 1;
                    foreach ($repeaters as $rowKey => $repeaterId) {
                        $rowPrefix = "$fieldPrefix.$repeaterId";

                        $rowData = [];
                        $rowInput = "";

                        if ($action == 'view') {
                            $rowData[] = $rowCount;
                        } else if ($action == 'edit') {
                            if (isset($entity->institution_repeater_surveys[$fieldId][$repeaterId]['id'])) {
                                $rowInput .= $form->hidden($rowPrefix.".id", ['value' => $entity->institution_repeater_surveys[$fieldId][$repeaterId]['id']]);
                            }

                            $rowData[] = $rowCount . $rowInput;
                        }

                        foreach ($questions as $colKey => $question) {
                            $questionId = $question->custom_field->id;
                            $questionType = $question->custom_field->field_type;

                            $cellPrefix = "$rowPrefix.$questionId";
                            $cellInput = "";
                            $cellValue = "";
                            $cellOptions = ['label' => false, 'value' => ''];
                            $answerObj = null;

                            // put back answer value for edit and validation failed
                            if (isset($entity->institution_repeater_surveys[$fieldId][$repeaterId][$questionId])) {
                                $answerObj = $entity->institution_repeater_surveys[$fieldId][$repeaterId][$questionId];
                            }

                            switch ($questionType) {
                                case 'TEXT':
                                    $answerValue = !is_null($answerObj['text_value']) ? $answerObj['text_value'] : null;

                                    $cellOptions['type'] = 'string';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    $cellValue = !is_null($answerValue) ? $answerValue : '';
                                    break;
                                case 'NUMBER':
                                    $answerValue = !is_null($answerObj['number_value']) ? $answerObj['number_value'] : null;

                                    $cellOptions['type'] = 'number';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    $cellValue = !is_null($answerValue) ? $answerValue : '';
                                    break;
                                case 'DROPDOWN':
                                    $answerValue = !is_null($answerObj['number_value']) ? $answerObj['number_value'] : null;

                                    $dropdownOptions = [];
                                    $dropdownDefault = null;
                                    foreach ($question->custom_field->custom_field_options as $key => $obj) {
                                        $dropdownOptions[$obj->id] = $obj->name;
                                        if ($obj->is_default == 1) {
                                            $dropdownDefault = $obj->id;
                                        }
                                    }
                                    $dropdownDefault = !is_null($dropdownDefault) ? $dropdownDefault : key($dropdownOptions);

                                    $cellOptions['type'] = 'select';
                                    $cellOptions['default'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                    $cellOptions['options'] = $dropdownOptions;

                                    $cellValue = !is_null($answerValue) ? $dropdownOptions[$answerValue] : $dropdownOptions[$dropdownDefault];
                                    break;
                                default:
                                    break;
                            }

                            $cellInput .= $form->input($cellPrefix.".".$fieldTypes[$questionType], $cellOptions);

                            if ($action == 'view') {
                                $rowData[$colKey+$colOffset] = $cellValue;
                            } else if ($action == 'edit') {
                                $rowData[$colKey+$colOffset] = $cellInput;
                            }
                        }

                        // remove button
                        if ($action == 'edit') {
                            $rowData[] = '<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);"><i class="fa fa-close"></i>' . __('Remove') . '</button>';
                        }

                        $tableCells[$rowKey] = $rowData;
                        $rowCount++;
                    }
                } else {
                    // No repeaters
                    Log::write('debug', $debugInfo . ': has no repeaters.');
                }
            } else {
                // Survey Questions not setup for the form or not in the supported field type.
                Log::write('debug', $debugInfo . ': Student List Survey Form ID: '.$formId.' has no questions.');
            }
        } else {
            // Survey Form ID not found
            Log::write('debug', $debugInfo . ': Student List Survey Form ID is not configured.');
        }

        // $attr['attr']['classOptions'] = $classOptions;
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        } else if ($action == 'edit') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function formatRepeaterEntity(Event $event, Entity $entity, ArrayObject $settings) {
        $surveysArray = $entity->has('institution_repeater_surveys') ? $entity->institution_repeater_surveys : [];
        $repeatersArray = $entity->has('institution_repeaters') ? $entity->institution_repeaters : [];

        if (isset($entity->id)) {
            $fieldKey = $settings['fieldKey'];
            $formKey = $settings['formKey'];
            $customField = $settings['customField'];

            $params = json_decode($customField->params, true);
            if (array_key_exists($formKey, $params)) {
                $RepeaterSurveys = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
                $RepeaterSurveyAnswers = TableRegistry::get('InstitutionRepeater.RepeaterSurveyAnswers');

                $status = $entity->status_id;
                $institutionId = $entity->institution_id;
                $periodId = $entity->academic_period_id;
                $formId = $params[$formKey];

                $surveysArray[$customField->id][$formKey] = $formId;
                $repeatersArray[$customField->id] = [];
                $surveyResults = $RepeaterSurveys
                    ->find()
                    ->contain(['CustomFieldValues'])
                    ->where([
                        $RepeaterSurveys->aliasField('status_id') => $status,
                        $RepeaterSurveys->aliasField('institution_id') => $institutionId,
                        $RepeaterSurveys->aliasField('academic_period_id') => $periodId,
                        $RepeaterSurveys->aliasField($formKey) => $formId
                    ])
                    ->all();

                if (!$surveyResults->isEmpty()) {
                    foreach ($surveyResults as $survey) {
                        $answersArray = [];
                        if ($survey->has('custom_field_values')) {
                            foreach ($survey->custom_field_values as $answer) {
                                $answersArray[$answer->{$fieldKey}] = [
                                    'text_value' => $answer->text_value,
                                    'number_value' => $answer->number_value,
                                    'textarea_value' => $answer->textarea_value,
                                    'date_value' => $answer->date_value,
                                    'time_value' => $answer->time_value
                                ];
                            }
                        }
                        $surveysArray[$customField->id][$survey->repeater_id] = $answersArray;
                        $surveysArray[$customField->id][$survey->repeater_id]['id'] = $survey->id;
                        $repeatersArray[$customField->id][] = $survey->repeater_id;
                    }
                }
            }
        }

        $model = $this->_table;
        $session = $model->request->session();
        $registryAlias = $model->registryAlias();
        $sessionKey = "$registryAlias.repeater_surveys";
        $session->write($sessionKey, $surveysArray);
        $repeaterSessionKey = "$registryAlias.repeaters";
        $session->write($repeaterSessionKey, $repeatersArray);

        $entity->set('institution_repeater_surveys', $surveysArray);
        $entity->set('institution_repeaters', $repeatersArray);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
        if ($entity->has('institution_repeater_surveys')) {
            $fieldKey = 'survey_question_id';
            $formKey = 'survey_form_id';
            $RepeaterSurveys = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
            $RepeaterSurveyAnswers = TableRegistry::get('InstitutionRepeater.RepeaterSurveyAnswers');

            $status = $entity->status_id;
            $institutionId = $entity->institution_id;
            $periodId = $entity->academic_period_id;
            $parentFormId = $entity->{$formKey};

            foreach ($entity->institution_repeater_surveys as $fieldId => $fieldObj) {
                $formId = $fieldObj[$formKey];
                unset($fieldObj[$formKey]);

                // Logic to delete all answers before re-insert
                $repeaterIds = array_keys($fieldObj);
                $originalRepeaterIds = [];
                $originalRepeaterSurveys = $entity->extractOriginal(['institution_repeater_surveys']);
                if (array_key_exists('institution_repeater_surveys', $originalRepeaterSurveys)) {
                    if (array_key_exists($fieldId, $originalRepeaterSurveys['institution_repeater_surveys'])) {
                        if (array_key_exists($formKey, $originalRepeaterSurveys['institution_repeater_surveys'][$fieldId])) {
                            unset($originalRepeaterSurveys['institution_repeater_surveys'][$fieldId][$formKey]);
                        }
                        $originalRepeaterIds = array_keys($originalRepeaterSurveys['institution_repeater_surveys'][$fieldId]);
                    }
                }

                $surveyIds = [];
                if (!empty($originalRepeaterIds)) {
                    $surveyIds = $RepeaterSurveys
                        ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                        ->where([
                            $RepeaterSurveys->aliasField('status_id') => $status,
                            $RepeaterSurveys->aliasField('institution_id') => $institutionId,
                            $RepeaterSurveys->aliasField('academic_period_id') => $periodId,
                            $RepeaterSurveys->aliasField($formKey) => $formId,
                            $RepeaterSurveys->aliasField('repeater_id IN ') => $originalRepeaterIds
                        ])
                        ->toArray();
                }
                
                if (!empty($surveyIds)) {
                    // always deleted all existing answers before re-insert
                    $RepeaterSurveyAnswers->deleteAll([
                        $RepeaterSurveyAnswers->aliasField('institution_repeater_survey_id IN ') => $surveyIds
                    ]);
                }

                if (!empty($repeaterIds)) {
                    if (!empty($originalRepeaterIds)) {
                        $missingRepeaters = array_diff($originalRepeaterIds, $repeaterIds);
                        if (!empty($missingRepeaters)) {
                            // if user has remove particular repeater from form, delete away that repeater from database too
                            $RepeaterSurveys->deleteAll([
                                $RepeaterSurveys->aliasField('status_id') => $status,
                                $RepeaterSurveys->aliasField('institution_id') => $institutionId,
                                $RepeaterSurveys->aliasField('academic_period_id') => $periodId,
                                $RepeaterSurveys->aliasField($formKey) => $formId,
                                $RepeaterSurveys->aliasField('repeater_id IN ') => $missingRepeaters
                            ]);
                        }
                    }
                } else {
                    // if user remove all rows from form, delete away all repeater records
                    $RepeaterSurveys->deleteAll([
                        $RepeaterSurveys->aliasField('status_id') => $status,
                        $RepeaterSurveys->aliasField('institution_id') => $institutionId,
                        $RepeaterSurveys->aliasField('academic_period_id') => $periodId,
                        $RepeaterSurveys->aliasField($formKey) => $formId
                    ]);
                }
                // End

                foreach ($fieldObj as $repeaterId => $repeaterObj) {
                    if (is_array($repeaterObj)) {                        
                        $surveyData = [
                            'status_id' => $status,
                            'institution_id' => $institutionId,
                            'academic_period_id' => $periodId,
                            $formKey => $formId,
                            'parent_form_id' => $parentFormId,
                            'repeater_id' => $repeaterId
                        ];
                        // for edit record
                        if (array_key_exists('id', $repeaterObj)) {
                            $surveyData['id'] = $repeaterObj['id'];
                            unset($repeaterObj['id']);
                        }
                        // End

                        $answers = [];
                        foreach ($repeaterObj as $questionId => $answerObj) {
                            // checking to skip insert if is empty
                            $textValue = isset($answerObj['text_value']) && strlen($answerObj['text_value']) > 0 ? $answerObj['text_value'] : null;
                            $numberValue = isset($answerObj['number_value']) && strlen($answerObj['number_value']) > 0 ? $answerObj['number_value'] : null;
                            $textareaValue = isset($answerObj['textarea_value']) && strlen($answerObj['textarea_value']) > 0 ? $answerObj['textarea_value'] : null;
                            $dateValue = isset($answerObj['date_value']) && strlen($answerObj['date_value']) > 0 ? $answerObj['date_value'] : null;
                            $timeValue = isset($answerObj['time_value']) && strlen($answerObj['time_value']) > 0 ? $answerObj['time_value'] : null;

                            if (!is_null($textValue) || !is_null($numberValue) || !is_null($textareaValue) || !is_null($dateValue) || !is_null($timeValue)) {
                                $answerObj = array_merge($answerObj, [
                                    $fieldKey => $questionId
                                ]);

                                $answers[] = $answerObj;
                            }
                        }

                        $surveyData['custom_field_values'] = $answers;
                        $surveyEntity = $RepeaterSurveys->newEntity($surveyData, ['validate' => false]);
                        // save repeater by repeater
                        if ($RepeaterSurveys->save($surveyEntity)) {
                        } else {
                            Log::write('debug', $surveyEntity->errors());
                        }
                    }
                }
            }
        }
    }

    public function updateWorkflowStatus(Event $event, $entity, $statusId) {
        $RepeaterSurveys = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
        $RepeaterSurveys->updateAll(
            ['status_id' => $statusId],
            [
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'parent_form_id' => $entity->survey_form_id
            ]
        );
    }
}
