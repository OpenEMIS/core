<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupDateBehavior extends SetupBehavior
{
    private $rangeValidationOptions;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->rangeValidationOptions = [
            'no' => __('No Validation'),
            'earlier' => __('Should not be earlier than'),
            'later' => __('Should not be later than'),
            'between' => __('In between (inclusive)')
        ];

        $this->_table->addBehavior('ControllerAction.DatePicker', ['start_date', 'end_date']);
    }

    public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra)
    {
        $fieldType = '';
        if (!empty($this->_table->request->data)) {
            $fieldType = (array_key_exists('field_type', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['field_type']: null;
        } else {
            if (!empty($entity)) {
                $fieldType = $entity->field_type;
            }
        }

        if (isset($fieldType) && $fieldType == 'DATE') {
            $this->addDateValidation();
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->_table->request->is('POST')) {
            $fieldType = (array_key_exists('field_type', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['field_type']: null;
            if ($fieldType == 'DATE') {
                $this->addDateValidation();
            }
        }
    }

    private function addDateValidation()
    {
        $validator = $this->_table->validator();
        $validator->notEmpty('validation_rules_date');
        $validator->notEmpty('start_date');
        $validator->notEmpty('end_date');

        $validator->add('start_date', 'ruleCompareDate', [
            'rule' => ['compareDate', 'end_date', true],
            'provider' => 'table',
            'on' => function ($context) {
                return $context['data']['field_type'] == $this->fieldTypeCode && $context['data']['validation_rules_date'] == 'between';
            }
        ]);
    }

    public function onSetDateElements(Event $event, Entity $entity)
    {
        $fieldType = strtolower($this->fieldTypeCode);

        $paramsArray = [];
        if ($this->_table->action == 'edit') {
            if (empty($this->_table->request->data)) {
                $paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
            }
        }

        if (!empty($this->_table->request->data)) {
            $selectedRangeValidation = (array_key_exists($this->_table->alias(), $this->_table->request->data) && array_key_exists('validation_rules_date', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['validation_rules_date']: null;
        } else {
            if (array_key_exists('start_date', $paramsArray) && array_key_exists('end_date', $paramsArray)) {
                $selectedRangeValidation = 'between';
            } else if (array_key_exists('start_date', $paramsArray)) {
                $selectedRangeValidation = 'earlier';
            } else if (array_key_exists('end_date', $paramsArray)) {
                $selectedRangeValidation = 'later';
            } else {
                $selectedRangeValidation = 'no';
            }
        }

        $this->_table->field('validation_rules_date', ['options' => $this->rangeValidationOptions, 'onChangeReload' => true, 'after' => 'is_mandatory', 'default' => $selectedRangeValidation, 'attr' => ['required' => 'required', 'label' => $this->_table->getMessage('general.validationRules')]]);

        if (!empty($selectedRangeValidation)) {
            switch ($selectedRangeValidation) {
                case 'earlier':
                    $options = ['type' => 'date', 'after' => 'validation_rules_date', 'null' => false];
                    if (array_key_exists('start_date', $paramsArray)) {
                        $options['value'] = $paramsArray['start_date'];
                    }
                    $this->_table->field('start_date', $options);
                    break;
                case 'later':
                    $options = ['type' => 'date', 'after' => 'validation_rules_date', 'null' => false];
                    if (array_key_exists('end_date', $paramsArray)) {
                        $options['value'] = $paramsArray['end_date'];
                    }
                    $this->_table->field('end_date', $options);
                    break;
                case 'between':
                    $options = ['type' => 'date', 'after' => 'validation_rules_date', 'null' => false];
                    if (array_key_exists('start_date', $paramsArray)) {
                        $options['value'] = $paramsArray['start_date'];
                    }
                    $this->_table->field('start_date', $options);
                    $options = ['type' => 'date', 'after' => 'start_date', 'null' => false];
                    if (array_key_exists('end_date', $paramsArray)) {
                        $options['value'] = $paramsArray['end_date'];
                    }
                    $this->_table->field('end_date', $options);
                    break;
                case 'no':
                default:
                    // no code required
                    break;
            }
        }
    }

    public function onGetValidationRulesDate(Event $event, Entity $entity)
    {
        $decodedParams = $event->subject()->HtmlField->decodeEscapeHtmlEntity($entity->params);
        $paramsArray = (!empty($decodedParams))? json_decode($decodedParams, true): [];
        if (array_key_exists('start_date', $paramsArray) && array_key_exists('end_date', $paramsArray)) {
            return $this->rangeValidationOptions['between'].' '.$paramsArray['start_date'].' - '.$paramsArray['end_date'];
        } else if (array_key_exists('start_date', $paramsArray)) {
            return $this->rangeValidationOptions['earlier'].' '.$paramsArray['start_date'];
        } else if (array_key_exists('end_date', $paramsArray)) {
            return $this->rangeValidationOptions['later'].' '.$paramsArray['end_date'];
        } else {
            return $this->rangeValidationOptions['no'];
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (array_key_exists('validation_rules_date', $data)) {
            if ($data['field_type'] == $this->fieldTypeCode) {
                $paramsArray = [];
                $start_date = (array_key_exists('start_date', $data))? $data['start_date']: null;
                $end_date = (array_key_exists('end_date', $data))? $data['end_date']: null;

                if (!empty($start_date)) {
                    $paramsArray['start_date'] = $start_date;
                }
                if (!empty($end_date)) {
                    $paramsArray['end_date'] = $end_date;
                }

                if (!empty($paramsArray)) {
                    $data['params'] = json_encode($paramsArray);
                } else {
                    $data['params'] = '';
                }
            }
        }
    }
}
