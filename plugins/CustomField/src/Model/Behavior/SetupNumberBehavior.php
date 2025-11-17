<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupNumberBehavior extends SetupBehavior
{
    private $validationOptions = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->ruleOptions = [
            'min_value' => __('Should not be less than'),
            'max_value' => __('Should not be greater than'),
            'range' => __('In between (inclusive)')
        ];
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $fieldTypes = $model->getFieldTypes();
        $selectedFieldType = isset($model->request->getData($model->getAlias())['field_type']) ? $model->request->getData($model->getAlias())['field_type'] : key($fieldTypes);

        if ($selectedFieldType == $this->fieldTypeCode) {
            $this->buildNumberValidator();
        }
    }

    public function editAfterQuery(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->field_type == $this->fieldTypeCode) {
            $this->buildNumberValidator();
        }
    }

    private function buildNumberValidator()
    {
        $min = $this->inputLimits['number_value']['min'];
        $max = $this->inputLimits['number_value']['max'];

        $validator = $this->_table->getValidator();
        $validator
            ->notEmpty('minimum_value')
            ->add('minimum_value', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('minimum_value', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->notEmpty('maximum_value')
            ->add('maximum_value', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('maximum_value', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->notEmpty('lower_limit')
            ->add('lower_limit', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('lower_limit', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ->add('lower_limit', 'comparison', [
                'rule' => function ($value, $context) {
                    return intval($value) <= intval($context['data']['upper_limit']);
                },
                'message' => __('Lower Limit cannot be more than the Upper Limit.')
            ])
            ->notEmpty('upper_limit')
            ->add('upper_limit', 'validateLower', [
                'rule' => function ($value, $context) use ($min) {
                    return intval($value) >= $min;
                },
                'message' => vsprintf(__('This field cannot be less than %d'), [$min])
            ])
            ->add('upper_limit', 'validateUpper', [
                'rule' => function ($value, $context) use ($max) {
                    return intval($value) <= $max;
                },
                'message' => vsprintf(__('This field cannot be more than %d'), [$max])
            ])
            ;
    }

    public function onSetNumberElements(EventInterface $event, Entity $entity)
    {
        $model = $this->_table;
        $request = $model->request;
        if ($model->request->is(['get'])) {
            if (isset($entity->id)) {
                // view / edit
                if ($entity->has('params') && !empty($entity->params)) {
                    $params = json_decode($entity->params, true);
                    if (isset($params['min_value'])) {
                        //$model->request->query['number_rule'] = 'min_value';
                        $request = $request->withQueryParams(array_merge($request->getQueryParams(), ['number_rule' => 'min_value']));
                        $entity->minimum_value = $params['min_value'];
                    } else if (isset($params['max_value'])) {
                        //$model->request->query['number_rule'] = 'max_value';
                        $request = $request->withQueryParams(array_merge($request->getQueryParams(), ['number_rule' => 'max_value']));
                        $entity->maximum_value = $params['max_value'];
                    } else if (isset($params['range'])) {
                        //$model->request->query['number_rule'] = 'range';
                        $request = $request->withQueryParams(array_merge($request->getQueryParams(), ['number_rule' => 'range']));
                        $entity->lower_limit = $params['range']['lower'];
                        $entity->upper_limit = $params['range']['upper'];
                    }
                    $model->request = $request;
                }
            } else {
                // add
                $queryParams = $request->getQueryParams();
                //unset($model->request->query['number_rule']);
                unset($queryParams['number_rule']);
                $request = $request->withQueryParams($queryParams);
                $model->request = $request;
            }

            if ($model->action == 'view') {
                $selectedRule = $model->request->getQuery('number_rule');
                $entity->validation_rule = !is_null($selectedRule) ? $this->ruleOptions[$selectedRule] : __('No Validation');
            }
        }

        $ruleOptions = ['' => __('No Validation')] + $this->ruleOptions;
        $selectedRule = $model->queryString('number_rule', $ruleOptions);

        $model->field('validation_rule', [
            'type' => 'select',
            'options' => $ruleOptions,
            'default' => $selectedRule,
            'value' => $selectedRule,
            'onChangeReload' => 'changeRule',
            'after' => 'is_unique'
        ]);

        switch ($selectedRule) {
            case 'min_value':
                $model->field('minimum_value', [
                    'type' => 'integer',
                    'after' => 'validation_rule'
                ]);
                break;
            case 'max_value':
                $model->field('maximum_value', [
                    'type' => 'integer',
                    'after' => 'validation_rule'
                ]);
                break;
            case 'range':
                $model->field('lower_limit', [
                    'type' => 'integer',
                    'after' => 'validation_rule'
                ]);
                $model->field('upper_limit', [
                    'type' => 'integer',
                    'after' => 'lower_limit'
                ]);
                break;
            default:
                break;
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['field_type']) && $data['field_type'] == $this->fieldTypeCode) {
            if (isset($data['validation_rule'])) {
                $model = $this->_table;
                $request = $model->request;
                unset($request->getQuery['number_rule']);

                if (!empty($data['validation_rule'])) {
                    $selectedRule = $data['validation_rule'];
                    //$request->query['number_rule'] = $selectedRule;
                    $request = $request->withQueryParams(array_merge($request->getQueryParams(), ['number_rule' => $selectedRule]));
                    $model->request = $request;
                    $params = [];

                    switch ($selectedRule) {
                        case 'min_value':
                            $minValue = $data->offsetExists('minimum_value') ? $data->offsetGet('minimum_value'): null;

                            if (!is_null($minValue)) {
                                $params['min_value'] = $data->offsetGet('minimum_value');
                            }
                            break;
                        case 'max_value':
                            $maxValue = $data->offsetExists('maximum_value') ? $data->offsetGet('maximum_value'): null;

                            if (!is_null($maxValue)) {
                                $params['max_value'] =  $data->offsetGet('maximum_value');
                            }
                            break;
                        case 'range':
                            $lowerLimit = $data->offsetExists('lower_limit') ? $data->offsetGet('lower_limit'): null;
                            $upperLimit = $data->offsetExists('upper_limit') ? $data->offsetGet('upper_limit'): null;

                            if (!is_null($lowerLimit) && !is_null($upperLimit)) {
                                $params['range'] = [
                                    'lower' => $data['lower_limit'],
                                    'upper' => $data['upper_limit']
                                ];
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
