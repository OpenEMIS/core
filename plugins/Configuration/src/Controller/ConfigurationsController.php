<?php
namespace Configuration\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class ConfigurationsController extends AppController {
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->model('Configuration.ConfigItems', ['index', 'view', 'edit']);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $header = 'System Configurations';

        $this->Navigation->addCrumb($header, ['plugin' => null, 'controller' => $this->name, 'action' => 'index']);
        $session = $this->request->session();
        $action = $this->request->params['action'];

        $this->set('contentHeader', __($header));
    }

    public function ProductLists()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigProductLists']); }
    public function Authentication()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigAuthentication']); }
    public function ExternalDataSource()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigExternalDataSource']); }
    public function CustomValidation()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigCustomValidation']); }
    public function AdministrativeBoundaries()  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigAdministrativeBoundaries']); }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function generateServerAuthorisationToken()
    {
        $this->autoRender = false;
        $externalDataSourceType = $this->request->query('external_data_source_type');
        $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        echo $ExternalDataSourceAttributes->generateServerAuthorisationToken($externalDataSourceType);
    }

    public function isActionIgnored(Event $event, $action)
    {
        if ($action == 'generateServerAuthorisationToken') {
            return true;
        }
    }

}
