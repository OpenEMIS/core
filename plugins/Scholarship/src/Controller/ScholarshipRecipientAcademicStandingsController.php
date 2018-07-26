<?php
namespace Scholarship\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class ScholarshipRecipientAcademicStandingsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.Users');        
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Scholarship.RecipientAcademicStandings');

        $this->loadComponent('Scholarship.ScholarshipTabs');
        $this->Page->loadElementsFromTable($this->RecipientAcademicStandings);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        return $event;
    }

    public function beforeFilter(Event $event)
    {
    	$page = $this->Page;
        parent::beforeFilter($event);

        $queryString = $this->request->query('queryString');
        $recipientId = $this->paramsDecode($queryString)['recipient_id'];
        $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];
        $recipientName = $this->Users->get($recipientId)->name;

		$page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
		$page->addCrumb('Recipients', ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipients', 'action' => 'index']);
        $page->addCrumb($recipientName);
        $page->addCrumb('Academic Standings');

        $page->setHeader($recipientName . ' - ' . __('Academic Standings'));  

        $page->get('scholarship_semester_id')
            ->setLabel('Semester');

        $page->get('gpa')
            ->setLabel('GPA');

        $page->get('date')
            ->setLabel('Date Entered');

        $page->setQueryString('recipient_id', $recipientId);
        $page->setQueryString('scholarship_id', $scholarshipId);

        $page->get('recipient_id')->setControlType('hidden')->setValue($recipientId);
        $page->get('scholarship_id')->setControlType('hidden')->setValue($scholarshipId);

         // set options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList(); 
        $this->setupTabElements();  
    }

   public function index()
   {
        parent::index();
        $page = $this->Page;

        $page->exclude(['recipient_id', 'scholarship_id', 'comments']);

        $this->reorderFields();
   }

    public function view($id)
    {
        $page = $this->Page;
        parent::view($id);

        $this->reorderFields();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit($id);
    }

    public function delete($id)
    {
        $page = $this->Page;
        parent::delete($id);
        $this->reorderFields();
    }

    private function addEdit($id=0)
    {
        $page = $this->Page;

        // set academic
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions);

        $page->get('scholarship_semester_id')
            ->setControlType('select');

        $this->reorderFields();
    }

    public function reorderFields() 
    {
        $page = $this->Page;

        $page->move('academic_period_id')->first();
        $page->move('scholarship_semester_id')->after('academic_period_id');
        $page->move('date')->after('scholarship_semester_id');
        $page->move('gpa')->after('date');
        $page->move('comments')->after('gpa');
    }


    public function setupTabElements()
    {
        $page = $this->Page;
        $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('AcademicStandings')->setActive('true');
    }

}
