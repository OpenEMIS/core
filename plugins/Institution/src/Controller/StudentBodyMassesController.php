<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Profile\Controller\BodyMassesController as BaseController;

class StudentBodyMassesController extends BaseController
{
     public function initialize()
    {
        parent::initialize();
        $this->Page->enable(['download']);
        // $this->Page->enable('page.FileUpload', [
        //     'name' => 'file_name',
        //     'content' => 'file_content',
        //     'size' => '10MB',
        //     'contentEditable' => true,
        //     'allowable_file_types' => 'all',
        //     'useDefaultName' => true
        // ]);
    }

    public function index()
    {
        $page = $this->Page;

        // set field
        $page->exclude(['id','security_user_id','file_name', 'file_content', 'comment']);
        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set the file download for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');

        parent::view($id);

        $entity = $page->getData();

    } 

    private function addEditBodyMass()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set the file upload for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');
    }

    public function add()
    {
        $this->addEditBodyMass();
        parent::add();
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    public function edit($id)
    {
        $this->addEditBodyMass();
        parent::edit($id);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');

        parent::beforeFilter($event);

        // set header
        $page->setHeader($studentName . ' - ' . __('Body Mass'));

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
        $page->setQueryString('security_user_id', $studentId);

        // set Breadcrumb
        $this->setBreadCrumb([
            'userId' => $studentId,
            'userName' => $studentName,
            'userRole' => 'Student',
            'institutionId' => $institutionId,
            'institutionName' => $institutionName
        ]);

        // set Tabs
        $this->setupInstitutionTabElements([
            'userId' => $studentId,
            'userName' => $studentName,
            'userRole' => 'Student',
            'institutionId' => $institutionId
        ]);

        $page->get('security_user_id')->setControlType('hidden')->setValue($studentId); // set value and hide the student_id
    
        $this->setTooltip();
    }
}