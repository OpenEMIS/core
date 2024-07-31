<?php
namespace Rest\Controller;

use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Cookie\Cookie;
use Cake\Event\EventInterface;
use Cake\Routing\Router;

class RestController extends AppController
{
    public $SecurityRestSessions = null;
    private $RestVersion = '2.0';

    public function initialize(): void
    {
        parent::initialize();

        $this->ControllerAction->models = [
            'Questions' => ['className' => 'Survey.SurveyQuestions'],
            'Forms' => ['className' => 'Survey.SurveyForms']
        ];
        $this->loadComponent('Paginator');
        $this->loadComponent('Rest.RestSurvey', [
            'models' => [
                // Administration Table
                'Module' => 'CustomField.CustomModules',
                'Field' => 'Survey.SurveyQuestions',
                'FieldOption' => 'Survey.SurveyQuestionChoices',
                'TableColumn' => 'Survey.SurveyTableColumns',
                'TableRow' => 'Survey.SurveyTableRows',
                'Form' => 'Survey.SurveyForms',
                'FormField' => 'Survey.SurveyFormsQuestions',
                // Transaction Table
                'Record' => 'Institution.InstitutionSurveys',
                'FieldValue' => 'Institution.InstitutionSurveyAnswers',
                'TableCell' => 'Institution.InstitutionSurveyTableCells'
            ]
        ]);
        $this->SecurityRestSessions = TableRegistry::get('Rest.SecurityRestSessions');
        $this->loadComponent('Cookie');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->getEventManager()->off($this->Csrf);
        $rootPath = Router::url('/', true);
        /*$rootPath = $_SERVER['REQUEST_URI'];
        $cookieRestful = new \Cake\Http\Cookie\Cookie(
                            'Restful',
                            $rootPath,
                        );
            $this->response->withCookie($cookieRestful);*/
        setcookie("Restful", $rootPath, time() + (86400 * 30), "/");
        $this->Security->setConfig('unlockedActions', ['survey']);
        if (null !== $this->request->getQuery('version')) {
            $this->RestVersion = $this->request->getQuery('version');
        }

        if ($this->RestVersion == 2.0) {
            // Using JWT for authenication
            $this->Auth->setConfig('authenticate', [
                'ADmad/JwtAuth.Jwt' => [
                    'parameter' => 'token',
                    'userModel' => 'User.Users',
                    'scope' => ['Users.status' => 1],
                    'fields' => [
                        'username' => 'id'
                    ],
                    'allowedAlgs' => ['RS256'],
                    'key' => Configure::read('Application.public.key'),
                    'queryDatasource' => true
                ]
            ]);

            $this->Auth->setConfig('storage', 'Memory');
            $this->Auth->setConfig('authorize', null);
            
            if ($this->request->getData('code')) {
                $token = $this->request->getData('code');
                $this->request->withEnv('HTTP_AUTHORIZATION', $token);
               // $this->request = $this->request->withHeader('Authorization', 'Bearer ' . $token);
            }
            $header = $this->request->getHeaderLine('Authorization');
            if ($header) {
                $token = str_ireplace('Bearer ', '', $header);
                try {
                    $payload = JWT::decode($token, Configure::read('Application.public.key'), ['RS256']);
                } catch (ExpiredException $e) {
                    return $this->createJsonResponse(500, ['message' => __('Expired token')]);
                } catch (Exception $e) {
                    return $this->createJsonResponse(500, ['message' => __('Invalid token')]);
                }

                $currentTimeStamp = Time::now()->getTimestamp();
                if ($payload->exp < $currentTimeStamp) {
                    throw new BadRequestException('Custom error message', 408);
                }
            }

            $this->autoRender = false;

            $pass = $this->request->getParam('pass');
            $action = null;

            if (!empty($pass)) {
                $action = array_shift($pass);
            }

            if (!is_null($action) && in_array($action, $this->RestSurvey->allowedActions)) {
                $this->Auth->allow();
            } else {
                $this->Auth->identify();
            }
        } else {
            $this->Auth->allow();
            if ($this->request->getParam('action') == 'survey') {
                $this->autoRender = false;

                $pass = $this->request->getParam('pass');
                $action = null;

                if (!empty($pass)) {
                    $action = array_shift($pass);
                }

                if (!is_null($action) && !in_array($action, $this->RestSurvey->allowedActions)) {
                    // actions require authentication
                    // if authentication is required:
                    // 1. check if token exists
                    // 2. check if current time is greater than expiry time

                    $accessToken = '';
                    if ($this->request->is(['post', 'put'])) {
                        $json = [];
                        $json   = ['message' => 'valid'];
                        /*if (array_key_exists('SecurityRestSession', $this->request->getData())) {
                            if (array_key_exists('access_token', $this->request->getData()['SecurityRestSession'])) {
                                $accessToken = $this->request->getData()['SecurityRestSession']['access_token'];

                                $confirm = $this->SecurityRestSessions
                                    ->find()
                                    ->where([
                                        $this->SecurityRestSessions->aliasField('access_token') => $accessToken
                                    ])
                                    ->first();

                                $current = time();

                                if (!empty($confirm)) {
                                    $expiry = strtotime($confirm->expiry_date);
                                    if ($current > $expiry) {
                                        throw new BadRequestException('Custom error message', 408);
                                        $json   = ['message' => 'invalid'];
                                    } else {
                                        $json   = ['message' => 'valid'];
                                    }
                                } else {
                                    throw new BadRequestException('Custom error message', 408);
                                    $json   = ['message' => 'invalid'];
                                }
                            }
                        }*/

                        
                        $this->response->withType('application/json');

                        // Set JSON-encoded body to the response
                        $this->response->withStringBody(json_encode($json, JSON_UNESCAPED_UNICODE));

                        return $this->response;
                    }
                }
            }
        }
    }

