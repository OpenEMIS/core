<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Utility\Security;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;

use Cake\Utility\Text;

class InstitutionTestCommitteesTable extends ControllerActionTable
{
    use HtmlTrait;

    private $studentOptions = [];
    private $availableStudent = [];

    public function initialize(array $config)
    {
        $this->table('institution_committees');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionCommitteeTypes', ['className' => 'Institutions.InstitutionCommitteeTypes']);
        $this->hasMany('InstitutionCommitteeAttachments', [
            'className' => 'Institutions.InstitutionCommitteeAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('InstitutionCommitteeMeeting', [
            'className' => 'Institutions.InstitutionCommitteeMeeting',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->behaviors()->get('ControllerAction')->config([
            'actions' => ['search' => false],
        ]);
    }
    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('meeting_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('end_time', 'ruleCompareTimeReverse', [
                'rule' => ['compareDateReverse', 'start_time', false]
            ]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_committee_type_id':
                return __('Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']  
            ]);
        }

        if (array_key_exists('selectedCommiteeTypeOption', $extra) && $extra['selectedCommiteeTypeOption'] != -1) {
            $query->where([
                $this->aliasField('institution_committee_type_id') => $extra['selectedCommiteeTypeOption']  
            ]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
        $committeeTypeOptions = $this->getCommitteeTypeOptions();
        
        

         if (isset($requestQuery) && array_key_exists('type', $requestQuery)) {
            $selectedTypeId = $requestQuery['type'];
        } else {
            $selectedTypeId = -1;
        }

        $extra['selectedCommiteeTypeOption'] = $selectedTypeId;
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);
        
        // $extra['selectedCommiteeTypeOptions'] = $InstitutionCommitteeTypesTypes
        // ->find()
        // ->select([$InstitutionCommitteeTypesTypes->aliasField('id'),$InstitutionCommitteeTypesTypes->aliasField('name')])
        // ->toArray();
  
        //echo '<pre>';print_r($extra['selectedCommiteeTypeOptions']);die;
        $extra['elements']['control'] = [
            'name' => 'Institution.CommitteeMeeting/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'committeeTypeOption'=> $committeeTypeOptions,            
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions'],
                'selectedCommiteeTypeOption'=> $extra['selectedCommiteeTypeOption']
            ],
            'order' => 3
        ];
    }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (isset($request->query) && array_key_exists('period', $request->query)) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } 

        return $selectedAcademicPeriod;
    }

    // Get Options
    public function getCommitteeTypeOptions()
    { 
        $committeeTypeOptions = $this->InstitutionCommitteeTypes
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => $this->InstitutionCommitteeTypes->aliasField('id'),
                'name' => $this->InstitutionCommitteeTypes->aliasField('name')
            ])
            ->toArray();

        if (!empty($committeeTypeOptions)) {
            $committeeTypeOptions = ['-1' => __('All Types')] + $committeeTypeOptions;
        }

        return $committeeTypeOptions;
    }
    
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('institution_committee_type_id', ['type' => 'select']);
        $this->field('meeting_section', [
            'type' => 'element',
            'element' => 'Institution.CommitteeMeeting/committee_meeting'
        ]);
        $this->setFieldOrder(['academic_period_id', 'institution_committee_type_id', 'name', 'chairperson', 'telephone','email','comment','meeting_section']);
    }

    // OnUpdate Events
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList();
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    // Change Events
    public function addOnAddTimeslot(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'meeting';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->alias())) {
            $data[$this->alias()][$fieldKey][] = [
                'meeting_section' => '',
            ];
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'InstitutionCommitteeMeeting'
            ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('name');
        $this->field('institution_committee_type_id');
        $this->field('meeting_section', [
            'type' => 'element',
            'element' => 'Institution.CommitteeMeeting/committee_meeting'
        ]);

        $this->setFieldOrder(['academic_period_id', 'institution_committee_type_id', 'name', 'chairperson', 'telephone','email','comment','meeting_section']);
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $this->setupTabElements($encodedInstitutionId, $id);
    }

    public function setupTabElements($encodedInstitutionId, $query)
    {
        //$page = $this->Page;
        $tabElements = [];
    
        $encodeCommitteeId = '007b00220069006e0073007400690074007500740069006f006e005f0063006f006d006d00690074007400650065005f006900640022003a0031007d';

        // $decodeCommitteeId = $page->decode($query);
        // $committeeId = $decodeCommitteeId['id'];
        // $encodeCommitteeId = $page->encode(['institution_committee_id' => $committeeId]);
        //print_r($this->alias());die;
        $tabElements = [
            'InstitutionCommittees' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'InstitutionCommittees', 'action' => 'view', $query],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'InstitutionCommitteeAttachments', 'action' => 'index', 'querystring' => $encodeCommitteeId],
                'text' => __('Attachments')
            ]
        ];
       $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction','InstitutionCommittees');
    }

    private function setupTabElements222($entity)
    {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

        $options = [
            // 'userRole' => 'Student',
            // 'action' => $this->action,
            // 'id' => $id,
            // 'userId' => $entity->id
        ];

        $tabElements = $this->controller->getUserTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }


     public function beforeMarshal22(Event $event, ArrayObject $data, ArrayObject $options)
    {
 echo '<pre>';print_r($data);
        // for adding timeslots end time validation as here will have all the informations needed to do the validations
        if (array_key_exists('submit', $data) && $data['submit'] == 'save') {
            $options['associated'] = [
                'Timeslots' => ['validate' => true]
            ];

            $timeslotList = [];
            if (array_key_exists('meeting', $data) && !empty($data['meeting'])) {
                $hasEmpty = false;
                $totalInterval = 0;
                foreach ($data['meeting'] as $i => $timeslot) {
                    if (!$hasEmpty) {
                        if (array_key_exists('meeting_date', $timeslot) && !empty($timeslot['meeting_date'])) {
                            $timeslotList['meeting_date'] = $timeslot['meeting_date'];
                            $timeslotList['start_time'] = $timeslot['start_time'];
                            $timeslotList['end_time'] = $timeslot['end_time'];
                            $timeslotList['comment'] = $timeslot['comment'];
                        } else {
                            $hasEmpty = true;
                        }
                    } 

                    if ($hasEmpty) {
                        $timeslotList['meeting_date'] = null;
                        $timeslotList['start_time'] = null;
                        $timeslotList['end_time'] = null;
                        $timeslotList['comment'] = null;
                    }
                }
            }
        echo '<pre>';print_r($timeslotList);die;
            $timeslotValidator = $this->Timeslots->validator();
            $timeslotValidator
                ->add('interval', 'checkEndTime', [
                    'rule' => function($value, $context) use ($shiftStartTime, $shiftEndTime, $timeslotList) {
                        $order = $context['data']['order'];
                        $totalInterval = $timeslotList[$order];
                        if (!is_null($totalInterval)) {
                            $intervalStartTime = clone $shiftStartTime;
                            $modifyString = '+' . $totalInterval . ' minutes';
                            $intervalEndTime = $intervalStartTime->modify($modifyString);
                            return $intervalEndTime <= $shiftEndTime;
                        } 
                        //return true;
                    },
                    'on' => 'create',
                    'message' => __('Value entered exceed the end time of the shift selected.')
                ])
                ->requirePresence('institution_schedule_interval_id', false);

        } else {
            // for non-save actions so the timeslot entity can be patched
            $options['associated'] = [
                'Timeslots' => ['validate' => false]
            ];
        }
    }


     

    
   
}
