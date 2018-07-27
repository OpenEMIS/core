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
        $this->Page->disable(['search']);
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
        $page->setQueryString('institution_id', $institutionId);
        
        $page->get('meeting_date')->setLabel('Date of Meeting');

        $page->move('academic_period_id')->after('id');
        $page->move('institution_committee_type_id')->after('academic_period_id')->setLabel('Type');

        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $academicPeriodId = !is_null($page->getQueryString('academic_period_id')) ? $page->getQueryString('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $page->setQueryString('academic_period_id', $academicPeriodId);
    }

    public function index()
    {
        parent::index();
        $page = $this->Page;
        $page->exclude(['comment', 'institution_id', 'academic_period_id']);

        $institutionCommitteeTypes = $this->InstitutionCommitteeTypes
            ->find('optionList', ['defaultOption' => false])
            ->toArray();
        $institutionCommitteeTypes = [null => __('All Types')] + $institutionCommitteeTypes;

        $page->addFilter('academic_period_id')
            ->setOptions($this->academicPeriodOptions);

        $page->addFilter('institution_committee_type_id')
            ->setOptions($institutionCommitteeTypes);
    }

    public function view($id)
    {
        parent::view($id);
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $this->setupTabElements($encodedInstitutionId, $id);
    }

    public function add()
    {
        parent::add();
        $page = $this->Page;
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions)
            ->setValue($this->AcademicPeriods->getCurrent());

        $page->get('institution_committee_type_id')
            ->setControlType('select');
        $page->get('start_time')
            ->setRequired(true);
        $page->get('end_time')
            ->setRequired(true);
    }
    public function setupTabElements($encodedInstitutionId, $query)
    {
        $page = $this->Page;
        $tabElements = [];

        $decodeCommitteeId = $page->decode($query);
        $committeeId = $decodeCommitteeId['id'];
        $encodeCommitteeId = $page->encode(['institution_committee_id' => $committeeId]);

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

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('InstitutionCommittees')->setActive('true');
    }
}
