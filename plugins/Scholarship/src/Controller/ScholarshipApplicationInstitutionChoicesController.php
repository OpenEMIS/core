<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use Scholarship\Controller\InstitutionChoicesController as BaseController;

class ScholarshipApplicationInstitutionChoicesController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        if (isset($this->request->query['queryString'])) {
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

            $this->setBreadCrumb(['userName' => $userName]);
            $this->setupTabElements();

            $page->exclude(['is_selected']); 
        }
    }
}
