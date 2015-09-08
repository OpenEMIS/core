<?php
namespace API\Controller;

use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Exception\BadRequestException;
use App\Controller\AppController;

class ApiController extends AppController
{
	private $_app_id = '1234';
	private $_app_key = 'acd87adcas9d8cad';

	public function initialize() {
		parent::initialize();

		if (array_key_exists('0', $this->request->pass)) {
			$app_id = $this->request->action;
			$app_key = $this->request->pass[0];
			if ($app_id == $this->_app_id && $app_key == $this->_app_key) {
				$user = $this->login();
				if ($user) {
					$this->request->params['action'] = 'index';
				} else {
					throw new BadRequestException('Unable to login. Please contact system administrator.', 500);
				}
			} else {
				throw new BadRequestException('Bad App ID & App Key', 401);
			}
		} else {
			throw new BadRequestException('App Key is missing', 400);
		}
		$this->Students = TableRegistry::get('Student.Students');
	}

	public function login() {
		$data = [
			'username' => 'admin',
			'password' => 'demo',
			'submit' => 'login',
			'System' => [
            	'language' => 'en'
        	]
		];
		$this->request->data = $data;

		$user = $this->Auth->identify();
		if ($user) {
			$this->Auth->setUser($user);
			return $user;
		} else {
			return false;
		}
	}

	public function index() {
		$this->autoRender = false;
		$json = [];

		if ($this->request->isGet()) {
			if (!empty($this->request->query)) {
				$params = $this->request->query;
				// pr($this->request->query);die;
				$query = $this->Students->find();
				$data = $query
							->contain([
								'Genders',
								'Identities'
							])
							->where([$this->Students->aliasField('openemis_no') => $params['ss_id']])
							// ->where(['default_identity_type' => $params['ss_id']])
							->first();
				$json = $data;
			} else {
				throw new BadRequestException('Missing query', 401);
			}
		} else {
			throw new BadRequestException('Bad Request', 400);
		}

		$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
		$this->response->type('json');

		return $this->response;
	}
	


	// public function auth() {
	// 	$this->autoRender = false;
	// 	$json = [];

	// 	// We check if request came from a post form
	// 	if ($this->request->is(['post', 'put'])) {
	// 		// do the login..
	// 		$user = $this->login();

	// 		if ($user) {
	// 			// get all the user details if login is successful.
	// 			$userID = $user['id'];
	// 			$accessToken = sha1(time() . $userID);
	// 			$refreshToken = sha1(time());
	// 			$json = ['message' => 'success', 'access_token' => $accessToken, 'refresh_token' => $refreshToken];

	// 			// set the values, and save the data
	// 			$startDate = time() + 3600; // current time + one hour
 //                $expiryTime = new Time($startDate);
	// 			$saveData = [
	// 				'access_token' => $accessToken,
	// 				'refresh_token' => $refreshToken,
	// 				'expiry_date' => $expiryTime
	// 			];

	// 			$entity = $this->SecurityAPISessions->newEntity($saveData);
	// 			$this->SecurityAPISessions->save($entity);
	// 		} else {
	// 			// if the login is wrong, show the error message.
	// 			$json = ['message' => 'failure'];
	// 		}
	// 	}

	// 	$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
	// 	$this->response->type('json');

	// 	return $this->response;
	// }

	// public function refreshToken() {
	// 	$this->autoRender = false;
	// 	// This function checks for the existence of both the access and refresh tokens
	// 	// If found, updates the refresh token, and the expiry time accordingly.
	// 	$accessToken = '';
	// 	$refreshToken = '';
	// 	$json = [];

	// 	if ($this->request->is(['post', 'put'])) {
	// 		$accessToken = $this->request->data['access_token'];
	// 		$refreshToken = $this->request->data['refresh_token'];

	// 		$search = $this->SecurityAPISessions
	// 			->find()
	// 			->where([
	// 				$this->SecurityAPISessions->aliasField('access_token') => $accessToken,
	// 				$this->SecurityAPISessions->aliasField('refresh_token') => $refreshToken
	// 			])
	// 			->first();

	// 		if (!empty($search)) {
	// 			$refreshToken = sha1(time());
	// 			$startDate = time() + 3600; // current time + one hour
	// 			$expiryTime = date('Y-m-d H:i:s', $startDate);

	// 			$search->refresh_token = $refreshToken;
	// 			$search->expiry_date = $expiryTime;

	// 			$this->SecurityAPISessions->save($search);
	// 			$json = ['message' => 'updated', 'refresh_token' => $refreshToken];
	// 		} else {
	// 			throw new BadRequestException('Custom error message', 302);
	// 		}
	// 	} else {
	// 		throw new BadRequestException('Custom error message', 400);
	// 	}

	// 	$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
	// 	$this->response->type('json');

	// 	return $this->response;
	// }

	// public function token() {
	// 	$this->autoRender = false;
	// 	$accessToken = '';
	// 	$refreshToken = '';
	// 	$json = [];

	// 	if ($this->request->is(['post', 'put'])) {
	// 		$accessToken = $this->request->data['access_token'];
	// 		$refreshToken = $this->request->data['refresh_token'];

	// 		$search = $this->SecurityAPISessions
	// 			->find()
	// 			->where([
	// 				$this->SecurityAPISessions->aliasField('access_token') => $accessToken,
	// 				$this->SecurityAPISessions->aliasField('refresh_token') => $refreshToken
	// 			])
	// 			->first();

	// 		// check if the record actually exists. if it does, do the update, else just return fail.
	// 		// we check if the expiry time has already passed. if it has passed, return error.
	// 		if (!empty($search)) {
	// 			$current = time();
	// 			$expiry = strtotime($search->expiry_date);

	// 			if ($current < $expiry) {
	// 				$refreshToken = sha1(time());
	// 				$startDate = time() + 3600; // current time + one hour
	// 				$expiryTime = date('Y-m-d H:i:s', $startDate);

	// 				$search->refresh_token = $refreshToken;
	// 				$search->expiry_date = $expiryTime;

	// 				$this->SecurityAPISessions->save($search);
	// 				$json = ['message' => 'success', 'refresh_token' => $refreshToken];
	// 			} else {
	// 				$json = ['message' => 'token not updated'];
	// 			}
	// 		} else {
	// 			$json = ['message' => 'token not found'];
	// 		}
	// 	}

	// 	$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
	// 	$this->response->type('json');

	// 	return $this->response;
	// }
}
