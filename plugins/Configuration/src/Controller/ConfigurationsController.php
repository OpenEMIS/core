<?php
namespace Configuration\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Http\Client;
use Cake\Http\ServerRequest;
use Page\Traits\EncodingTrait;
use Cake\Event\EventInterface;

class ConfigurationsController extends AppController
{

    use EncodingTrait;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Configuration.Configuration');
        $this->ControllerAction->model('Configuration.ConfigItems', ['index', 'view', 'edit']);
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $header = 'System Configurations';

        $this->Navigation->addCrumb($header, ['plugin' => null, 'controller' => $this->getName(), 'action' => 'index']);

        $this->set('contentHeader', __($header));
    }

    public function AutomatedStudentEnrollment() //POCOR-8689
    { 
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigAutomatedStudentEnrollments']);
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
    public function Theme()
    {
        return $this->redirect(['plugin' => 'Theme', 'controller' => 'Themes', 'action' => 'index', 'querystring' => $this->encode($this->request->getQuery())]);
    }

    public function Themes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Theme.Themes']);
    }
    public function StaffTransfers()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigStaffTransfers']);
    }
    public function StaffReleases()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigStaffReleases']);
    }

    public function implementedEvents(): array
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
                //$ExternalAttributes->aliasField('external_data_source_type').' = ConfigItems.value'
                $ExternalAttributes->aliasField('external_data_source_type').' = ConfigItems.name' //POCOR-7981
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
            '{page}' => $this->request->getQuery('page'),
            '{limit}' => $this->request->getQuery('limit'),
            '{first_name}' => $this->request->getQuery('first_name'),
            '{last_name}' => $this->request->getQuery('last_name'),
            '{identity_number}' => $this->request->getQuery('identity_number'),
            '{date_of_birth}' => $this->request->getQuery('date_of_birth')
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
        if ($this->request->getParam('action') == 'setAlert') {
            return true;
        }
    }

    public function setAlert()
    {
        $this->autoRender = false;
        if ($this->request->getQuery('message') && $this->request->getQuery('alertType')) {
            $alertType = $this->request->getQuery('alertType');
            $alertMessage = $this->request->getQuery('message');
            $this->Alert->$alertType($alertMessage);
        }
    }

    // Start POCOR-7507
    public function ExternalDataSourceIdentity()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigExternalDataSource']);
    }
    // End POCOR-7507
    //POCOR-7531 start
    public function ExternalDataSourceExams()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigExternalDataSourceExam']);
    }
      //POCOR-7531 end

    public function getConfigItemValue()
    {
        $requestData = $this->request->input('json_decode', true);
        $requestDataParams = $requestData['params'];
        $ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
        $ConfigItemsData = $ConfigItemsTable->findByCode($requestDataParams)->first();

        if ($ConfigItemsData) {
            $configItemValue = !empty($ConfigItemsData->value) ? $ConfigItemsData->value : $ConfigItemsData->default_value;
            echo json_encode($configItemValue, JSON_PARTIAL_OUTPUT_ON_ERROR); die;
        } else {
            throw new NotFoundException('Configuration item not found');
        }
    }

    public function ExternalDataSourceLMS()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ExternalDataSourceLMS']);
    }
}
