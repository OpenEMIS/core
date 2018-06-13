<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class InstitutionCommitteesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionCommitteeTypes', ['className' => 'Institutions.InstitutionCommitteeTypes']);
        $this->hasMany('InstitutionCommitteeAttachments', ['className' => 'Institutions.InstitutionCommitteeAttachments']);
        // $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' =>'student_id']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain([
            'InstitutionCommitteeAttachments'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_committee_type_id', ['attr' => ['label' => __('Type')]]);
        $this->setFieldOrder(['academic_period_id', 'institution_committee_type_id', 'name', 'meeting_date', 'start_time', 'end_time','comment']);
        // $request = $this->controller->request->params;
        // pr($this->paramsDecode($request['pass'][1]));die;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_committee_type_id', ['attr' => ['label' => ('Type')]]);
        $this->field('academic_period_id',['visible' => false]);
        $this->field('comment',['visible' => false]);
        $this->setFieldOrder(['institution_committee_type_id', 'name', 'meeting_date', 'start_time', 'end_time']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {

        $session = $this->Session;
        $institutionId = $session->read('Institution.Institutions.id');

        // Academic Periods
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriodId]);

        // Institution Committee Types
        $InstitutionCommitteeTypes = TableRegistry::get('Institution.InstitutionCommitteeTypes');
        $institutionCommitteeTypeOptions = $InstitutionCommitteeTypes
            ->find('list')
            ->toArray();
        $institutionCommitteeTypeOptions = ['-1' => __('All Types')] + $institutionCommitteeTypeOptions;

        $selectedInstitutionCommitteeType = !empty($requestQuery['institution_committee_type_id']) ? $requestQuery['institution_committee_type_id'] : -1;

        if ($selectedInstitutionCommitteeType != -1) {
            $query->where([$this->aliasField('institution_committee_type_id') => $selectedInstitutionCommitteeType]);
        }

        $extra['elements']['control'] = [
            'name' => 'Committees/controls',
            'data' => [
                'academicPeriodOptions' => $academicPeriodOptions,
                'institutionCommitteeTypeOptions' => $institutionCommitteeTypeOptions,
                'selectedAcademicPeriod' => $selectedAcademicPeriodId,
                'selectedInstitutionCommitteeType' => $selectedInstitutionCommitteeType
            ],
            'options' => [],
            'order' => 0];
    }
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['institution_committee_type_id']['type'] = 'select';
    }

    public function setTabElements()
    {
        
        $plugin = $this->controller->plugin;
        $name = $this->controller->name;
        $request = $this->controller->request->params;
        // pr($this->paramsDecode($request['pass'][1]));die;
        // $id = $this->ControllerAction->buttons['view']['url'][0];
        $action = $this->ControllerAction->url('view');
        $id = $action[1];
        // pr($this->paramsDecode($action[1]));die;
        // if ($id=='view' || $id=='edit') {
        //     if (isset($this->ControllerAction->buttons['view']['url'][1])) {
        //         $id = $this->ControllerAction->buttons['view']['url'][1];
        //     }
        // }

        $tabElements = [
            $this->alias => [
                'url' => [],
                'text' => __('Overview')
            ],
            'InstitutionCommitteeAttachments' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'InstitutionCommitteeAttachments', 'index', $this->paramsEncode(['institution_committee_id' => $id])],
                'text' => __('Attachments')
            ]
        ];


        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('selectedAction', $this->alias);
        $this->controller->set('tabElements', $tabElements);
    }

    public function afterAction(Event $event)
    {
        if (isset($this->action) && in_array($this->action, ['view'])) {
            $this->setTabElements();
        }

        // if (isset($this->action) && strtolower($this->action) != 'index') {
        //     $this->Navigation->addCrumb($this->getHeader($this->action));
        // }
    }

}
