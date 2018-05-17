<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class UsersDirectoryController extends PageController
{
    public function initialize()
    {
        parent::initialize();
<<<<<<< HEAD:plugins/Scholarship/src/Controller/UsersDirectoryController.php
        $this->loadModel('Scholarship.UsersDirectory');
        $this->Page->loadElementsFromTable($this->UsersDirectory);
=======
        $this->loadModel('Scholarship.ApplicantsDirectory');
        $this->loadComponent('User.User');
        $this->Page->loadElementsFromTable($this->ApplicantsDirectory);
>>>>>>> ef67a37a852b71bed8645f1392ed6fe617f4f06a:plugins/Scholarship/src/Controller/ApplicantsDirectoryController.php
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
        $page->addCrumb('Users Directory');

        $page->setHeader(__('Scholarships') . ' - ' . __('Users Directory'));

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
