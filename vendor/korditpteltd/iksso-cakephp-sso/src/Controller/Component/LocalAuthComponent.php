<?php
declare(strict_types=1);

namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;

class LocalAuthComponent extends Component
{
    /**
     * Components used by this component.
     *
     * @var array<string>
     */
    public $components = [
        'Auth',
        'Alert',
    ];

    /**
     * Default component configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'homePageURL' => null,
        'loginPageURL' => null,
    ];

    /**
     * Define events handled by this component.
     *
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();

        //$events['Controller.Auth.beforeAuthenticate'] = 'beforeAuthenticate';
        $events['Controller.Auth.authenticate'] = 'authenticate';

        return $events;
    }

    /**
     * Component beforeFilter event.
     *
     * @param \Cake\Event\EventInterface $event Event object.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        $controller = $this->getController();

        $controller->Auth->setConfig('authenticate', [
            'Form' => [
                'userModel' => $this->getConfig('userModel'),
                'passwordHasher' => [
                    'className' => 'Fallback',
                    'hashers' => [
                        'Default',
                        'Legacy',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Authenticate user.
     *
     * @param \Cake\Event\EventInterface $event Event object.
     * @param \ArrayObject $extra Additional authentication data.
     * @return mixed
     */
    public function authenticate(
        EventInterface $event,
        ArrayObject $extra
    ) {
        $controller = $this->getController();
        $request = $controller->getRequest();

        if (!$request->is('post')) {
            $homePageURL = $this->getConfig('homePageURL');

            if (!empty($homePageURL)) {
                return $controller->redirect($homePageURL);
            }

            return false;
        }

        $submit = $request->getData('submit');

        if ($submit === 'login') {
            $username = $request->getData('username');

            return $this->checkLogin($username);
        }

        if ($submit === 'reload') {
            $username = $request->getData('username');
            $password = $request->getData('password');

            $session = $request->getSession();

            $session->write('login.username', $username);
            $session->write('login.password', $password);

            $loginPageURL = $this->getConfig('loginPageURL');

            if (!empty($loginPageURL)) {
                return $controller->redirect($loginPageURL);
            }

            return false;
        }

        return false;
    }

    /**
     * Check user login.
     *
     * @param string|null $username Username.
     * @param array $extra Additional authentication data.
     * @return bool
     */
    private function checkLogin(
        ?string $username = null,
        array $extra = []
    ): bool {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $session = $request->getSession();

        $remoteAddress = $request->getAttribute('clientIp');

        if (empty($remoteAddress)) {
            $remoteAddress = $request->getEnv('REMOTE_ADDR') ?? 'unknown';
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
                $extra['status'] = false;
            } else {
                $this->Auth->setUser($user);

                /*
                 * Rehash the password when required by the
                 * authentication provider.
                 */
                $authenticationProvider = $this->Auth->authenticationProvider();

                if (
                    $authenticationProvider &&
                    $authenticationProvider->needsPasswordRehash()
                ) {
                    $userModel = $this->getConfig('userModel');

                    if (!empty($userModel)) {
                        $this->Users = TableRegistry::getTableLocator()->get(
                            $userModel
                        );

                        $userId = $this->Auth->user('id');

                        if ($userId !== null) {
                            $userEntity = $this->Users->get($userId);

                            $password = $request->getData('password');

                            if (!empty($password)) {
                                $userEntity->password = $password;
                                $this->Users->save($userEntity);
                            }
                        }
                    }
                }

                $extra['loginStatus'] = true;
            }
        }

        $controller->dispatchEvent(
            'Controller.Auth.afterCheckLogin',
            [$extra],
            $this
        );

        return (bool)$extra['loginStatus'];
    }
}
