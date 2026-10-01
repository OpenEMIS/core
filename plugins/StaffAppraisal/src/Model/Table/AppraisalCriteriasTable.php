<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use StaffAppraisal\Model\Table\AppraisalNumbersTable as AppraisalNumbers;
use StaffAppraisal\Model\Table\AppraisalSlidersTable as AppraisalSliders;

class AppraisalCriteriasTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('FieldTypes', ['className' => 'FieldOption.FieldTypes', 'foreignKey' => 'field_type_id']);
        $this->hasOne('AppraisalSliders', ['className' => 'StaffAppraisal.AppraisalSliders', 'foreignKey' => 'appraisal_criteria_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasOne('AppraisalNumbers', ['className' => 'StaffAppraisal.AppraisalNumbers', 'foreignKey' => 'appraisal_criteria_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalSliderOptions', [
            'className' => 'StaffAppraisal.AppraisalSliderOptions',
            'foreignKey' => 'appraisal_criteria_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalDropdownOptions', [
            'className' => 'StaffAppraisal.AppraisalDropdownOptions',
            'foreignKey' => 'appraisal_criteria_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('AppraisalForms', [
            'className' => 'StaffAppraisal.AppraisalForms',
            'foreignKey' => 'appraisal_criteria_id',
            'targetForeignKey' => 'appraisal_form_id',
            'joinTable' => 'appraisal_forms_criterias',
            'through' => 'StaffAppraisal.AppraisalFormsCriterias',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        // Added
        $this->hasOne('AppraisalScores', ['className' => 'StaffAppraisal.AppraisalFormsCriteriasScores',
            'foreignKey' => 'appraisal_criteria_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->add('code', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('Code must be unique')
            ])
            ->requirePresence('field_type_id', 'create');
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['FieldTypes', 'AppraisalSliders', 'AppraisalNumbers', 'AppraisalDropdownOptions.AppraisalDropdownAnswers', 'AppraisalSliderOptions']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $code = $entity->field_type->code;
        switch ($code) {
            case 'TEXTAREA':
                // No implementation
                break;
            case 'SLIDER':
                $this->field('slider_type', ['after' => 'field_type_id']);
                if ($entity->appraisal_slider->slider_type == AppraisalSliders::TEXT) {
                    $this->field('slider_options', [
                        'type' => 'element',
                        'element' => 'StaffAppraisal.slider_options',
                        'after' => 'slider_type'
                    ]);
                } else {
                    $this->field('min', ['after' => 'slider_type']);
                    $this->field('max', ['after' => 'min']);
                    $this->field('step', ['after' => 'max']);
                }
                break;
            case 'DROPDOWN':
                $this->field('options', [
                    'type' => 'element',
                    'element' => 'StaffAppraisal.dropdown_options',
                    'after' => 'field_type_id'
                ]);
                break;
            case 'NUMBER':
                $this->setupNumberField($entity);
                break;
        }
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('name');
        $this->field('description', ['type' => 'text']);//POCOR-8864
        $this->field('field_type_id', [
            'type' => 'select',
            'entity' => $entity
        ]);     
    }

    public function onGetMin(EventInterface $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->min);
    }

    public function onGetMax(EventInterface $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->max);
    }

    public function onGetStep(EventInterface $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->step);
    }

    public function onGetSliderType(EventInterface $event, Entity $entity)
    {
        $options = $this->AppraisalSliders->getSliderTypeOptions();
        $sliderType = $entity->appraisal_slider->slider_type ?? null;
        return $options[$sliderType] ?? '';
    }

    // public function onUpdateFieldFieldTypeId(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldFieldTypeId(EventInterface $event, array $attr, $action)
    {
        if ($action == 'add' || $action == 'edit') {
            $fieldTypeOptions = $this->FieldTypes
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code'
                ])
                ->order([$this->FieldTypes->aliasField('id')])
                ->toArray();

            $entity = $attr['entity'];
            $fieldTypeId = $entity->field_type_id;

            if (!$entity->isNew()) { // edit not allow to change field type
                $attr['type'] = 'readonly';
                $attr['value'] = $fieldTypeId;
                $attr['attr']['value'] = $entity->field_type->name;
            }

            if (isset($fieldTypeOptions[$fieldTypeId])) {
                switch ($fieldTypeOptions[$fieldTypeId]) {
                    case 'TEXTAREA':
                        // No implementation
                        break;
                    case 'SLIDER':
                        $this->setupSliderField($entity);
                        break;
                    case 'DROPDOWN':
                        $this->field('options', [
                            'type' => 'element',
                            'element' => 'StaffAppraisal.dropdown_options'
                        ]);
                        break;
                    case 'NUMBER':
                        $this->setupNumberField($entity);
                        break;
                }
            }
            $attr['onChangeReload'] = 'changeFieldType';
        }

        return $attr;
    }

    public function addEditOnAddOption(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists($this->getAlias())) {//POCOR-9187[START] alias -> getAlias()
            $recordData = $data[$this->getAlias()];
            $fieldTypeCode = null;
            if (!empty($recordData['field_type_id'])) {
                $fieldTypeCode = $this->FieldTypes->get($recordData['field_type_id'])->code;
            }

            if ($fieldTypeCode === 'SLIDER') {
                if (array_key_exists('appraisal_slider_options', $recordData)) {
                    $sliderOptions = $recordData['appraisal_slider_options'];
                    $data[$this->getAlias()]['appraisal_slider_options'] = array_values($sliderOptions); // reindex array keys
                }
                $data[$this->getAlias()]['appraisal_slider_options'][] = [
                    'label' => '',
                    'value' => ''
                ];
            } else {
                if (array_key_exists('appraisal_dropdown_options', $recordData)) {
                    $dropdownOptions = $recordData['appraisal_dropdown_options'];
                    $data[$this->getAlias()]['appraisal_dropdown_options'] = array_values($dropdownOptions); // reindex array keys
                }
                $data[$this->getAlias()]['appraisal_dropdown_options'][] = [
                    'name' => '',
                    'is_default' => 0
                ];
            }
        }

        $options['associated'] = [
            'AppraisalDropdownOptions' => ['validate' => false],
            'AppraisalSliderOptions' => ['validate' => false]
        ];
    }

    //POCOR-9187[START]
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
    //POCOR-9187[END]

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['field_type_id']) && !empty($data['field_type_id'])) {
            $fieldTypeCode = $this->FieldTypes->get($data['field_type_id'])->code;
            if ($fieldTypeCode == 'DROPDOWN') {
                if (!isset($data['appraisal_dropdown_options'])) {
                    $data['appraisal_dropdown_options'] = []; // enables all options to be deleted
                }
                if (!empty($data['appraisal_dropdown_options']) && isset($data['is_default'])) {
                    $defaultKey = $data['is_default'];
                    $data['appraisal_dropdown_options'][$defaultKey]['is_default'] = 1; // set default option
                }
            } elseif ($fieldTypeCode == 'NUMBER') {
                if ($data['submit'] == 'save') {
                    $this->AppraisalNumbers->updateData($data);
                }
            } elseif ($fieldTypeCode == 'SLIDER') {
                $sliderType = $data['appraisal_slider']['slider_type'] ?? null;
                if ($sliderType === null && !empty($data['id'])) {
                    // Slider Type is read-only after creation, so it may not be resubmitted on
                    // edit - fall back to what's already saved so option deletions still work.
                    $existingSlider = $this->AppraisalSliders->find()
                        ->where([$this->AppraisalSliders->aliasField('appraisal_criteria_id') => $data['id']])
                        ->first();
                    $sliderType = $existingSlider->slider_type ?? null;
                }
                if ($sliderType == AppraisalSliders::TEXT) {
                    if (!isset($data['appraisal_slider_options'])) {
                        $data['appraisal_slider_options'] = []; // enables all rows to be deleted
                    }
                } elseif ($sliderType == AppraisalSliders::NUMBER) {
                    $data['appraisal_slider_options'] = []; // clear any previously saved text options
                }
            }
        }
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->AppraisalDropdownOptions->getAlias(),
            $this->AppraisalSliderOptions->getAlias()
        ];
    }

    // Number field type
    public function setupNumberField(Entity $entity)
    {
        $this->field('appraisal_number.validation_rule', [
            'type' => 'select',
            'select' => false,
            'after' => 'field_type_id',
            'options' => $this->AppraisalNumbers->getValidationTypeOptions(),
            'onChangeReload' => true,
            'attr' => [
                'label' => __('Validation Rule'),
                'required' => true
            ]
        ]);

        $validationRuleType = '';
        if ($entity->has('appraisal_number')) {
            $appraisalNumber = $entity->appraisal_number;
            if ($appraisalNumber->has('validation_rule')) {
                $validationRuleType = $appraisalNumber->validation_rule;
            }
        }

        switch ($validationRuleType) {
            case AppraisalNumbers::LESS_THAN:
                $this->field('appraisal_number.max_exclusive', [
                    'type' => 'integer',
                    'after' => 'appraisal_number.validation_rule',
                    'attr' => [
                        'label' => __('Value'),
                        'required' => true
                    ]
                ]);
                break;
            case AppraisalNumbers::LESS_THAN_OR_EQUAL:
                $this->field('appraisal_number.max_inclusive', [
                    'type' => 'integer',
                    'after' => 'appraisal_number.validation_rule',
                    'attr' => [
                        'label' => __('Value'),
                        'required' => true
                    ]
                ]);
                break;
            case AppraisalNumbers::GREATER_THAN:
                $this->field('appraisal_number.min_exclusive', [
                    'type' => 'integer',
                    'after' => 'appraisal_number.validation_rule',
                    'attr' => [
                        'label' => __('Value'),
                        'required' => true
                    ]
                ]);
                break;
            case AppraisalNumbers::GREATER_THAN_OR_EQUAL:
                $this->field('appraisal_number.min_inclusive', [
                    'type' => 'integer',
                    'after' => 'appraisal_number.validation_rule',
                    'attr' => [
                        'label' => __('Value'),
                        'required' => true
                    ]
                ]);
                break;
            case AppraisalNumbers::BETWEEN:
                $this->field('appraisal_number.min_inclusive', [
                    'type' => 'integer',
                    'after' => 'appraisal_number.validation_rule',
                    'attr' => [
                        'label' => __('Lower Limit'),
                        'required' => true
                    ]
                ]);
                $this->field('appraisal_number.max_inclusive', [
                    'type' => 'integer',
                    'after' => 'appraisal_number.min_inclusive',
                    'attr' => [
                        'label' => __('Upper Limit'),
                        'required' => true
                    ]
                ]);
                break;
        }
    }
    // Slider field type
    public function setupSliderField(Entity $entity)
    {
        $sliderTypeOptions = $this->AppraisalSliders->getSliderTypeOptions();

        $sliderType = '';
        if ($entity->has('appraisal_slider')) {
            $appraisalSlider = $entity->appraisal_slider;
            if (is_array($appraisalSlider)) {
                // may still be a plain array (not yet marshalled into an Entity) the first
                // time 'Slider' is selected, before any appraisal_slider.* data is posted
                $sliderType = $appraisalSlider['slider_type'] ?? '';
            } elseif ($appraisalSlider->has('slider_type')) {
                $sliderType = $appraisalSlider->slider_type;
            }
        }

        $sliderTypeAttr = [
            'type' => 'select',
            'select' => false,
            'after' => 'field_type_id',
            'options' => $sliderTypeOptions,
            'onChangeReload' => true,
            'attr' => [
                'label' => __('Slider Type'),
                'required' => true
            ]
        ];

        if (!$entity->isNew()) { // edit not allowed to change slider type, matching field_type_id
            $sliderTypeAttr['type'] = 'readonly';
            $sliderTypeAttr['value'] = $sliderType;
            $sliderTypeAttr['attr']['value'] = $sliderTypeOptions[$sliderType] ?? $sliderType;
        }

        $this->field('appraisal_slider.slider_type', $sliderTypeAttr);

        switch ($sliderType) {
            case AppraisalSliders::TEXT:
                $this->field('slider_options', [
                    'type' => 'element',
                    'element' => 'StaffAppraisal.slider_options',
                    'after' => 'appraisal_slider.slider_type'
                ]);
                break;
            case AppraisalSliders::NUMBER:
            default:
                $this->field('appraisal_slider.min', [
                    'type' => 'integer',
                    'after' => 'appraisal_slider.slider_type',
                    'attr' => ['label' => __('Min'),
                    'required' => true]
                ]);
                $this->field('appraisal_slider.max', [
                    'type' => 'integer',
                    'after' => 'appraisal_slider.min',
                    'attr' => ['label' => __('Max'),
                    'required' => true]
                ]);
                $this->field('appraisal_slider.step', [
                    'type' => 'integer',
                    'after' => 'appraisal_slider.max',
                    'attr' => ['label' => __('Step'),
                    'required' => true]
                ]);
                break;
        }
    }

    // Start POCOR-5188
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $visible = ['index' => false, 'view' => true, 'edit' => true, 'add' => true];//POCOR-8864
        $this->field('description', ['visible' => $visible,'after'=>'field_type_id']);//POCOR-8864
		$is_manual_exist = $this->getManualUrl('Administration','Criterias','Staff Appraisals');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
    }
    // End POCOR-5188

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'code') {
            return __('Code');
        }else if ($field == 'name') {
            return __('Name');
        }else if ($field == 'field_type_id') {
            return __('Field Type');//POCOR-8864
        }else if ($field == 'modified_user_id') {
            return __('Modified User');
        }else if ($field == 'modified') {
            return __('Modified');
        }else if ($field == 'created_user_id') {
            return __('Created User');
        }else if ($field == 'created') {
            return __('Created');
        }
        else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
