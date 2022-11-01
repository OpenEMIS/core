<?php
namespace Textbook\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class TextbooksController extends AppController
{
    public function initialize() {
        parent::initialize();
        $this->loadComponent('Paginator');

        $this->ControllerAction->models = [
            'ImportTextbooks'=> ['className' => 'Textbook.ImportTextbooks', 'actions' => ['add']]
        ];
    }

    // CAv4
    public function Textbooks() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Textbook.Textbooks']); }
    // End

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) 
    {
        $header = __('Textbooks');
        $this->Navigation->addCrumb('Textbooks', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        
        $this->set('contentHeader', $header);
    }
}
