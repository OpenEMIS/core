<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Security;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;
use Cake\Http\ServerRequest;

use Cake\Utility\Text;

class InstitutionTestCommitteesTable extends ControllerActionTable
{
    use HtmlTrait;

    private $studentOptions = [];
    private $availableStudent = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_committees');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionCommitteeTypes', ['className' => 'Institution.InstitutionCommitteeTypes']);
        $this->hasMany('InstitutionCommitteeAttachments', [
            'className' => 'Institution.InstitutionCommitteeAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('InstitutionCommitteeMeeting', [
            'className' => 'Institution.InstitutionCommitteeMeeting',
            'dependent' => true,
            'cascadeCallbacks' => false
        ]);
        $controllerActionBehavior = $this->behaviors()->get('ControllerAction');
        $controllerActionBehavior->setConfig(['actions' => ['search' => false]]);
        $this->addBehavior('Excel', ['pages' => ['index']]);

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Committees' =>['id','institution_committee_id']
            ]
        ]);
    }

    /*public function validationDefault(Validator $validator): Validator
    {

        $validator->setProvider('custom', $this);
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('meeting_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('end_time', 'ruleCompareTimeReverse', [
                'rule' => ['compareDateReverse', 'start_time', false]
            ]);
    }*/

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'academic_period_id':
                return __('Academic Period');
            case 'telephone':
                return __('Telephone');
            case 'comment':
                return __('Comment');
            case 'institution_committee_type_id':
                return __('Type');
            case 'name':
                return __('Name');
            case 'email':
                return __('Email');
            case 'meeting_section':
                return __('Meeting Section');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
            ]);
        }

        if (isset($extra['selectedCommiteeTypeOption']) && $extra['selectedCommiteeTypeOption'] != -1) {
            $query->where([
                $this->aliasField('institution_committee_type_id') => $extra['selectedCommiteeTypeOption']
            ]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->getQuery();
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
        $committeeTypeOptions = $this->getCommitteeTypeOptions();

        if (isset($requestQuery) && isset($requestQuery['type'])) {
            $selectedTypeId = $requestQuery['type'];
        } else {
            $selectedTypeId = -1;
        }
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['selectedCommiteeTypeOption'] = $selectedTypeId;
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);
        $extra['elements']['control'] = [
            'name' => 'Institution.CommitteeMeeting/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'periodOptions'=> $academicPeriodOptions,
                'committeeTypeOption'=> $committeeTypeOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions'],
                'selectedCommiteeTypeOption'=> $extra['selectedCommiteeTypeOption']
            ],
            'order' => 3
        ];

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Institution Committees','Committees');
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
		// End POCOR-5188
    }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            $requestQuery = $request->getQuery();
            if (!is_null($requestQuery) && isset($requestQuery['period'])) {
                $selectedAcademicPeriod = $requestQuery['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        }

        return $selectedAcademicPeriod;
    }

    // Get Options
    public function getCommitteeTypeOptions()
    {
        $InstitutionCommitteeTypes = TableRegistry::getTableLocator()->get('Institution.InstitutionCommitteeTypes');
        $committeeTypeOptions = $InstitutionCommitteeTypes
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => $InstitutionCommitteeTypes->aliasField('id'),
                'name' => $InstitutionCommitteeTypes->aliasField('name')
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
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($attr['entity']->academic_period_id));
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList();
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            //$attr['onChangeReload'] = 'changeAcademicPeriod';
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $periodOptions[$attr['entity']->academic_period_id];
            $attr['value'] = $attr['entity']->academic_period_id;
        }
        return $attr;
    }
    // OnUpdate Events
    public function onUpdateFieldInstitutionCommitteeTypeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $InstitutionCommitteeTypes = TableRegistry::getTableLocator()->get('Institution.InstitutionCommitteeTypes');
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'edit') {
                $committeType = $InstitutionCommitteeTypes->get($attr['entity']->institution_committee_type_id);

                $attr['type'] = 'readonly';
                $attr['attr']['value'] =  $committeType->name;
                $attr['value'] = $attr['entity']->institution_committee_type_id;
            }
        }
        return $attr;
    }
     // OnUpdate Events
    public function onUpdateFieldName(Event $event, array $attr, $action, ServerRequest $request)
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
    public function onUpdateFieldComment(Event $event, array $attr, $action, ServerRequest $request)
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

        if (empty($data[$this->getAlias()][$fieldKey])) {
            $data[$this->getAlias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->getAlias())) {
            $data[$this->getAlias()][$fieldKey][] = [
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
        $session = $this->request->getSession();
        $institutionId = $session->read('Institution.Institutions.id');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $query = $this->request->getParam('pass')[1];
        $this->setupTabElements($encodedInstitutionId, $query);
    }

    public function setupTabElements($encodedInstitutionId, $query)
    {
        $tabElements = [];
        $queryString = $this->request->getQuery('queryString');
        if(empty($queryString)){
            $queryString = $this->request->getParam('pass')[1];
        }
        $tabElements = [
            'InstitutionCommittees' => [
                 'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Committees','view', 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'CommitteeAttachments', 'queryString' => $queryString],
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

    if (isset($entity['meeting']) && $entity['meeting'] != '') {
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

            if (\Cake\ORM\TableRegistry::getTableLocator()->exists('Institution.InstitutionCommitteeMeeting')) {
                $meetingTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Institution.InstitutionCommitteeMeeting');
            } else {
                $meetingTable = \Cake\ORM\TableRegistry::get('Institution.InstitutionCommitteeMeeting', ['table' => 'institution_committee_meeting']);
            }

            $return = true;

            foreach ($newEntities as $key => $newEntity) {
                $textbookStudentEntity = $meetingTable->newEntity($newEntity);

                if (!$meetingTable->save($textbookStudentEntity)) {
                    $return = false;
                }
            }

            return $return;
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

    // POCOR-6171 start
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        // $institutionId = $this->Session->read('Institution.Institutions.id');
        $institutionId = $this->Session->read('Institution.Institutions.primaryKey.institution_id');
        $requestQuery = $this->request->getQuery();

        $academicPeriod = !empty($requestQuery['period']) ? $requestQuery['period'] : $this->AcademicPeriods->getCurrent();
        $committeType = !empty($requestQuery['type']) ? $requestQuery['type'] : -1;
        $InstitutionCommitteeTypes = TableRegistry::getTableLocator()->get('Institution.InstitutionCommitteeTypes');
		$query
		->select(['name' => 'InstitutionTestCommittees.name',
            'chairperson' => 'InstitutionTestCommittees.chairperson',
            'telephone' => 'InstitutionTestCommittees.telephone',
            'email' => 'InstitutionTestCommittees.email',
            'comment' => 'InstitutionTestCommittees.comment',
            'type' => 'InstitutionCommitteeTypes.name',
            'academic_period' => 'AcademicPeriods.name',
        ])
		->LeftJoin([$this->AcademicPeriods->getAlias() => $this->AcademicPeriods->getTable()],[
			$this->AcademicPeriods->aliasField('id').' = ' . 'InstitutionTestCommittees.academic_period_id'
		])
        ->LeftJoin([$InstitutionCommitteeTypes->getAlias() => $InstitutionCommitteeTypes->getTable()],[
			$InstitutionCommitteeTypes->aliasField('id').' = ' . 'InstitutionTestCommittees.institution_committee_type_id'
		])
        ->where([
            'InstitutionTestCommittees.academic_period_id' =>  $academicPeriod,
            'InstitutionTestCommittees.institution_id' =>  $institutionId
        ]);

        if($committeType > 0){
            $query
            ->where([
                'InstitutionTestCommittees.institution_committee_type_id' =>  $committeType
            ]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraField[] = [
            'key' => 'InstitutionTestCommittees.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key' => 'InstitutionTestCommittees.chairperson',
            'field' => 'chairperson',
            'type' => 'string',
            'label' => __('Chairperson')
        ];

        $extraField[] = [
            'key' => 'InstitutionTestCommittees.telephone',
            'field' => 'telephone',
            'type' => 'integer',
            'label' => __('Telephone')
        ];

        $extraField[] = [
            'key' => 'InstitutionTestCommittees.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];

        $extraField[] = [
            'key' => 'InstitutionTestCommittees.comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];

        $extraField[] = [
            'key' => 'InstitutionCommitteeTypes.name',
            'field' => 'type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period' ,
            'type' => 'integer',
            'label' => __('Academic Period')
        ];


        $fields->exchangeArray($extraField);
    }
    // POCOR-6171 ends

}
