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

class InstitutionStaffAppraisalsTable extends ControllerActionTable
{
    private $periodList = [];

    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);

        // for file upload
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
        $this->addBehavior('OpenEmis.Section');
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->hasMany('AppraisalTextAnswers', ['className' => 'StaffAppraisal.AppraisalTextAnswers', 'foreignKey' => 'institution_staff_appraisal_id']);
        $this->hasMany('AppraisalSliderAnswers', ['className' => 'StaffAppraisal.AppraisalTextAnswers', 'foreignKey' => 'institution_staff_appraisal_id']);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $userId = $this->request->query('user_id');
        $staff = $this->Users->get($userId);
        $this->staff = $staff;
        $this->controller->set('contentHeader', $staff->name. ' - ' .__('Appraisals'));
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

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('title');
        $this->field('academic_period_id', ['type' => 'select', 'attr' => ['required' => true]]);
        $this->field('to');
        $this->field('from');
        $this->field('staff_id', ['type' => 'hidden', 'value' => $this->staff->id]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')], 'type' => 'select']);
        $this->field('appraisal_period_id', ['type' => 'select', 'options' => $this->periodList]);
        $this->field('appraisal_form_id', ['type' => 'disabled']);

        $this->field('file_content');
        $this->field('comment');

        $appraisalPeriodId = $this->request->data($this->aliasField('appraisal_period_id'));
        if ($appraisalPeriodId) {
            $appraisalCriterias = $this->AppraisalPeriods->get($appraisalPeriodId, ['contain' => ['AppraisalForms.AppraisalCriterias.AppraisalSliders', 'AppraisalForms.AppraisalCriterias.FieldTypes']])->appraisal_form->appraisal_criterias;
            $appraisalFormsCriterias = [];
            $section = null;
            $sectionCount = 0;
            foreach ($appraisalCriterias as $key => $criteria) {
                $details = new ArrayObject([
                    'appraisal_forms_criteria_id' => $criteria->_joinData->id,
                    'section' => $criteria->_joinData->section,
                    'field_type' => $criteria->code,
                    'criteria_name' => $criteria->name
                ]);
                if ($section != $details['section']) {
                    $section = $details['section'];
                    $this->field('section' . $sectionCount++, ['type' => 'section', 'title' => $details['section']]);
                }
                $this->appraisalCustomFieldExtra($details, $criteria, $key);
                $appraisalFormsCriterias[] = $details->getArrayCopy();
            }
        }
    }

    private function appraisalCustomFieldExtra(ArrayObject $details, Entity $criteria, int $key)
    {
        $fieldTypeCode = $criteria['field_type']['code'];
        switch ($fieldTypeCode) {
            case 'SLIDER':
                $details['key'] = 'appraisal_slider_answers';
                $details[$fieldTypeCode] = $criteria->appraisal_slider->toArray();
                $this->field($details['key'].'.'.$key.'.answer', ['attr' => ['label' => $details['criteria_name']]]);

                break;
            case 'TEXT':
                $details['key'] = 'appraisal_text_answers';
                $details[$fieldTypeCode] = null;
                $this->field($details['key'].'.'.$key.'.answer', ['attr' => ['label' => $details['criteria_name']]]);
                break;
        }

        $this->field($details['key'].'.'.$key.'.appraisal_forms_criteria_id', ['type' => 'hidden', 'value' => $details['appraisal_forms_criteria_id']]);
    }

    private function getAppraisalPeriods($academicPeriodId, $appraisalTypeId) : array
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
                $this->periodList = $this->AppraisalPeriods->find()
                    ->innerJoinWith('AppraisalTypes')
                    ->contain(['AppraisalForms', 'AcademicPeriods'])
                    ->where([
                        'AppraisalTypes.id' => $appraisalTypeId,
                        'AcademicPeriods.id' => $academicPeriodId
                    ])
                    ->formatResults(function ($results) {
                        $list = [];
                        foreach ($results as $r) {
                            $list[$r->id] = $r->period_form_name;
                        }
                        return $list;
                    })
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
        $this->controller->set('selectedAction', $this->alias());
    }
}
