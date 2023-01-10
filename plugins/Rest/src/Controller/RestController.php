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
use Cake\Network\Exception\BadRequestException;

class RestController extends AppController
{
    public $SecurityRestSessions = null;
    private $RestVersion = '1.0';

    public function initialize()
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

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->eventManager()->off($this->Csrf);
        $this->Security->config('unlockedActions', ['survey']);
        if (isset($this->request->query['version'])) {
            $this->RestVersion = $this->request->query('version');
        }

        if ($this->RestVersion == 2.0) {
            // Using JWT for authenication
            $this->Auth->config('authenticate', [
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
            $this->Auth->config('storage', 'Memory');
            $this->Auth->config('authorize', null);
            $this->Auth->allow(['auth']);
            if ($this->request->data('code')) {
                $token = $this->request->data('code');
                $this->request->env('HTTP_AUTHORIZATION', $token);
            }
            $header = $this->request->header('authorization');
            if ($header) {
                $token = str_ireplace('bearer ', '', $header);
                try {
                    $payload = JWT::decode($token, Configure::read('Application.public.key'), ['RS256']);
                } catch (ExpiredException $e) {
                    $this->response->statusCode(500);
                    $this->response->body(json_encode((['message' => __('Expired token')]), JSON_UNESCAPED_UNICODE));
                    $this->response->type('json');
                    return $this->response;
                }
                $currentTimeStamp = (new Time)->toUnixString();
                $exp = $payload->exp;
                if ($exp < $currentTimeStamp) {
                    throw new BadRequestException('Custom error message', 408);
                    $event->stopPropagation();
                }
            }

            $this->autoRender = false;

            $pass = $this->request->params['pass'];
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
            if ($this->request->action == 'survey') {
                $this->autoRender = false;

                $pass = $this->request->params['pass'];
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

                        if (array_key_exists('SecurityRestSession', $this->request->data)) {
                            if (array_key_exists('access_token', $this->request->data['SecurityRestSession'])) {
                                $accessToken = $this->request->data['SecurityRestSession']['access_token'];

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
                        }

                        $this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
                        $this->response->type('json');

                        //return $this->response;
                    }
                }
            }
        }
    }

    public function survey()
    {
        $this->autoRender = false;
        $pass = $this->request->params['pass'];
        $action = 'index';

        if (!empty($pass)) {
            $action = array_shift($pass);
        }

        if (method_exists($this->RestSurvey, $action)) {
            return call_user_func_array(array($this->RestSurvey, $action), $pass);
        } else {
            return false;
        }
    }

    public function login()
    {
        // $username	= $this->request->data['username'];
        // $password	= $this->request->data['password'];

        // $password	= AuthComponent::password($password);

        $user = $this->Auth->identify();

        // $check		= $this->SecurityUser->find('first', array(
        // 	'conditions' => array(
        // 		'SecurityUser.username' => $username,
        // 		'SecurityUser.password' => $password
        // 	)
        // ));
        if ($user) {
            // $data = true;
            return $user;
        } else {
            return false;
        }
    }

    public function auth()
    {
        $json = [];

        if ($this->RestVersion == '2.0') {
            if (isset($this->request->query['payload'])) {
                if (!$this->Cookie->check('Restful.Call')) {
                    $redirectUrl = $this->ControllerAction->url('auth');
                    $redirectUrl['version'] = '2.0';
                    if (isset($redirectUrl['payload'])) {
                        unset($redirectUrl['payload']);
                    }
                    $this->redirect($redirectUrl);
                }
                $url = $this->Cookie->read('Restful.CallBackURL');
                $token = $this->request->query('payload');
                $url = $url.'?code='.$token;
                $this->redirect($url);
            } else {
                $this->Cookie->configKey('Restful', 'path', '/');
                $this->Cookie->configKey('Restful', [
                    'expires' => '+5 minutes'
                ]);
                $url = $this->request->query('redirect_uri');
                $this->Cookie->write('Restful.Call', true);
                $this->Cookie->write('Restful.CallBackURL', $url);
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

        $this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

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
            $accessToken = $this->request->data['access_token'];
            $refreshToken = $this->request->data['refresh_token'];

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

        $this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }

    public function token()
    {
        $this->autoRender = false;
        $accessToken = '';
        $refreshToken = '';
        $json = [];

        if ($this->request->is(['post', 'put'])) {
            $accessToken = $this->request->data['access_token'];
            $refreshToken = $this->request->data['refresh_token'];

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
            }

            $this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
            $this->response->type('json');

            return $this->response;
        }
    }
}
