<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupTimeBehavior extends SetupBehavior
{
    private $rangeValidationOptions;

    public function initialize(array $config): void
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

    public function editAfterQuery(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $fieldType = '';
        if (!empty($this->_table->request->getData())) {
            $fieldType = (array_key_exists('field_type', $this->_table->request->getData($this->_table->getAlias())))? $this->_table->request->getData($this->_table->getAlias())['field_type']: null;
        } else {
            if (!empty($entity)) {
                $fieldType = $entity->field_type;
            }
        }

        if (isset($fieldType) && $fieldType == 'TIME') {
            $this->addTimeValidation();
        }
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        if ($this->_table->request->is('POST')) {
            $fieldType = (array_key_exists('field_type', $this->_table->request->getData()[$this->_table->getAlias()]))? $this->_table->request->getData()[$this->_table->getAlias()]['field_type']: null;
            if ($fieldType == 'TIME') {
                $this->addTimeValidation();
            }
        }
    }

    private function addTimeValidation()
    {
        $validator = $this->_table->getValidator();
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

    public function onSetTimeElements(EventInterface $event, Entity $entity)
    {
        $fieldType = strtolower($this->fieldTypeCode);

        $paramsArray = [];
        if ($this->_table->action == 'edit') {
            if (empty($this->_table->request->getData())) {
                $paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
            }
        }

        if (!empty($this->_table->request->getData())) {
            $selectedRangeValidation = (array_key_exists($this->_table->getAlias(), $this->_table->request->getData()) && array_key_exists('validation_rules_time', $this->_table->request->getData($this->_table->getAlias())))? $this->_table->request->getData($this->_table->getAlias())['validation_rules_time']: null;
        } else {
            if (isset($paramsArray['start_time']) && isset($paramsArray['end_time'])) {
                $selectedRangeValidation = 'between';
            } else if (isset($paramsArray['start_time'])) {
                $selectedRangeValidation = 'earlier';
            } else if (isset($paramsArray['end_time'])) {
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
                    if (isset($paramsArray['start_time'])) {
                        $options['value'] = $paramsArray['start_time'];
                    }
                    $this->_table->field('start_time', $options);
                    break;
                case 'later':
                    $options = ['type' => 'time', 'after' => 'validation_rules_time', 'null' => false];
                    if (isset($paramsArray['end_time'])) {
                        $options['value'] = $paramsArray['end_time'];
                    }
                    $this->_table->field('end_time', $options);
                    break;
                case 'between':
                    $options = ['type' => 'time', 'after' => 'validation_rules_time', 'null' => false];
                    if (isset($paramsArray['start_time'])) {
                        $options['value'] = $paramsArray['start_time'];
                    }
                    $this->_table->field('start_time', $options);
                    $options = ['type' => 'time', 'after' => 'start_time', 'null' => false];
                    if (isset($paramsArray['end_time'])) {
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

    public function onGetValidationRulesTime(EventInterface $event, Entity $entity)
    {
        $decodedParams = $event->getSubject()->HtmlField->decodeEscapeHtmlEntity($entity->params);
        $paramsArray = (!empty($decodedParams))? json_decode($decodedParams, true): [];
        if (isset($paramsArray['start_time']) && isset($paramsArray['end_time'])) {
            return $this->rangeValidationOptions['between'].' '.$this->_table->formatTime(new Time($paramsArray['start_time'])).' - '.$this->_table->formatTime(new Time($paramsArray['end_time']));
        } else if (isset($paramsArray['start_time'])) {
            return $this->rangeValidationOptions['earlier'].' '.$this->_table->formatTime(new Time($paramsArray['start_time']));
        } else if (isset($paramsArray['end_time'])) {
            return $this->rangeValidationOptions['later'].' '.$this->_table->formatTime(new Time($paramsArray['end_time']));
        } else {
            return $this->rangeValidationOptions['no'];
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if ($data->offsetExists('validation_rules_time')) {
            if ($data['field_type'] == $this->fieldTypeCode) {
                $paramsArray = [];
                $start_time = $data->offsetExists('start_time') ? $data->offsetGet('start_time') : null;
                $end_time = $data->offsetExists('end_time') ? $data->offsetGet('end_time') : null;

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
