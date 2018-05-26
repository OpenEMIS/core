<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupTimeBehavior extends SetupBehavior
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

        $this->_table->addBehavior('ControllerAction.TimePicker', ['start_time', 'end_time']);
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

        if (isset($fieldType) && $fieldType == 'TIME') {
            $this->addTimeValidation();
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->_table->request->is('POST')) {
            $fieldType = (array_key_exists('field_type', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['field_type']: null;
            if ($fieldType == 'TIME') {
                $this->addTimeValidation();
            }
        }
    }

    private function addTimeValidation()
    {
        $validator = $this->_table->validator();
        $validator->notEmpty('validation_rules_time');
        $validator->notEmpty('start_time');
        $validator->notEmpty('end_time');

        $validator->add('start_time', 'ruleCompareTime', [
            'rule' => ['compareTime', 'end_time', true],
            'provider' => 'table',
            'on' => function ($context) {
                return $context['data']['field_type'] == $this->fieldTypeCode && $context['data']['validation_rules_time'] == 'between';
            }
        ]);
    }

    public function onSetTimeElements(Event $event, Entity $entity)
    {
        $fieldType = strtolower($this->fieldTypeCode);

        $paramsArray = [];
        if ($this->_table->action == 'edit') {
            if (empty($this->_table->request->data)) {
                $paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
            }
        }

        if (!empty($this->_table->request->data)) {
            $selectedRangeValidation = (array_key_exists($this->_table->alias(), $this->_table->request->data) && array_key_exists('validation_rules_time', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['validation_rules_time']: null;
        } else {
            if (array_key_exists('start_time', $paramsArray) && array_key_exists('end_time', $paramsArray)) {
                $selectedRangeValidation = 'between';
            } else if (array_key_exists('start_time', $paramsArray)) {
                $selectedRangeValidation = 'earlier';
            } else if (array_key_exists('end_time', $paramsArray)) {
                $selectedRangeValidation = 'later';
            } else {
                $selectedRangeValidation = 'no';
            }
        }

        $this->_table->field('validation_rules_time', ['options' => $this->rangeValidationOptions, 'onChangeReload' => true, 'after' => 'is_mandatory', 'default' => $selectedRangeValidation, 'attr' => ['required' => 'required', 'label' => $this->_table->getMessage('general.validationRules')]]);

        if (!empty($selectedRangeValidation)) {
            switch ($selectedRangeValidation) {
                case 'earlier':
                    $options = ['type' => 'time', 'after' => 'validation_rules_time', 'null' => false];
                    if (array_key_exists('start_time', $paramsArray)) {
                        $options['value'] = $paramsArray['start_time'];
                    }
                    $this->_table->field('start_time', $options);
                    break;
                case 'later':
                    $options = ['type' => 'time', 'after' => 'validation_rules_time', 'null' => false];
                    if (array_key_exists('end_time', $paramsArray)) {
                        $options['value'] = $paramsArray['end_time'];
                    }
                    $this->_table->field('end_time', $options);
                    break;
                case 'between':
                    $options = ['type' => 'time', 'after' => 'validation_rules_time', 'null' => false];
                    if (array_key_exists('start_time', $paramsArray)) {
                        $options['value'] = $paramsArray['start_time'];
                    }
                    $this->_table->field('start_time', $options);
                    $options = ['type' => 'time', 'after' => 'start_time', 'null' => false];
                    if (array_key_exists('end_time', $paramsArray)) {
                        $options['value'] = $paramsArray['end_time'];
                    }
                    $this->_table->field('end_time', $options);
                    break;
                case 'no':
                default:
                    // no code required
                    break;
            }
        }
    }

    public function onGetValidationRulesTime(Event $event, Entity $entity)
    {
        $decodedParams = $event->subject()->HtmlField->decodeEscapeHtmlEntity($entity->params);
        $paramsArray = (!empty($decodedParams))? json_decode($decodedParams, true): [];
        if (array_key_exists('start_time', $paramsArray) && array_key_exists('end_time', $paramsArray)) {
            return $this->rangeValidationOptions['between'].' '.$this->_table->formatTime(new Time($paramsArray['start_time'])).' - '.$this->_table->formatTime(new Time($paramsArray['end_time']));
        } else if (array_key_exists('start_time', $paramsArray)) {
            return $this->rangeValidationOptions['earlier'].' '.$this->_table->formatTime(new Time($paramsArray['start_time']));
        } else if (array_key_exists('end_time', $paramsArray)) {
            return $this->rangeValidationOptions['later'].' '.$this->_table->formatTime(new Time($paramsArray['end_time']));
        } else {
            return $this->rangeValidationOptions['no'];
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (array_key_exists('validation_rules_time', $data)) {
            if ($data['field_type'] == $this->fieldTypeCode) {
                $paramsArray = [];
                $start_time = (array_key_exists('start_time', $data))? $data['start_time']: null;
                $end_time = (array_key_exists('end_time', $data))? $data['end_time']: null;

                if (!empty($start_time)) {
                    $paramsArray['start_time'] = $start_time;
                }
                if (!empty($end_time)) {
                    $paramsArray['end_time'] = $end_time;
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
