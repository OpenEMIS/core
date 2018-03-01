<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class AppraisalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->hasMany('AppraisalTextAnswers', ['className' => 'StaffAppraisal.AppraisalTextAnswers', 'foreignKey' => 'institution_staff_appraisal_id']);
        $this->hasMany('AppraisalSliderAnswers', ['className' => 'StaffAppraisal.AppraisalSliderAnswers', 'foreignKey' => 'institution_staff_appraisal_id']);

        $this->addBehavior('OpenEmis.Section');

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );

        $this->toggle('remove', false);
        $this->toggle('edit', false);
        $this->toggle('add', false);
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->Auth->user('id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('appraisal_period_id', ['visible' => false]);
        $this->setFieldOrder(['appraisal_type_id', 'title', 'to', 'from', 'appraisal_form_id']);
        $this->setupTabElements();
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'AppraisalTextAnswers', 'AppraisalSliderAnswers',
            'AppraisalPeriods.AcademicPeriods', 'AppraisalPeriods.AppraisalForms',
            'AppraisalTypes'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End
        $this->field('staff_id', ['visible' => false]);
        $this->field('title');
        $this->field('appraisal_period.academic_period.name', ['attr' => ['label' => __('Academic Period')]]);
        $this->field('from');
        $this->field('to');
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('appraisal_period_id');
        $this->field('appraisal_period.appraisal_form.name', ['attr' => ['label' => __('Appraisal Form')]]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment');
        $this->printAppraisalCustomField($entity->appraisal_period_id);
    }

    private function printAppraisalCustomField($appraisalPeriodId)
    {
        if ($appraisalPeriodId) {
            $appraisalCriterias = $this->AppraisalPeriods->get($appraisalPeriodId, ['contain' => ['AppraisalForms.AppraisalCriterias.AppraisalSliders', 'AppraisalForms.AppraisalCriterias.FieldTypes']])->appraisal_form->appraisal_criterias;
            $section = null;
            $sectionCount = 0;
            $criteriaCounter = new ArrayObject();
            foreach ($appraisalCriterias as $key => $criteria) {
                $details = new ArrayObject([
                    'appraisal_form_id' => $criteria->_joinData->appraisal_form_id,
                    'appraisal_criteria_id' => $criteria->_joinData->appraisal_criteria_id,
                    'section' => $criteria->_joinData->section,
                    'field_type' => $criteria->code,
                    'criteria_name' => $criteria->name
                ]);
                if ($section != $details['section']) {
                    $section = $details['section'];
                    $this->field('section' . $sectionCount++, ['type' => 'section', 'title' => $details['section']]);
                }
                $this->appraisalCustomFieldExtra($details, $criteria, $criteriaCounter);
            }
        }
    }

    private function appraisalCustomFieldExtra(ArrayObject $details, Entity $criteria, ArrayObject $criteriaCounter)
    {
        $fieldTypeCode = $criteria['field_type']['code'];
        if (!$criteriaCounter->offsetExists($fieldTypeCode)) {
            $criteriaCounter[$fieldTypeCode] = 0;
        }
        switch ($fieldTypeCode) {
            case 'SLIDER':
                $details['key'] = 'appraisal_slider_answers';
                $details[$fieldTypeCode] = $criteria->appraisal_slider->toArray();
                $min = $criteria->appraisal_slider->min;
                $max = $criteria->appraisal_slider->max;
                $step = $criteria->appraisal_slider->step;
                $this->field($details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.answer', ['type' => 'slider', 'max' => $max, 'min' => $min, 'step' => $step, 'attr' => ['label' => $details['criteria_name']]]);
                break;
            case 'TEXT':
                $details['key'] = 'appraisal_text_answers';
                $details[$fieldTypeCode] = null;
                $this->field($details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.answer', ['type' => 'text', 'attr' => ['label' => $details['criteria_name']]]);
                break;
        }
        $this->field($details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.appraisal_form_id', ['type' => 'hidden', 'value' => $details['appraisal_form_id']]);
        $this->field($details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.appraisal_criteria_id', ['type' => 'hidden', 'value' => $details['appraisal_criteria_id']]);

        $criteriaCounter[$fieldTypeCode]++;
    }
}
