<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use CustomField\Model\Behavior\RenderBehavior;

class RenderNoteBehavior extends RenderBehavior
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function onGetCustomNoteElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $value = '';

        // for edit
        $customField = $attr['customField'];
        $fieldId = $customField->id;
        $fieldValues = $attr['customFieldValues'];
        $savedId = null;
        $displayValue = null;

        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
        }
        if ($customField->has('description')) {
            $displayValue = $customField->description;
        }
        // End

        if ($action == 'view') {
            if (!is_null($displayValue)) {
                $value = nl2br($displayValue);
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'textarea';
            $options['disabled'] = 'disabled';
            if (!is_null($displayValue)) {
                $options['value'] = $displayValue;
            }
           
            $value .= $form->input($fieldPrefix.".textarea_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix.".textarea_value";
            $unlockFields[] = $fieldPrefix.".".$attr['attr']['fieldKey'];
            if (!is_null($savedId)) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
                $unlockFields[] = $fieldPrefix.".id";
            }
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }

        $event->stopPropagation();
        return $value;
    }
}
