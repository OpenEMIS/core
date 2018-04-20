<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;

use CustomField\Model\Behavior\SetupBehavior;

class SetupTableBehavior extends SetupBehavior
{
    private $validationOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->ruleOptions = [
            'number' => __('Number Validation'),
            'decimal' => __('Decimal Validation')
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
        $minLength = $this->inputLimits['decimal_value']['length']['min'];
        $maxLength = $this->inputLimits['decimal_value']['length']['max'];

        $minPrecision = $this->inputLimits['decimal_value']['precision']['min'];
        $maxPrecision = $this->inputLimits['decimal_value']['precision']['max'];

        $validator = $this->_table->validator();
        $validator
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
        // start table rule
        $model = $this->_table;

        if ($model->request->is(['get'])) {
            if (isset($entity->id)) {
                // view / edit
                if ($entity->has('params') && !empty($entity->params)) {
                    $params = json_decode($entity->params, true);
                    if (array_key_exists('number', $params)) {
                        $model->request->query['table_rule'] = 'number';
                    } else if (array_key_exists('decimal', $params)) {
                        $model->request->query['table_rule'] = 'decimal';
                        $decimalAttr = $params['decimal'];

                        if (array_key_exists('length', $decimalAttr)) {
                            $entity->table_decimal_length = $decimalAttr['length'];
                        }

                        if (array_key_exists('precision', $decimalAttr)) {
                            $entity->table_decimal_precision = $decimalAttr['precision'];
                        }
                    }
                }
            } else {
                // add
                unset($model->request->query['table_rule']);
            }

            if ($model->action == 'view') {
                $selectedRule = $model->request->query('table_rule');
                $entity->validation_rule = !is_null($selectedRule) ? $this->ruleOptions[$selectedRule] : __('No Validation');
            }
        }

        $ruleOptions = ['' => __('No Validation')] + $this->ruleOptions;
        $selectedRule = $model->queryString('table_rule', $ruleOptions);

        if ($model->action == 'edit') {
            $model->field('validation_rule', [
                'type' => 'readonly',
                'value' => $selectedRule,
                'attr' => [
                    'value' => $ruleOptions[$selectedRule]
                ]
            ]);
        } else {
            $model->field('validation_rule', [
                'type' => 'select',
                'options' => $ruleOptions,
                'default' => $selectedRule,
                'value' => $selectedRule,
                'onChangeReload' => true,
                'after' => 'is_unique'
            ]);
        }

        switch ($selectedRule) {
            case 'decimal':
                $model->field('table_decimal_length');
                $model->field('table_decimal_precision');
                break;
            case 'number':
                break;
            default:
                break;
        }
        // end table rule

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
            if (isset($data['validation_rule'])) {
                $model = $this->_table;
                $request = $model->request;
                unset($request->query['table_rule']);

                if (!empty($data['validation_rule'])) {
                    $selectedRule = $data['validation_rule'];
                    $request->query['table_rule'] = $selectedRule;
                    $params = [];

                    switch ($selectedRule) {
                        case 'number':
                            $params['number'] = 1;
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
                // turn off validation when reload
                if ($submit != 'save') {
                    $options['associated']['CustomTableColumns'] = ['validate' => false];
                    $options['associated']['CustomTableRows'] = ['validate' => false];
                }
            }
        }
    }
}
