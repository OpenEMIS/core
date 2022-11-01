<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;

use CustomField\Model\Behavior\SetupBehavior;

class SetupTextBehavior extends SetupBehavior
{
    private $ruleOptions = [];
    private $lengthValidationOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->ruleOptions = [
            '' => __('No Validation'),
            'length' => __('Length Validation'),
            'url' => __('URL Validation'),
            'input_mask' => __('Custom Validation')
        ];

        $this->lengthValidationOptions = [
            'min_length' => __('Should be at least'),
            'max_length' => __('Should not exceed'),
            'range' => __('Should be between')
        ];
    }

    public function addBeforeAction(Event $event)
    {
        $model = $this->_table;
        $fieldTypes = $model->getFieldTypes();
        $selectedFieldType = isset($model->request->data[$model->alias()]['field_type']) ? $model->request->data[$model->alias()]['field_type'] : key($fieldTypes);

        if ($selectedFieldType == $this->fieldTypeCode) {
            $this->buildTextValidator();
        }
    }

    public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->field_type == $this->fieldTypeCode) {
            $this->buildTextValidator();
        }
    }

    private function buildTextValidator()
    {
        $max = $this->inputLimits['text_value']['max'];

        $validator = $this->_table->validator();
        $validator
            // LENGTH - Mininum Length
            ->notEmpty('text_minimum_length')
            ->add('text_minimum_length', 'naturalNumber', [
                'rule' => 'naturalNumber',
                'message' => __('This field cannot be less than or equal zero')
            ])
            ->add('text_minimum_length', 'validateLength', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            // LENGTH - Maximum Length
            ->notEmpty('text_maximum_length')
            ->add('text_maximum_length', 'naturalNumber', [
                'rule' => 'naturalNumber',
                'message' => __('This field cannot be less than or equal zero')
            ])
            ->add('text_maximum_length', 'validateLength', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            // LENGTH - Range
            ->notEmpty('text_lower_limit')
            ->add('text_lower_limit', 'naturalNumber', [
                'rule' => 'naturalNumber',
                'message' => __('This field cannot be less than or equal zero')
            ])
            ->add('text_lower_limit', 'validateLength', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->notEmpty('text_upper_limit')
            ->add('text_upper_limit', 'naturalNumber', [
                'rule' => 'naturalNumber',
                'message' => __('This field cannot be less than or equal zero')
            ])
            ->add('text_upper_limit', 'validateLength', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->add('text_lower_limit', 'comparison', [
                'rule' => function ($value, $context) {
                    if (array_key_exists('text_upper_limit', $context['data']) && strlen($context['data']['text_upper_limit']) > 0) {
                        return intval($value) <= intval($context['data']['text_upper_limit']);
                    }

                    return true;
                },
                'message' => __('Minimum Length cannot be more than the Maximum Length')
            ])
            // INPUT MASK
            ->notEmpty('validation_format')
            ->add('validation_format', 'validateLength', [
                'rule' => function ($value, $context) use ($max) {
                    return strlen($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ]);
    }

    public function onSetTextElements(Event $event, Entity $entity)
    {
        $model = $this->_table;

        // addOnInitialize or editOnInitialize
        if ($model->request->is(['get'])) {
            // view and edit
            if (!$entity->isNew()) {
                if ($entity->has('params') && !empty($entity->params)) {
                    $params = json_decode($entity->params, true);
                    if (array_key_exists('min_length', $params)) {
                        $entity->text_validation_rule = 'length';
                        $entity->text_length_validation = 'min_length';

                        $entity->text_minimum_length = $params['min_length'];
                    } else if (array_key_exists('max_length', $params)) {
                        $entity->text_validation_rule = 'length';
                        $entity->text_length_validation = 'max_length';

                        $entity->text_maximum_length = $params['max_length'];
                    } else if (array_key_exists('range', $params)) {
                        $entity->text_validation_rule = 'length';
                        $entity->text_length_validation = 'range';

                        $entity->text_lower_limit = $params['range']['lower'];
                        $entity->text_upper_limit = $params['range']['upper'];
                    } else if (array_key_exists('url', $params)) {
                        $entity->text_validation_rule = 'url';
                    } else if (array_key_exists('input_mask', $params)) {
                        $entity->text_validation_rule = 'input_mask';
                        $entity->validation_format = $params['input_mask'];
                    } else {
                        $entity->text_validation_rule = '';
                    }
                }
            }
        }

        $model->field('text_validation_rule', [
            'after' => 'is_unique',
            'attr' => [
                'label' => __('Validation Rule'),
                'required' => true
            ],
            'entity' => $entity
        ]);

        $selectedRule = $entity->has('text_validation_rule') ? $entity->text_validation_rule : '';
        switch ($selectedRule) {
            case 'length':
                $model->field('text_length_validation', [
                    'after' => 'text_validation_rule',
                    'attr' => [
                        'label' => __('Length Validation'),
                        'required' => true
                    ],
                    'entity' => $entity
                ]);

                $selectedLengthValidation = $entity->has('text_length_validation') ? $entity->text_length_validation : key($this->lengthValidationOptions);
                switch ($selectedLengthValidation) {
                    case 'min_length':
                        $model->field('text_minimum_length', [
                            'type' => 'integer',
                            'after' => 'text_length_validation',
                            'attr' => [
                                'label' => __('Minimum Length'),
                                'required' => true
                            ]
                        ]);
                        break;

                    case 'max_length':
                        $model->field('text_maximum_length', [
                            'type' => 'integer',
                            'after' => 'text_length_validation',
                            'attr' => [
                                'label' => __('Maximum Length'),
                                'required' => true
                            ]
                        ]);
                        break;

                    case 'range':
                        $model->field('text_lower_limit', [
                            'type' => 'integer',
                            'after' => 'text_length_validation',
                            'attr' => [
                                'label' => __('Lower Limit'),
                                'required' => true
                            ]
                        ]);
                        $model->field('text_upper_limit', [
                            'type' => 'integer',
                            'after' => 'text_lower_limit',
                            'attr' => [
                                'label' => __('Upper Limit'),
                                'required' => true
                            ]
                        ]);
                        break;

                    default:
                        break;
                }
                break;
            case 'url':
                break;
            case 'input_mask':
                $fieldType = strtolower($this->fieldTypeCode);
                $model->field('validation_reference', [
                    'type' => 'element',
                    'element' => 'CustomField.Setup/' . $fieldType,
                    'valueClass' => 'table-full-width',
                    'after' => 'validation_rule'
                ]);
                $model->field('validation_format', [
                    'type' => 'string',
                    'attr' => ['onkeypress' => 'return Config.inputMaskCheck(event);'],
                    'after' => 'validation_reference'
                ]);
                break;
            default:
                // No Validation
                break;
        }
    }

    public function onGetTextValidationRule(Event $event, Entity $entity)
    {
        $value = '';
        $selectedValidationRule = $entity->has('text_validation_rule') ? $entity->text_validation_rule : key($this->ruleOptions);
        $value = array_key_exists($selectedValidationRule, $this->ruleOptions) ? $this->ruleOptions[$selectedValidationRule] : current($this->ruleOptions);

        return $value;
    }

    public function onGetTextLengthValidation(Event $event, Entity $entity)
    {
        $value = '';
        $selectedLengthValidation = $entity->has('text_length_validation') ? $entity->text_length_validation : key($this->lengthValidationOptions);
        $value = array_key_exists($selectedLengthValidation, $this->lengthValidationOptions) ? $this->lengthValidationOptions[$selectedLengthValidation] : current($this->lengthValidationOptions);

        return $value;
    }

    public function onUpdateFieldTextValidationRule(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $this->ruleOptions;
        }

        return $attr;
    }

    public function onUpdateFieldTextLengthValidation(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = 'changeLengthValidation';
            $attr['select'] = false;
            $attr['options'] = $this->lengthValidationOptions;
        }

        return $attr;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['field_type']) && $data['field_type'] == $this->fieldTypeCode) {
            if (isset($data['text_validation_rule'])) {
                if (!empty($data['text_validation_rule'])) {
                    $selectedRule = $data['text_validation_rule'];

                    $params = [];
                    switch ($selectedRule) {
                        case 'length':
                            if (isset($data['text_length_validation']) && !empty($data['text_length_validation'])) {
                                $selectedLengthValidation = $data['text_length_validation'];
                                switch ($selectedLengthValidation) {
                                    case 'min_length':
                                        $minLength = array_key_exists('text_minimum_length', $data) ? $data['text_minimum_length']: null;

                                        if (!is_null($minLength)) {
                                            $params['min_length'] = $minLength;
                                        }
                                        break;

                                    case 'max_length':
                                        $maxLength = array_key_exists('text_maximum_length', $data) ? $data['text_maximum_length']: null;

                                        if (!is_null($maxLength)) {
                                            $params['max_length'] = $maxLength;
                                        }
                                        break;

                                    case 'range':
                                        $lowerLimit = array_key_exists('text_lower_limit', $data) ? $data['text_lower_limit']: null;
                                        $upperLimit = array_key_exists('text_upper_limit', $data) ? $data['text_upper_limit']: null;

                                        if (!is_null($lowerLimit) && !is_null($upperLimit)) {
                                            $params['range'] = [
                                                'lower' => $lowerLimit,
                                                'upper' => $upperLimit
                                            ];
                                        }
                                        break;
                                    
                                    default:
                                        break;
                                }
                            }
                            break;

                        case 'url':
                            $params['url'] = 1;
                            break;
                        case 'input_mask':
                            if (array_key_exists('validation_format', $data) && !empty($data['validation_format'])) {
                                $params['input_mask'] = $data['validation_format'];
                            }
                            break;
                        default:
                            break;
                    }

                    $data['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
                } else {
                    $data['params'] = '';
                }
            }
        }
    }
}