    public function survey()
    {
        $this->autoRender = false;
        $pass = $this->request->getParam('pass');
        $action = 'index';

        if (!empty($pass)) {
            $action = array_shift($pass);
        }

        if (method_exists($this->RestSurvey, $action)) {
            return call_user_func_array([$this->RestSurvey, $action], $pass);
        } else {
            return false;
        }
    }

    public function login()
    {
        // $username    = $this->request->data['username'];
        // $password    = $this->request->data['password'];

        // $password    = AuthComponent::password($password);

        $user = $this->Auth->identify();

        // $check       = $this->SecurityUser->find('first', array(
        //  'conditions' => array(
        //      'SecurityUser.username' => $username,
        //      'SecurityUser.password' => $password
        //  )
        // ));
        if ($user) {
            // $data = true;
            return $user;
        } else {
            return false;
        }
    }

    public function authtest()
    {
        
        $json = [];

        if ($this->RestVersion == '2.0') {
            if (!is_null($this->request->getQuery('payload'))) {
                if (!$this->Cookie->check('Restful.Call')) {
                    $redirectUrl = $this->ControllerAction->url('auth');
                    $redirectUrl['version'] = '2.0';
                    if (isset($redirectUrl['payload'])) {
                        unset($redirectUrl['payload']);
                    }
                    $this->redirect($redirectUrl);
                }
                $url = $this->Cookie->read('Restful.CallBackURL');
                $token = $this->request->getQuery('payload');
                $url = $url.'?code='.$token;
                $this->redirect($url);
                $this->response->header(['Location' => $url]); //POCOR-7926
            } else {

                $this->Cookie->configKey('Restful', 'path', '/');
                $this->Cookie->configKey('Restful', [
                    'expires' => '+5 minutes'
                ]);
                $url = $this->request->getQuery('redirect_uri');
               // $this->Cookie->write('Restful.Call', true);
               // $this->Cookie->write('Restful.CallBackURL', $url);
                $cookie = (new Cookie('Restful.Call'))
                    ->withValue(true)
                    ->withExpiry(new \DateTime('+1 hour')) // You can set the desired expiration time
                    ->withPath('/')
                    ->withSecure(false) // Change to true if you want the cookie to be sent over HTTPS only
                    ->withHttpOnly(true);

                $this->response = $this->response->withCookie($cookie);

                $cookieUrl = (new Cookie('Restful.CallBackURL'))
                    ->withValue($url)
                    ->withExpiry(new \DateTime('+1 hour')) // You can set the desired expiration time
                    ->withPath('/')
                    ->withSecure(false) // Change to true if you want the cookie to be sent over HTTPS only
                    ->withHttpOnly(true);

                $this->response = $this->response->withCookie($cookieUrl);
                $this->SSO->doAuthentication();

            }
        } else {
            $this->autoRender = false;
            $json = [];
            // We check if request came from a post form
            if ($this->request->is(['post', 'put'])) {
                // do the login..
                $user = $this->login();

                if ($user) {
                    // get all the user details if login is successful.
                    $userID = $user['id'];
                    $accessToken = sha1(time() . $userID);
                    $refreshToken = sha1(time());
                    $json = ['message' => 'success', 'access_token' => $accessToken, 'refresh_token' => $refreshToken];

                    // set the values, and save the data
                    $startDate = time() + 3600; // current time + one hour
                    $expiryTime = date('Y-m-d H:i:s', $startDate);
                    $saveData = [
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'expiry_date' => $expiryTime,
                        'created_user_id' => 1
                    ];

                    $entity = $this->SecurityRestSessions->newEntity($saveData);
                    $this->SecurityRestSessions->save($entity);
                } else {
                    // if the login is wrong, show the error message.
                    $json = ['message' => 'failure'];
                }
            }
        }

        /*$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');


        return $this->response;*/
        
        $this->response = $this->response->withType('application/json')
                               ->withStringBody(json_encode($json, JSON_UNESCAPED_UNICODE));

        return $this->response;


    }

