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
        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        $savedId = null;

        $surveyQuestions = TableRegistry::get('Survey.SurveyQuestions');
        $surveyQuestionsDesc = $surveyQuestions
            ->find()
            ->select('description')
            ->where([$surveyQuestions->aliasField('id') => $fieldId])
            ->toArray();
            //pr($surveyQuestionsDesc);
        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
        }
        // End

        if ($action == 'view') {
             $value = $surveyQuestionsDesc[0]['description'];
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'textarea';
            $options['disabled'] = 'disabled';
            $options['value'] = $surveyQuestionsDesc[0]['description'];
           
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

    public function processNoteValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $settings['valueKey'] = 'textarea_value';
        $this->processValues($entity, $data, $settings);
    }
}
