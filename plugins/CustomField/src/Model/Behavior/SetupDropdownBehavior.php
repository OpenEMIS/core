<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\SetupBehavior;

class SetupDropdownBehavior extends SetupBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

    public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.viewEdit.beforeQuery'] = 'viewEditBeforeQuery';
		$events['ControllerAction.Model.addEdit.onChangeType'] = 'addEditOnChangeType';
		$events['ControllerAction.Model.addEdit.onAddOption'] = 'addEditOnAddOption';
		$events['ControllerAction.Model.addEdit.beforePatch'] = 'addEditBeforePatch';
		$events['Setup.'.'set'.$this->fieldType.'Elements'] = 'onSet'.$this->fieldType.'Elements';
		return $events;
	}

	public function onSetDropdownElements(Event $event, Entity $entity) {
		$fieldType = strtolower($this->fieldTypeCode);
		$this->_table->ControllerAction->addField('options', [
            'type' => 'element',
            'order' => 0,
            'element' => 'CustomField.Setup/' . $fieldType,
            'visible' => true,
            'valueClass' => 'table-full-width'
        ]);
        $this->sortFieldOrder('options');
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$queryCopy = clone($query);
		$entity = $queryCopy->first();
		if ($entity->field_type == $this->fieldTypeCode) {
			$query->contain(['CustomFieldOptions']);
		}
	}

	public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$model = $this->_table;
		$request = $model->request;
		if ($request->is(['post', 'put'])) {
			if (array_key_exists($model->alias(), $request->data)) {
				if (array_key_exists('custom_field_options', $request->data[$model->alias()])) {
					unset($data[$model->alias()]['custom_field_options']);
				}
			}
		}
	}

    public function addEditOnAddOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$model = $this->_table;
    	if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
			$fieldOptions = [
				'name' => '',
				'visible' => 1
			];
			$data[$model->alias()]['custom_field_options'][] = $fieldOptions;

			//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
			$options['associated'] = [
				'CustomFieldOptions' => ['validate' => false]
			];
    	}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$model = $this->_table;
    	if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
			if (isset($data[$model->alias()]['is_default']) && !empty($data[$model->alias()]['custom_field_options'])) {
				$defaultKey = $data[$model->alias()]['is_default'];
				$data[$model->alias()]['custom_field_options'][$defaultKey]['is_default'] = 1;
			}
		}
	}
}
