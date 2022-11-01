<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Scholarship\Controller\InstitutionChoicesController as BaseController;

class ProfileApplicationInstitutionChoicesController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Scholarship.Scholarships');
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        if (isset($this->request->query['queryString'])) {
            $queryString = $this->request->query('queryString');
            $applicantId = $this->paramsDecode($queryString)['applicant_id'];
            $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];
            $applicantName = $this->Users->get($applicantId)->name;
            $scholarshipName = $this->Scholarships->get($scholarshipId)->name;

            parent::beforeFilter($event);

            $page->setHeader($scholarshipName . ' - ' . __('Institution Choices'));

            $page->setQueryString('applicant_id', $applicantId);
            $page->setQueryString('scholarship_id', $scholarshipId);

            $page->get('applicant_id')->setControlType('hidden')->setValue($applicantId);
            $page->get('scholarship_id')->setControlType('hidden')->setValue($scholarshipId);

            $this->setBreadCrumb(['userName' => $applicantName, 'userId' => $applicantId]);
            $this->setupTabElements();

            $page->exclude(['is_selected']);
        }
    }
}
