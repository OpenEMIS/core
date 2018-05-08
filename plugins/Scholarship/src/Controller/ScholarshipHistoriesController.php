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
       
        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'index']);
        $page->addCrumb('Applicants', ['plugin' => 'Scholarship','controller' => 'ScholarshipApplications', 'action' => 'ScholarshipApplications', 'index', 'queryString' => $queryString]);
        $page->addCrumb($userName);
        $page->addCrumb('Scholarship History');
      
        $page->disable(['add', 'edit', 'delete', 'view']);
    
        $this->setupTabElements(['queryString' => $queryString]);
    }
    
    public function index()
    {         
        $page = $this->Page;
        $page->setAutoContain(false);
        
        parent::index();

        $this->reorderFields();
    }

    public function setupTabElements($options)
     {  
        $page = $this->Page;
        $plugin = $this->plugin;
        $queryString = array_key_exists('queryString', $options) ? $options['queryString'] : '';

        $tabElements = [
            'ScholarshipApplications' => ['text' => __('Overview')],
            // 'Generals' => ['text' => __('General')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['text' => __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Guardians' => ['text' => __('Guardians')],
            // 'ExaminationResults' => ['text' => __('Examinations')],  
            // 'Qualifications' => ['text' => __('Qualifications')],
            'ScholarshipHistories' => ['text' => __('Scholarship History')], //page
            'ApplicationInstitutionChoices' => ['text' => __('Institution Choice')], //page
            'ScholarshipApplicationAttachments' => ['text' => __('Attachments')], //page
        ];

        foreach ($tabElements as $action => &$obj) {
            if ($action == 'ScholarshipApplications') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => 'ScholarshipApplications',
                    'action' => $action,
                    'view',
                    $queryString,
                    'queryString' => $queryString
                ];
            } elseif (in_array($action, ['ScholarshipHistories', 'ApplicationInstitutionChoices', 'ScholarshipApplicationAttachments'])) {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $action,
                    'action' => 'index',
                    'queryString' => $queryString
                ];
            } else {
                $url = [
                    'plugin' => $plugin,
                    'controller' => 'ScholarshipApplications',
                    'action' => $action,
                    'index',
                    'queryString' => $queryString
                ];
                // exceptions
                if ($action == 'UserNationalities') {
                    $url['action'] = 'Nationalities';
                }
            }
            $obj['url'] = $url;
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('ScholarshipHistories')->setActive('true');
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->exclude(['applicant_id', 'assignee_id', 'requested_amount']);

        $page->addNew('academic_period');
        $page->get('academic_period')->setDisplayFrom('scholarship.academic_period.name');

        $page->addNew('comment');
        $page->get('comment')->setDisplayFrom('scholarship.comment');

        
        $page->move('status_id')->first();
        $page->move('academic_period')->after('status_id');
    }


    
}
