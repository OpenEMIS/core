<?php
namespace App\Controller;

use Cake\Event\Event;

use Page\Controller\PageController;

class CounselingsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->Counselings);

        $this->loadComponent('RenderDate'); // will get the date format from config
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Page.onRenderCounselorId'] = 'onRenderCounselorId';
        $events['Controller.Page.onRenderGuidanceTypeId'] = 'onRenderGuidanceTypeId';
        return $events;
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');

        parent::beforeFilter($event);

        $page = $this->Page;
        $page->exclude(['file_name', 'file_content']);
        $page->get('student_id')->setControlType('hidden')->setValue($studentId); // set value and hide the student_id

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $page->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Students', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $page->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);
        $page->addCrumb('Counselings');

        // set header
        $header = $page->getHeader();
        $page->setHeader($studentName . ' - ' . $header);

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
        $page->setQueryString('student_id', $studentId);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['counselor_id', 'student_id']);

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;

        parent::view($id);
    }

    public function add()
    {
        $this->addEditCounseling();

        parent::add();
    }

    public function edit($id)
    {
        $this->addEditCounseling();

        parent::edit($id);
    }

    public function onRenderCounselorId(Event $event, $entity, $key)
    {
        return $entity->counselor->name;
    }

    public function onRenderGuidanceTypeId(Event $event, $entity, $key)
    {
        return $entity->guidance_type->name;
    }

    private function addEditCounseling()
    {
        $page = $this->Page;

        $institutionId = $page->getQueryString('institution_id');
        $studentId = $page->getQueryString('student_id');

        // set the options for guidance_type_id
        $guidanceTypeOptions = $this->Counselings->getGuidanceTypeOptions();
        $page->get('guidance_type_id')->setControlType('dropdown')->setOptions($guidanceTypeOptions);

        // set the options for counselor_id
        $counselorOptions = $this->Counselings->getCounselorOptions($institutionId);
        $page->get('counselor_id')->setControlType('dropdown')->setOptions($counselorOptions);
    }
}
