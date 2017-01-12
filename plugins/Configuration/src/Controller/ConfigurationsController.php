<?php
namespace Configuration\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Http\Client;

class ConfigurationsController extends AppController {
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Configuration.Configuration');
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

    public function getExternalUsers()
    {
        $this->autoRender = false;
        $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalDataSourceAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->innerJoin(['ConfigItems' => 'config_items'], [
                'ConfigItems.code' => 'external_data_source_type',
                $ExternalDataSourceAttributes->aliasField('external_data_source_type').' = ConfigItems.value'
            ])
            ->toArray();

        $serverAuthorisationToken = $ExternalDataSourceAttributes->generateServerAuthorisationToken($attributes['client_id'], $attributes['scope'], $attributes['token_uri'], $attributes['private_key']);

        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $serverAuthorisationToken
        ];

        $fieldMapping = [
            '{page}' => $this->request->query('page'),
            '{limit}' => $this->request->query('limit'),
            '{first_name}' => $this->request->query('first_name'),
            '{last_name}' => $this->request->query('last_name'),
            '{identity_number}' => $this->request->query('identity_number'),
            '{date_of_birth}' => $this->request->query('date_of_birth')
        ];
        $http = new Client();
        $response = $http->post($attributes['token_uri'], $data);
        $noData = json_encode(['data' => [], 'total' => 0], JSON_PRETTY_PRINT);
        if ($response->isOK()) {
            $body = $response->body('json_decode');
            $recordUri = $attributes['record_uri'];

            foreach ($fieldMapping as $key => $map) {
                $recordUri = str_replace($key, $map, $recordUri);
            }

            $http = new Client([
                'headers' => ['Authorization' => $body->token_type.' '.$body->access_token]
            ]);

            $response = $http->get($recordUri);

            if ($response->isOK()) {
                echo json_encode($response->body('json_decode'), JSON_PRETTY_PRINT);
            } else {
                echo $noData;
            }
        } else {
            echo $noData;
        }

    }

    public function isActionIgnored(Event $event, $action)
    {
        if ($action == 'generateServerAuthorisationToken') {
            return true;
        }
    }

}
