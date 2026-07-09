<?php
namespace Configuration\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Http\Client;
use Cake\Http\ServerRequest;
use Page\Traits\EncodingTrait;
use Cake\Event\EventInterface;
use Configuration\Model\Table\ConfigExternalDataSourceTable; //POCOR-9590: source name constants

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
        $this->redirect(['plugin' => 'Configuration', 'controller' => 'Webhooks', 'action' => 'Webhooks']); //POCOR-9257: redirect to standalone WebhooksController
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

    public function isActionIgnored(EventInterface $event, $action) //POCOR-9509: CakePHP 5 - Event → EventInterface
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

    //POCOR-9590: Test Connection endpoint - returns JSON with connection status for the active external data source
    public function testExternalConnection()
    {
        $this->autoRender = false;
        $this->response = $this->response->withType('application/json');

        $ExternalAttrs = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        $ConfigItems   = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        //POCOR-9590: pick the first enabled External Data Source Identity (same per-source enable
        //convention the wizards use — value='1' means enabled, name is the source label).
        $activeItem = $ConfigItems->find()
            ->where([
                'type' => 'External Data Source - Identity',
                'value' => '1',
            ])
            ->first();

        if (!$activeItem) {
            $this->response->getBody()->write(json_encode(['status' => 'no_config', 'message' => 'No external data source is enabled']));
            return $this->response;
        }

        $sourceType = $activeItem->name;
        //POCOR-9590: 'Seychellois' is an alias for 'Seychelles Civil Status' in some deployments — normalize early so DB lookups and routing both work
        if ($sourceType === ConfigExternalDataSourceTable::SOURCE_SEYCHELLOIS) {
            $sourceType = ConfigExternalDataSourceTable::SOURCE_SEYCHELLES;
        }

        $attrs = $ExternalAttrs->find('list', ['keyField' => 'attribute_field', 'valueField' => 'value'])
            ->where(['external_data_source_type' => $sourceType])
            ->toArray();

        $tokenUri = $attrs['token_uri'] ?? null;

        if (empty($tokenUri)) {
            $this->response->getBody()->write(json_encode(['status' => 'no_address', 'message' => 'Token URI is not configured for ' . $sourceType]));
            return $this->response;
        }

        //POCOR-9590: attempt to reach token URI - just a reachability check (HEAD/GET without credentials)
        try {
            $client = new Client(['timeout' => 5, 'ssl_verify_peer' => false, 'ssl_verify_host' => false]);

            if ($sourceType === ConfigExternalDataSourceTable::SOURCE_SEYCHELLES) {
                //POCOR-9590: Seychelles uses client_credentials OAuth2 - test by posting to token URI
                $clientId     = $attrs['client_id'] ?? '';
                $clientSecret = $attrs['client_secret'] ?? '';
                $grantType    = $attrs['grant_type'] ?? 'client_credentials';
                $scopes       = $attrs['scopes'] ?? '';

                if (empty($clientId) || empty($clientSecret)) {
                    $this->response->getBody()->write(json_encode(['status' => 'credentials_missing', 'message' => 'client_id or client_secret not configured']));
                    return $this->response;
                }

                $tokenResponse = $client->post($tokenUri, [
                    'grant_type'    => $grantType,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'scope'         => $scopes,
                ]);

                $code = $tokenResponse->getStatusCode();
                $body = $tokenResponse->getStringBody();
                $json = json_decode($body, true);

                if ($code === 200 && !empty($json['access_token'])) {
                    $this->response->getBody()->write(json_encode(['status' => 'ok', 'message' => 'Connected to ' . $sourceType . ' — token obtained successfully', 'http_code' => $code]));
                } elseif ($code === 401 || $code === 403) {
                    $this->response->getBody()->write(json_encode(['status' => 'credentials_error', 'message' => 'Address reachable but credentials rejected (HTTP ' . $code . ')', 'http_code' => $code, 'body' => substr($body, 0, 300)]));
                } else {
                    $this->response->getBody()->write(json_encode(['status' => 'address_error', 'message' => 'Address responded with HTTP ' . $code, 'http_code' => $code, 'body' => substr($body, 0, 300)]));
                }

            } else {
                //POCOR-9590: for other sources just do a GET ping on the token URI
                $pingResponse = $client->get($tokenUri);
                $code = $pingResponse->getStatusCode();

                if ($code >= 200 && $code < 500) {
                    $this->response->getBody()->write(json_encode(['status' => 'address_ok', 'message' => 'Address reachable (HTTP ' . $code . ') — full auth test not implemented for ' . $sourceType, 'http_code' => $code]));
                } else {
                    $this->response->getBody()->write(json_encode(['status' => 'address_error', 'message' => 'Address returned HTTP ' . $code, 'http_code' => $code]));
                }
            }
        } catch (\Exception $e) {
            $this->response->getBody()->write(json_encode(['status' => 'no_address', 'message' => 'Cannot reach address: ' . $e->getMessage()]));
        }

        return $this->response;
    }
    //POCOR-9590: end testExternalConnection

    // POCOR-9403
    public function ExternalDataSourceWebhook()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigExternalDataWebhook']);
    }
    // End POCOR-7507
    // Start POCOR-8286
    public function ExternalAlertServiceSMS()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigExternalAlertServiceSms']);
    }
    // End POCOR-8286
    // Start POCOR-9303
    public function PDFService()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigPrintingServicePdf']);
    }
    // End POCOR-9303
    //POCOR-7531 start
    public function ExternalDataSourceExams()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigExternalDataSourceExam']);
    }
      //POCOR-7531 end

    //POCOR-9164[START]
    public function AutoGeneratedCandidateNumber()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigAutoGeneratedCandidateNumber']);
    }
    //POCOR-9164[END]
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
