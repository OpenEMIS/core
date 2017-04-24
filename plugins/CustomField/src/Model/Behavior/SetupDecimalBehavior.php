<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

use CustomField\Model\Behavior\SetupBehavior;

class SetupDecimalBehavior extends SetupBehavior
{
	private $validationOptions = [];

	public function initialize(array $config)
	{
        parent::initialize($config);

  //       $this->ruleOptions = [
  //       	'length' => __('Length Validation'),
  //       	'input_mask' => __('Custom Validation')
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

		$tooltipMessage = [
			'length' => __('Maximum digits of the field') . __(' (1 to 20)'),
			'precision' => __('Maximum digits after decimal') . __(' (0 to 6)'),
		];

		$model->ControllerAction->field('length', [
			'type' => 'integer',
			'after' => 'is_unique',
			'attr' => [
				'min' => 1,
				'max' => 20,
				'label' => [
					'text' => __('Maximum Length') . ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="' . $tooltipMessage['length'] . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false, //disable the htmlentities (on LabelWidget) so can show html on label.
                    'class' => 'tooltip-desc' //css class for label
				]
			]
		]);

		$model->ControllerAction->field('precision', [
			'type' => 'integer',
			'after' => 'length',
			'attr' => [
				'min' => 0,
				'max' => 6,
				'label' => [
					'text' => __('Precision') . ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="' . $tooltipMessage['precision'] . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false, //disable the htmlentities (on LabelWidget) so can show html on label.
                    'class' => 'tooltip-desc' //css class for label
				]
			]
		]);
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
