<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use OneLogin\Saml2\Auth;

class SamlAuthComponent extends Component
{
    public $components = ['Auth'];

    private $saml;
    private $clientId;
    private $authType;
    private $createUser;
    private $userNameField;
    private $controller;
    private $session;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->getController();
        $request = $this->controller->getRequest();
        $this->session = $request->getSession();

        $settings = [];

        $returnUrl = Router::url(
            [
                'plugin' => null,
                'controller' => 'Users',
                'action' => 'postLogin'
            ],
            true
        );

        $logout = Router::url(
            [
                'plugin' => null,
                'controller' => 'Users',
                'action' => 'logout'
            ],
            true
        );

        $IdpSamlTable = TableRegistry::getTableLocator()->get('SSO.IdpSaml');

        $samlAttributes = $config['authAttribute'];
        $mappingAttributes = $config['mappingAttribute'];

        $setting['sp'] = [
            'entityId' => $samlAttributes['sp_entity_id'],
            'assertionConsumerService' => [
                'url' => $samlAttributes['sp_acs'],
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['sp_slo'],
            ],
            'NameIDFormat' => $samlAttributes['sp_name_id_format'],
        ];

        $setting['idp'] = [
            'entityId' => $samlAttributes['idp_entity_id'],
            'singleSignOnService' => [
                'url' => $samlAttributes['idp_sso'],
                'binding' => $samlAttributes['idp_sso_binding'],
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['idp_slo'],
                'binding' => $samlAttributes['idp_slo_binding'],
            ],
        ];

        // Native PHP hash() is preferred over CakePHP Security::hash()
        $this->authType = hash(
            'sha256',
            serialize($setting['idp'])
        );

        $this->addCertFingerPrintInformation($setting, $samlAttributes);

        $this->clientId = $samlAttributes['idp_entity_id'];

        $this->userNameField = $mappingAttributes['mapped_username'];

        $this->createUser = $mappingAttributes['allow_create_user'];

        $this->saml = new Auth($setting);

        $this->Auth->setConfig('authenticate', [
            'Form' => [
                'userModel' => $this->getConfig('userModel'),
                'passwordHasher' => [
                    'className' => 'Fallback',
                    'hashers' => ['Default', 'Legacy'],
                ],
            ],
            'SSO.Saml' => [
                'userModel' => $this->getConfig('userModel'),
                'createUser' => $this->createUser,
                'authAttribute' => $samlAttributes,
                'mappedFields' => $mappingAttributes,
            ],
        ]);
    }

    /**
     * Add SAML certificate/fingerprint information to the settings.
     */
    private function addCertFingerPrintInformation(
        array &$setting,
        array $attributes
    ): void {
        $arr = [
            'certFingerprint' => 'idp_cert_fingerprint',
            'certFingerprintAlgorithm' => 'idp_cert_fingerprint_algorithm',
            'x509cert' => 'idp_x509cert',
            'privateKey' => 'sp_private_key',
        ];

        foreach ($arr as $cert => $value) {
            if (!empty($attributes[$value])) {
                $type = explode('_', $value)[0];
                $setting[$type][$cert] = $attributes[$value];
            }
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.Auth.authenticate'] = 'authenticate';

        return $events;
    }

    private function idpLogin(): bool
    {
        try {
            $this->processResponse();

            return $this->isAuthenticated();
        } catch (\Exception $e) {
            $this->login();

            return false;
        }
    }

    /**
     * Initiates the SSO process.
     *
     * @param string|null $returnTo The target URL the user should be returned to after login.
     * @param array $parameters Extra parameters to be added to the GET.
     * @param bool $forceAuthn When true the AuthNRequest will set ForceAuthn='true'.
     * @param bool $isPassive When true the AuthNRequest will set IsPassive='true'.
     */
    public function login(
        ?string $returnTo = null,
        array $parameters = [],
        bool $forceAuthn = false,
        bool $isPassive = false
    ): void {
        $this->saml->login(
            $returnTo,
            $parameters,
            $forceAuthn,
            $isPassive
        );
    }

    /**
     * Initiates the SLO process.
     *
     * @param string|null $returnTo The target URL the user should be returned to after logout.
     * @param array $parameters Extra parameters to be added to the GET.
     * @param string|null $nameId The NameID that will be set in the LogoutRequest.
     * @param string|null $sessionIndex The SessionIndex.
     */
    public function logout(
        ?string $returnTo = null,
        array $parameters = [],
        ?string $nameId = null,
        ?string $sessionIndex = null
    ): void {
        $this->saml->logout(
            $returnTo,
            $parameters,
            $nameId,
            $sessionIndex
        );
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * @param string|null $requestId The ID of the AuthNRequest sent by this SP.
     */
    public function processResponse(?string $requestId = null): void
    {
        $this->saml->processResponse($requestId);
    }

    /**
     * Returns if there were any errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->saml->getErrors();
    }

    /**
     * Checks if the user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->saml->isAuthenticated();
    }

    /**
     * Returns the set of SAML attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->saml->getAttributes();
    }

    /**
     * Returns the requested SAML attribute.
     *
     * @param string $name
     * @return array|null
     */
    public function getAttribute(string $name): ?array
    {
        return $this->saml->getAttribute($name);
    }

    public function authenticate(
        EventInterface $event,
        ArrayObject $extra
    ) {
        $extra['authType'] = $this->authType;

        if ($this->Auth->user()) {
            return true;
        }

        if ($this->idpLogin()) {
            $userData = $this->getAttributes();

            if (isset($userData[$this->userNameField][0])) {
                $userName = $userData[$this->userNameField][0];

                $this->session->write(
                    'Saml.userAttribute',
                    $userData
                );

                return $this->checkLogin($userName);
            }

            $this->session->write('Auth.fallback', true);

            return false;
        }

        $this->session->write('Auth.fallback', true);

        return false;
    }

    private function checkLogin(
        $username = null,
        array $extra = []
    ): bool {
        $request = $this->controller->getRequest();

        $clientIp = $request->clientIp();

        $this->log(
            '[' . $username . '] Attempt to login as ' .
            $username . '@' . $clientIp,
            'debug'
        );

        $user = $this->Auth->identify();

        $extra['status'] = true;
        $extra['loginStatus'] = false;
        $extra['fallback'] = false;

        if ($user) {
            $statusField = $this->getConfig('statusField');

            if (($user[$statusField] ?? null) != 1) {
                $this->session->write('Auth.fallback', true);
                $extra['status'] = false;
            } else {
                $this->Auth->setUser($user);

                $extra['loginStatus'] = true;
            }
        } else {
            $this->session->write('Saml.remoteFail', true);
        }

        if ($this->session->read('Auth.fallback')) {
            $extra['fallback'] = true;
        }

        $this->controller->dispatchEvent(
            'Controller.Auth.afterCheckLogin',
            [$extra],
            $this
        );

        return $extra['loginStatus'];
    }
}