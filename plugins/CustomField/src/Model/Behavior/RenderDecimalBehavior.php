<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Event\Event;

use CustomField\Model\Behavior\RenderBehavior;

class RenderDecimalBehavior extends RenderBehavior
{
	public function initialize(array $config)
    {
        parent::initialize($config);
    }

	public function onGetCustomDecimalElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $value = '';

        // for edit
        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        $savedId = null;
        $savedValue = null;

        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['decimal_value'])) {
                $savedValue = $fieldValues[$fieldId]['decimal_value'];
            }
        }
        // End

        if ($action == 'view') {
            if (!is_null($savedValue)) {
                $value = $savedValue;
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'number';
            if (!is_null($savedValue)) {
                $options['value'] = $savedValue;
            }

            // set the options
            if (array_key_exists('customField', $attr) && isset($attr['customField']['params'])) {
                $params = json_decode($attr['customField']['params'], true);

                // for set the field step precission 6 will be 0.000001 step (6 digit after decimal point
                if (array_key_exists('precision', $params) && ($params['precision'] > 0)) {
                    $options['min'] = 0;
                    $step = '0.';

                    for ($i=1; $i <= $params['precision']; $i++) {
                        // last precision will be 1
                        if ($i == ($params['precision'])) {
                            $step = $step . '1';
                        } else {
                            $step = $step . '0';
                        }
                    }

                    $options['step'] = $step;
                }
            }

            $value .= $form->input($fieldPrefix.".decimal_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix.".decimal_value";
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

    public function processDecimalValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $settings['valueKey'] = 'decimal_value';
        $this->processValues($entity, $data, $settings);
    }
}
