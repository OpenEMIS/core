<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;

use CustomField\Model\Behavior\SetupBehavior;

class SetupTableBehavior extends SetupBehavior
{
    private $ruleOptions = [];
    private $numberValidationOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->ruleOptions = [
            '' => __('No Validation'),
            'number' => __('Number Validation'),
            'decimal' => __('Decimal Validation')
        ];

        $this->numberValidationOptions = [
            1 => __('No Validation'),
            'min_value' => __('Should not be lesser than'),
            'max_value' => __('Should not be greater than'),
            'range' => __('In between (inclusive)')
        ];
    }

    public function addBeforeAction(Event $event)
    {
        $model = $this->_table;
        $fieldTypes = $model->getFieldTypes();
        $selectedFieldType = isset($model->request->data[$model->alias()]['field_type']) ? $model->request->data[$model->alias()]['field_type'] : key($fieldTypes);

        if ($selectedFieldType == $this->fieldTypeCode) {
            $this->buildTableValidator();
        }
    }

    public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->field_type == $this->fieldTypeCode) {
            $this->buildTableValidator();
        }
    }

    private function buildTableValidator()
    {
        $min = $this->inputLimits['number_value']['min'];
        $max = $this->inputLimits['number_value']['max'];

        $minLength = $this->inputLimits['decimal_value']['length']['min'];
        $maxLength = $this->inputLimits['decimal_value']['length']['max'];

        $minPrecision = $this->inputLimits['decimal_value']['precision']['min'];
        $maxPrecision = $this->inputLimits['decimal_value']['precision']['max'];

        $validator = $this->_table->validator();
        $validator
            // NUMBER
            ->notEmpty('table_minimum_value')
            ->add('table_minimum_value', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('table_minimum_value', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->notEmpty('table_maximum_value')
            ->add('table_maximum_value', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('table_maximum_value', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->notEmpty('table_lower_limit')
            ->add('table_lower_limit', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('table_lower_limit', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->add('table_lower_limit', 'comparison', [
                'rule' => function ($value, $context) {
                    return intval($value) <= intval($context['data']['table_upper_limit']);
                },
                'message' => __('Lower Limit cannot be more than the Upper Limit.')
            ])
            ->notEmpty('table_upper_limit')
            ->add('table_upper_limit', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('table_upper_limit', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            // DECIMAL
            ->notEmpty('table_decimal_length')
            ->add('table_decimal_length', [
                'ruleRange' => [
                    'rule' => ['range', $minLength, $maxLength]
                ]
            ])
            ->notEmpty('table_decimal_precision')
            ->add('table_decimal_precision', [
                'ruleRange' => [
                    'rule' => ['range', $minPrecision, $maxPrecision]
                ]
            ])
        ;
    }

    public function onSetTableElements(Event $event, Entity $entity)
    {
        $model = $this->_table;

        // addOnInitialize or editOnInitialize
        if ($model->request->is(['get'])) {
            // view and edit
            if (!$entity->isNew()) {
                if ($entity->has('params') && !empty($entity->params)) {
                    $params = json_decode($entity->params, true);
                    if (array_key_exists('number', $params)) {
                        $entity->table_validation_rule = 'number';

                        $numberAttr = $params['number'];
                        if (is_array($numberAttr)) {
                            if (array_key_exists('min_value', $numberAttr)) {
                                $entity->table_number_validation = 'min_value';
                                $entity->table_minimum_value = $numberAttr['min_value'];
                            }

                            if (array_key_exists('max_value', $numberAttr)) {
                                $entity->table_number_validation = 'max_value';
                                $entity->table_maximum_value = $numberAttr['max_value'];
                            }

                            if (array_key_exists('range', $numberAttr)) {
                                $entity->table_number_validation = 'range';

                                if (array_key_exists('lower', $numberAttr['range'])) {
                                    $entity->table_lower_limit = $numberAttr['range']['lower'];
                                }

                                if (array_key_exists('upper', $numberAttr['range'])) {
                                    $entity->table_upper_limit = $numberAttr['range']['upper'];
                                }
                            }
                        } else {
                            $entity->table_number_validation = 1;
                        }
                    } else if (array_key_exists('decimal', $params)) {
                        $entity->table_validation_rule = 'decimal';

                        $decimalAttr = $params['decimal'];
                        if (array_key_exists('length', $decimalAttr)) {
                            $entity->table_decimal_length = $decimalAttr['length'];
                        }

                        if (array_key_exists('precision', $decimalAttr)) {
                            $entity->table_decimal_precision = $decimalAttr['precision'];
                        }
                    } else {
                        $entity->table_validation_rule = '';
                    }
                }
            }
        }

        $model->field('table_validation_rule', [
            'after' => 'is_unique',
            'attr' => [
                'label' => __('Validation Rule'),
                'required' => true
            ],
            'entity' => $entity
        ]);

        $selectedRule = $entity->has('table_validation_rule') ? $entity->table_validation_rule : '';
        switch ($selectedRule) {
            case 'number':
                $model->field('table_number_validation', [
                    'after' => 'table_validation_rule',
                    'attr' => [
                        'label' => __('Number Validation'),
                        'required' => true
                    ],
                    'entity' => $entity
                ]);

                $selectedNumberValidation = $entity->has('table_number_validation') ? $entity->table_number_validation : '';
                switch ($selectedNumberValidation) {
                    case 'min_value':
                        $model->field('table_minimum_value', [
                            'type' => 'integer',
                            'after' => 'table_number_validation',
                            'attr' => [
                                'label' => __('Minimum Value'),
                                'required' => true
                            ]
                        ]);
                        break;

                    case 'max_value':
                        $model->field('table_maximum_value', [
                            'type' => 'integer',
                            'after' => 'table_number_validation',
                            'attr' => [
                                'label' => __('Maximum Value'),
                                'required' => true
                            ]
                        ]);
                        break;

                    case 'range':
                        $model->field('table_lower_limit', [
                            'type' => 'integer',
                            'after' => 'table_number_validation',
                            'attr' => [
                                'label' => __('Lower Limit'),
                                'required' => true
                            ]
                        ]);
                        $model->field('table_upper_limit', [
                            'type' => 'integer',
                            'after' => 'table_lower_limit',
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
            case 'decimal':
                $model->field('table_decimal_length');
                $model->field('table_decimal_precision');
                break;
            default:
                // No Validation
                break;
        }

        // start tables element
        $fieldType = strtolower($this->fieldTypeCode);
        $this->_table->field('tables', [
            'type' => 'element',
            'order' => 0,
            'element' => 'CustomField.Setup/' . $fieldType,
            'visible' => true,
            'valueClass' => 'table-full-width'
        ]);
        $this->sortFieldOrder('tables');
        // end tables element
    }

    public function onGetTableValidationRule(Event $event, Entity $entity)
    {
        $value = '';
        $selectedValidationRule = $entity->has('table_validation_rule') ? $entity->table_validation_rule : key($this->ruleOptions);
        $value = array_key_exists($selectedValidationRule, $this->ruleOptions) ? $this->ruleOptions[$selectedValidationRule] : current($this->ruleOptions);

        return $value;
    }

    public function onGetTableNumberValidation(Event $event, Entity $entity)
    {
        $value = '';
        $selectedNumberValidation = $entity->has('table_number_validation') ? $entity->table_number_validation : key($this->numberValidationOptions);
        $value = array_key_exists($selectedNumberValidation, $this->numberValidationOptions) ? $this->numberValidationOptions[$selectedNumberValidation] : current($this->numberValidationOptions);

        return $value;
    }

    public function onUpdateFieldTableValidationRule(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $this->ruleOptions;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $selectedValidationRule = $entity->has('table_validation_rule') ? $entity->table_validation_rule : key($this->ruleOptions);

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedValidationRule;
            $attr['attr']['value'] = $this->ruleOptions[$selectedValidationRule];
        }

        return $attr;
    }

    public function onUpdateFieldTableNumberValidation(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = 'changeValidation';
            $attr['select'] = false;
            $attr['options'] = $this->numberValidationOptions;
        }

        return $attr;
    }

    public function onUpdateFieldTableDecimalLength(Event $event, array $attr, $action, Request $request)
    {
        $minLength = $this->inputLimits['decimal_value']['length']['min'];
        $maxLength = $this->inputLimits['decimal_value']['length']['max'];

        $tooltipMessage = vsprintf(__('%d - %d'), [$minLength, $maxLength]);

        $attr['attr']['min'] = $minLength;
        $attr['attr']['max'] = $maxLength;
        $attr['attr']['label']['text'] = __('Length') .
            ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="' .
            $tooltipMessage .
            '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
        $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label

        if ($action == 'add') {
            $attr['type'] = 'integer';
        } else if ($action == 'edit') {
            $attr['type'] = 'readOnly';
        }

        return $attr;
    }

    public function onUpdateFieldTableDecimalPrecision(Event $event, array $attr, $action, Request $request)
    {
        $minPrecision = $this->inputLimits['decimal_value']['precision']['min'];
        $maxPrecision = $this->inputLimits['decimal_value']['precision']['max'];

        $tooltipMessage = vsprintf(__('%d - %d'), [$minPrecision, $maxPrecision]);

        $attr['attr']['min'] = $minPrecision;
        $attr['attr']['max'] = $maxPrecision;
        $attr['attr']['label']['text'] = __('Decimal Place') .
            ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="' .
            $tooltipMessage .
            '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
        $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label

        if ($action == 'add') {
            $attr['type'] = 'integer';
        } else if ($action == 'edit') {
            $attr['type'] = 'readOnly';
        }

        return $attr;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryCopy = clone($query);
        $entity = $queryCopy->first();
        if ($entity->field_type == $this->fieldTypeCode) {
            $query->contain(['CustomTableColumns', 'CustomTableRows']);
        }

        $queryCopy = clone($query);
        $cellAnswerCount = $queryCopy
            ->matching('CustomTableCells')
            ->count();
        $editable = $cellAnswerCount == 0;

        $query->formatResults(function (ResultSetInterface $results) use ($editable) {
            return $results->map(function ($row) use ($editable) {
                $row->editable = $editable;
                return $row;
            });
        });
    }

    public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        $request = $model->request;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($model->alias(), $request->data)) {
                if (array_key_exists('custom_table_columns', $request->data[$model->alias()])) {
                    unset($data[$model->alias()]['custom_table_columns']);
                }
                if (array_key_exists('custom_table_rows', $request->data[$model->alias()])) {
                    unset($data[$model->alias()]['custom_table_rows']);
                }
            }
        }
    }

    public function addEditOnAddColumn(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
            $columnOptions = [
                'name' => '',
                'visible' => 1
            ];
            $data[$this->_table->alias()]['custom_table_columns'][] = $columnOptions;

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'CustomTableColumns' => ['validate' => false],
                'CustomTableRows' => ['validate' => false]
            ];
        }
    }

    public function addEditOnAddRow(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
            $rowOptions = [
                'name' => '',
                'visible' => 1
            ];
            $data[$this->_table->alias()]['custom_table_rows'][] = $rowOptions;

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'CustomTableColumns' => ['validate' => false],
                'CustomTableRows' => ['validate' => false]
            ];
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['field_type']) && $data['field_type'] == $this->fieldTypeCode) {
            if (isset($data['table_validation_rule'])) {
                if (!empty($data['table_validation_rule'])) {
                    $selectedRule = $data['table_validation_rule'];

                    $params = [];
                    switch ($selectedRule) {
                        case 'number':
                            if (isset($data['table_number_validation']) && !empty($data['table_number_validation'])) {
                                $selectedNumberValidation = $data['table_number_validation'];
                                switch ($selectedNumberValidation) {
                                    case 'min_value':
                                        $minValue = array_key_exists('table_minimum_value', $data) ? $data['table_minimum_value']: null;

                                        if (!is_null($minValue)) {
                                            $params['number']['min_value'] = $minValue;
                                        }
                                        break;

                                    case 'max_value':
                                        $maxValue = array_key_exists('table_maximum_value', $data) ? $data['table_maximum_value']: null;

                                        if (!is_null($maxValue)) {
                                            $params['number']['max_value'] = $maxValue;
                                        }
                                        break;

                                    case 'range':
                                        $lowerLimit = array_key_exists('table_lower_limit', $data) ? $data['table_lower_limit']: null;
                                        $upperLimit = array_key_exists('table_upper_limit', $data) ? $data['table_upper_limit']: null;

                                        if (!is_null($lowerLimit) && !is_null($upperLimit)) {
                                            $params['number']['range'] = [
                                                'lower' => $lowerLimit,
                                                'upper' => $upperLimit
                                            ];
                                        }
                                        break;
                                    case 1:
                                        $params['number'] = 1;
                                        break;
                                    
                                    default:
                                        break;
                                }
                            }
                            break;

                        case 'decimal':
                            $length = array_key_exists('table_decimal_length', $data) ? $data['table_decimal_length'] : null;
                            $precision = array_key_exists('table_decimal_precision', $data) ? $data['table_decimal_precision'] : null;

                            $params['decimal'] = [
                                'length' => $length,
                                'precision' => $precision
                            ];
                            break;

                        default:
                            break;
                    }

                    $data['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
                } else {
                    $data['params'] = '';
                }

                $submit = isset($data['submit']) ? $data['submit'] : 'save';
                // turn off validation when reload, to fix when changing validation rule and columns / rows name is empty
                if ($submit != 'save') {
                    $options['associated']['CustomTableColumns'] = ['validate' => false];
                    $options['associated']['CustomTableRows'] = ['validate' => false];
                }
            }
        }
    }
}
