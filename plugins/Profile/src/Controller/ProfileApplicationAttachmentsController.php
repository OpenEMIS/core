<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Scholarship\Controller\AttachmentsController as BaseController;

class ProfileApplicationAttachmentsController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Scholarship.Scholarships');
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        $queryString = $this->request->query['queryString'];
        $applicantId = $this->paramsDecode($queryString)['applicant_id'];
        $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];
        $applicantName = $this->Users->get($applicantId)->name;
        $scholarshipName = $this->Scholarships->get($scholarshipId)->name;

        parent::beforeFilter($event);

        $page->setHeader($scholarshipName . ' - ' . __('Attachments'));

        $page->setQueryString('applicant_id', $applicantId);
        $page->setQueryString('scholarship_id', $scholarshipId);

        $page->get('applicant_id')->setControlType('hidden')->setValue($applicantId);
        $page->get('scholarship_id')->setControlType('hidden')->setValue($scholarshipId);

        $this->setBreadCrumb(['userName' => $applicantName, 'userId' => $applicantId]);
        $this->setupTabElements();
    }
}
