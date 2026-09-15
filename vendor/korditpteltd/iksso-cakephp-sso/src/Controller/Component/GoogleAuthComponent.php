<?php
declare(strict_types=1);

namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Google\Auth\Exception as GoogleAuthException;
use Google\Client as GoogleClient;

class GoogleAuthComponent extends Component
{
    /**
     * Components used by this component.
     *
     * @var array<string>
     */
    public $components = ['Auth'];

    /**
     * Google OAuth client ID.
     *
     * @var string
     */
    private string $clientId = '';

    /**
     * Google OAuth client secret.
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
     * Google hosted domain.
     *
     * @var string
     */
    private string $hostedDomain = '';

    /**
     * Google OAuth client.
     *
     * @var \Google\Client|null
     */
    private ?GoogleClient $client = null;

    /**
     * Authentication type/hash.
     *
     * @var string
     */
    private string $authType = '';

    /**
     * Whether users can be created automatically.
     *
     * @var bool
     */
    private bool $createUser = false;

    /**
     * Request object.
     *
     * @var \Cake\Http\ServerRequest
     */
    private $request;

    /**
     * Controller instance.
     *
     * @var \Cake\Controller\Controller
     */
    private $controller;

    /**
     * Session object.
     *
     * @var \Cake\Http\Session
     */
    private $session;

    /**
     * Initialize component.
     *
     * @param array $config Configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->getController();
        $this->request = $this->controller->getRequest();
        $this->session = $this->request->getSession();

        $googleAttributes = $config['authAttribute'] ?? [];
        $mappingAttributes = $config['mappingAttribute'] ?? [];

        $this->clientId = (string)($googleAttributes['client_id'] ?? '');
        $this->clientSecret = (string)($googleAttributes['client_secret'] ?? '');
        $this->redirectUri = (string)($googleAttributes['redirect_uri'] ?? '');
        $this->hostedDomain = (string)($googleAttributes['hd'] ?? '');

        $this->createUser = (bool)($mappingAttributes['allow_create_user'] ?? false);

        /*
         * Generate a hash based on the Google configuration.
         *
         * redirect_uri is intentionally excluded because it can vary
         * between environments.
         */
        $hashAttributes = $googleAttributes;
        unset($hashAttributes['redirect_uri']);

        $this->authType = hash(
            'sha256',
            serialize($hashAttributes)
        );

        $this->session->write(
            'Google.hostedDomain',
            $this->hostedDomain
        );

        $client = new GoogleClient();

        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes([
            'openid',
            'email',
            'profile',
        ]);
        $client->setAccessType('offline');

        if ($this->hostedDomain !== '') {
            $client->setHostedDomain($this->hostedDomain);
        }

        $this->client = $client;