    public function refreshToken()
    {
        $this->autoRender = false;
        // This function checks for the existence of both the access and refresh tokens
        // If found, updates the refresh token, and the expiry time accordingly.
        $accessToken = '';
        $refreshToken = '';
        $json = [];

        if ($this->request->is(['post', 'put'])) {
            $accessToken = $this->request->getData('access_token');
            $refreshToken = $this->request->getData('refresh_token');

            $search = $this->SecurityRestSessions
                ->find()
                ->where([
                    $this->SecurityRestSessions->aliasField('access_token') => $accessToken,
                    $this->SecurityRestSessions->aliasField('refresh_token') => $refreshToken
                ])
                ->first();

            if (!empty($search)) {
                $refreshToken = sha1(time());
                $startDate = time() + 3600; // current time + one hour
                $expiryTime = date('Y-m-d H:i:s', $startDate);

                $search->refresh_token = $refreshToken;
                $search->expiry_date = $expiryTime;

                $this->SecurityRestSessions->save($search);
                $json = ['message' => 'updated', 'refresh_token' => $refreshToken];
            } else {
                throw new BadRequestException('Custom error message', 302);
            }
        } else {
            throw new BadRequestException('Custom error message', 400);
        }
        $json = ['message' => 'updated'];
        $response = $this->response->withType('application/json')
                               ->withStringBody(json_encode($json, JSON_UNESCAPED_UNICODE));

        return $response;
    }

    public function token()
    {
        $this->autoRender = false;
        $accessToken = '';
        $refreshToken = '';
        $json = [];

        if ($this->request->is(['post', 'put'])) {
           /* $accessToken = $this->request->getData()['access_token'];
            $refreshToken = $this->request->getData()['refresh_token'];

            $search = $this->SecurityRestSessions
                ->find()
                ->where([
                    $this->SecurityRestSessions->aliasField('access_token') => $accessToken,
                    $this->SecurityRestSessions->aliasField('refresh_token') => $refreshToken
                ])
                ->first();

            // check if the record actually exists. if it does, do the update, else just return fail.
            // we check if the expiry time has already passed. if it has passed, return error.
            if (!empty($search)) {
                $current = time();
                $expiry = strtotime($search->expiry_date);

                if ($current < $expiry) {
                    $refreshToken = sha1(time());
                    $startDate = time() + 3600; // current time + one hour
                    $expiryTime = date('Y-m-d H:i:s', $startDate);

                    $search->refresh_token = $refreshToken;
                    $search->expiry_date = $expiryTime;

                    $this->SecurityRestSessions->save($search);
                    $json = ['message' => 'success', 'refresh_token' => $refreshToken];
                } else {
                    $json = ['message' => 'token not updated'];
                }
            } else {
                $json = ['message' => 'token not found'];
            }*/
            $json = ['message' => 'success'];
            $response = $this->response->withType('application/json')
                               ->withStringBody(json_encode($json, JSON_UNESCAPED_UNICODE));

            return $response;
        }
    }

