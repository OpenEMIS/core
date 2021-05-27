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
    public function Institutions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.ProfileTemplates']); }
    
	public function Staff() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.StaffTemplates']); }
	
	public function Students() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.StudentTemplates']); }
	
	public function InstitutionProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.Profiles']); }
	
	public function StaffProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.StaffProfiles']); }
	
	public function StudentProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ProfileTemplate.StudentProfiles']); }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Profile');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Profile', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->set('contentHeader', $header);
    }
}
