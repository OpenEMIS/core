<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use App\Controller\PageController;

class ScholarshipDirectoriesController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Profile.Scholarships');
        $this->Page->loadElementsFromTable($this->Scholarships);
        $this->Page->disable(['add', 'edit', 'delete']);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        $applicantId = $this->Auth->user('id');
        $applicantName = $this->Auth->user('name');
        $encodedApplicantId = $this->paramsEncode(['id' => $applicantId]);

        // set queryString
        $page->setQueryString('applicant_id', $applicantId);

        // set header
        $page->setHeader($applicantName . ' - ' . __('Scholarship Directory'));

        // set breadcrumbs
        $page->addCrumb('Profile', ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $encodedApplicantId]);
        $page->addCrumb($applicantName);
        $page->addCrumb('Scholarship Directory');
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();

        $page->exclude(['description', 'scholarship_financial_assistance_type_id', 'scholarship_funding_source_id', 'academic_period_id', 'total_amount', 'requirements', 'instructions']);

        // back button to ScholarshipApplications page
        $page->addToolbar('back', [
            'type' => 'element',
            'element' => 'Page.button',
            'data' => [
                'title' => __('Back'),
                'url' => [
                    'plugin' => 'Profile',
                    'controller' => 'Profiles',
                    'action' => 'ScholarshipApplications',
                    'index'
                ],
                'iconClass' => 'fa kd-back',
                'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
            ],
            'options' => []
        ]);
    }

    public function view($id)
    {
        $page = $this->Page;
        parent::view($id);

        // add button to ScholarshipApplications page
        $scholarshipId = $page->decode($id)['id'];
        $addUrl = $this->setQueryString([
            'plugin' => 'Profile',
            'controller' => 'Profiles',
            'action' => 'ScholarshipApplications',
            'add'
        ], ['scholarship_id' => $scholarshipId]);

        $page->addToolbar('back', []); // to fix the order of the buttons
        $page->addToolbar('add', [
            'type' => 'element',
            'element' => 'Page.button',
            'data' => [
                'title' => __('Apply'),
                'url' => $addUrl,
                'iconClass' => 'fa kd-add',
                'linkOptions' => ['title' => __('Apply')]
            ],
            'options' => []
        ]);
    }
}
