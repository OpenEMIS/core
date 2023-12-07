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
        $institutionId = $this->getInstitutionID();
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
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
            'institutionId' => $encodedInstitutionId,
            'institutionName' => $institutionName
        ]);

        // set Tabs
        $this->setupInstitutionTabElements([
            'userId' => $studentId,
            'userName' => $studentName,
            'userRole' => 'Student',
            'institutionId' => $encodedInstitutionId
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

    private function getInstitutionID()
    {
        $session = $this->request->session();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = isset($this->request->params['institutionId']) ?
            $this->request->params['institutionId'] :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }
}
