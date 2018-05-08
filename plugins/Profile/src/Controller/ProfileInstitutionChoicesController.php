<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Scholarship\Controller\InstitutionChoicesController as BaseController;

class ProfileInstitutionChoicesController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        $queryString = $this->request->query['queryString'];
        $applicantId = $this->paramsDecode($queryString)['applicant_id'];
        $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];
        $userName = $this->Users->get($applicantId)->name;

        parent::beforeFilter($event);

        // set header
        $page->setHeader($userName . ' - ' . __('Institution Choices'));

        $page->setQueryString('applicant_id', $applicantId);
        $page->setQueryString('scholarship_id', $scholarshipId);

        $page->get('applicant_id')->setControlType('hidden')->setValue($applicantId);
        $page->get('scholarship_id')->setControlType('hidden')->setValue($scholarshipId);

        $this->setBreadCrumb(['userName' => $userName, 'userId' => $applicantId]);
        $this->setupTabElements(['queryString' => $queryString]);

        $page->exclude(['selection_id']);
    }
}
