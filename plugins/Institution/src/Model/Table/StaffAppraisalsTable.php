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
use Cake\Utility\Hash;
use Cake\Datasource\ResultSetInterface;

use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;

class StaffAppraisalsTable extends ControllerActionTable
{
    private $periodList = [];
    private $staff;

    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->hasMany('AppraisalTextAnswers', [
            'className' => 'StaffAppraisal.AppraisalTextAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalSliderAnswers', [
            'className' => 'StaffAppraisal.AppraisalSliderAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalDropdownAnswers', [
            'className' => 'StaffAppraisal.AppraisalDropdownAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalNumberAnswers', [
            'className' => 'StaffAppraisal.AppraisalNumberAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        // for file upload
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Institution.StaffProfile'); // POCOR-4047 to get staff profile data
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

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
            ->add('appraisal_period_from', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ]
            ])
            ->add('appraisal_period_to', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'appraisal_period_from', true],
                    'message' => __('Appraisal Period To Date should not be earlier than Appraisal Period From Date')
                ]
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if (in_array($this->action, ['view', 'edit', 'delete'])) {
            $modelAlias = 'Staff Appraisals';
            $userType = 'StaffUser';
            $this->controller->changeUserHeader($this, $modelAlias, $userType);
        }

        if ($this->action != 'download') {
            if (!is_null($this->request->query('user_id'))) {
                $userId = $this->request->query('user_id');
            } else {
                $session = $this->request->session();
                if ($session->check('Staff.Staff.id')) {
                    $userId = $session->read('Staff.Staff.id');
                }
            }

            if (!is_null($userId)) {
                $staff = $this->Users->get($userId);
                $this->staff = $staff;
                $this->controller->set('contentHeader', $staff->name. ' - ' .__('Appraisals'));
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('appraisal_period_id', ['visible' => false]);
        $this->setFieldOrder(['appraisal_type_id', 'appraisal_form_id', 'appraisal_period_from', 'appraisal_period_to', 'date_appraised']);
        $this->setupTabElements();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('staff_id') => $this->staff->id]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'AppraisalPeriods.AcademicPeriods', 'AppraisalForms',
            'AppraisalTypes'
        ]);
    }

    public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_id', ['type' => 'hidden', 'value' => $entity->staff_id]);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->appraisal_period->academic_period_id, 'attr' => ['value' => $entity->appraisal_period->academic_period->name]]);
        $this->field('appraisal_period_from');
        $this->field('appraisal_period_to');
        $this->field('appraisal_type_id', ['type' => 'readonly', 'value' => $entity->appraisal_type_id, 'attr' => ['label' => __('Type'), 'value' => $entity->appraisal_type->name]]);
        $this->field('appraisal_period_id', ['type' => 'readonly', 'value' => $entity->appraisal_period_id, 'attr' => ['value' => $entity->appraisal_period->name]]);
        $this->field('appraisal_form_id', ['type' => 'readonly', 'value' => $entity->appraisal_form_id, 'attr' => ['value' => $entity->appraisal_form->name]]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $this->field('comment');
        $this->printAppraisalCustomField($entity->appraisal_form_id, $entity);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['type' => 'hidden', 'value' => $this->staff->id]);
        $this->field('academic_period_id', ['type' => 'select', 'attr' => ['required' => true]]);
        $this->field('appraisal_period_from');
        $this->field('appraisal_period_to');
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')], 'type' => 'select']);
        $this->field('appraisal_period_id', ['type' => 'select', 'options' => $this->periodList, 'onChangeReload' => true]);
        $this->field('appraisal_form_id', ['type' => 'readonly']);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $this->field('comment');

        $entity = $this->newEntity();
        $appraisalFormId = $this->request->data($this->aliasField('appraisal_form_id'));
        $this->printAppraisalCustomField($appraisalFormId, $entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFieldOrder();
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
        $this->field('academic_period_id', ['fieldName' => 'appraisal_period.academic_period.name']);
        $this->field('appraisal_period_from');
        $this->field('appraisal_period_to');
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('appraisal_period_id');
        $this->field('appraisal_form_id');
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment');
        $this->printAppraisalCustomField($entity->appraisal_form_id, $entity);
        $this->setupFieldOrder();
    }
 
    private function setupFieldOrder()
    {
        $this->setFieldOrder(['academic_period_id', 'appraisal_type_id', 'appraisal_period_id', 'appraisal_form_id', 'appraisal_period_from', 'appraisal_period_to', 'date_appraised', 'file_content', 'comment']);
    }

