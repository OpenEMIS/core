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
            'cascadeCallbacks' => false
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
        $this->field('institution_committee_type_id', ['type' => 'select','entity' => $entity]);
        $this->field('name', ['entity' => $entity]);
       // $this->field('comment', ['type' => 'textarea''entity' => $entity]);
        $this->field('meeting_section', [
            'type' => 'element',
            'element' => 'Institution.CommitteeMeeting/committee_meeting'
        ]);
        $this->setFieldOrder(['academic_period_id', 'institution_committee_type_id', 'name', 'chairperson', 'telephone','email','comment','meeting_section']);
    }

    // OnUpdate Events
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($attr['entity']->academic_period_id));
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList();
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            // $attr['onChangeReload'] = 'changeAcademicPeriod';
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $periodOptions[$attr['entity']->academic_period_id];
            $attr['value'] = $attr['entity']->academic_period_id;
        }
        return $attr;
    }
    // OnUpdate Events
    public function onUpdateFieldInstitutionCommitteeTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

        if ($action == 'edit') {

                $committeType = $this->InstitutionCommitteeTypes->get($attr['entity']->institution_committee_type_id);
               
                $attr['type'] = 'readonly';
                $attr['attr']['value'] =  $committeType->name;
                $attr['value'] = $attr['entity']->institution_committee_type_id;

            }
        }
        return $attr;
    }
     // OnUpdate Events
    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->name;
            }
        }
        return $attr;
    }
    // OnUpdate Events
    public function onUpdateFieldComment(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
        if ($action == 'add') {
            $attr['type'] = 'textarea';
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->comment;
            }
        }
        return $attr;
    }

    // Change Events
    public function addEditOnAddTimeslot(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
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
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        
        $query = $this->request->pass[1]; 
        $this->setupTabElements($encodedInstitutionId, $query);
    }

    public function setupTabElements($encodedInstitutionId, $query)
    {
        $tabElements = [];
        $decodeCommitteeId = $this->paramsDecode($query);
        $committeeId = $decodeCommitteeId['id'];
        $encodeCommitteeId = $this->paramsEncode(['institution_committee_id' => $committeeId]);

        $tabElements = [
            'InstitutionCommittees' => [
                 'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'Committees','view', $query],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'CommitteeAttachments', 'querystring' => $encodeCommitteeId],
                'text' => __('Attachments')
            ]
            // 'Attachments' => [
            //     'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'InstitutionCommitteeAttachments', 'action' => 'index', 'querystring' => $encodeCommitteeId],
            //     'text' => __('Attachments')
            // ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction','InstitutionCommittees');
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data) {

        $newEntities = [];
        
        if (isset($entity['meeting']) && $entity['meeting'] != ''){
             $textbooks = $entity['meeting'];     
                if (count($textbooks)) {
                    foreach ($textbooks as $key => $textbook) {
                        $obj['meeting_date'] = $textbook['meeting_date'];
                        $obj['start_time'] = $textbook['start_time'];
                        $obj['end_time'] = $textbook['end_time'];
                        $obj['comment'] = $textbook['comment'];
                        $obj['institution_committee_id'] = $entity['id'];
                        $obj['counterNo'] = $key;
                        $newEntities[] = $obj;
                    }
 
                    $meetingTable = \Cake\ORM\TableRegistry::get('InstitutionCommitteeMeeting', array('table' => 'institution_committee_meeting'));
                    $success = $this->connection()->transactional(function() use ($newEntities, $entity ,$meetingTable) {
                        $return = true;
                        foreach ($newEntities as $key => $newEntity) {
                            $textbookStudentEntity = $meetingTable->newEntity($newEntity);
                            if (!$meetingTable->save($textbookStudentEntity)) {
                                $return = false;
                            } 
                        }
                        return $return;
                    });
                    return $success;
                }
        } 
    }
    
    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }
}
