<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use App\Controller\PageController;


class ScholarshipsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
      
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Education.EducationFieldOfStudies');
        $this->loadModel('Scholarship.ScholarshipsFieldOfStudies');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $page = $this->Page;

        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'index']);
        $page->addCrumb('Scholarships');

        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();
    }

    public function index()
    {
        parent::index();
        $page = $this->Page;
       
        $page->exclude(['description', 'financial_assistance_type_id', 'scholarship_funding_source_id', 'academic_period_id', 'total_amount', 'requirements', 'instructions']);
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit();
    }

    private function addEdit()
    {
        $page = $this->Page;

        $page->get('financial_assistance_type_id')
            ->setControlType('select');

        $page->get('scholarship_funding_source_id')
            ->setControlType('select');

        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions, false);
    
        $maxYear = 20;
        $bondOptions = $this->getBondOptions($maxYear);

        $page->get('bond')
            ->setControlType('select')
            ->setOptions($bondOptions);

        $educationFieldOfStudiesOptions = $this->EducationFieldOfStudies
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $page->addNew('education_field_of_studies')
            ->setLabel('Fields Of Study')
            ->setControlType('select')
            ->setAttributes('multiple', true)
            ->setAttributes('placeholder', __('Select Study Fields'))
            ->setOptions($educationFieldOfStudiesOptions, false);

        $page->move('education_field_of_studies')->after('scholarship_funding_source_id');

    }

    public function view($id)
    {
        parent::view($id);
        $page = $this->Page;

        $this->setupTabElements(['id' => $id]); 

        $page->addNew('education_field_of_studies')
            ->setLabel('Fields Of Study')
            ->setControlType('select')
            ->setAttributes('multiple', true);

        $page->move('education_field_of_studies')->after('funding_source_id');
    }


    public function setupTabElements($options)
    {   
        $page = $this->Page;
        $plugin = $this->plugin;

        $scholarshipId = $page->decode($options['id'])['id']; //get actual value of scholarship
        $queryString = $page->encode(['scholarship_id' => $scholarshipId]); 

        $tabElements = [
            'Scholarships' => [
                'url' => ['plugin' => $this->plugin, 'controller' => 'Scholarships', 'action' => 'view', $options['id']],
                'text' => __('Scholarships')
            ],
            'ScholarshipAttachmentTypes' => [
                'url' => ['plugin' => $this->plugin, 'controller' => 'ScholarshipAttachmentTypes', 'action' => 'index', 'querystring' => $queryString],
                'text' => __('Attachments')
            ],
        ];

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        $page->getTab('Scholarships')->setActive('true');    
      
    }

    public function getBondOptions($maxYears)
    {
        $bondOptions = [];    

        for ($i=0; $i<$maxYears; $i++) {
            $bondOptions [] = __($i .' Years');
        }

        return $bondOptions;
    }
}
