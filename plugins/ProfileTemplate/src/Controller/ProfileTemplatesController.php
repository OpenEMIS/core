<?php
namespace ProfileTemplate\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ProfileTemplatesController extends AppController
{
    public function initialize() {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    // CAv4
    public function Templates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ReportCard.ReportCards']); }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Report Cards');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Report Cards', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->set('contentHeader', $header);
    }
}
