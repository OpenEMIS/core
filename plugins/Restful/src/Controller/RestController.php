<?php
namespace Restful\Controller;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Exception\BadRequestException;
use App\Controller\AppController;

class RestController extends AppController
{
	public $SecurityRestSession;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Questions' => ['className' => 'Survey.SurveyQuestions'],
			'Forms' => ['className' => 'Survey.SurveyForms']
		];
		$this->loadComponent('Paginator');
		$this->loadComponent('Restful.RestSurvey', [
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
		// $this->Auth->config('authorize', 'xxxx');
		$this->SecurityRestSession = TableRegistry::get('SecurityRestSessions');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Auth->allow();

		if ($this->request->action == 'survey') {
			$this->autoRender = false;

			$pass = $this->params->pass;
			$action = null;

			if (!empty($pass)) {
				$action = array_shift($pass);
			}
			if (!is_null($action) && !in_array($action, $this->RestSurvey->allowedActions)) {

				// actions require authentication
				// if authentication is required:
				// 1. check if token exists
				// 2. check if current time is greater than expiry time

				$accessToken    = '';
				if ($this->request->is(['post', 'put'])) {

					$accessToken    = $this->request->data['SecurityRestSession']['access_token'];
					$confirm = $this->SecurityRestSession->find()
						->where([$this->SecurityRestSession->aliasField('access_token') => $accessToken])
						->first();

					$current	= time();
					$json = [];

					if (!empty($confirm)) {
						$expiry		= strtotime($confirm->expiry_date);
						if ($current > $expiry) {
							throw new BadRequestException('Custom error message', 408);
							$json	= ['message' => 'invalid'];
						} else {
							$json	= ['message' => 'valid'];
						}
					} else {
						throw new BadRequestException('Custom error message', 408);
						$json	= ['message' => 'invalid'];
					}

					$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
					$this->response->type('json');

					return $this->response;
				}
			}
		}
    }

    public function survey() {
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

    public function login() {
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
			$data = true;
		} else {
			$data = false;
		}
		return $data;
	}
	
	public function auth() {
		$this->autoRender = false;
		// We check if request came from a post form
		if ($this->request->is(['post', 'put'])) {
			// do the login..
			$result = $this->login();
		}

		$json = [];
		if (isset($result) && $result == true) {

			// get all the user details if login is successful.
			$userID = $this->Auth->user('id');
			$accessToken = sha1(time() . $userID);
			$refreshToken = sha1(time());
			$json = ['message' => 'success', 'access_token' => $accessToken, 'refresh_token' => $refreshToken];

			// set the values, and save the data
			$startDate = time() + 3600; // current time + one hour
			$expiryTime = date('Y-m-d H:i:s', $startDate);
			$saveData = [
				'access_token' => $accessToken,
				'refresh_token' => $refreshToken,
				'expiry_date' => $expiryTime
			];
			$entity = $this->SecurityRestSession->newEntity($saveData);
			$this->SecurityRestSession->save($entity);
		} else {

			// if the login is wrong, show the error message.
			$json = ['message' => 'failure'];
		}

		$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
		$this->response->type('json');

		return $this->response;
	}

	public function refreshToken() {
		$this->autoRender = false;
		// This function checks for the existence of both the access and refresh tokens
		// If found, updates the refresh token, and the expiry time accordingly.
		$accessToken    = '';
		$refreshToken   = '';

		$json = [];
		if($this->request->is(['post', 'put'])) {

			$accessToken    = $this->request->data['access_token'];
			$refreshToken   = $this->request->data['refresh_token'];

			$search = $this->SecurityRestSession->find()
			->where([
				$this->SecurityRestSession->aliasField('access_token') => $accessToken,
				$this->SecurityRestSession->aliasField('refresh_token') => $refreshToken
			])
			->first();

			if (!empty($search)) {
				$refreshToken = sha1(time());
				$startDate    = time()+ 3600; // current time + one hour
				$expiryTime   = date('Y-m-d H:i:s', $startDate);

				$search->refresh_token = $refreshToken;
				$search->expiry_date = $expiryTime;

				$this->SecurityRestSession->save($search);
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

	public function token() {
		$this->autoRender = false;
		$accessToken    = '';
		$refreshToken   = '';
		$json = [];

		if ($this->request->is(['post', 'put'])) {

			$accessToken    = $this->request->data['access_token'];
			$refreshToken   = $this->request->data['refresh_token'];

			$search = $this->SecurityRestSession->find()
			->where([
				$this->SecurityRestSession->aliasField('access_token') => $accessToken,
				$this->SecurityRestSession->aliasField('refresh_token') => $refreshToken
			])
			->first();

			// check if the record actually exists. if it does, do the update, else just return fail.
			// we check if the expiry time has already passed. if it has passed, return error.
			if (!empty($search)) {

				$current    = time();
				$expiry     = strtotime($search->expiry_date);

				if ($current < $expiry) {

					$refreshToken= sha1(time());
					$startDate   = time()+ 3600; // current time + one hour
					$expiryTime  = date('Y-m-d H:i:s', $startDate);

					$search->refresh_token = $refreshToken;
					$search->expiry_date = $expiryTime;

					$this->SecurityRestSession->save($search);

					$json = ['message' => 'success', 'refresh_token' => $refreshToken];
				} else {
					$json = ['message' => 'token not updated'];
				}
			} else {
				$json = ['message' => 'token not found'];

			}
		}
		$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
		$this->response->type('json');

		return $this->response;
	}
}
