<?php
namespace Configuration\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Http\Client;
use Page\Traits\EncodingTrait;

class ConfigurationsController extends AppController
{
    use EncodingTrait;

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

        $this->set('contentHeader', __($header));
    }

    public function Webhooks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigWebhooks']);
    }
    public function ProductLists()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigProductLists']);
    }
    public function Authentication()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigAuthentication']);
    }
    public function AuthSystemAuthentications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigSystemAuthentications']);
    }
    public function ExternalDataSource()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigExternalDataSource']);
    }
    public function CustomValidation()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigCustomValidation']);
    }
    public function AdministrativeBoundaries()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigAdministrativeBoundaries']);
    }
    public function Themes()
    {
        return $this->redirect(['plugin' => 'Theme', 'controller' => 'Themes', 'action' => 'index', 'querystring' => $this->encode($this->request->query)]);
    }
    public function StaffTransfers()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigStaffTransfers']);
    }
    public function StaffReleases()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigStaffReleases']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function getExternalUsers()
    {
        $this->autoRender = false;
        $ExternalAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->innerJoin(['ConfigItems' => 'config_items'], [
                'ConfigItems.code' => 'external_data_source_type',
                $ExternalAttributes->aliasField('external_data_source_type').' = ConfigItems.value'
            ])
            ->toArray();

        $clientId = $attributes['client_id'];
        $scope = $attributes['scope'];
        $tokenUri = $attributes['token_uri'];
        $privateKey = $attributes['private_key'];

        $token = $ExternalAttributes->generateServerAuthorisationToken($clientId, $scope, $tokenUri, $privateKey);

        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $token
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
                $this->response->body(json_encode($response->body('json_decode'), JSON_PRETTY_PRINT));
            } else {
                $this->response->body($noData);
            }
        } else {
            $this->response->body($noData);
        }
    }

    public function isActionIgnored(Event $event, $action)
    {
        if (in_array($action, ['generateServerAuthorisationToken', 'getExternalUsers'])) {
            return true;
        }
        if ($this->request->param('action') == 'setAlert') {
            return true;
        }
    }

    public function setAlert()
    {
        $this->autoRender = false;
        if ($this->request->query('message') && $this->request->query('alertType')) {
            $alertType = $this->request->query('alertType');
            $alertMessage = $this->request->query('message');
            $this->Alert->$alertType($alertMessage);
        }
    }
}
