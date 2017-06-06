<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

use CustomField\Model\Behavior\SetupBehavior;

class SetupDecimalBehavior extends SetupBehavior
{
    private $validationOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $fieldTypes = $model->getFieldTypes();

        $selectedFieldType = isset($model->request->data[$model->alias()]['field_type']) ? $model->request->data[$model->alias()]['field_type'] : key($fieldTypes);

        if ($selectedFieldType == $this->fieldTypeCode) {
            $this->buildDecimalValidator();
        }
    }

    public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->field_type == $this->fieldTypeCode) {
            $this->buildDecimalValidator();
        }
    }

    private function buildDecimalValidator()
    {
        $minLength = $this->inputLimits['decimal_value']['length']['min'];
        $maxLength = $this->inputLimits['decimal_value']['length']['max'];

        $minPrecision = $this->inputLimits['decimal_value']['precision']['min'];
        $maxPrecision = $this->inputLimits['decimal_value']['precision']['max'];

        $validator = $this->_table->validator();
        $validator
            ->notEmpty('decimal_length')
            ->add('decimal_length', [
                'ruleRange' => [
                    'rule' => ['range', $minLength, $maxLength]
                ]
            ])
            ->notEmpty('decimal_precision')
            ->add('decimal_precision', [
                'ruleRange' => [
                    'rule' => ['range', $minPrecision, $maxPrecision]
                ]
            ])
        ;
    }

    public function onSetDecimalElements(Event $event, Entity $entity)
    {
        $model = $this->_table;

        if ($model->request->is(['get'])) {
            if (isset($entity->id)) {
                // view / edit
                if ($entity->has('params') && !empty($entity->params)) {
                    $params = json_decode($entity->params, true);

                    if (array_key_exists('length', $params)) {
                        $entity->decimal_length = $params['length'];
                    }

                    if (array_key_exists('precision', $params)) {
                        $entity->decimal_precision = $params['precision'];
                    }
                }
            }
        }

        $model->field('decimal_length');
        $model->field('decimal_precision');
    }

    public function onUpdateFieldDecimalLength(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldDecimalPrecision(Event $event, array $attr, $action, Request $request)
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

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['field_type']) && $data['field_type'] == $this->fieldTypeCode) {
            $length = array_key_exists('decimal_length', $data) ? $data['decimal_length'] : null;
            $precision = array_key_exists('decimal_precision', $data) ? $data['decimal_precision'] : null;

            $params = [
                'length' => $length,
                'precision' => $precision
            ];

            $data['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
        }
    }
}
