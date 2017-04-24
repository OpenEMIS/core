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

        // $this->ruleOptions = [
        //  'length' => __('Length Validation'),
        //  'input_mask' => __('Custom Validation')
        // ];
    }

    public function addBeforeAction(Event $event)
    {
        $model = $this->_table;
        $fieldTypes = $model->getFieldTypes();

        $selectedFieldType = isset($model->request->data[$model->alias()]['field_type']) ? $model->request->data[$model->alias()]['field_type'] : key($fieldTypes);

        if ($selectedFieldType == $this->fieldTypeCode) {
            $this->buildDecimalValidator();
        }
    }

    public function editAfterQuery(Event $event, Entity $entity)
    {
        if ($entity->field_type == $this->fieldTypeCode) {
            $this->buildDecimalValidator();
        }
    }

    private function buildDecimalValidator()
    {
        $validator = $this->_table->validator();
        $validator
            ->add('length', [
                'ruleRange' => [
                    'rule' => ['range', 1, 20]
                ]
            ])
            ->add('precision', [
                'ruleRange' => [
                    'rule' => ['range', 0, 6]
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

                    if (array_key_exists('length', $params) && array_key_exists('precision', $params)) {
                        $entity->length = $params['length'];
                        $entity->precision = $params['precision'];
                    }
                }
            }
        }

        $model->ControllerAction->field('length');
        $model->ControllerAction->field('precision');
    }

    public function onUpdateFieldLength(Event $event, array $attr, $action, Request $request)
    {
        $tooltipMessage = __('Maximum digits of the field') . __(' (1 to 20)');

        if ($action == 'add') {
            $attr['type'] = 'integer';
            $attr['attr']['min'] = 1;
            $attr['attr']['max'] = 20;
            $attr['attr']['label']['text'] = __('Maximum Length') .
                ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="' .
                $tooltipMessage .
                '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
            $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
            $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        } else if ($action == 'edit') {
            $attr['type'] = 'readOnly';
        }

        return $attr;
    }

    public function onUpdateFieldPrecision(Event $event, array $attr, $action, Request $request)
    {
        $tooltipMessage = __('Maximum digits after decimal') . __(' (0 to 6)');

        if ($action == 'add') {
            $attr['type'] = 'integer';
            $attr['attr']['min'] = 0;
            $attr['attr']['max'] = 6;
            $attr['attr']['label']['text'] = __('Decimal Place') .
                ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="' .
                $tooltipMessage .
                '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
            $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
            $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        } else if ($action == 'edit') {
            $attr['type'] = 'readOnly';
        }

        return $attr;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['field_type']) && $data['field_type'] == $this->fieldTypeCode) {
            $length = array_key_exists('length', $data) && strlen($data['length']) > 0 ? $data['length'] : null;
            $precision = array_key_exists('precision', $data) && strlen($data['precision']) > 0 ? $data['precision'] : null;

            $params = [
                'length' => $length,
                'precision' => $precision
            ];

            $data['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
        }
    }
}
