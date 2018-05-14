<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use App\Model\Traits\OptionsTrait;

class ScholarshipHistoriesController extends PageController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.Users');
        $this->loadModel('Scholarship.Histories'); 
        $this->Page->loadElementsFromTable($this->Histories); 
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        return $event;
    }

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

        $page->setQueryString('applicant_id', $applicantId); // will automatically build into query if the name matches
        $page->setQueryString('scholarshipId', $scholarshipId);
       
        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
        $page->addCrumb('Applicants', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Applications', 'index']);
        $page->addCrumb($userName);
        $page->addCrumb('Scholarship History');
      
        $page->disable(['add', 'edit', 'delete', 'view']);
    
        $this->setupTabElements();
    }
    
    public function index()
    {         
        $page = $this->Page;
        
        parent::index();
        $this->reorderFields();
    }

    public function setupTabElements()
    {  
        $page = $this->Page;

        if (array_key_exists('queryString', $this->request->query)) {
            $queryString = $this->request->query('queryString');
        }

        $tabElements = [
            'Applications' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Applications', 'view', $queryString, 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'Identities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Identities', 'index', 'queryString' => $queryString],
                'text' => __('Identities')
            ],
            'UserNationalities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Nationalities', 'index', 'queryString' => $queryString],
                'text' => __('Nationalities')
            ],
            'Contacts' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Contacts', 'index', $queryString, 'queryString' => $queryString],
                'text' => __('Contacts')
            ],
            'Guardians' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Guardians', 'index', $queryString, 'queryString' => $queryString],
                'text' => __('Guardians')
            ],
            'Histories' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipHistories', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Scholarship History')
            ],
            'InstitutionChoices' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplicationInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Institution Choices')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplicationAttachments', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Attachments')
            ],
     
        ];

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('Histories')->setActive('true');
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->exclude(['id','applicant_id', 'assignee_id', 'requested_amount']);

        $page->addNew('academic_period');
        $page->get('academic_period')->setDisplayFrom('scholarship.academic_period.name');

        $page->addNew('comment');
        $page->get('comment')->setDisplayFrom('scholarship.comment');

        $page->move('status_id')->first();
        $page->move('academic_period')->after('status_id');
    }
}
