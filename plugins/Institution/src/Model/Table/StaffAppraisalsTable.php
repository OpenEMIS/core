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
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class StaffAppraisalsTable extends ControllerActionTable
{
    private $periodList = [];

    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->hasMany('AppraisalTextAnswers', ['className' => 'StaffAppraisal.AppraisalTextAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalSliderAnswers', ['className' => 'StaffAppraisal.AppraisalSliderAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        // for file upload
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Institution.StaffProfile'); // POCOR-4047 to get staff profile data

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->allowEmpty('file_content')
            ->add('from', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ]
            ])
            ->add('to', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'from', true],
                    'message' => __('To Date should not be earlier than From Date')
                ]
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action != 'download') {
            $userId = $this->request->query('user_id');
            $staff = $this->Users->get($userId);
            $this->staff = $staff;
            $this->controller->set('contentHeader', $staff->name. ' - ' .__('Appraisals'));
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('appraisal_period_id', ['visible' => false]);
        $this->setFieldOrder(['appraisal_type_id', 'title', 'from', 'to', 'appraisal_form_id']);
        $this->setupTabElements();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('staff_id') => $this->staff->id]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'AppraisalTextAnswers' => function ($q) {
                return $q
                    ->innerJoin(['AppraisalFormsCriterias' => 'appraisal_forms_criterias'], [
                        '`AppraisalFormsCriterias`.`appraisal_form_id` = `AppraisalTextAnswers`.`appraisal_form_id`',
                        '`AppraisalFormsCriterias`.`appraisal_criteria_id` = `AppraisalTextAnswers`.`appraisal_criteria_id`'
                    ])
                    ->order(['AppraisalFormsCriterias.order']);
            },
            'AppraisalSliderAnswers' => function ($q) {
                return $q
                    ->innerJoin(['AppraisalFormsCriterias' => 'appraisal_forms_criterias'], [
                        '`AppraisalFormsCriterias`.`appraisal_form_id` = `AppraisalSliderAnswers`.`appraisal_form_id`',
                        '`AppraisalFormsCriterias`.`appraisal_criteria_id` = `AppraisalSliderAnswers`.`appraisal_criteria_id`'
                    ])
                    ->order(['AppraisalFormsCriterias.order']);
            },
            'AppraisalPeriods.AcademicPeriods', 'AppraisalPeriods.AppraisalForms',
            'AppraisalTypes'
        ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_id', ['type' => 'hidden', 'value' => $entity->staff_id]);
        $this->field('title');
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->appraisal_period->academic_period_id, 'attr' => ['value' => $entity->appraisal_period->academic_period->name]]);
        $this->field('from');
        $this->field('to');
        $this->field('appraisal_type_id', ['type' => 'readonly', 'value' => $entity->appraisal_type_id, 'attr' => ['label' => __('Type'), 'value' => $entity->appraisal_type->name]]);
        $this->field('appraisal_period_id', ['type' => 'readonly', 'value' => $entity->appraisal_period_id, 'attr' => ['value' => $entity->appraisal_period->name]]);
        $this->field('appraisal_form_id', ['type' => 'disabled', 'attr' => ['value' => $entity->appraisal_period->appraisal_form->name]]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $this->field('comment');
        $this->printAppraisalCustomField($entity->appraisal_period_id);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['type' => 'hidden', 'value' => $this->staff->id]);
        $this->field('title');
        $this->field('academic_period_id', ['type' => 'select', 'attr' => ['required' => true]]);
        $this->field('from');
        $this->field('to');
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')], 'type' => 'select']);
        $this->field('appraisal_period_id', ['type' => 'select', 'options' => $this->periodList]);
        $this->field('appraisal_form_id', ['type' => 'disabled']);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $this->field('comment');
        $appraisalPeriodId = $this->request->data($this->aliasField('appraisal_period_id'));
        $this->printAppraisalCustomField($appraisalPeriodId);
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
        $this->field('academic_period_id', ['fieldName' => 'appraisal_period.academic_period.name']);
        $this->field('from');
        $this->field('to');
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('appraisal_period_id');
        $this->field('appraisal_form_id', ['fieldName' => 'appraisal_period.appraisal_form.name']);
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
                $this->field($details['key'].'_'.$criteriaCounter[$fieldTypeCode].'_answer', [
                    'type' => 'slider',
                    'fieldName' => $details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.answer',
                    'max' => $max,
                    'min' => $min,
                    'step' => $step,
                    'attr' => ['label' => $details['criteria_name']]
                ]);
                break;
            case 'TEXTAREA':
                $details['key'] = 'appraisal_text_answers';
                $details[$fieldTypeCode] = null;
                $this->field($details['key'].'_'.$criteriaCounter[$fieldTypeCode].'_answer', [
                    'type' => 'text',
                    'fieldName' => $details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.answer',
                    'attr' => ['label' => $details['criteria_name']]
                ]);
                break;
        }
        $this->field($details['key'].'_'.$criteriaCounter[$fieldTypeCode].'_appraisal_form_id', [
            'type' => 'hidden',
            'value' => $details['appraisal_form_id'],
            'fieldName' => $details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.appraisal_form_id'
        ]);
        $this->field($details['key'].'_'.$criteriaCounter[$fieldTypeCode].'_appraisal_criteria_id', [
            'type' => 'hidden',
            'value' => $details['appraisal_criteria_id'],
            'fieldName' => $details['key'].'.'.$criteriaCounter[$fieldTypeCode].'.appraisal_criteria_id'
        ]);

        $criteriaCounter[$fieldTypeCode]++;
    }

    private function getAppraisalPeriods($academicPeriodId, $appraisalTypeId)
    {
        return $this->AppraisalPeriods->find()
            ->innerJoinWith('AppraisalTypes')
            ->where(['AppraisalTypes.id' => $appraisalTypeId, 'AcademicPeriods.id' => $academicPeriodId])
            ->contain(['AppraisalForms', 'AcademicPeriods'])
            ->formatResults(function ($results) {
                $list = [];
                foreach ($results as $r) {
                    $list[$r->id] = $r->period_form_name;
                }
                return $list;
            })
            ->toArray();
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
            $attr['options'] = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getYearList();
            return $attr;
        }
    }

    public function onUpdateFieldAppraisalTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
            if ($request->data($this->aliasField('academic_period_id')) && $request->data($this->aliasField('appraisal_type_id'))) {
                $appraisalTypeId = $request->data($this->aliasField('appraisal_type_id'));
                $academicPeriodId = $request->data($this->aliasField('academic_period_id'));
                $this->periodList = $this->AppraisalPeriods->find('list')
                    ->innerJoinWith('AppraisalTypes')
                    ->where([
                        'AppraisalTypes.id' => $appraisalTypeId,
                        $this->AppraisalPeriods->aliasField('academic_period_id') => $academicPeriodId,
                        $this->AppraisalPeriods->aliasField('date_enabled').' <=' => new Date(),
                        $this->AppraisalPeriods->aliasField('date_disabled').' >=' => new Date()
                    ])
                    ->toArray();
            }
            return $attr;
        }
    }

    public function onUpdateFieldAppraisalPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
            if ($request->data($this->aliasField('appraisal_period_id'))) {
                $appraisalPeriodId = $request->data($this->aliasField('appraisal_period_id'));
                $appraisalPeriodEntity = $this->AppraisalPeriods->get($appraisalPeriodId, ['contain' => ['AcademicPeriods', 'AppraisalForms']]);
                $this->fields['appraisal_form_id']['attr']['value'] = $appraisalPeriodEntity->appraisal_form->code_name;
            }
            return $attr;
        }
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->request->query('user_id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StaffAppraisals');
    }
}
