<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class StudentListBehavior extends Behavior {
    protected $_defaultConfig = [
        'setup' => false,
        'module' => 'Student.StudentSurveys',
        'models' => [
            'CustomModules' => 'CustomField.CustomModules',
            'CustomFieldTypes' => 'CustomField.CustomFieldTypes',
            'CustomFields' => 'Survey.SurveyQuestions',
            'CustomForms' => 'Survey.SurveyForms',
            'CustomFormsFields' => 'Survey.SurveyFormsQuestions',
            'SurveyQuestionParams' => 'Survey.SurveyQuestionParams',
            'Sections' => 'Institution.InstitutionSiteSections',
            'SectionStudents' => 'Institution.InstitutionSiteSectionStudents',
            'StudentSurveys' => 'Student.StudentSurveys',
            'StudentSurveyAnswers' => 'Student.StudentSurveyAnswers'
        ],
        'fieldKey' => 'survey_question_id',
        'formKey' => 'survey_form_id',
        'recordKey' => 'institution_student_survey_id'
    ];

    public function initialize(array $config) {
        parent::initialize($config);

        $models = $this->config('models');
        foreach ($models as $key => $model) {
            if (!is_null($model)) {
                $this->{$key} = TableRegistry::get($model);
                $this->{lcfirst($key).'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->alias())) . '_id';
            } else {
                $this->{$key} = null;
            }
        }

        if ($this->config('setup')) {
            $formOptions = $this->CustomForms
                ->find('list')
                ->innerJoin(
                    [$this->CustomModules->alias() => $this->CustomModules->table()],
                    [
                        $this->CustomModules->aliasField('id = ') . $this->CustomForms->aliasField('custom_module_id'),
                        $this->CustomModules->aliasField('model') => $this->config('module')
                    ]
                )
                ->toArray();

            $this->_table->ControllerAction->field('survey_form', [
                'options' => $formOptions
            ]);

            $this->_table->ControllerAction->setFieldOrder(['field_type', 'name', 'survey_form']);
        }
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        if ($this->config('setup')) {
            $events['ControllerAction.Model.add.afterSave'] = ['callable' => 'addAfterSave', 'priority' => 100];
            $events['ControllerAction.Model.edit.afterAction'] = ['callable' => 'editAfterAction', 'priority' => 100];
            $events['ControllerAction.Model.onUpdateFieldSurveyForm'] = ['callable' => 'onUpdateFieldSurveyForm', 'priority' => 100];
        }

        return $events;
    }

    public function onGetSurveyForm(Event $event, Entity $entity) {
        $paramsResults = $this->SurveyQuestionParams
            ->find()
            ->where([
                $this->SurveyQuestionParams->aliasField($this->config('fieldKey')) => $entity->id,
                $this->SurveyQuestionParams->aliasField('param_key') => $this->config('formKey')
            ])
            ->all();

        if (!$paramsResults->isEmpty()) {
            $paramValue = $paramsResults->first()->param_value;

            $customFormResults = $this->CustomForms
                ->find()
                ->where([
                    $this->CustomForms->aliasField('id') => $paramValue
                ])
                ->all();

            if (!$customFormResults->isEmpty()) {
                return $customFormResults->first()->name;
            }
        }
    }

    public function onGetCustomStudentListElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $Form = $event->subject()->Form;
        $sectionOptions = [];
        $tableHeaders = [];
        $tableCells = [];
        $cellCount = 0;

        $customFieldObj = $attr['customField'];
        $formId = null;
        // Find Survey Form ID from survey_question_params table
        $paramsResults = $this->SurveyQuestionParams
            ->find()
            ->where([
                $this->SurveyQuestionParams->aliasField($this->config('fieldKey')) => $customFieldObj->id,
                $this->SurveyQuestionParams->aliasField('param_key') => $this->config('formKey')
            ])
            ->all();
        if (!$paramsResults->isEmpty()) {
            $formId = $paramsResults->first()->param_value;
        }
        // End

        if (!is_null($formId)) {
            $customFields = $this->CustomFormsFields
                ->find('all')
                ->find('order')
                ->contain([
                    'CustomFields.CustomFieldOptions' => function($q) {
                        return $q
                            ->find('visible')
                            ->find('order');
                    }
                ])
                ->innerJoin(
                    [$this->CustomFields->alias() => $this->CustomFields->table()],
                    [
                        $this->CustomFields->aliasField('id = ') . $this->CustomFormsFields->aliasField($this->config('fieldKey'))
                    ]
                )
                ->where([
                    $this->CustomFormsFields->aliasField($this->config('formKey')) => $formId
                ])
                ->group([
                    $this->CustomFormsFields->aliasField($this->config('fieldKey'))
                ])
                ->toArray();

            if (!empty($customFields)) {
                $institutionId = $entity->institution_site_id;
                $periodId = $entity->academic_period_id;

                $sectionOptions = $this->Sections
                    ->find('list')
                    ->where([
                        $this->Sections->aliasField('institution_site_id') => $institutionId,
                        $this->Sections->aliasField('academic_period_id') => $periodId
                    ])
                    ->toArray();

                // Set selectedSection to session and read it back.
                $selectedSection = key($sectionOptions);
                $session = $this->_table->request->session();
                $plugin = $this->_table->controller->plugin;
                $model = $this->_table->alias();
                $sessionKey = "$plugin.$model.custom_field_values.$customFieldObj->id.institution_site_section";

                if ($this->_table->request->is(['get'])) {
                    // Clear session if is not redirect from save
                } else if ($this->_table->request->is(['post', 'put'])) {
                    if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
                        if (array_key_exists($attr['field'], $this->_table->request->data[$this->_table->alias()]['custom_field_values'])) {
                            if (array_key_exists('institution_site_section', $this->_table->request->data[$this->_table->alias()]['custom_field_values'][$attr['field']])) {
                                $session->write($sessionKey, $this->_table->request->data[$this->_table->alias()]['custom_field_values'][$attr['field']]['institution_site_section']);
                            }
                        }
                    }
                }

                if ($session->check($sessionKey)) {
                    $selectedSection = $session->read($sessionKey);
                }
                // End

                $Sections = $this->Sections;
                $userId = $this->_table->Auth->user('id');

                $this->_table->advancedSelectOptions($sectionOptions, $selectedSection, [
                    'message' => '{{label}} - ' . $this->_table->getMessage('InstitutionSurveys.noAccess'),
                    'callable' => function($id) use ($Sections, $institutionId, $periodId, $userId) {
                        if ($this->_table->AccessControl->check(['Institutions', 'AllClasses', 'index'])) {
                            // User has access to AllClasses
                            return 1;
                        } else {
                            $sectionResults = $Sections
                                ->find()
                                ->where([
                                    $Sections->aliasField('id') => $id,
                                    $Sections->aliasField('institution_site_id') => $institutionId,
                                    $Sections->aliasField('academic_period_id') => $periodId,
                                    $Sections->aliasField('security_user_id') => $userId
                                ])
                                ->first();

                            if (!empty($sectionResults)) {
                                // User has access to the Class
                                return 1;
                            } else {
                                // Do not have access to the Class
                                return 0;
                            }
                        }
                    }
                ]);

                $studentQuery = $this->SectionStudents
                    ->find()
                    ->contain(['Users']);

                if ($action == 'view') {
                    // Filter section by staff if user do not have access to AllClasses
                    $sectionConditions = [
                        $this->Sections->aliasField('id = ') . $this->SectionStudents->aliasField('institution_site_section_id'),
                        $this->Sections->aliasField('institution_site_id') => $institutionId,
                        $this->Sections->aliasField('academic_period_id') => $periodId
                    ];
                    if (!$this->_table->AccessControl->check(['Institutions', 'AllClasses', 'index'])) {
                        $sectionConditions[$this->Sections->aliasField('security_user_id')] = $userId;
                    }

                    $studentQuery
                        ->innerJoin(
                            [$this->Sections->alias() => $this->Sections->table()], $sectionConditions
                        );

                } else if ($action == 'edit') {
                    $studentQuery
                        ->where([
                            $this->SectionStudents->aliasField('institution_site_section_id') => $selectedSection
                        ]);
                }

                $students = $studentQuery->toArray();

                // Build table header
                $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
                $headerHtml = __('OpenEMIS ID');
                $headerHtml .= $Form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
                $tableHeaders[] = $headerHtml;
                $tableHeaders[] = __('Student Name');
                $colOffset = 2; // 0 -> OpenEMIS ID, 1 -> Student Name

                foreach ($customFields as $colKey => $customField) {
                    $customFieldName = !is_null($customField->name) ? $customField->name : $customField->custom_field->name;
                    $tableHeaders[$colKey+$colOffset] = $customFieldName;
                }
                // End

                if (!empty($students)) {
                    $fieldTypes = $this->CustomFieldTypes
                        ->find('list', ['keyField' => 'code', 'valueField' => 'value'])
                        ->toArray();

                    foreach ($students as $rowKey => $student) {
                        $studentId = $student->student_id;
                        $rowPrefix = $fieldPrefix . '.' . $this->StudentSurveys->alias() . '.' . $studentId;

                        $rowData = [];
                        $rowInput = "";
                        $rowValue = "";

                        // Record Id
                        $recordId = null;
                        $recordResults = $this->StudentSurveys
                            ->find()
                            ->where([
                                $this->StudentSurveys->aliasField('institution_id') => $institutionId,
                                $this->StudentSurveys->aliasField('academic_period_id') => $periodId,
                                $this->StudentSurveys->aliasField('student_id') => $studentId,
                                $this->StudentSurveys->aliasField('survey_form_id') => $formId
                            ])
                            ->all();
                        if (!$recordResults->isEmpty()) {
                            $recordId = $recordResults->first()->id;
                        }
                        // End

                        if ($action == 'view') {
                            $rowData[] = $student->user->openemis_no . $rowValue;
                            $rowData[] = $student->user->name;
                        } else if ($action == 'edit') {
                            $rowInput .= $Form->hidden($rowPrefix.".institution_id", ['value' => $institutionId]);
                            $rowInput .= $Form->hidden($rowPrefix.".academic_period_id", ['value' => $periodId]);
                            $rowInput .= $Form->hidden($rowPrefix.".student_id", ['value' => $studentId]);
                            $rowInput .= $Form->hidden($rowPrefix.".survey_form_id", ['value' => $formId]);

                            if (!is_null($recordId)) {
                                $rowInput .= $Form->hidden($rowPrefix.".id", ['value' => $recordId]);
                            }

                            $rowData[] = $student->user->openemis_no . $rowInput;
                            $rowData[] = $student->user->name;
                        }

                        foreach ($customFields as $colKey => $customField) {
                            $fieldId = $customField->custom_field->id;
                            $fieldType = $customField->custom_field->field_type;

                            $cellPrefix = $rowPrefix . '.custom_field_values.' . $cellCount++;
                            $cellInput = "";
                            $cellValue = "";
                            $cellOptions = ['label' => false, 'value' => ''];
                            $answerValue = null;

                            if (!is_null($recordId)) {
                                $answerResults = $this->StudentSurveyAnswers
                                    ->find()
                                    ->where([
                                        $this->StudentSurveyAnswers->aliasField($this->config('recordKey')) => $recordId,
                                        $this->StudentSurveyAnswers->aliasField($this->config('fieldKey')) => $fieldId
                                    ])
                                    ->all();

                                if (!$answerResults->isEmpty()) {
                                    $answerObj = $answerResults->first();
                                    $answerId = $answerObj->id;
                                    $answerValue = $answerObj->{$fieldTypes[$fieldType]};

                                    $cellInput .= $Form->hidden($cellPrefix.".id", ['value' => $answerId]);
                                }
                            }

                            switch ($fieldType) {
                                case 'TEXT':
                                    $cellOptions['type'] = 'string';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    $cellValue = !is_null($answerValue) ? $answerValue : '';
                                    break;
                                case 'NUMBER':
                                    $cellOptions['type'] = 'number';
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : '';

                                    $cellValue = !is_null($answerValue) ? $answerValue : '';
                                    break;
                                case 'DROPDOWN':
                                    $dropdownOptions = [];
                                    $dropdownDefault = null;
                                    foreach ($customField->custom_field->custom_field_options as $key => $obj) {
                                        $dropdownOptions[$obj->id] = $obj->name;
                                        if ($obj->is_default == 1) {
                                            $dropdownDefault = $obj->id;
                                        }
                                    }

                                    $cellOptions['type'] = 'select';
                                    $cellOptions['default'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                    $cellOptions['value'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                                    $cellOptions['options'] = $dropdownOptions;

                                    $cellValue = !is_null($answerValue) ? $dropdownOptions[$answerValue] : $dropdownOptions[$dropdownDefault];
                                    break;
                                default:
                                    break;
                            }

                            $cellInput .= $Form->input($cellPrefix.".".$fieldTypes[$fieldType], $cellOptions);
                            $cellInput .= $Form->hidden($cellPrefix.".".$this->config('fieldKey'), ['value' => $fieldId]);

                            if ($action == 'view') {
                                $rowData[$colKey+$colOffset] = $cellValue;
                            } else if ($action == 'edit') {
                                $rowData[$colKey+$colOffset] = $cellInput;
                            }
                        }
                        $tableCells[$rowKey] = $rowData;
                    }
                } else {
                    // No Student for the school in the academic period.
                }
            } else {
                // Survey Questions not setup for the form or not in the supported field type.
            }
        } else {
            // Survey Form ID not found.
        }

        $attr['alias'] = $fieldPrefix;
        $attr['sectionOptions'] = $sectionOptions;
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view') {
            $value = $event->subject()->renderElement('CustomField.student_list', ['attr' => $attr]);
        } else if ($action == 'edit') {
            $value = $event->subject()->renderElement('CustomField.student_list', ['attr' => $attr]);  
        }

        return $value;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
        // For Student List field type, save survey_form_id into survey_question_params table.
        $paramsData = [
            'param_key' => 'survey_form_id',
            'param_value' => $entity->survey_form,
            'survey_question_id' => $entity->id
        ];

        $paramsEntity = $this->SurveyQuestionParams->newEntity($paramsData);
        if ($this->SurveyQuestionParams->save($paramsEntity)) {
        } else {
            $this->log($paramsEntity->errors(), 'debug');
        }
    }

    public function editAfterAction(Event $event, Entity $entity) {
        $this->_table->ControllerAction->field('survey_form');
        $paramsResults = $this->SurveyQuestionParams
            ->find()
            ->where([
                $this->SurveyQuestionParams->aliasField($this->config('fieldKey')) => $entity->id,
                $this->SurveyQuestionParams->aliasField('param_key') => $this->config('formKey')
            ])
            ->all();

        if (!$paramsResults->isEmpty()) {
            $entity->survey_form = $paramsResults->first()->param_value;
        }
    }

    public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }

        return $attr;
    }
}
