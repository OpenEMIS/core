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
    public function Templates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.ProfileTemplates']); }
    
	public function StaffTemplates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.StaffTemplates']); }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Profile');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Profile', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->set('contentHeader', $header);
    }
}
