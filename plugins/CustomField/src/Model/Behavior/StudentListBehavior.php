<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class StudentListBehavior extends Behavior {
    protected $_defaultConfig = [
        'module' => 'Student.Students',
        'models' => [
            'CustomModules' => 'CustomField.CustomModules',
            'CustomFieldTypes' => 'CustomField.CustomFieldTypes',
            'CustomFields' => 'Survey.SurveyQuestions',
            'CustomForms' => 'Survey.SurveyForms',
            'CustomFormsFields' => 'Survey.SurveyFormsQuestions',
            'Sections' => 'Institution.InstitutionSiteSections',
            'SectionStudents' => 'Institution.InstitutionSiteSectionStudents',
            'StudentSurveys' => 'Institution.StudentSurveys'
        ],
        'fieldType' => ['TEXT', 'NUMBER', 'DROPDOWN'],
        'fieldKey' => 'survey_question_id',
        'formKey' => 'survey_form_id'
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

        if (isset($config['setup']) && $config['setup'] == true) {
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

            $this->_table->ControllerAction->addField('survey_form', [
                'options' => $formOptions
            ]);

            $this->_table->ControllerAction->setFieldOrder(['field_type', 'name', 'survey_form']);
        }
    }

    public function onGetSurveyForm(Event $event, Entity $entity) {
        foreach ($entity->custom_field_params as $key => $fieldParam) {
            if ($fieldParam->param_key == 'survey_form_id') {
                return $this->CustomForms->get($fieldParam->param_value)->name;
                break;
            }
        }
    }

    public function onGetCustomStudentListElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $Form = $event->subject()->Form;
        $tableHeaders = [];
        $tableCells = [];
        $cellCount = 0;

        $customFieldObj = $attr['customField'];
        $formId = null;
        foreach ($customFieldObj->custom_field_params as $key => $fieldParam) {
            if ($fieldParam->param_key == 'survey_form_id') {
                $formId = $fieldParam->param_value;
                break;
            }
        }

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
                        $this->CustomFields->aliasField('id = ') . $this->CustomFormsFields->aliasField($this->config('fieldKey')),
                        $this->CustomFields->aliasField('field_type IN') => $this->config('fieldType')
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
                // Build table header
                $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
                $headerHtml = __('Students') . ' / ' . __('Questions');
                $headerHtml .= $Form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
                $tableHeaders[] = $headerHtml;
                foreach ($customFields as $colKey => $customField) {
                    $customFieldName = !is_null($customField->name) ? $customField->name : $customField->custom_field->name;
                    $tableHeaders[$colKey+1] = $customFieldName;
                }
                // End

                $institutionId = $entity->institution_site_id;
                $periodId = $entity->academic_period_id;

                $students = $this->SectionStudents
                    ->find()
                    ->contain(['Users'])
                    ->innerJoin(
                        [$this->Sections->alias() => $this->Sections->table()],
                        [
                            $this->Sections->aliasField('id = ') . $this->SectionStudents->aliasField('institution_site_section_id'),
                            $this->Sections->aliasField('institution_site_id') => $institutionId,
                            $this->Sections->aliasField('academic_period_id') => $periodId
                        ]
                    )
                    ->toArray();

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
                        if ($action == 'view') {
                            $rowValue = $student->user->name_with_id;
                            $rowData[] = $rowValue;
                        } else if ($action == 'edit') {
                            $rowInput = $student->user->name_with_id;
                            $rowInput .= $Form->hidden($rowPrefix.".institution_id", ['value' => $institutionId]);
                            $rowInput .= $Form->hidden($rowPrefix.".academic_period_id", ['value' => $periodId]);
                            $rowInput .= $Form->hidden($rowPrefix.".student_id", ['value' => $studentId]);
                            $rowInput .= $Form->hidden($rowPrefix.".survey_form_id", ['value' => $formId]);

                            $rowData[] = $rowInput;
                        }

                        foreach ($customFields as $colKey => $customField) {
                            $fieldId = $customField->custom_field->id;
                            $fieldType = $customField->custom_field->field_type;

                            $cellPrefix = $rowPrefix . '.custom_field_values.' . $cellCount++;
                            $cellInput = "";
                            $cellValue = "";
                            $cellOptions = ['label' => false, 'value' => ''];

                            switch ($fieldType) {
                                case 'TEXT':
                                    break;
                                case 'NUMBER':
                                    $cellOptions['type'] = 'number';
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
                                    // $cellOptions['default'] = !is_null($attr['value']) ? $attr['value'] : $dropdownDefault;
                                    $cellOptions['default'] = $dropdownDefault;
                                    $cellOptions['options'] = $dropdownOptions;
                                    break;
                                default:
                                    break;
                            }
                            $cellInput .= $Form->input($cellPrefix.".".$fieldTypes[$fieldType], $cellOptions);
                            $cellInput .= $Form->hidden($cellPrefix.".".$this->config('fieldKey'), ['value' => $fieldId]);

                            if ($action == 'view') {
                                $rowData[$colKey+1] = $cellValue;
                            } else if ($action == 'edit') {
                                $rowData[$colKey+1] = $cellInput;
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

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view') {
            $value = $event->subject()->renderElement('CustomField.student_list', ['attr' => $attr]);
        } else if ($action == 'edit') {
            $value = $event->subject()->renderElement('CustomField.student_list', ['attr' => $attr]);  
        }

        return $value;
    }
}
