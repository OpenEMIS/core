<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class DropdownBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
		if (isset($config['setup']) && $config['setup'] == true) {
			$this->_table->ControllerAction->addField('options', [
	            'type' => 'element',
	            'order' => 5,
	            'element' => 'CustomField.CustomFields/dropdown',
	            'visible' => true
	        ]);
        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		if (isset($data[$this->_table->alias()]['is_default']) && !empty($data[$this->_table->alias()]['custom_field_options'])) {
			$defaultKey = $data[$this->_table->alias()]['is_default'];
			$data[$this->_table->alias()]['custom_field_options'][$defaultKey]['is_default'] = 1;
		}
    	return compact('entity', 'data', 'options');
    }

    public function addEditOnAddDropdownOption(Event $event, Entity $entity, array $data, array $options) {
		$fieldOptions = [
			'name' => '',
			'visible' => 1
		];
		$data[$this->_table->alias()]['custom_field_options'][] = $fieldOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'CustomFieldOptions' => ['validate' => false]
		];

		return compact('entity', 'data', 'options');
	}

    public function onGetCustomDropdownElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        if ($action == 'index' || $action == 'view') {
            //$value = $data->$attr['field'];
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'select';
            $options['default'] = $attr['value'];

            $dropdownOptions = [];
    		foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
				$dropdownOptions[$obj->id] = $obj->name;
			}
			$options['options'] = $dropdownOptions;

 			$fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
            $value = $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".custom_field_id", ['value' => $attr['customField']->id]);
			if (!is_null($attr['id'])) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            }
        }

        return $value;
    }
}
