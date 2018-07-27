<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class FeederIncomingInstitutionsTable  extends ControllerActionTable
{
    private $institutionId = null;

    public function initialize(array $config)
    {
        $this->table('feeders_institutions');
        parent::initialize($config);
        $this->belongsTo('FeederInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.FeederInstitutions', 'foreignKey' => 'feeder_institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);        
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        return $entity->institution->code;
    }

    public function onGetFeederInstitution(Event $event, Entity $entity)
    {
        return $entity->institution->name;
    }

    public function onGetAreaEducation(Event $event, Entity $entity)
    {
        return $entity->institution->area->name;
    }

    public function onGetAcademicPeriod(Event $event, Entity $entity)
    {
        return $entity->academic_period->name;
    }       

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $this->institutionId = $session->read('Institution.Institutions.id');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
        $extra['selectedAcademicPeriod'] = $this->getSelectedAcademicPeriod($this->request);
        $extra['elements']['control'] = [
            'name' => 'Institution.Feeders/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriod']
            ],
            'order' => 3
        ];

        $this->field('code');
        $this->field('feeder_institution');
        $this->field('area_education');
        $this->field('no_of_students');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;

        $query
        ->contain([
            'Institutions' => [
                'fields' => [
                    'name',
                    'code'
                ]
            ],
            'Institutions.Areas' => [
                'fields' => [
                    'name'
                ]
            ],
        ]);

        $conditions = [];
        if ($extra->offsetExists('selectedAcademicPeriod')) {
            $selectedAcademicPeriod = $extra['selectedAcademicPeriod'];
            $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        }

        if (!empty($conditions)) {
            $query->where($conditions);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period');
        $this->field('code');
        $this->field('feeder_institution');
        $this->field('area_education');
        $this->field('no_of_students');
        $this->setFieldOrder(['academic_period', 'code', 'feeder_institution', 'area_education', 'no_of_students', 'modified', 'modified_user_id','created','created_user_id']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
        ->contain([
            'Institutions' => [
                'fields' => [
                    'name',
                    'code'
                ]
            ],
            'AcademicPeriods',
            'Institutions.Areas' => [
                'fields' => [
                    'name'
                ]
            ],
        ]);
    }

    // public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
 //    {
        // $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        // if (!$this->AccessControl->isAdmin()) {
        //     if (array_key_exists('remove', $buttons)) {
        //         unset($buttons['remove']);
        //     }
        // }
        // return $buttons;
    // }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (isset($request->query) && array_key_exists('period', $request->query)) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }
}
