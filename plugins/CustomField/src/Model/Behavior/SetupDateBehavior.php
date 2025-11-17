<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupDateBehavior extends SetupBehavior
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

        $this->_table->addBehavior('ControllerAction.DatePicker', ['start_date', 'end_date']);
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

        if (isset($fieldType) && $fieldType == 'DATE') {
            $this->addDateValidation();
        }
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        if ($this->_table->request->is('POST')) {
            $fieldType = (array_key_exists('field_type', $this->_table->request->getData()[$this->_table->getAlias()]))? $this->_table->request->getData($this->_table->getAlias())['field_type']: null;
            if ($fieldType == 'DATE') {
                $this->addDateValidation();
            }
        }
    }

    private function addDateValidation()
    {
        $validator = $this->_table->getValidator();
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

    public function onSetDateElements(EventInterface $event, Entity $entity)
    {
        $fieldType = strtolower($this->fieldTypeCode);

        $paramsArray = [];
        if ($this->_table->action == 'edit') {
            if (empty($this->_table->request->getData())) {
                $paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
            }
        }

        if (!empty($this->_table->request->getData())) {
            $selectedRangeValidation = (array_key_exists($this->_table->getAlias(), $this->_table->request->getData()) && array_key_exists('validation_rules_date', $this->_table->request->getData($this->_table->getAlias())))? $this->_table->request->getData($this->_table->getAlias())['validation_rules_date']: null;
        } else {
            if (isset($paramsArray['start_date']) && isset($paramsArray['end_date'])) {
                $selectedRangeValidation = 'between';
            } else if (isset($paramsArray['start_date'])) {
                $selectedRangeValidation = 'earlier';
            } else if (isset($paramsArray['end_date'])) {
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
                    if (isset($paramsArray['start_date'])) {
                        $options['value'] = $paramsArray['start_date'];
                    }
                    $this->_table->field('start_date', $options);
                    break;
                case 'later':
                    $options = ['type' => 'date', 'after' => 'validation_rules_date', 'null' => false];
                    if (isset($paramsArray['end_date'])) {
                        $options['value'] = $paramsArray['end_date'];
                    }
                    $this->_table->field('end_date', $options);
                    break;
                case 'between':
                    $options = ['type' => 'date', 'after' => 'validation_rules_date', 'null' => false];
                    if (isset($paramsArray['start_date'])) {
                        $options['value'] = $paramsArray['start_date'];
                    }
                    $this->_table->field('start_date', $options);
                    $options = ['type' => 'date', 'after' => 'start_date', 'null' => false];
                    if (isset($paramsArray['end_date'])) {
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

    public function onGetValidationRulesDate(EventInterface $event, Entity $entity)
    {
        $decodedParams = $event->getSubject()->HtmlField->decodeEscapeHtmlEntity($entity->params);
        $paramsArray = (!empty($decodedParams))? json_decode($decodedParams, true): [];
        if (isset($paramsArray['start_date']) && isset($paramsArray['end_date'])) {
            return $this->rangeValidationOptions['between'].' '.$paramsArray['start_date'].' - '.$paramsArray['end_date'];
        } else if (isset($paramsArray['start_date'])) {
            return $this->rangeValidationOptions['earlier'].' '.$paramsArray['start_date'];
        } else if (isset($paramsArray['end_date'])) {
            return $this->rangeValidationOptions['later'].' '.$paramsArray['end_date'];
        } else {
            return $this->rangeValidationOptions['no'];
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if ($data->offsetExists('validation_rules_date')) {
            if ($data['field_type'] == $this->fieldTypeCode) {
                $paramsArray = [];
                // $start_date = (isset($data['start_date']))? $data['start_date']: null;
                // $end_date = (isset($data['end_date']))? $data['end_date']: null;
                $start_date = $data->offsetExists('start_date') ? $data->offsetGet('start_date') : null;
                $end_date = $data->offsetExists('end_date') ? $data->offsetGet('end_date') : null;

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
