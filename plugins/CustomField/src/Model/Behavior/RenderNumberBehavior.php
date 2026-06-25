<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use CustomField\Model\Behavior\RenderBehavior;

class RenderNumberBehavior extends RenderBehavior {
	public function initialize(array $config): void {
        parent::initialize($config);
    }

    public function onGetCustomNumberElement(EventInterface $event, $action, $entity, $attr, $options = [])
    {
        $value = '';

        // for edit
        $fieldId = $attr['customField']->id;
        // POCOR-9332 start
        $fieldValues = $attr['customFieldValues'] ?? [];
        $savedId = $fieldValues[$fieldId]['id'] ?? null;
        $savedValue = $fieldValues[$fieldId]['number_value'] ?? null;

        if ($action === 'view') {
            if ($savedValue !== null) {
                $value = $savedValue;
            }
        } elseif ($action == 'edit') {
            $form = $event->getSubject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'number';
            // if ($savedValue !== null) {
            //     $options['value'] = $savedValue;
            // }

            if (!is_null($savedValue)) {
                $options['value'] = $savedValue;
            }else { // POCOR-9066
                $options['value'] = '';
            }

            $params = [];
            if (!empty($attr['customField']->params)) {
                $decoded = json_decode($attr['customField']->params, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $params = $decoded;
                }
            }
            $min = $this->getMinFromParams($params);
            $max = $this->getMaxFromParams($params);
            $step = $this->getStepFromParams($params);

// sanity: ensure min <= max if both exist
            if ($min !== null && $max !== null && $min > $max) {
                [$min, $max] = [$max, $min];
            }

            if ($min !== null) $options['min'] = (string)$min;
            if ($max !== null) $options['max'] = (string)$max;
            if ($step !== null) $options['step'] = (string)$step;

            // POCOR-9332 end
            $value .= $form->input($fieldPrefix . ".number_value", $options);
            $value .= $form->hidden($fieldPrefix . "." . $attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix . ".number_value";
            $unlockFields[] = $fieldPrefix . "." . $attr['attr']['fieldKey'];

            if ($savedId !== null) {
                $value .= $form->hidden($fieldPrefix . ".id", ['value' => $savedId]);
                $unlockFields[] = $fieldPrefix . ".id";
            }

            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processNumberValues(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'number_value';
        $this->processValues($entity, $data, $settings);
    }
}
