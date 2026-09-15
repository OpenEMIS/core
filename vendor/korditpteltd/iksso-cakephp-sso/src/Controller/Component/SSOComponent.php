<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class SSOComponent extends Component
{
    private $controller;
    private $session;
    private $authType = 'Local';

    public $components = ['Auth'];

    protected $_defaultConfig = [
        'excludedAuthType' => [],
        'homePageURL' => null,
        'loginPageURL' => null,
        'loginAction' => 'login',
        'cookieAuth' => [
            'username' => 'openemis_no',
            'enabled' => true,
        ],
        'restful' => false,
        'cookie' => [
            'name' => 'CookieAuth',
            'path' => '/',
            'expires' => '+2 weeks',
            'domain' => '',
            'encryption' => false,
        ],
        'userModel' => 'Users',
        'statusField' => 'status',
        'recordKey' => null,
    ];

    /**
     * Is called before the controller's beforeFilter method.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->getController();
        $this->session = $this->controller
            ->getRequest()
            ->getSession();
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();

        $events['Controller.Auth.afterAuthenticate'] = 'afterAuthenticate';

        return $events;
    }

    public function getAuthenticationType()
    {
        return $this->authType;
    }

    public function doAuthentication(
        string $authenticationType = 'Local',
        $code = null
    ) {
        if ($authenticationType !== 'Local') {
            $systemAuthenticationsTable = TableRegistry::getTableLocator()
                ->get('SSO.SystemAuthentications');

            $attribute = $systemAuthenticationsTable
                ->find()
                ->contain([$authenticationType])
                ->where([
                    $systemAuthenticationsTable->aliasField('code') => $code,
                ])
                ->enableHydration(false)
                ->first();

            if (!empty($attribute) && !empty($attribute['status'])) {
                $authKey = Inflector::underscore($authenticationType);

                $authAttribute = $attribute[$authKey];

                unset($attribute[$authKey]);

                $mappingAttribute = $attribute;

                $this->setConfig([
                    'authAttribute' => $authAttribute,
                    'mappingAttribute' => $mappingAttribute,
                    'recordKey' => $attribute['id'],
                ]);
            } else {
                $authenticationType = 'Local';
            }
        }

        $this->controller->loadComponent(
            'SSO.' . $authenticationType . 'Auth',
            $this->getConfig()
        );

        $extra = new ArrayObject([]);

        // $this->controller->dispatchEvent(
        //     'Controller.Auth.beforeAuthenticate',
        //     [$extra],
        //     $this
        // );

        $event = $this->controller->dispatchEvent(
            'Controller.Auth.authenticate',
            [$extra],
            $this
        );

        if ($event->getResult()) {
            $this->controller->dispatchEvent(
                'Controller.Auth.afterAuthenticate',
                [$extra],
                $this
            );

            $event = $this->controller->dispatchEvent(
                'Controller.Auth.beforeRedirection',
                [$extra],
                $this
            );

            if (!$event->getResult()) {
                return $this->controller->redirect(
                    $this->getConfig('homePageURL')
                );
            }
        }

        return $this->controller->redirect(
            $this->getConfig('homePageURL')
        );
    }

    public function afterAuthenticate(
        EventInterface $event,
        ArrayObject $extra
    ): void {
        $request = $this->getController()->getRequest();
        $user = $this->Auth->user();

        if ($user) {
            $request->trustProxy = true;

            $clientIp = $request->clientIp();
            $sessionId = $request->getSession()->id();

            TableRegistry::getTableLocator()
                ->get('SSO.SecurityUserLogins')
                ->addLoginEntry(
                    $user['id'],
                    $clientIp,
                    $sessionId
                );
        }
    }
}