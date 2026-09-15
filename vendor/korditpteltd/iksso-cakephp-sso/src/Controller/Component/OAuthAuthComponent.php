<?php
declare(strict_types=1);

namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use SSO\OAuth\Custom_Client;

class OAuthAuthComponent extends Component
{
    /**
     * Components used by this component.
     *
     * @var array<string>
     */
    public $components = ['Auth'];

    /**
     * OAuth client ID.
     *
     * @var string
     */
    private string $clientId = '';

    /**
     * OAuth client secret.
     *
     * @var string
     */
    private string $clientSecret = '';

    /**
     * OAuth redirect URI.
     *
     * @var string
     */
    private string $redirectUri = '';

    /**
     * OAuth client.
     *
     * @var \SSO\OAuth\Custom_Client|null
     */
    private ?Custom_Client $client = null;

    /**
     * Authentication type/hash.
     *
     * @var string
     */
    private string $authType = '';

    /**
     * Attribute mapping.
     *
     * @var array<string, mixed>
     */
    private array $mapping = [];

    /**
     * User information endpoint.
     *
     * @var string
     */
    private string $userInfoUri = '';

    /**
     * Whether a user can be created automatically.
     *
     * @var bool
     */
    private bool $createUser = false;

    /**
     * Controller instance.
     *
     * @var \Cake\Controller\Controller
     */
    private $controller;

    /**
     * Request instance.
     *
     * @var \Cake\Http\ServerRequest
     */
    private $request;

    /**
     * Session instance.
     *
     * @var \Cake\Http\Session
     */
    private $session;

    /**
     * Retry message.
     *
     * @var string
     */
    private string $retryMessage = '';

    /**
     * Initialize component.
     *
     * @param array $config Component configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->getController();
        $this->request = $this->controller->getRequest();
        $this->session = $this->request->getSession();

        $oAuthAttributes = $config['authAttribute'] ?? [];
        $mappingAttributes = $config['mappingAttribute'] ?? [];

        $this->clientId = (string)($oAuthAttributes['client_id'] ?? '');
        $this->clientSecret = (string)($oAuthAttributes['client_secret'] ?? '');

        /*
         * Load OpenID Connect discovery configuration when
         * well_known_uri is configured.
         */
        if (!empty($oAuthAttributes['well_known_uri'])) {
            $this->loadOpenIdConfiguration(
                $oAuthAttributes
            );
        }

        $this->redirectUri = (string)(
            $oAuthAttributes['redirect_uri'] ?? ''
        );

        $this->userInfoUri = (string)(
            $oAuthAttributes['userinfo_endpoint'] ?? ''
        );

        /*
         * Store OAuth attribute mappings.
         */
        $this->mapping = [
            'username' => $mappingAttributes['mapped_username'] ?? '',
            'firstName' => $mappingAttributes['mapped_first_name'] ?? '',
            'lastName' => $mappingAttributes['mapped_last_name'] ?? '',
            'dob' => $mappingAttributes['mapped_date_of_birth'] ?? '',
            'gender' => $mappingAttributes['mapped_gender'] ?? '',
            'email' => $mappingAttributes['mapped_email'] ?? '',
        ];

        $this->createUser = (bool)(
            $mappingAttributes['allow_create_user'] ?? false
        );

        /*
         * Generate authentication type/hash.
         *
         * redirect_uri is excluded because it may vary between
         * environments.
         */
        $hashAttributes = $oAuthAttributes;
        unset($hashAttributes['redirect_uri']);

        $this->authType = hash(
            'sha256',
            serialize($hashAttributes)
        );

        /*
         * Create OAuth client.
         */
        $client = new Custom_Client(
            null,
            $oAuthAttributes
        );

        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes([
            'openid',
            'email',
            'profile',
        ]);
        $client->setAccessType('offline');

        $this->client = $client;

        $this->retryMessage =
            'Remote authentication failed. <br>'
            . 'Please try local login or '
            . '<a href="'
            . h($this->redirectUri)
            . '?submit=retry">Click here</a> '
            . 'to try again';

        /*
         * Configure the legacy Auth component.
         *
         * This is retained because the OpenEMIS SSO implementation
         * is currently using the existing Auth component architecture.
         */
        $userModel = $this->getConfig('userModel');

