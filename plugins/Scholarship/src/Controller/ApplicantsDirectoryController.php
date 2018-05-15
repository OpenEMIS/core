<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class ApplicantsDirectoryController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Scholarship.ApplicantsDirectory');
        $this->Page->loadElementsFromTable($this->ApplicantsDirectory);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
        $page->addCrumb('Applicants Directory');

        $page->setHeader(__('Scholarships') . ' - ' . __('Applicants Directory'));

        $page->disable(['add', 'edit', 'delete']);
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();

        $page->addToolbar('Back', [
            'type' => 'element',
            'element' => 'Page.button',
            'data' => [
                'title' => __('Back'),
                'url' => [
                    'plugin' => 'Scholarship',
                    'controller' => 'Scholarships',
                    'action' => 'Applications',
                    'index'
                ],
                'iconClass' => 'fa kd-back',
                'linkOptions' => ['title' => __('Back')]
            ],
            'options' => []
        ]);

        $page->exclude(['username', 'password','first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'email', 'date_of_death','address', 'postal_code', 'address_area_id', 'birthplace_area_id', 'nationality_id', 'photo_content', 'external_reference', 'is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status', 'preferred_language', 'last_login']);

        $page->addNew('name')->setDisplayFrom('name');

        $page->move('name')->after('openemis_no');
        $page->move('date_of_birth')->after('name');
    }

    public function view($id)
    {
        $page = $this->Page;
        parent::view($id);

        $applicantId = $page->decode($id)['id'];
        $queryString = $this->paramsEncode(['applicant_id' => $applicantId]); // v4 Encode

        $page->addToolbar('back', []); // to fix the order of the buttons
        $page->addToolbar('Apply', [
            'type' => 'element',
            'element' => 'Page.button',
            'data' => [
                'title' => __('Apply'),
                'url' => [
                    'plugin' => 'Scholarship',
                    'controller' => 'Scholarships',
                    'action' => 'Applications',
                    'add',
                    'queryString' => $queryString
                ],
                'iconClass' => 'fa kd-add',
                'linkOptions' => ['title' => __('Apply')]
            ],
            'options' => []
        ]);
    }
}
