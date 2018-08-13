<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class AppraisalBehavior extends Behavior 
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction'];
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->field('staff_id', ['visible' => false]);
        $model->field('file_name', ['visible' => false]);
        $model->field('file_content', ['visible' => false]);
        $model->field('comment', ['visible' => false]);
        $model->field('appraisal_period_id', ['visible' => false]);
        $model->setFieldOrder(['appraisal_type_id', 'appraisal_form_id', 'appraisal_period_from', 'appraisal_period_to', 'date_appraised']);
    }

    public function appraisalCustomFieldExtra(ArrayObject $details, Entity $formCritieria, ArrayObject $criteriaCounter, Entity $entity)
    {
        $model = $this->_table;
        $fieldTypeCode = $details['field_type'];
        if (!$criteriaCounter->offsetExists($fieldTypeCode)) {
            $criteriaCounter[$fieldTypeCode] = 0;
        }

        $key = [];
        $attr = [];
        $criteria = $formCritieria->appraisal_criteria;

        switch ($fieldTypeCode) {
            case 'SLIDER':
                $key = 'appraisal_slider_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'slider';
                $attr['max'] = $criteria->appraisal_slider->max;
                $attr['min'] = $criteria->appraisal_slider->min;
                $attr['step'] = $criteria->appraisal_slider->step;
                break;
            case 'TEXTAREA':
                $key = 'appraisal_text_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'text';
                break;
            case 'DROPDOWN':
                $key = 'appraisal_dropdown_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'select';
                $attr['options'] = Hash::combine($criteria->appraisal_dropdown_options, '{n}.id', '{n}.name');
                $attr['default'] = current(Hash::extract($criteria->appraisal_dropdown_options, '{n}[is_default=1].id'));
                break;
            case 'NUMBER':
                $key = 'appraisal_number_answers';
                $fieldKey = $key.'.'.$criteriaCounter[$fieldTypeCode];
                $attr['type'] = 'integer';

                if ($criteria->has('appraisal_number')) {
                    $model->field($fieldKey.'.validation_rule', ['type' => 'hidden', 'value' => $criteria->appraisal_number->validation_rule]);
                }
                break;
        }

        // build custom fields
        $attr['attr']['label'] = $details['criteria_name'];
        $attr['attr']['required'] = $details['is_mandatory'];

        // set each answer in entity
        if (!$entity->offsetExists($key)) {
            $entity->{$key} = [];
        }
        $entity->{$key}[$criteriaCounter[$fieldTypeCode]] = !empty($formCritieria->{$key}) ? current($formCritieria->{$key}) : [];

        $model->field($fieldKey.'.answer', $attr);
        $model->field($fieldKey.'.is_mandatory', ['type' => 'hidden', 'value' => $details['is_mandatory']]);
        $model->field($fieldKey.'.appraisal_form_id', ['type' => 'hidden', 'value' => $details['appraisal_form_id']]);
        $model->field($fieldKey.'.appraisal_criteria_id', ['type' => 'hidden', 'value' => $details['appraisal_criteria_id']]);

        $criteriaCounter[$fieldTypeCode]++;
    }
}
