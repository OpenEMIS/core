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
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Counselings', ['plugin' => false, 'controller' => 'Counselings', 'action' => 'index']);

        $page = $this->Page;
        $page->exclude(['file_name', 'file_content']);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['counselor_id', 'student_id']);
        $session = $this->request->session();
        $studentId = $session->read('Student.Students.id');

        parent::index();
    }

    public function add()
    {
        $page = $this->Page;
        // $page->exclude(['student_id']);

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
        $page->get('student_id')->setValue($studentId);

        parent::add();
    }

    // public function edit($id)
    // {
    //     $page = $this->Page;
    //     // $page->exclude(['student_id']);

    //     $session = $this->request->session();
    //     $institutionId = $session->read('Institution.Institutions.id');
    //     // $studentId = $session->read('Student.Students.id');

    //     // set the options for guidance_type_id
    //     $guidanceTypeOptions = $this->Counselings->getGuidanceTypeOptions();
    //     if (empty($guidanceTypeOptions)) {
    //         $guidanceTypeOptions = ['' => __('No Options')];
    //     }
    //     $page->get('guidance_type_id')->setControlType('dropdown')->setOptions($guidanceTypeOptions);

    //     // set the options for counselor_id
    //     $counselorOptions = $this->Counselings->getCounselorOptions($institutionId);
    //     if (empty($counselorOptions)) {
    //         $counselorOptions = ['' => __('No Options')];
    //     }
    //     $page->get('counselor_id')->setControlType('dropdown')->setOptions($counselorOptions);

    //     // set student_id
    //     // $page->get('student_id')->setValue($studentId);

    //     parent::edit($id);
    // }
}