    private function printAppraisalCustomField($appraisalFormId, Entity $entity)
    {
        if ($appraisalFormId) {
            $section = null;
            $sectionCount = 0;
            $criteriaCounter = new ArrayObject();
            $staffAppraisalId = $entity->has('id') ? $entity->id : -1;

            // retrieve all form criterias containing results
            $AppraisalFormsCriterias = TableRegistry::get('StaffAppraisal.AppraisalFormsCriterias');
            $formsCriterias = $AppraisalFormsCriterias->find()
                ->contain([
                    'AppraisalCriterias' => [
                        'FieldTypes',
                        'AppraisalSliders',
                        'AppraisalNumbers',
                        'AppraisalDropdownOptions' => ['sort' => ['AppraisalDropdownOptions.order' => 'ASC']]
                    ],
                    'AppraisalTextAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalTextAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalSliderAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalSliderAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalDropdownAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalDropdownAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalNumberAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalNumberAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    }
                ])
                ->where([$AppraisalFormsCriterias->aliasField('appraisal_form_id') => $appraisalFormId])
                ->order($AppraisalFormsCriterias->aliasField('order'))
                ->toArray();

            foreach ($formsCriterias as $key => $formCritieria) {
                $details = new ArrayObject([
                    'appraisal_form_id' => $formCritieria->appraisal_form_id,
                    'appraisal_criteria_id' => $formCritieria->appraisal_criteria_id,
                    'section' => $formCritieria->section,
                    'field_type' => $formCritieria->appraisal_criteria->field_type->code,
                    'criteria_name' => $formCritieria->appraisal_criteria->name,
                    'is_mandatory' => $formCritieria->is_mandatory
                ]);
                if ($section != $details['section']) {
                    $section = $details['section'];
                    $this->field('section' . $sectionCount++, ['type' => 'section', 'title' => $details['section']]);
                }
                $this->appraisalCustomFieldExtra($details, $formCritieria, $criteriaCounter, $entity);
            }
        }
    }

    private function appraisalCustomFieldExtra(ArrayObject $details, Entity $formCritieria, ArrayObject $criteriaCounter, Entity $entity)
    {
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
                    $this->field($fieldKey.'.validation_rule', ['type' => 'hidden', 'value' => $criteria->appraisal_number->validation_rule]);
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

        $this->field($fieldKey.'.answer', $attr);
        $this->field($fieldKey.'.is_mandatory', ['type' => 'hidden', 'value' => $details['is_mandatory']]);
        $this->field($fieldKey.'.appraisal_form_id', ['type' => 'hidden', 'value' => $details['appraisal_form_id']]);
        $this->field($fieldKey.'.appraisal_criteria_id', ['type' => 'hidden', 'value' => $details['appraisal_criteria_id']]);

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

    public function onUpdateFieldAppraisalFormId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if ($request->data($this->aliasField('appraisal_period_id'))) {
                $appraisalPeriodId = $request->data($this->aliasField('appraisal_period_id'));
                $appraisalPeriodEntity = $this->AppraisalPeriods->get($appraisalPeriodId, ['contain' => ['AppraisalForms']]);
                $attr['value'] = $appraisalPeriodEntity->appraisal_form_id;
                $attr['attr']['value'] = $appraisalPeriodEntity->appraisal_form->code_name;
                $request->data[$this->alias()]['appraisal_form_id'] = $appraisalPeriodEntity->appraisal_form_id;
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

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = WorkflowSteps::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('staff_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
                $this->AppraisalTypes->aliasField('name'),
                $this->AppraisalForms->aliasField('name'),
                $this->AppraisalPeriods->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([
                $this->Users->alias(),
                $this->AppraisalTypes->alias(),
                $this->AppraisalForms->alias(),
                $this->AppraisalPeriods->alias(),
                $this->Institutions->alias(),
                $this->CreatedUser->alias()
            ])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus
                ]);
            })
            ->where([
                $this->aliasField('assignee_id') => $userId
            ])
            ->order([
                $this->aliasField('created') => 'DESC'
            ])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffAppraisals',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'user_id' => $row->staff_id,
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    // Name (Type) for OpenEMIS ID - Staff Name in Appraisal Period
                    $row['request_title'] = sprintf(__('%s (%s) for %s in %s'), $row->appraisal_form->name, $row->appraisal_type->name, $row->user->name_with_id, $row->appraisal_period->name);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
