<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use App\Model\Traits\OptionsTrait;

class ScholarshipApplicationDirectoriesController extends PageController
{

    public function initialize()
    {
        parent::initialize();
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
        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'index']);
        $page->addCrumb('Directory');

        $page->setHeader(__('Scholarships - Directory'));

        $page->disable(['edit', 'delete']);
    }

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);

        $request = $this->request;
        $currentAction = $request->action;

        $page = $this->Page;
        $toolbars = $page->getToolbars();

        if ($toolbars->offsetExists('add')) {
            $toolbars->offsetUnset('add');
        }

        if($currentAction == 'index') {
            $page->addToolbar('Back', [
                'type' => 'element',
                'element' => 'Page.button',
                'data' => [
                    'title' => __('Back'),
                    'url' => [
                        'plugin' => 'Scholarship',
                        'controller' => 'ScholarshipApplications',
                        'action' => 'ScholarshipApplications',
                        'index'
                    ],
                    'iconClass' => 'fa kd-back',
                    'linkOptions' => ['title' => __('Back')]
                ],
                'options' => []
            ]);
        }
    }
    
    public function index()
    {         
        $page = $this->Page;
      
        parent::index();
        
        $this->reorderFields();
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->exclude(['username', 'password','first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'email', 'date_of_death','address', 'postal_code', 'address_area_id', 'birthplace_area_id', 'nationality_id', 'photo_content', 'external_reference', 'is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status', 'preferred_language', 'last_login']);

        
        $page->addNew('name');
        $page->get('name')->setDisplayFrom('name');
        
        $page->move('name')->after('openemis_no');
        $page->move('date_of_birth')->after('name');
    } 
}
