<?php
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CookieComponent;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use SSO\ProcessToken;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * An authentication adapter for authenticating using JSON Web Tokens.
 *
 * ```
 *  $this->Auth->config('authenticate', [
 *      'Cookie' => [
 *          'userModel' => 'Users',
 *          'fields' => [
 *              'username' => 'id'
 *          ],
 *          'scope' => ['Users.active' => 1],
 *          'crypt' => 'aes',
 *          'cookie' => [
 *              'name' => 'CookieAuth',
 *              'time' => '+2 weeks',
 *          ]
 *      ]
 *  ]);
 *
 */
class CookieAuthenticate extends BaseAuthenticate
{

    public function implementedEvents()
    {
        $event['Auth.logout'] = 'logout';
        return $event;
    }
    /**
     * Constructor.
     *
     * Settings for this object.
     *
     * - `allowedAlgs` - List of supported verification algorithms.
     *   Defaults to ['HS256']. See API of JWT::decode() for more info.
     * - `userModel` - The model name of users, defaults to `Users`.
     * - `fields` - Key `username` denotes the identifier field for fetching user
     *   record. The `sub` claim of JWT must contain identifier value.
     *   Defaults to ['username' => 'id'].
     * - `finder` - Finder method.
     * - `key` - The key, or map of keys used to decode JWT. If not set, value
     *   of Security::salt() will be used.
     *
     * @param \Cake\Controller\ComponentRegistry $registry The Component registry
     *   used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, $config)
    {
        $this->_registry = $registry;
        $this->config([
            'cookie' => [
                'name' => 'CookieAuth',
                'expires' => '+2 weeks'
            ],
            'crypt' => 'aes'
        ]);

        $this->config([
            'allowedAlgs' => ['HS256'],
            'fields' => ['username' => 'id'],
            'key' => null
        ]);
        parent::__construct($registry, $config);
    }

    public function getUser(Request $request)
    {
        $cookieName = $this->_config['cookie']['name'];
        $this->_registry->Cookie->configKey($this->_config['cookie']['name'], $this->_config['cookie']);
        $data = $this->_registry->Cookie->read($this->_config['cookie']['name']);
        if (empty($data)) {
            return false;
        }
        extract($this->_config['fields']);
        try {
            $processedToken = ProcessToken::decodeToken($data);
            $data = $processedToken->sub;
            if (empty($data->$username)) {
                return false;
            } else {
                $authType = $processedToken->auth_type;

                if ($authType != $this->_config['authType']) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        $user = $this->_findUser($data->$username);

        if (!$user) {
            TableRegistry::get($this->_config['userModel']);
            $userName = $data->$username;
            $userInfo = json_decode(json_encode($data), true);
            $userInfo['dateOfBirth'] = $userInfo['date_of_birth'];
            $userInfo['firstName'] = $userInfo['first_name'];
            $userInfo['lastName'] = $userInfo['last_name'];
            $User = TableRegistry::get($this->_config['userModel']);
            $event = $User->dispatchEvent('Model.Auth.createAuthorisedUser', [$userName, $userInfo], $this);
            if ($event->result === false) {
                return false;
            } else {
                return $this->_findUser($event->result);
            }
        }
        return $user;
    }

    /**
     * Authenticates the identity contained in a request. Will use the `config.userModel`, and `config.fields`
     * to find POST data that is used to find a matching record in the `config.userModel`. Will return false if
     * there is no post data, either username or password is missing, or if the scope conditions have not been met.
     *
     * @param \Cake\Network\Request $request The request that contains login information.
     * @param \Cake\Network\Response $response Unused response object.
     * @return mixed False on login failure.  An array of User data on success.
     */
    public function authenticate(Request $request, Response $response)
    {
        $user = $this->getUser($request);
        return $user;
    }

    public function logout(Event $event, array $user)
    {
        $this->_registry->Cookie->configKey($this->_config['cookie']['name'], $this->_config['cookie']);
        $this->_registry->Cookie->delete($this->_config['cookie']['name']);
    }
}