        $this->Auth->setConfig('authenticate', [
            'Form' => [
                'userModel' => $userModel,
                'passwordHasher' => [
                    'className' => 'Fallback',
                    'hashers' => [
                        'Default',
                        'Legacy',
                    ],
                ],
            ],
            'SSO.OAuth' => [
                'userModel' => $userModel,
                'mappingAttribute' => $mappingAttributes,
                'authAttribute' => $oAuthAttributes,
                'createUser' => $this->createUser,
            ],
        ]);
    }

    /**
     * Load OpenID Connect discovery configuration.
     *
     * @param array $oAuthAttributes OAuth configuration.
     * @return void
     */
    private function loadOpenIdConfiguration(
        array &$oAuthAttributes
    ): void {
        $http = new Client();

        try {
            $response = $http->get(
                $oAuthAttributes['well_known_uri'],
                [],
                [
                    'redirect' => 3,
                ]
            );
        } catch (\Throwable $e) {
            return;
        }

        if ($response->getStatusCode() !== 200) {
            return;
        }

        $body = $response->getStringBody();

        if ($body === '') {
            return;
        }

        $configuration = json_decode(
            $body,
            true
        );

        if (!is_array($configuration)) {
            return;
        }

        $isChange = false;

        /*
         * Map OpenID Connect discovery properties to the
         * configuration used by the application.
         */
        $attributeMap = [
            'issuer' => 'issuer',
            'authorization_endpoint' => 'authorization_endpoint',
            'token_endpoint' => 'token_endpoint',
            'userinfo_endpoint' => 'userinfo_endpoint',
            'jwks_uri' => 'jwk_uri',
        ];

        foreach ($attributeMap as $discoveryKey => $configKey) {
            if (!isset($configuration[$discoveryKey])) {
                continue;
            }

            $value = $configuration[$discoveryKey];

            if (
                !isset($oAuthAttributes[$configKey]) ||
                $oAuthAttributes[$configKey] !== $value
            ) {
                $oAuthAttributes[$configKey] = $value;
                $isChange = true;
            }
        }

        /*
         * Save the updated OpenID configuration.
         */
        if (
            $isChange &&
            isset($oAuthAttributes['system_authentication_id'])
        ) {
            try {
                $OAuthTable = TableRegistry::getTableLocator()->get(
                    'SSO.IdpOauth'
                );

                $entity = $OAuthTable->get(
                    [
                        'system_authentication_id' =>
                            $oAuthAttributes['system_authentication_id'],
                    ]
                );

                $entity = $OAuthTable->patchEntity(
                    $entity,
                    $oAuthAttributes
                );

                $OAuthTable->save($entity);
            } catch (\Throwable $e) {
                /*
                 * Discovery information should not prevent
                 * the OAuth login flow from continuing.
                 */
            }
        }
    }

    /**
     * Define events handled by this component.
     *
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();

        $events['Controller.Auth.authenticate'] = 'authenticate';

        return $events;
    }

    /**
     * Start OAuth login flow.
     *
     * @return mixed
     */
    private function idpLogin()
    {
        if ($this->client === null) {
            return false;
        }

        $client = $this->client;

        /*
         * Handle OAuth authorization code.
         */
        $code = $this->request->getQuery('code');

        if (!empty($code)) {
            try {
                $client->authenticate($code);
            } catch (\Throwable $e) {
                return false;
            }

            $accessToken = $client->getAccessToken();

            if (!empty($accessToken)) {
                $this->session->write(
                    'OAuth.accessToken',
                    $accessToken
                );
            }
        }

        /*
         * Continue with an existing access token.
         */
        $accessToken = $this->session->read(
            'OAuth.accessToken'
        );

        if (!empty($accessToken)) {
            if ($this->Auth->user()) {
                $client->setAccessToken($accessToken);
            } else {
                /*
                 * Remove the token if the user is not authenticated.
                 *
                 * revokeToken() was intentionally not enabled in the
                 * original implementation.
                 */
                $this->session->delete(
                    'OAuth.accessToken'
                );

                $this->Auth->logout();

                if ($this->session->read('OAuth.reLogin')) {
                    $authUrl = $client->createAuthUrl();

                    $this->session->write(
                        'OAuth.reLogin',
                        false
                    );

                    return $this->controller->redirect(
                        $authUrl
                    );
                }
            }
        } else {
            $authUrl = $client->createAuthUrl();

            return $this->controller->redirect(
                $authUrl
            );
        }

        /*
         * Check the access token and authenticate the user.
         */
        $clientAccessToken = $client->getAccessToken();

        if (!empty($clientAccessToken)) {
            if (!$client->isAccessTokenExpired()) {
                $this->session->write(
                    'OAuth.accessToken',
                    $clientAccessToken
                );

                return $this->checkLogin();
            }

            $authUrl = $client->createAuthUrl();

            return $this->controller->redirect(
                $authUrl
            );
        }

        return false;
    }

    /**
     * Authenticate event handler.
     *
     * @param \Cake\Event\EventInterface $event Event object.
     * @param \ArrayObject $extra Additional authentication data.
     * @return mixed
     */
    public function authenticate(
        EventInterface $event,
        ArrayObject $extra
    ) {
        return $this->idpLogin();
    }

    /**
     * Check OAuth user login.
     *
     * @param string|null $username Username.
     * @param array $extra Additional authentication data.
     * @return bool
     */
    private function checkLogin(
        ?string $username = null,
        array $extra = []
    ): bool {
        $user = $this->Auth->identify();

        $extra['status'] = true;
        $extra['loginStatus'] = false;
        $extra['fallback'] = false;

        $statusField = $this->getConfig('statusField');

        if ($user) {
            /*
             * User must be active.
             */
            if (
                !empty($statusField) &&
                isset($user[$statusField]) &&
                (int)$user[$statusField] !== 1
            ) {
                $extra['status'] = true;
            } else {
                $this->Auth->setUser($user);

                $this->session->delete(
                    'OAuth.remoteFail'
                );

                $extra['loginStatus'] = true;
            }
        } else {
            $extra['loginStatus'] = false;

            if (
                $this->session->read('Auth.fallback') ||
                $this->session->read('OAuth.remoteFail')
            ) {
                $extra['fallback'] = true;
            }
        }

        $this->controller->dispatchEvent(
            'Controller.Auth.afterCheckLogin',
            [$extra],
            $this
        );

        return (bool)$extra['loginStatus'];
    }
}