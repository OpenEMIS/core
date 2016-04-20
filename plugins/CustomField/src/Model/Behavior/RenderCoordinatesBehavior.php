<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

use Cake\View\Helper\IdGeneratorTrait;

class RenderCoordinatesBehavior extends RenderBehavior {
	use IdGeneratorTrait;

	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomCoordinatesElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);

        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        $savedId = null;
        $savedValue = null;
        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['text_value'])) {
                $savedValue = $fieldValues[$fieldId]['text_value'];
            }
        }

        if ($action == 'view') {
        
        } else if ($action == 'edit') {

            $form = $event->subject()->Form;
            $html = '';
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];
            $attr['fieldPrefix'] = $fieldPrefix;
            $attr['form'] = $form;

        }

        if (!is_null($savedValue)) {
            $values = json_decode($savedValue);
        } else {
            $values = null;
        }
        
        $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['action' => $action, 'values' => $values, 'id' => $savedId, 'attr' => $attr]);

        $event->stopPropagation();
        return $value;
    }

    public function processCoordinatesValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['customValue']['text_value'] = json_encode([
            'latitude' => $settings['customValue']['latitude'],
            'longitude' => $settings['customValue']['longitude']
        ]);
        $settings['valueKey'] = 'text_value';
        $this->processValues($entity, $data, $settings);
    }
}
