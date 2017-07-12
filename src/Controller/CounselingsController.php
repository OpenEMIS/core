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
        $this->Page->disable(['delete']);

        $this->loadComponent('RenderDate');
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');

        // Breadcrumb
        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $this->Navigation->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $this->Navigation->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Students', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $this->Navigation->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);

        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Counselings', ['plugin' => false, 'controller' => 'Counselings', 'action' => 'index']);

        $page = $this->Page;
        $page->exclude(['file_name', 'file_content']);
    }

    public function index()
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $studentId = $session->read('Student.Students.id');

        $page = $this->Page;
        $page->exclude(['counselor_id', 'student_id']);

        $page->setQueryOption('student_id', $studentId); // passed the student Id

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['student_id']);

        parent::view($id);
    }

    public function add()
    {
        $page = $this->Page;

        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $studentId = $session->read('Student.Students.id');

        // set the options for guidance_type_id
        $guidanceTypeOptions = $this->Counselings->getGuidanceTypeOptions();
        if (empty($guidanceTypeOptions)) {
            $guidanceTypeOptions = ['' => __('No Options')];
        }
        $page->get('guidance_type_id')->setControlType('dropdown')->setOptions($guidanceTypeOptions);

        // set the options for counselor_id
        $counselorOptions = $this->Counselings->getCounselorOptions($institutionId);
        if (empty($counselorOptions)) {
            $counselorOptions = ['' => __('No Options')];
        }
        $page->get('counselor_id')->setControlType('dropdown')->setOptions($counselorOptions);

        // // set student_id
        $page->get('student_id')->setControlType('hidden')->setValue($studentId);

        parent::add();
    }

    public function edit($id)
    {
        $page = $this->Page;

        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $studentId = $session->read('Student.Students.id');

        // set the options for guidance_type_id
        $guidanceTypeOptions = $this->Counselings->getGuidanceTypeOptions();
        if (empty($guidanceTypeOptions)) {
            $guidanceTypeOptions = ['' => __('No Options')];
        }
        $page->get('guidance_type_id')->setControlType('dropdown')->setOptions($guidanceTypeOptions);

        // set the options for counselor_id
        $counselorOptions = $this->Counselings->getCounselorOptions($institutionId);
        if (empty($counselorOptions)) {
            $counselorOptions = ['' => __('No Options')];
        }
        $page->get('counselor_id')->setControlType('dropdown')->setOptions($counselorOptions);

        // set student_id
        $page->get('student_id')->setControlType('hidden')->setValue($studentId);

        parent::edit($id);
    }
}
