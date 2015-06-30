<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class CheckboxBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
		if (isset($config['setup']) && $config['setup'] == true) {
			$this->_table->ControllerAction->addField('options', [
	            'type' => 'element',
	            'order' => 5,
	            'element' => 'CustomField.CustomFields/checkbox',
	            'visible' => true
	        ]);
		}
    }

    public function addEditOnAddCheckboxOption(Event $event, Entity $entity, array $data, array $options) {
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

    public function onGetCustomCheckboxElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        if ($action == 'index' || $action == 'view') {
            //$value = $data->$attr['field'];
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'multicheckbox';

            $checkboxOptions = [];
    		foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
				$checkboxOptions[$obj->id] = $obj->name;
			}
			$options['options'] = $checkboxOptions;

			$fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
            $value = $form->input($fieldPrefix.".text_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
			if (!is_null($attr['id'])) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            }
        }

        return $value;
    }
}
