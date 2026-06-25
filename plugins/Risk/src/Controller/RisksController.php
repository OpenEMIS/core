<?php
namespace Risk\Controller;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class RisksController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    // CAv4

    public function Risks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Risk.Risks']);
    }
    // End

    public function beforeFilter(EventInterface $event)
    {
        if ($this->getPlugin() == 'Risk') {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);

        $header = __('Risks');
        $this->Navigation->addCrumb('Risks', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Risks']);
        $this->set('contentHeader', $header);
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }
}