        /*
         * Keep the existing Auth component configuration because
         * OpenEMIS is currently using the legacy Auth-based SSO flow.
         */
        $this->Auth->setConfig('authenticate', [
            'Form' => [
                'userModel' => $config['userModel'] ?? 'Users',
                'passwordHasher' => [
                    'className' => 'Fallback',
                    'hashers' => [
                        'Default',
                        'Legacy',
                    ],
                ],
            ],
            'SSO.Google' => [
                'userModel' => $config['userModel'] ?? 'Users',
                'createUser' => $this->createUser,
                'authAttribute' => $googleAttributes,
                'mappingAttribute' => $mappingAttributes,
            ],
        ]);
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
     * Start Google OAuth login flow.
     *
     * @return bool|array
     */
    private function idpLogin()
    {
        if ($this->client === null) {
            return false;
        }

        $client = $this->client;

        /*
         * If Google has returned an authorization code, exchange it
         * for an access token.
         */
        $code = $this->request->getQuery('code');

        if (!empty($code)) {
            try {
                $client->authenticate($code);
            } catch (GoogleAuthException $e) {
                return false;
            }

            $accessToken = $client->getAccessToken();

            if (!empty($accessToken)) {
                $this->session->write(
                    'Google.accessToken',
                    $accessToken
                );
            }
        }

        /*
         * If an access token exists, continue authentication.
         * Otherwise generate the Google authentication URL.
         */
        $accessToken = $this->session->read('Google.accessToken');

        if (!empty($accessToken)) {
            if ($this->Auth->user()) {
                $client->setAccessToken($accessToken);
            } else {
                /*
                 * Revoke the token if the user is not authorized.
                 */
                try {
                    $client->revokeToken($accessToken);
                } catch (\Throwable $e) {
                    // Token may already be invalid or revoked.
                }

                $this->session->delete('Google.accessToken');

                $this->Auth->logout();

                if ($this->session->read('Google.reLogin')) {
                    $authUrl = $client->createAuthUrl();

                    $this->session->write(
                        'Google.reLogin',
                        false
                    );

                    return $this->redirectToGoogle($authUrl);
                }
            }
        } else {
            $authUrl = $client->createAuthUrl();

            return $this->redirectToGoogle($authUrl);
        }

        /*
         * Retrieve and verify the ID token.
         */
        $clientAccessToken = $client->getAccessToken();

        if (!empty($clientAccessToken)) {
            /*
             * Check if the access token has expired.
             */
            if (!$client->isAccessTokenExpired()) {
                $this->session->write(
                    'Google.accessToken',
                    $clientAccessToken
                );

                $tokenData = $client->verifyIdToken();

                /*
                 * Depending on the installed Google API client version,
                 * verifyIdToken() may return an object or an array.
                 */
                if (
                    is_object($tokenData) &&
                    method_exists($tokenData, 'getAttributes')
                ) {
                    $tokenData = $tokenData->getAttributes();
                }

                if (is_array($tokenData)) {
                    /*
                     * Some Google API versions return:
                     *
                     * [
                     *     'payload' => [...]
                     * ]
                     *
                     * while others expose the payload directly.
                     */
                    $payload = $tokenData['payload'] ?? $tokenData;

                    if (!is_array($payload)) {
                        return false;
                    }

                    /*
                     * Verify the hosted domain when configured.
                     */
                    if (
                        $this->hostedDomain !== '' &&
                        isset($payload['hd']) &&
                        $payload['hd'] !== $this->hostedDomain
                    ) {
                        return false;
                    }

                    $username = $payload['email'] ?? null;

                    if (empty($username)) {
                        return false;
                    }

                    return $this->checkLogin($username);
                }
            } else {
                $authUrl = $client->createAuthUrl();

                return $this->redirectToGoogle($authUrl);
            }
        }

        return false;
    }

    /**
     * Redirect the user to Google authentication.
     *
     * @param string $url Authentication URL.
     * @return mixed
     */
    private function redirectToGoogle(string $url)
    {
        return $this->controller->redirect($url);
    }

    /**
     * Authenticate event handler.
     *
     * @param \Cake\Event\EventInterface $event Event object.
     * @param \ArrayObject $extra Additional data.
     * @return mixed
     */
    public function authenticate(EventInterface $event, ArrayObject $extra)
    {
        return $this->idpLogin();
    }

    /**
     * Check whether the authenticated Google user exists and is authorized.
     *
     * @param string|null $username Username/email.
     * @param array $extra Additional login information.
     * @return bool
     */
    private function checkLogin(
        ?string $username = null,
        array $extra = []
    ): bool {
        $remoteAddress = $this->request->getAttribute('clientIp');

        if (empty($remoteAddress)) {
            $remoteAddress = $this->request->getEnv('REMOTE_ADDR') ?? 'unknown';
        }

        $this->log(
            sprintf(
                '[%s] Attempt to login as %s@%s',
                $username ?? '',
                $username ?? '',
                $remoteAddress
            ),
            'debug'
        );

        $user = $this->Auth->identify();

        $extra['status'] = true;
        $extra['loginStatus'] = false;
        $extra['fallback'] = false;

        $statusField = $this->getConfig('statusField');

        if ($user) {
            if (
                !empty($statusField) &&
                isset($user[$statusField]) &&
                (int)$user[$statusField] !== 1
            ) {
                $extra['status'] = true;
            } else {
                $this->Auth->setUser($user);

                $this->session->delete('Google.remoteFail');

                $extra['loginStatus'] = true;
            }
        } else {
            $extra['loginStatus'] = false;

            if (
                $this->session->read('Auth.fallback') ||
                $this->session->read('Google.remoteFail')
            ) {
                $extra['fallback'] = true;
            }
        }

        $this->controller->dispatchEvent(
            'Controller.Auth.afterCheckLogin',
            [$extra],
            $this
        );

        return $extra['loginStatus'];
    }
}