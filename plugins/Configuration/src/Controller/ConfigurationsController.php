<?php
namespace Configuration\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;
use Firebase\JWT\JWT;
use Cake\I18n\Time;
use Cake\Utility\Security;

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

    public function generateServerAuthorisationToken()
    {
        $externalDataSourceType = $this->request->query('external_data_source_type');
        $this->autoRender = false;
        $ConfigExternalDataSource = TableRegistry::get('Configuration.ConfigExternalDataSource');
        return $ConfigExternalDataSource->generateServerAuthorisationToken($externalDataSourceType);
    }

}
