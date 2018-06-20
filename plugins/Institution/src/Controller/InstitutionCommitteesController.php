<?php
namespace Institution\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Log\Log;
use App\Controller\PageController;

class InstitutionCommitteesController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Institution.InstitutionCommitteeTypes');

        $this->loadComponent('Institution.InstitutionCommitteeTabs');

        $this->Page->disable(['search']);
    }
    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.getEntityRowActions'] = 'getEntityRowActions';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Committees');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Committees'));

        // set institution_id
        $page->get('institution_id')
            ->setControlType('hidden')
            ->setValue($institutionId);

        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $page->move('academic_period_id')->after('id');
        $page->move('institution_committee_type_id')->after('academic_period_id')->setLabel('Type');
        $page->get('meeting_date')->setLabel('Date of Meeting');
    }

    public function index()
    {
        parent::index();

        $page = $this->Page;
        $page->exclude(['comment', 'institution_id', 'academic_period_id']);
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;
        $page->move('institution_committee_type_id')->after('academic_period_id');
        $page->move('name')->after('institution_committee_type_id');
        $this->setupTabElements();
    }

    public function add()
    {
        parent::add();

        $page = $this->Page;
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions);

        $page->get('institution_committee_type_id')
            ->setControlType('select');
    }

    public function setupTabElements()
    {
        $page = $this->Page;
        $tabElements = [];
       
        $tabElements = $this->InstitutionCommitteeTabs->getInstitutionCommitteeTabs();

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('InstitutionCommittees')->setActive('true');
    }

    public function getEntityRowActions(Event $event, $entity, ArrayObject $rowActions)
    {
        $rowActionsArray = $rowActions->getArrayCopy();
        $institutionCommitteeId = $entity->id;

        $querystring = $this->Page->encode([
            'institution_committee_id' => $institutionCommitteeId
        ]);

        if (array_key_exists('view', $rowActions)) {
            $rowActionsArray['view']['url']['querystring'] = $querystring;
        }

        if (array_key_exists('edit', $rowActions)) {
            $rowActionsArray['edit']['url']['querystring'] = $querystring;
        }

        $rowActions->exchangeArray($rowActionsArray);
    }
}
