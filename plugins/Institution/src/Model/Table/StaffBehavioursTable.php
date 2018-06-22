<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

use Page\Traits\EncodingTrait;

class StaffBehavioursTable extends ControllerActionTable
{
    use OptionsTrait;
    use EncodingTrait;
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffBehaviourCategories', ['className' => 'Staff.StaffBehaviourCategories']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);
        $this->hasMany('StaffBehaviourAttachments', [
            'className' => 'Institutions.StaffBehaviourAttachments', 
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Institution.Case');

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['InstitutionCase.onSetCustomCaseTitle'] = 'onSetCustomCaseTitle';
        $events['InstitutionCase.onSetCustomCaseSummary'] = 'onSetCustomCaseSummary';
        $events['InstitutionCase.onIncludeCustomExcelFields'] = 'onIncludeCustomExcelFields';
        $events['InstitutionCase.onBuildCustomQuery'] = 'onBuildCustomQuery';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_of_behaviour', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'provider' => 'table'
                ]
            ])
        ;
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $event->subject()->Html->link($entity->staff->openemis_no, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StaffUser',
                'view',
                $this->paramsEncode(['id' => $entity->staff->id])
            ]);
        } else {
            return $entity->staff->openemis_no;
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('description', ['visible' => false]);
        $this->field('action', ['visible' => false]);
        $this->field('time_of_behaviour', ['visible' => false]);

        $this->fields['staff_id']['sort'] = ['field' => 'Staff.first_name']; // POCOR-2547 adding sort

        $this->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'staff_behaviour_category_id', 'behaviour_classification_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => [], 'order' => 1];

        // Setup period options
        // $periodOptions = ['0' => __('All Periods')];
        $periodOptions = $this->AcademicPeriods->getYearList();
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }

        $Staff = TableRegistry::get('Institution.Staff');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
            'callable' => function ($id) use ($Staff, $institutionId) {
                return $Staff
                    ->findByInstitutionId($institutionId)
                    ->find('academicPeriod', ['academic_period_id' => $id])
                    ->count();
            }
        ]);

        if (!empty($selectedPeriod)) {
            $query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
        }

        $this->controller->set(compact('periodOptions'));

        // will need to check for search by name: AdvancedNameSearchBehavior

        // POCOR-2547 Adding sortWhiteList to $extra
        $sortList = ['Staff.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // POCOR-2547 sort list of staff and student by name
        if (!isset($this->request->query['sort'])) {
            $query->order([$this->Staff->aliasField('first_name'), $this->Staff->aliasField('last_name')]);
        }
        // end POCOR-2547
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('openemis_no', ['entity' => $entity]);

        $this->setFieldOrder(['academic_period_id', 'openemis_no', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour']);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('date_of_behaviour', ['entity' => $entity]);
        $this->field('staff_behaviour_category_id', ['type' => 'select']);
        $this->field('behaviour_classification_id', ['type' => 'select']);
        $this->setFieldOrder(['academic_period_id', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour']);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AcademicPeriods', 'Staff', 'StaffBehaviourCategories', 'BehaviourClassifications']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('date_of_behaviour', ['entity' => $entity]);
        $this->field('staff_behaviour_category_id', ['entity' => $entity]);
        $this->field('behaviour_classification_id', ['entity' => $entity]);

        $this->setFieldOrder(['academic_period_id', 'openemis_no', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour']);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $entity->showDeletedValueAs = $entity->description;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $entity = $attr['entity'];

            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
            if ($entity->has('academic_period_id')) {
                $selectedPeriod = $entity->academic_period_id;
            } else {
                if (is_null($request->query('academic_period_id'))) {
                    $selectedPeriod = $this->AcademicPeriods->getCurrent();
                } else {
                    $selectedPeriod = $request->query('academic_period_id');
                }
                $entity->academic_period_id = $selectedPeriod;
            }

            $attr['select'] = false;
            $attr['options'] = $academicPeriodOptions;
            $attr['value'] = $selectedPeriod;
            $attr['attr']['value'] = $selectedPeriod;
            $attr['onChangeReload'] = 'changePeriod';
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }

        return $attr;
    }

    public function onUpdateFieldDateOfBehaviour(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $selectedPeriod = $entity->academic_period_id;
            $academicPeriod = $this->AcademicPeriods->get($selectedPeriod);

            $startDate = $academicPeriod->start_date;
            $endDate = $academicPeriod->end_date;

            if ($action == 'add') {
                $todayDate = Date::now();

                if (!empty($request->data[$this->alias()]['date_of_behaviour'])) {
                    $inputDate = Date::createfromformat('d-m-Y', $request->data[$this->alias()]['date_of_behaviour']); //string to date object

                    // if today date is not within selected academic period, default date will be start of the year
                    if ($inputDate < $startDate || $inputDate > $endDate) {
                        $attr['value'] = $startDate->format('d-m-Y');

                        // if today date is within selected academic period, default date will be current date
                        if ($todayDate >= $startDate && $todayDate <= $endDate) {
                            $attr['value'] = $todayDate->format('d-m-Y');
                        }
                    }
                } else {
                    if ($todayDate <= $startDate || $todayDate >= $endDate) {
                        $attr['value'] = $startDate->format('d-m-Y');
                    } else {
                        $attr['value'] = $todayDate->format('d-m-Y');
                    }
                }
            }
      
            $attr['date_options'] = ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')];
            $attr['date_options']['todayBtn'] = false;
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $staffOptions = [];

            $entity = $attr['entity'];
            $selectedPeriod = $entity->academic_period_id;

            if (!empty($selectedPeriod)) {
                $institutionId = $this->Session->read('Institution.Institutions.id');
                $Staff = TableRegistry::get('Institution.Staff');
                $staffOptions = $Staff
                ->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name'])
                ->matching('Users')
                ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                ->where([$Staff->aliasField('institution_id') => $institutionId])
                ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
                ->toArray();
            }

            $attr['options'] = $staffOptions;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->staff->name_with_id;
        }
        return $attr;
    }

    public function onUpdateFieldStaffBehaviourCategoryId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_behaviour_category_id;
            $attr['attr']['value'] = $entity->staff_behaviour_category->name;
        }

        return $attr;
    }

    public function onUpdateFieldBehaviourClassificationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->behaviour_classification_id;
            $attr['attr']['value'] = $entity->behaviour_classification->name;
        }

        return $attr;
    }

    public function viewBeforeAction(Event $event)
    {
        $tabElements = $this->getStaffBehaviourTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function onSetCustomCaseTitle(Event $event, Entity $entity)
    {
        $recordEntity = $this->get($entity->id, [
            'contain' => ['Staff', 'StaffBehaviourCategories', 'Institutions', 'BehaviourClassifications']
        ]);
        $title = '';
        $title .= $recordEntity->staff->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->staff_behaviour_category->name;

        return [$title, true];
    }

    public function onSetCustomCaseSummary(Event $event, $id = null)
    {
        $recordEntity = $this->get($id, [
            'contain' => ['Staff', 'StaffBehaviourCategories', 'Institutions', 'BehaviourClassifications']
        ]);
        $summary = '';
        $summary .= $recordEntity->staff->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->staff_behaviour_category->name;

        return $summary;
    }

    public function onIncludeCustomExcelFields(Event $event, $newFields)
    {
        $newFields[] = [
            'key' => 'Staff.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Staff.full_name',
            'field' => 'full_name',
            'type' => 'string',
            'label' => ''
        ];

        return $newFields;
    }

    public function onBuildCustomQuery(Event $event, $query)
    {
        $query
            ->select([
                'openemis_no' => 'Staff.openemis_no',
                'first_name' => 'Staff.first_name',
                'middle_name' =>'Staff.middle_name',
                'third_name' =>'Staff.third_name',
                'last_name' =>'Staff.last_name',
                'preferred_name' =>'Staff.preferred_name'
         
             ])
            ->innerJoinWith('InstitutionCaseRecords.StaffBehaviours.Staff');
        
        return $query;
    }

    public function getStaffBehaviourTabElements($options = [])
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $paramPass = $this->request->param('pass');
        $ids = isset($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : [];
        $studentBehaviourId = $ids['id'];
        $queryString = $this->encode(['staff_behaviour_id' => $studentBehaviourId]);
       
        $tabElements = [
            'StaffBehaviours' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviours', 'view', $paramPass[1]],
                'text' => __('Overview')
            ],
            'StaffBehaviourAttachments' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'StaffBehaviourAttachments', 'action' => 'index', 'querystring' => $queryString, 'institutionId' => $encodedInstitutionId],
                'text' => __('Attachments')
            ]
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }
}
