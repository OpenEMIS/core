<?php
namespace Risk\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class RisksController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    // CAv4

    public function Risks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Risk.Risks']);
    }
    // End

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        
        $header = __('Risks');
        $this->Navigation->addCrumb('Risks', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Risks']);
        $this->set('contentHeader', $header);
    }
}
