<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use Scholarship\Controller\ApplicationAttachmentsController as BaseController;

class ProfileApplicationAttachmentsController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        $queryString = $this->request->query['queryString'];
        $applicantId = $this->paramsDecode($queryString)['applicant_id'];
        $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];
        $applicantName = $this->Users->get($applicantId)->name;

        parent::beforeFilter($event);

        // set header
        $page->setHeader($applicantName . ' - ' . __('Attachments'));

        $page->setQueryString('applicant_id', $applicantId);
        $page->setQueryString('scholarship_id', $scholarshipId);

        $page->get('applicant_id')->setControlType('hidden')->setValue($applicantId);
        $page->get('scholarship_id')->setControlType('hidden')->setValue($scholarshipId);

        $this->setBreadCrumb(['userName' => $applicantName, 'userId' => $applicantId]);
        $this->setupTabElements(['queryString' => $queryString]);

    }
}