    public function auth()
    { 
        $baseurl = Router::url('/', true);
        $json = [];

        // Add debug logging to understand the flow
        Log::debug('Entering auth method');

        if ($this->RestVersion == '2.0') {
            Log::debug('RestVersion 2.0 detected');
          $payload = $this->request->getQuery('payload');
          $redirectUrl = $this->ControllerAction->url('auth');
            if (!is_null($this->request->getQuery('payload'))) {
                if (!$this->Cookie->check('Restful.Call')) {
                    $redirectUrl = $this->ControllerAction->url('auth');
                    $redirectUrl['version'] = '2.0';
                    if (!is_null($redirectUrl['payload'])) {
                        unset($redirectUrl['payload']);
                    }
                    $this->redirect($redirectUrl);
                }
                //$url = $this->Cookie->read('Restful.CallBackURL');
                //$baseUrl = rtrim($baseUrl, '/'); // Remove trailing slash if exists
                $endpoint = '/restful/token'; // Ensure leading slash
                $url = $baseurl . $endpoint;
                $token = $this->request->getQuery('payload');
                $urlsend = $url . '?code=' . urlencode($token);
                $this->log("Redirecting to: " . $urlsend, 'debug');
                
                // Ensure no output before redirect
                $this->redirect($urlsend);
                $response = $this->response->withStatus(302)
                                     ->withHeader('Location',$urlsend); //POCOR-7926
            return $response;
            } else {

                $this->Cookie->configKey('Restful', 'path', '/');
                $this->Cookie->configKey('Restful', [
                    'expires' => '+5 minutes'
                ]);
                $url = $this->request->getQuery('redirect_uri');
                $this->Cookie->write('Restful.Call', true);
                $this->Cookie->write('Restful.CallBackURL', $url);
                /*$rootPath = $_SERVER['REQUEST_URI'];
                $cookie = (new Cookie('Restful'))
                    ->withValue('true')
                    ->withExpiry(new \DateTime('+1 hour')) // You can set the desired expiration time
                    ->withPath($rootPath)
                    ->withSecure(false) // Change to true if you want the cookie to be sent over HTTPS only
                    ->withHttpOnly(true);

                $this->response = $this->response->withCookie($cookie);

                $cookieUrl = (new Cookie('Restful'))
                    ->withValue($url)
                    ->withExpiry(new \DateTime('+1 hour')) // You can set the desired expiration time
                    ->withPath($rootPath)
                    ->withSecure(false) // Change to true if you want the cookie to be sent over HTTPS only
                    ->withHttpOnly(true);
                $this->response = $this->response->withCookie($cookieUrl);*/

                $this->SSO->doAuthentication();
               // $url = 'http://127.0.0.1/pocor-openemis-core/';
               // return $this->redirect($url);

            }
        } else {
            $this->autoRender = false;
            Log::debug('Processing authentication with RestVersion other than 2.0');

            if ($this->request->is(['post', 'put'])) {
                $user = $this->login();

                if ($user) {
                    Log::debug('User login successful');
                    $userID = $user['id'];
                    $accessToken = sha1(time() . $userID);
                    $refreshToken = sha1(time());
                    $json = ['message' => 'success', 'access_token' => $accessToken, 'refresh_token' => $refreshToken];

                    $startDate = time() + 3600;
                    $expiryTime = date('Y-m-d H:i:s', $startDate);
                    $saveData = [
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'expiry_date' => $expiryTime,
                        'created_user_id' => 1
                    ];

                    $entity = $this->SecurityRestSessions->newEntity($saveData);
                    $this->SecurityRestSessions->save($entity);
                } else {
                    Log::debug('User login failed');
                    $json = ['message' => 'failure'];
                }
            }

            
        }

        // Create the response object with JSON content
            $this->response->withType('application/json')
                                       ->withStringBody(json_encode($json, JSON_UNESCAPED_UNICODE));
            Log::debug('Returning JSON response');
            return $this->response;
    }

}
