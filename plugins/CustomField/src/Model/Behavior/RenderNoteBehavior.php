<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use CustomField\Model\Behavior\RenderBehavior;

class RenderNoteBehavior extends RenderBehavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function onGetCustomNoteElement(EventInterface $event, $action, $entity, $attr, $options = [])
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
            //POCOR-9349 STARTS
            if (isset($fieldValues[$fieldId]['textarea_value'])) {
                $savedValue = $fieldValues[$fieldId]['textarea_value'];
            }//POCOR-9349 ENDS
        }
        if ($customField->has('description')) {
            $displayValue = $customField->description;
        }
        // End

        if ($action == 'view') {
            //POCOR-9349 STARTS
            if (!empty($displayValue)) {
                $value = nl2br($displayValue);
            } elseif (!empty($savedValue)) {
                $value = nl2br($savedValue);
            } else {
                $value = '';
            }//POCOR-9349 ENDS
        } else if ($action == 'edit') {
            $form = $event->getSubject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'textarea';
            $options['disabled'] = 'disabled';
            $options['style'] = 'height: 200px'; //POCOR-8956
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
