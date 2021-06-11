<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Profile\Controller\InsurancesController as BaseController;

class StudentInsurancesController extends BaseController
{
    public function index()
    {
        $page = $this->Page;

        // set field
        $page->exclude(['id','security_user_id','file_name', 'file_content', 'comment']);
        parent::index();
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
        $page->setHeader($studentName . ' - ' . __('Insurances'));

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

    private function addEditInsurance()
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
        $this->addEditInsurance();
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
        $this->addEditInsurance();
        parent::edit($id);
    }
}