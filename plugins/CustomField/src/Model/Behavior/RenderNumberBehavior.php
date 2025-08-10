<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderNumberBehavior extends RenderBehavior {
	public function initialize(array $config): void {
        parent::initialize($config);
    }

    public function onGetCustomNumberElement(Event $event, $action, $entity, $attr, $options = [])
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
            if ($savedValue !== null) {
                $options['value'] = $savedValue;
            }

            // ---- read params (min/max or range.lower/upper)
            $min = null;
            $max = null;

            $params = [];
            if (!empty($attr['customField']->params)) {
                $decoded = json_decode($attr['customField']->params, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $params = $decoded;
                }
            }

            // direct min/max
            if (isset($params['min_value']) && is_numeric($params['min_value'])) {
                $min = +$params['min_value'];
            }
            if (isset($params['max_value']) && is_numeric($params['max_value'])) {
                $max = +$params['max_value'];
            }

            // range overrides (if present)
            if (isset($params['range']) && is_array($params['range'])) {
                if (isset($params['range']['lower']) && is_numeric($params['range']['lower'])) {
                    $min = +$params['range']['lower'];
                }
                if (isset($params['range']['upper']) && is_numeric($params['range']['upper'])) {
                    $max = +$params['range']['upper'];
                }
            }

            // sanity: ensure min <= max if both exist
            if ($min !== null && $max !== null && $min > $max) {
                // swap to avoid invalid HTML attributes
                [$min, $max] = [$max, $min];
            }

            if ($min !== null) $options['min'] = (string)$min;
            if ($max !== null) $options['max'] = (string)$max;

            // (optional) respect a step param if you ever add it, e.g. {"step":"0.5"}
            if (isset($params['step']) && is_numeric($params['step'])) {
                $options['step'] = (string)+$params['step'];
            }
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

    public function processNumberValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'number_value';
        $this->processValues($entity, $data, $settings);
    }
}
