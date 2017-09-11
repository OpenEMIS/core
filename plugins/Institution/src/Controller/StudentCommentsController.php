<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Profile\Controller\CommentsController as BaseController;

class StudentCommentsController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');

        parent::beforeFilter($event);

        // set Header
        $page->setHeader($studentName . ' - Comments');

        // set QueryString (for findIndex)
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
    }

    public function add()
    {
        $page = $this->Page;
        $session = $this->request->session();

        $studentId = $session->read('Student.Students.id');
        $page->get('security_user_id')->setValue($studentId);

        parent::add();
    }
}
