<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\Log\Log;
use Cake\Utility\Text;

use Cake\View\Helper\IdGeneratorTrait;
use ControllerAction\Model\Traits\PickerTrait;

class RenderRepeaterBehavior extends RenderBehavior {
    use IdGeneratorTrait;
    use PickerTrait;

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
        $form->unlockField($fieldPrefix);
        $unlockFields = [$attr['model'] . '.repeater_question_id'];
        $form->unlockField($attr['model'] . '.repeater_question_id');
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
                        // get repeaters from request data
                        $repeaters = $this->getRepeaters($model, $requestData, $fieldId);
                    } else if ($submit == 'addRepeater') {
                        // from existing rows
                        $repeaters = $this->getRepeaters($model, $requestData, $fieldId);

                        if (array_key_exists($model->alias(), $requestData)) {
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
                    
                    //Get and build repeater error messages, repeater by repeater
                    $repeaterErrorObj = null;
                    foreach ($repeaters as $rowKey => $repeaterId) {
                        if($entity->institution_repeater_surveys_error_obj){
                            foreach($entity['institution_repeater_surveys_error_obj'] as $key => $value){
                                if($value['repeater_id'] == $repeaterId){
                                   $repeaterErrorObj = $value['custom_field_values'];
                                }
                            }
                        }
                        
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
                            $questionName = !is_null($question->name) ? $question->name : $question->custom_field->name;
                            $tableHeaders[$colKey + $colOffset] = $questionName;

                            $questionId = $question->custom_field->id;
                            $questionType = $question->custom_field->field_type;

                            $cellPrefix = "$rowPrefix.$questionId";
                            $cellInput = "";
                            $cellValue = "";
                            $cellOptions = ['label' => false, 'value' => ''];
                            $answerObj = null;

                            //To get the error message of each individual fields in a repeater
                            $errors = null;
                            $errorInput = "";
                            if($repeaterErrorObj){
                                foreach ($repeaterErrorObj as $repeaterKey => $repeaterValue) {
                                    if($repeaterValue['survey_question_id'] == $questionId){
                                        $fieldErrors = $repeaterValue->errors();
                                        foreach ($fieldErrors as $fieldErrorRule => $fieldErrorMessage) {
                                            foreach ($fieldErrorMessage as $key => $value) {
                                                $errors = $value;
                                                if(!is_null($errors)){
                                                    $errorInput = '<div class="error-message">'.$errors.'</div>';
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // put back answer value for edit and validation failed
                            if (isset($entity->institution_repeater_surveys[$fieldId][$repeaterId][$questionId])) {
                                $answerObj = $entity->institution_repeater_surveys[$fieldId][$repeaterId][$questionId];
                            }

                            switch ($questionType) {
                                case 'TEXT':
                                    $answerValue = !is_null($answerObj['text_value']) ? $answerObj['text_value'] : null;

                                    $cellOptions['type'] = 'string';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    // input mask for custom text
                                    if ($question['custom_field']->has('params') && !empty($question['custom_field']->params)) {
                                        $params = json_decode($question['custom_field']->params, true);
                                        if (array_key_exists('input_mask', $params) && !empty($params['input_mask'])) {
                                            $HtmlField = $event->subject();
                                            $HtmlField->includes['jasny']['include'] = true;
                                            $cellOptions['data-mask'] = $params['input_mask'];
                                        }
                                    }
                                    //end

                                    $cellValue = !is_null($answerValue) ? $answerValue : '';
                                    break;
                                case 'NUMBER':
                                    $answerValue = !is_null($answerObj['number_value']) ? $answerObj['number_value'] : null;

                                    $cellOptions['type'] = 'number';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    $cellValue = !is_null($answerValue) ? $answerValue : '';
                                    break;
                                case 'DECIMAL':
                                    $answerValue = !is_null($answerObj['decimal_value']) ? $answerObj['decimal_value'] : null;

                                    $cellOptions['type'] = 'number';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    if ($question->has('custom_field') && $question->custom_field->has('params')) {
                                        $params = json_decode($question->custom_field->params, true);

                                        $cellOptions['min'] = 0;
                                        $step = $this->getStepFromParams($params);
                                        if (!is_null($step)) {
                                            $cellOptions['step'] = $step;
                                        }
                                    }

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
                                case 'TEXTAREA':
                                    $answerValue = !is_null($answerObj['textarea_value']) ? $answerObj['textarea_value'] : null;

                                    $cellOptions['type'] = 'textarea';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    $cellValue = !is_null($answerValue) ? $answerValue : '';
                                    break;
                                case 'DATE':
                                    $answerValue = !is_null($answerObj['date_value']) ? $answerObj['date_value'] : null;

                                    $_options = [
                                        'format' => 'dd-mm-yyyy',
                                        'todayBtn' => 'linked',
                                        'orientation' => 'auto',
                                        'autoclose' => true,
                                    ];

                                    $attr['date_options'] = $_options;
                                    $attr['id'] = $attr['model'] . '_' . $attr['field'];

                                    $attr['fieldName'] = $cellPrefix.".".$fieldTypes[$questionType];
                                    if (array_key_exists('fieldName', $attr)) {
                                        $attr['id'] = $this->_domId($attr['fieldName']);
                                    }

                                    $defaultDate = false;
                                    if (!isset($attr['default_date'])) {
                                        $attr['default_date'] = $defaultDate;
                                    }

                                    if (!array_key_exists('value', $attr)) {
                                        if (!is_null($answerValue)) {
                                            if ($answerValue instanceof Time || $answerValue instanceof Date) {
                                                $attr['value'] = $answerValue->format('d-m-Y');
                                            } else {
                                                $attr['value'] = date('d-m-Y', strtotime($answerValue));
                                            }
                                        } else if ($attr['default_date']) {
                                            $attr['value'] = date('d-m-Y');
                                        }
                                    } else {    
                                        if ($attr['value'] instanceof Time || $answerValue instanceof Date) {
                                            $attr['value'] = $attr['value']->format('d-m-Y');
                                        } else {
                                            $attr['value'] = date('d-m-Y', strtotime($attr['value']));
                                        }
                                    }

                                    $attr['null'] = !$attr['customField']['is_mandatory'];

                                    $event->subject()->viewSet('datepicker', $attr);
                                    $cellInput = $event->subject()->renderElement('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);
                                    $cellValue = !is_null($answerValue) ? $this->_table->formatDate($answerValue) : '';
                                    if($errorInput){
                                        $cellInput .= $errorInput;
                                    }
                                    unset($attr['value']); // Need to unset so that it will not effect other Date or Time elements.
                                    break;

                                case 'TIME':
                                    $answerValue = !is_null($answerObj['time_value']) ? $answerObj['time_value'] : null;
                                    $_options = [
                                        'defaultTime' => false
                                    ];

                                    $attr['fieldName'] = $cellPrefix.".".$fieldTypes[$questionType];
                                    $attr['id'] = $attr['model'] . '_' . $attr['field'];

                                    if (array_key_exists('fieldName', $attr)) {
                                        $attr['id'] = $this->_domId($attr['fieldName']);
                                    }

                                    if (!isset($attr['time_options'])) {
                                        $attr['time_options'] = [];
                                    }
                                    if (!isset($attr['default_time'])) {
                                        $attr['default_time'] = true;
                                    }

                                    $attr['time_options'] = array_merge($_options, $attr['time_options']);
                                  
                                    if (!array_key_exists('value', $attr)) {
                                        if (!is_null($answerValue)) {
                                            $attr['value'] = date('h:i A', strtotime($answerValue));
                                            $attr['time_options']['defaultTime'] = $attr['value'];
                                        } else if ($attr['default_time']) {
                                            $attr['value'] = date('h:i A');
                                            $attr['time_options']['defaultTime'] = $attr['value'];
                                        }
                                    } else {
                                        if ($attr['value'] instanceof Time) {
                                            $attr['value'] = $attr['value']->format('h:i A');
                                            $attr['time_options']['defaultTime'] = $attr['value'];
                                        } else {
                                            $attr['value'] = date('h:i A', strtotime($attr['value']));
                                            $attr['time_options']['defaultTime'] = $attr['value'];
                                        }
                                    }

                                    $attr['null'] = !$attr['customField']['is_mandatory'];

                                    $event->subject()->viewSet('timepicker', $attr);
                                    $cellInput = $event->subject()->renderElement('ControllerAction.bootstrap-timepicker/timepicker_input', ['attr' => $attr]);
                                    $cellValue = !is_null($answerValue) ? $this->_table->formatTime($answerValue) : '';
                                    if($errorInput){
                                        $cellInput .= $errorInput;
                                    }
                                    unset($attr['value']); // Need to unset so that it will not effect other Date or Time elements.
                                    break;

                                default:
                                    break;
                            }
                            if (in_array($questionType, ['TEXT', 'NUMBER', 'DECIMAL', 'DROPDOWN', 'TEXTAREA'])) {
                                $cellInput .= $form->input($cellPrefix.".".$fieldTypes[$questionType], $cellOptions);
                                if($errorInput){
                                    $cellInput .= $errorInput;
                                }
                            }

                            if ($action == 'view') {
                                $rowData[$colKey+$colOffset] = $cellValue;
                            } else if ($action == 'edit') {
                                $rowData[$colKey+$colOffset] = [$cellInput, ['style' => 'vertical-align: top']];
                            }
                        }

                        // remove button
                        if ($action == 'edit') {
                            $tableHeaders[$colKey + $colOffset + $colOffset] = '';
                            $rowData[] = '<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);"><i class="fa fa-close"></i> '.__('Remove') . '</button>';
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
                Log::write('debug', $debugInfo . ': Repeater Survey Form ID: '.$formId.' has no questions.');
            }
        } else {
            // Survey Form ID not found
            Log::write('debug', $debugInfo . ': Repeater Survey Form ID is not configured.');
        }

        // $attr['attr']['classOptions'] = $classOptions;
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        } else if ($action == 'edit') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
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
                                    'decimal_value' => $answer->decimal_value,
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

    public function processRepeaterValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        if ($entity->has('institution_repeater_surveys')) {
            $CustomFields = TableRegistry::get('Survey.SurveyQuestions');

            $fieldKey = 'survey_question_id';
            $formKey = 'survey_form_id';

            $status = $entity->status_id;
            $institutionId = $entity->institution_id;
            $periodId = $entity->academic_period_id;
            $parentFormId = $entity->{$formKey};

            foreach ($entity->institution_repeater_surveys as $fieldId => $fieldObj) {
                $formId = $fieldObj[$formKey];
                unset($fieldObj[$formKey]);
                if(!empty($fieldObj)){
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
                                $field = $CustomFields->find()
                                ->where(['id IN' => $questionId])
                                ->first()
                                ;

                                if($field['is_mandatory']){
                                    $answerObj = array_merge($answerObj, [
                                        $fieldKey => $questionId
                                    ]);
                                    $answerObj['params'] = $field['params'];
                                    $answerObj['mandatory'] = $field['is_mandatory'];
                                    $answerObj['field_type'] = $field['field_type'];
                                    $answerObj['unique'] = $field['is_unique'];

                                    $answers[] = $answerObj;

                                }else{
                                    // checking to skip insert if is empty
                                    $textValue = isset($answerObj['text_value']) && strlen($answerObj['text_value']) > 0 ? $answerObj['text_value'] : null;
                                    $numberValue = isset($answerObj['number_value']) && strlen($answerObj['number_value']) > 0 ? $answerObj['number_value'] : null;
                                    $decimalValue = isset($answerObj['decimal_value']) && strlen($answerObj['decimal_value']) > 0 ? $answerObj['decimal_value'] : null;
                                    $textareaValue = isset($answerObj['textarea_value']) && strlen($answerObj['textarea_value']) > 0 ? $answerObj['textarea_value'] : null;
                                    $dateValue = isset($answerObj['date_value']) && strlen($answerObj['date_value']) > 0 ? $answerObj['date_value'] : null;
                                    $timeValue = isset($answerObj['time_value']) && strlen($answerObj['time_value']) > 0 ? $answerObj['time_value'] : null;

                                    if (!is_null($textValue) || !is_null($numberValue) || !is_null($decimalValue) || !is_null($textareaValue) || !is_null($dateValue) || !is_null($timeValue)) {
                                        $answerObj = array_merge($answerObj, [
                                            $fieldKey => $questionId
                                        ]);
                                        $answerObj['params'] = $field['params'];
                                        $answerObj['mandatory'] = $field['is_mandatory'];
                                        $answerObj['field_type'] = $field['field_type'];
                                        $answerObj['unique'] = $field['is_unique'];

                                        $answers[] = $answerObj;
                                    }
                                }
                            }
                            
                            $surveyData['custom_field_values'] = $answers;
                            $tmp[] = $surveyData;
                        }
                    }
                    $settings['repeaterValues'] = $tmp;
                }
            }
        }
    }

    // TODO: To implement delete logic for survey relevance
    // public function deleteCustomFieldValues(Event $event, $parentFormId, $deleteFieldIds)
    // {
        // $RepeaterSurveys = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
        // $institutionRepeaterSurveyIds = $RepeaterSurveys
        //     ->find()
        //     ->where([$RepeaterSurveys->aliasField('parent_form_id') => $parentFormId])
        //     ->select([$RepeaterSurveys->aliasField('id')]);



        // $SurveyQuestions = TableRegistry::get('Survey.SurveyQuestions');
        // $formIds = $SurveyQuestions
        //     ->find()
        //     ->where([
        //         $SurveyQuestions->aliasField('id').' IN ' => $deleteFieldIds,
        //         $SurveyQuestions->aliasField('field_type') => 'REPEATER'
        //     ])
        //     ->select([$SurveyQuestions->aliasField('params')])
        //     ->toArray();
        //     ;
        // $surveyFormId = [];
        // foreach ($formIds as $formId) {
        //     $param = json_decode($formId->params);
        //     $surveyFormId[] = $param->survey_form_id;
        // }

        // if (!empty($surveyFormId)) {
        //     $SurveyFormsQuestions = TableRegistry::get('Survey.SurveyFormsQuestions');
        //     $RepeaterSurveyAnswers = TableRegistry::get('InstitutionRepeater.RepeaterSurveyAnswers');
        //     $RepeaterSurveyTableCells = TableRegistry::get('InstitutionRepeater.RepeaterSurveyTableCells')
        //     $questionId = $SurveyFormsQuestions
        //         ->find()
        //         ->innerJoinWith('CustomFields')
        //         ->where([
        //             $SurveyFormsQuestions->aliasField('survey_form_id').' IN ' => $surveyFormId,
        //             'CustomFields.field_type = ' => 'REPEATER'
        //         ])
        //         ->select([$SurveyFormsQuestions->aliasField('survey_question_id')]);
        //     $RepeaterSurveyAnswers->deleteAll([
        //         'institution_repeater_survey_id IN ' => $institutionRepeaterSurveyIds,
        //         'survey_question_id IN ' => $questionId
        //     ]);

        //     $RepeaterSurveyAnswers->deleteAll([
        //         'institution_repeater_survey_id IN ' => $institutionRepeaterSurveyIds,
        //         'survey_question_id IN ' => $questionId
        //     ]);
        // }
    // }

    private function getRepeaters($model, $requestData, $fieldId) {
        $repeaters = [];

        if (array_key_exists($model->alias(), $requestData)) {
            if (array_key_exists('institution_repeater_surveys', $requestData[$model->alias()])) {
                if (array_key_exists($fieldId, $requestData[$model->alias()]['institution_repeater_surveys'])) {
                    foreach ($requestData[$model->alias()]['institution_repeater_surveys'][$fieldId] as $repeaterKey => $repeaterObj) {
                        if ($repeaterKey == 'survey_form_id') { continue; }
                        $repeaters[] = $repeaterKey;
                    }
                }
            }
        }

        return $repeaters;
    }
}
