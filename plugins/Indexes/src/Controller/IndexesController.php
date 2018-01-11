<?php
namespace Indexes\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class IndexesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    // CAv4
    public function Indexes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Indexes.Indexes']); }
    // End

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        
        //Rename Indexes to Risk - POCOR - 4344
        $header = __('Risks');
        $this->Navigation->addCrumb('Risks', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Indexes']);
        $this->set('contentHeader', $header);
    }
}
