<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\Utility\Hash;
use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Http\ServerRequest;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Text;

class AppraisalBehavior extends Behavior
{
    public $periodList = [];

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.viewEdit.beforeQuery'] = 'viewEditBeforeQuery';
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        $events['ControllerAction.Model.addEdit.afterAction'] = 'addEditAfterAction';
        $events['ControllerAction.Model.edit.afterQuery'] = 'editAfterQuery';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['Model.excel.onExcelUpdateFields'] = ['callable' => 'onExcelUpdateFields', 'priority' => 110];
        $events['Model.excel.onExcelRenderCustomField']  = 'onExcelRenderCustomField';

        return $events;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->field('staff_id', ['visible' => false]);
        $model->field('file_name', ['visible' => false]);
        $model->field('file_content', ['visible' => false]);
        $model->field('comment', ['visible' => false]);
        $model->field('appraisal_period_id', ['visible' => false]);
        $model->setFieldOrder(['appraisal_type_id', 'appraisal_form_id', 'appraisal_period_from', 'appraisal_period_to', 'date_appraised']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $model->behaviors()->get('ControllerAction')->getConfig(
            'actions.download.show',
            $showFunc
        );
        // End
        $model->field('staff_id', ['visible' => false]);
        $model->field('academic_period_id', ['fieldName' => 'appraisal_period.academic_period.name']);
        $model->field('appraisal_period_from');
        $model->field('appraisal_period_to');
        $model->field('appraisal_type_id', ['attr' => ['label' => __('Type')]]);
        $model->field('appraisal_period_id');
        $model->field('appraisal_form_id');
        $model->field('file_name', ['visible' => false]);
        $model->field('file_content', ['visible' => false]);
        $model->field('comment');
        $model->printAppraisalCustomField($entity->appraisal_form_id, $entity);
        $model->setupFieldOrder();
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'AppraisalPeriods.AcademicPeriods', 'AppraisalForms',
            'AppraisalTypes'
        ]);
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->field('staff_id', ['type' => 'hidden', 'value' => $model->staff->id]);
        $model->field('academic_period_id', ['type' => 'select', 'attr' => ['required' => true]]);
        $model->field('appraisal_period_from');
        $model->field('appraisal_period_to');
        $model->field('appraisal_type_id', ['attr' => ['label' => __('Type')], 'type' => 'select']);
        $model->field('appraisal_period_id', ['type' => 'select', 'options' => $this->periodList, 'onChangeReload' => true]);
        $model->field('appraisal_form_id', ['type' => 'readonly']);
        $model->field('file_name', ['visible' => false]);
        $model->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $model->field('comment');

        $entity = $model->newEmptyEntity();
        $appraisalFormId = $model->request->getData($model->aliasField('appraisal_form_id'));
        $model->printAppraisalCustomField($appraisalFormId, $entity);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->setupFieldOrder();
    }

    public function editAfterQuery(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->field('staff_id', ['type' => 'hidden', 'value' => $entity->staff_id]);
        $model->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->appraisal_period->academic_period_id, 'attr' => ['value' => $entity->appraisal_period->academic_period->name]]);
        $model->field('appraisal_period_from');
        $model->field('appraisal_period_to');
        $model->field('appraisal_type_id', ['type' => 'readonly', 'value' => $entity->appraisal_type_id, 'attr' => ['label' => __('Type'), 'value' => $entity->appraisal_type->name]]);
        $model->field('appraisal_period_id', ['type' => 'readonly', 'value' => $entity->appraisal_period_id, 'attr' => ['value' => $entity->appraisal_period->name]]);
        $model->field('appraisal_form_id', ['type' => 'readonly', 'value' => $entity->appraisal_form_id, 'attr' => ['value' => $entity->appraisal_form->name]]);
        $model->field('file_name', ['visible' => false]);
        $model->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $model->field('comment');
        $model->printAppraisalCustomField($entity->appraisal_form_id, $entity);
    }

    public function setupFieldOrder()
    {
        $model = $this->_table;
        $model->setFieldOrder(['academic_period_id', 'appraisal_type_id', 'appraisal_period_id', 'appraisal_form_id', 'appraisal_period_from', 'appraisal_period_to', 'date_appraised', 'file_content', 'comment']);
    }


    public function getAppraisalPeriods($academicPeriodId, $appraisalTypeId)
    {
        $model = $this->_table;
        return $model->AppraisalPeriods->find()
            ->innerJoinWith('AppraisalTypes')
            ->where(['AppraisalTypes.id' => $appraisalTypeId, 'AcademicPeriods.id' => $academicPeriodId])
            ->contain(['AppraisalForms', 'AcademicPeriods'])
            ->formatResults(function ($results) {
                $list = [];
                foreach ($results as $r) {
                    $list[$r->id] = $r->period_form_name;
                }
                return $list;
            })
            ->toArray();
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event,  $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
            $attr['options'] = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getYearList();
            return $attr;
        }
    }

    public function onUpdateFieldAppraisalTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $model = $this->_table;
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
            if ($request->getData($model->aliasField('academic_period_id')) && $request->getData($model->aliasField('appraisal_type_id'))) {
                $appraisalTypeId = $request->getData($model->aliasField('appraisal_type_id'));
                $academicPeriodId = $request->getData($model->aliasField('academic_period_id'));
                $this->periodList = $model->AppraisalPeriods->find('list')
                    ->innerJoinWith('AppraisalTypes')
                    ->where([
                        'AppraisalTypes.id' => $appraisalTypeId,
                        $model->AppraisalPeriods->aliasField('academic_period_id') => $academicPeriodId,
                        $model->AppraisalPeriods->aliasField('date_enabled').' <=' => new Date(),
                        $model->AppraisalPeriods->aliasField('date_disabled').' >=' => new Date()
                    ])
                    ->toArray();
            }
            return $attr;
        }
    }

    public function onUpdateFieldAppraisalFormId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $model = $this->_table;
        if ($action == 'add') {
            if ($request->getData($model->aliasField('appraisal_period_id'))) {
                $appraisalPeriodId = $request->getData($model->aliasField('appraisal_period_id'));
                $appraisalPeriodEntity = $model->AppraisalPeriods->get($appraisalPeriodId, ['contain' => ['AppraisalForms']]);
                $attr['value'] = $appraisalPeriodEntity->appraisal_form_id;
                $attr['attr']['value'] = $appraisalPeriodEntity->appraisal_form->code_name;
                $data = $this->_table->request->getData($model->getAlias()) ?? []; //POCOR-8688
                $data['appraisal_form_id'] = $appraisalPeriodEntity->appraisal_form_id;
                $this->_table->request = $this->_table->request->withData($model->getAlias(), $data);

            // This part ensures that the form belonging to the previously selected Appraisal Period will not populate at the bottom when user choose "Select" from the dropdown next. It should be empty.
            }else{
                   $request->getData[$model->getAlias()]['appraisal_form_id'] = "";
            }
            return $attr;
        }
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $model = $this->_table;
        $errors = $entity->getErrors();

        $fileErrors = [];
        $session = $model->request->getSession();
        $sessionErrors = $model->getRegistryAlias().'.parseFileError';

        if ($session->check($sessionErrors)) {
            $fileErrors = $session->read($sessionErrors);
        }

        if (empty($errors) && empty($fileErrors)) {
            // redirect only when no errors
            $event->stopPropagation();
            return $model->controller->redirect($model->url('view'));
        }
    }

    public function printAppraisalCustomField($appraisalFormId, Entity $entity)
    {
        $model = $this->_table;
        if ($appraisalFormId) {
            $section = null;
            $sectionCount = 0;
            $criteriaCounter = new ArrayObject();
            $staffAppraisalId = $entity->has('id') ? $entity->id : -1;

            // retrieve all form criterias containing results
            $AppraisalFormsCriterias = TableRegistry::getTableLocator()->get('StaffAppraisal.AppraisalFormsCriterias');
            $query = $AppraisalFormsCriterias->find()
                ->contain([
                    'AppraisalCriterias' => [
                        'FieldTypes',
                        'AppraisalSliders',
                        'AppraisalNumbers',
                        'AppraisalDropdownOptions' => ['sort' => ['AppraisalDropdownOptions.order' => 'ASC']]
                    ],
                    'AppraisalTextAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalTextAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalSliderAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalSliderAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalDropdownAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalDropdownAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalNumberAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalNumberAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalScoreAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalScoreAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    }
                ])
                ->where([$AppraisalFormsCriterias->aliasField('appraisal_form_id') => $appraisalFormId])
                ->order($AppraisalFormsCriterias->aliasField('order'));

            $tabElements = [];

            $action = $model->action;
            $url = $model->url($action);

            //section tab
            $formsCriterias = $query->toArray();
            foreach ($formsCriterias as $key => $formCritieria) {
                if ($section != $formCritieria->section) {
                    $section = $formCritieria->section;
                    $tabName = Text::slug($section);
                    if (empty($tabElements)) {
                        $selectedAction = $tabName;
                    } else { // POCOR-9123
                        if (isset($url['?']) && $url['?'] != $tabName) {
                            unset($url['?']);
                        }
                    }

                    $url['tab_section'] = $tabName;
                    $url['?']['tab_section'] = $tabName; // POCOR-9123

                    $tabElements[$tabName] = [
                        'url' => $url,
                        'text' => $section,
                    ];
                }
            }
            //end

            if (!empty($tabElements)) {
                $queryTabSection = $model->request->getQuery('tab_section');
                if (!is_null($queryTabSection) && array_key_exists($queryTabSection, $tabElements)) {
                    $selectedAction = $queryTabSection;
                }
                if ($action != 'add') {
                    $model->controller->set('tabElements', $tabElements);
                    $model->controller->set('selectedAction', $selectedAction);
                }
                $query
                    ->where([
                    $AppraisalFormsCriterias->aliasField('section') => $tabElements[$selectedAction]['text']
                    ]);
            }

            if (($action != 'add' &&  !empty($tabElements)) || empty($tabElements)) {
                $formsCriterias = $query->toArray();
                foreach ($formsCriterias as $key => $formsCriteria) {
                    $details = new ArrayObject([
                        'appraisal_form_id' => $formsCriteria->appraisal_form_id,
                        'appraisal_criteria_id' => $formsCriteria->appraisal_criteria_id,
                        'section' => $formsCriteria->section,
                        'field_type' => $formsCriteria->appraisal_criteria->field_type->code,
                        'criteria_name' => $formsCriteria->appraisal_criteria->name,
                        'is_mandatory' => $formsCriteria->is_mandatory
                    ]);

                    $this->appraisalCustomFieldExtra($details, $formsCriteria, $criteriaCounter, $entity);
                }
            }
        }
    }

    public function appraisalCustomFieldExtra(ArrayObject $details, Entity $formCritieria, ArrayObject $criteriaCounter, Entity $entity)
    {
        $model = $this->_table;
        $fieldTypeCode = $details['field_type'];
        if (!$criteriaCounter->offsetExists($fieldTypeCode)) {
            $criteriaCounter[$fieldTypeCode] = 0;
        }

        $key = [];
        $attr = [];
        $criteria = $formCritieria->appraisal_criteria;

        switch ($fieldTypeCode) {
            case 'SLIDER':
                $key = 'appraisal_slider_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'slider';
                $attr['max'] = $criteria->appraisal_slider->max;
                $attr['min'] = $criteria->appraisal_slider->min;
                $attr['step'] = $criteria->appraisal_slider->step;
                break;
            case 'TEXTAREA':
                $key = 'appraisal_text_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'text';
                $name = $criteria->field_type->name ?? null;
                $action = $model->action;
                if($action == "edit" || ($name == 'Note' && $action == 'add')) {
                    $attr['attr']['disabled'] = true;
                }
                break;
            case 'DROPDOWN':
                $key = 'appraisal_dropdown_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'select';
                $attr['options'] = Hash::combine($criteria->appraisal_dropdown_options, '{n}.id', '{n}.name');
                $attr['default'] = current(Hash::extract($criteria->appraisal_dropdown_options, '{n}[is_default=1].id'));
                break;
            case 'NUMBER':
                $key = 'appraisal_number_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'integer';

                if ($criteria->has('appraisal_number')) {
                    $model->field($fieldKey.'.validation_rule', ['type' => 'hidden', 'value' => $criteria->appraisal_number->validation_rule]);
                }
                break;
            case 'SCORE':
                $key = 'appraisal_score_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $action = $model->action;
                if ($action == 'edit' || $action == 'add') {
                    $attr['type'] = 'readonly';
                } else if ($action == 'view') {
                    $attr['type'] = 'string';
                }
                break;
        }

        // build custom fields
        $attr['attr']['label'] = $details['criteria_name'];
        $attr['attr']['required'] = $details['is_mandatory'];

        // set each answer in entity
        if (!$entity->offsetExists($key)) {
            $entity->{$key} = [];
        }
        $entity->{$key}[$criteriaCounter[$fieldTypeCode]] = !empty($formCritieria->{$key}) ? current($formCritieria->{$key}) : [];

        $model->field($fieldKey.'.answer', $attr);
        $model->field($fieldKey.'.is_mandatory', ['type' => 'hidden', 'value' => $details['is_mandatory']]);
        $model->field($fieldKey.'.appraisal_form_id', ['type' => 'hidden', 'value' => $details['appraisal_form_id']]);
        $model->field($fieldKey.'.appraisal_criteria_id', ['type' => 'hidden', 'value' => $details['appraisal_criteria_id']]);

        $criteriaCounter[$fieldTypeCode]++;
    }

    //POCOR-8627 Start

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        // Logic for reorder field in report Start
        //Desired field order
        $desiredOrder = [
            "Status",
            "Assignee",
            "Academic Period",
            "Appraisal Type",
            "Appraisal Period",
            "Appraisal Form",
            "Appraisal Period From",
            "Appraisal Period To",
            "Date Appraised",
            "Comment"
        ];

        // Fields to remove
        $removeField = [
            "File Name",
            "Institution",
            "Staff"
        ];

        // Create an associative array for easier reordering
        $fieldsArray = iterator_to_array($fields);

        // Filter out fields that need to be removed
        $fieldsArray = array_filter($fieldsArray, function ($field) use ($removeField) {
            return !in_array($field['label'], $removeField, true);
        });

        // Sort the fields based on the desired order
        $sortedFields = [];
        foreach ($desiredOrder as $label) {
            foreach ($fieldsArray as $field) {
                if ($field['label'] === $label) {
                    $sortedFields[] = $field;
                    break;
                }
            }
        }

        // Update the original $fields ArrayObject
        $fields->exchangeArray($sortedFields);
        //End
        $recordId = $settings['id'];
        $appraisalFormId = $recordId ? $this->_table->get($recordId)->appraisal_form_id : json_decode($settings['process']['params'] ?? '{}')->appraisal_form_id;
        $staffAppraisalId = $recordId ? $this->_table->get($recordId)->id : -1;
        $AppraisalFormsCriterias = TableRegistry::getTableLocator()->get('StaffAppraisal.AppraisalFormsCriterias');

        if (!$appraisalFormId) {
            return; // Exit if appraisal_form_id is not found
        }

        // Define contain settings once
        $containSettings = [
            'AppraisalCriterias' => [
                'FieldTypes',
                'AppraisalSliders',
                'AppraisalNumbers',
                'AppraisalDropdownOptions' => ['sort' => ['AppraisalDropdownOptions.order' => 'ASC']]
            ],
            'AppraisalTextAnswers' => fn($q) => $q->where(['AppraisalTextAnswers.institution_staff_appraisal_id' => $staffAppraisalId]),
            'AppraisalSliderAnswers' => fn($q) => $q->where(['AppraisalSliderAnswers.institution_staff_appraisal_id' => $staffAppraisalId]),
            'AppraisalDropdownAnswers' => fn($q) => $q->where(['AppraisalDropdownAnswers.institution_staff_appraisal_id' => $staffAppraisalId]),
            'AppraisalNumberAnswers' => fn($q) => $q->where(['AppraisalNumberAnswers.institution_staff_appraisal_id' => $staffAppraisalId]),
            'AppraisalScoreAnswers' => fn($q) => $q->where(['AppraisalScoreAnswers.institution_staff_appraisal_id' => $staffAppraisalId]),
        ];

        // Execute query with common conditions
        $formsCriterias = $AppraisalFormsCriterias->find()
            ->contain($containSettings)
            ->where([$AppraisalFormsCriterias->aliasField('appraisal_form_id') => $appraisalFormId])
            ->order([$AppraisalFormsCriterias->aliasField('order')])
            ->toArray();

        // Process form criteria
        foreach ($formsCriterias as $formsCriteria) {
            $fieldTypeCode = $formsCriteria->appraisal_criteria->field_type->code ?? '';
            $fieldKey = ($fieldTypeCode === 'TEXTAREA') ? 'appraisal_text_answers' : strtolower("appraisal_{$fieldTypeCode}_answers");

            $fields[] = [
                'key' => $fieldKey,
                'field' => $fieldTypeCode,
                'type' => 'custom_field',
                'label' => $formsCriteria->appraisal_criteria->name ?? '',
                'appraisal_criteria_id' => $formsCriteria->appraisal_criteria_id
            ];
        }
        $fields[] = [
            'key' => 'StaffAppraisals',
            'field' => 'modified_user_id',
            'type' => 'string',
            'label' => 'Modified By',
        ];
        $fields[] = [
            'key' => 'StaffAppraisals',
            'field' => 'modified',
            'type' => 'date',
            'label' => 'Modified On',
        ];
        $fields[] = [
            'key' => 'StaffAppraisals',
            'field' => 'created_user_id',
            'type' => 'string',
            'label' => 'Created By',
        ];
        $fields[] = [
            'key' => 'StaffAppraisals',
            'field' => 'created',
            'type' => 'date',
            'label' => 'Created On',
        ];
    }


    public function onExcelRenderCustomField(EventInterface $event, Entity $entity, array $attr)
    {
        $recordId = $entity['id'];
        if(!empty( $recordId)) {
            $entity = $this->_table->get($recordId);
            $appraisalFormId = $entity->appraisal_form_id;
            $criteriaCounter = new ArrayObject();
            $staffAppraisalId = $entity->has('id') ? $entity->id : -1;
            // retrieve all form criterias containing results
            $AppraisalFormsCriterias = TableRegistry::getTableLocator()->get('StaffAppraisal.AppraisalFormsCriterias');
            $query = $AppraisalFormsCriterias->find()
            ->contain([
                'AppraisalCriterias' => [
                    'FieldTypes',
                    'AppraisalSliders',
                    'AppraisalNumbers',
                    'AppraisalDropdownOptions' => ['sort' => ['AppraisalDropdownOptions.order' => 'ASC']]
                ],
                'AppraisalTextAnswers' => function ($q) use ($staffAppraisalId) {
                    return $q->where(['AppraisalTextAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                },
                'AppraisalSliderAnswers' => function ($q) use ($staffAppraisalId) {
                    return $q->where(['AppraisalSliderAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                },
                'AppraisalDropdownAnswers' => function ($q) use ($staffAppraisalId) {
                    return $q->where(['AppraisalDropdownAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                },
                'AppraisalNumberAnswers' => function ($q) use ($staffAppraisalId) {
                    return $q->where(['AppraisalNumberAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                },
                'AppraisalScoreAnswers' => function ($q) use ($staffAppraisalId) {
                    return $q->where(['AppraisalScoreAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                }
            ])
            ->where([$AppraisalFormsCriterias->aliasField('appraisal_form_id') => $appraisalFormId])
            ->where([$AppraisalFormsCriterias->aliasField('appraisal_criteria_id') =>  $attr['appraisal_criteria_id']])
            ->order($AppraisalFormsCriterias->aliasField('order'));
            $formsCriterias = $query->toArray();

            foreach ($formsCriterias as $key => $formsCriteria) {
                $fieldTypeCode = $formsCriteria->appraisal_criteria->field_type->code;
                $fieldTypeCode = $formsCriteria->appraisal_criteria->field_type->code;

                if($fieldTypeCode == 'SLIDER' && $attr['field'] == 'SLIDER') {
                    return  $formsCriteria->appraisal_slider_answers[0]->answer;
                }
                if($fieldTypeCode == 'TEXTAREA' && $attr['field'] == 'TEXTAREA') {
                    return  $formsCriteria->appraisal_text_answers[0]->answer;
                }
                if($fieldTypeCode == 'DROPDOWN' && $attr['field'] == 'DROPDOWN') {
                    $answer = !empty($formsCriteria->appraisal_dropdown_answers[0]->answer) ? $formsCriteria->appraisal_dropdown_answers[0]->answer :0 ;
                    $finalAns = isset($formsCriteria->appraisal_criteria->appraisal_dropdown_options[$answer -1 ]['name']) ? $formsCriteria->appraisal_criteria->appraisal_dropdown_options[$answer -1 ]['name'] :'';
                    return  $finalAns;
                }
                if($fieldTypeCode == 'NUMBER' && $attr['field'] == 'NUMBER') {
                    return  $formsCriteria->appraisal_number_answers[0]->answer;
                }
                if($fieldTypeCode == 'SCORE' && $attr['field'] == 'SCORE') {
                    return  $formsCriteria->appraisal_score_answers[0]->answer;
                }
            }
        }
    }
    //POCOR-8627 End
}
