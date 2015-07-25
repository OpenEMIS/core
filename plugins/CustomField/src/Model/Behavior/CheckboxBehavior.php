<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
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
	            'visible' => true,
	            'valueClass' => 'table-full-width'
	        ]);
		}
    }

    public function addEditOnAddCheckboxOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$fieldOptions = [
			'name' => '',
			'visible' => 1
		];
		$data[$this->_table->alias()]['custom_field_options'][] = $fieldOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'CustomFieldOptions' => ['validate' => false]
		];
	}

    public function onGetCustomCheckboxElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';
		$checkboxOptions = [];
		foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
			$checkboxOptions[$obj->id] = $obj->name;
		}

		if ($action == 'view') {
			if (!is_null($attr['value'])) {
				$answer = [];
				foreach ($attr['value'] as $key => $obj) {
					if ($obj['value'] != 0) {
						$answer[] = $checkboxOptions[$obj['value']];
					}
				}
				$value = implode(', ', $answer);
			}
		} else if ($action == 'edit') {
			$form = $event->subject()->Form;

	        $html = '';
	        $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
	        foreach ($checkboxOptions as $key => $value) {
				$html .= '<div class="input">';
					$option = ['label' => false, 'class' => 'icheck-input'];
					if (!is_null($attr['value'])) {
						foreach ($attr['value'] as $obj) {
							if ($obj['value'] == $key) {
								$option['checked'] = 1;
							}
						}
					}
					$html .= $form->checkbox("$fieldPrefix.number_value.$key", $option);
					$html .= '<label class="checkbox-label">'. $value .'</label>';
				$html .= '</div>';
			}
			$html .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
			if (!is_null($attr['id'])) {
                $html .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            }

			$attr['output'] = $html;
			$value = $event->subject()->renderElement('CustomField.checkbox', ['attr' => $attr]);
		}

        return $value;
    }
}
