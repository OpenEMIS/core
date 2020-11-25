<?php
namespace ProfileTemplate\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class ProfileTemplateController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    // CAv4

    public function ProfileTemplate()
    {
       // $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.ProfileTemplate']);
    }
    // End

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        
        $header = __('ProfileTemplate');
        $this->Navigation->addCrumb('Risks', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ProfileTemplate']);
        $this->set('contentHeader', $header);
    }
}
