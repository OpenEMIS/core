<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class RestController extends RestfulAppController {
	public $uses = array(
		'Surveys.SurveyTemplate',
		'Surveys.SurveyQuestion',
		'Surveys.SurveyResponse',
		'SecurityRestSession'
	);

	public $components = array(
		'Paginator',
		'Auth' => array(
			'authorize' => 'Controller'
		),
		'Restful.RestSurvey' => array(
			'models' => array(
				'Module' => 'Surveys.SurveyModule',
				'Group' => 'Surveys.SurveyTemplate',
				'Field' => 'Surveys.SurveyQuestion',
				'FieldOption' => 'Surveys.SurveyQuestionChoice',
				'TableColumn' => 'Surveys.SurveyTableColumn',
				'TableRow' => 'Surveys.SurveyTableRow'
			)
		)
	);

	public $paginate = array(
		'limit' => 20,
		'contain' => array()
	);

	public function beforeFilter() {

		$this->Auth->allow();

		if ($this->action == 'survey') {
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
				if($this->request->is('post')) {

					$accessToken    = $this->data['SecurityRestSession']['access_token'];
					$confirm = $this->SecurityRestSession->find('first', array(
						'conditions' => array(
							'SecurityRestSession.access_token' => $accessToken,
						)
					));

					$current	= time();
					$expiry		= strtotime($confirm['SecurityRestSession']['expiry_date']);

					if (empty($confirm) || $current > $expiry) {

						throw new BadRequestException('Custom error message', 408);
						$json	= array('message' => 'invalid');
						$jdata	= json_encode($json);
						echo $jdata;
					} else {

						$json	= array('message' => 'valid');
						$jdata	= json_encode($json);
						echo $jdata;
					}
				}
			}
		}
	}

	public function survey() {
		$this->layout = 'auth';

		$this->autoRender = false;
		$pass = $this->params->pass;
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
		$username	= $this->data['username'];
		$password	= $this->data['password'];
		
		$password	= AuthComponent::password($password);
		
		$check		= $this->SecurityUser->find('first', array(
			'conditions' => array(
				'SecurityUser.username' => $username,
				'SecurityUser.password' => $password
			)
		));
		if (!empty($check['SecurityUser'])) {
			$data = true;
		} else {
			$data = false;
		}
		return $data;
	}
	
	public function auth() {
		$this->layout = 'auth';

		// We check if request came from a post form
		if($this->request->is('post')) {
			// do the login..
			$result = $this->login();
		}
		if (isset($result) && $result == true) {

			// get all the user details if login is successful.
			$userID = $this->Auth->user('id');
			$accessToken = sha1(time() . $userID);
			$refreshToken = sha1(time());
			$json = array('message' => 'success', 'access_token' => $accessToken, 'refresh_token' => $refreshToken);

			// set the values, and save the data
			$startDate = time()+ 3600; // current time + one hour
			$expiryTime = date('Y-m-d H:i:s', $startDate);
			$saveData = array(
				'access_token' => $accessToken,
				'refresh_token' => $refreshToken,
				'expiry_date' => $expiryTime
			);

			$this->SecurityRestSession->save($saveData);

			$data  = json_encode($json);
			$this->set(compact('data'));
		} else {

			// if the login is wrong, show the error message.
			$json = array('message' => 'failure');
			$data  = json_encode($json);
			$this->set(compact('data'));
		}
	}

	public function refreshToken() {

		// This function checks for the existence of both the access and refresh tokens
		// If found, updates the refresh token, and the expiry time accordingly.
		$this->layout = 'auth';
		$accessToken    = '';
		$refreshToken   = '';

		if($this->request->is('post')) {

			$accessToken    = $this->data['access_token'];
			$refreshToken   = $this->data['refresh_token'];

			$search = $this->SecurityRestSession->find('first', array(
				'conditions' => array(
					'SecurityRestSession.access_token' => $accessToken,
					'SecurityRestSession.refresh_token' => $refreshToken
				)
			));

			if (!empty($search)) {
				$refreshToken= sha1(time());
				$startDate   = time()+ 3600; // current time + one hour
				$expiryTime  = date('Y-m-d H:i:s', $startDate);
				$saveData = array(
					'refresh_token' => $refreshToken,
					'expiry_date' => $expiryTime
				);
				$this->SecurityRestSession->id = $search['SecurityRestSession']['id'];
				$this->SecurityRestSession->save($saveData);
				$json = array('message' => 'updated', 'refresh_token' => $refreshToken);
				$data  = json_encode($json);
				$this->set(compact('data'));
			} else {
				throw new BadRequestException('Custom error message', 302);
			}
		} else {
			throw new BadRequestException('Custom error message', 400);
		}
	}

	public function token() {
		$this->layout = 'auth';
		$accessToken    = '';
		$refreshToken   = '';

		if($this->request->is('post')) {

			$accessToken    = $this->data['access_token'];
			$refreshToken   = $this->data['refresh_token'];

			$search = $this->SecurityRestSession->find('first', array(
				'conditions' => array(
					'SecurityRestSession.access_token' => $accessToken,
					'SecurityRestSession.refresh_token' => $refreshToken
				)
			));

			// check if the record actually exists. if it does, do the update, else just return fail.
			// we check if the expiry time has already passed. if it has passed, return error.
			if (!empty($search)) {

				$current    = time();
				$expiry     = strtotime($search['SecurityRestSession']['expiry_date']);

				if ($current < $expiry) {

					$refreshToken= sha1(time());
					$startDate   = time()+ 3600; // current time + one hour
					$expiryTime  = date('Y-m-d H:i:s', $startDate);

					$saveData = array(
						'refresh_token' => $refreshToken,
						'expiry_date' => $expiryTime
					);

					$json = array('message' => 'success', 'refresh_token' => $refreshToken);

					$this->SecurityRestSession->id = $search['SecurityRestSession']['id'];
					$this->SecurityRestSession->save($saveData);

					$json = array('message' => 'token updated');
				} else {
					$json = array('message' => 'token not updated');
				}
			} else {

				$json = array('message' => 'token not found');

			}
			$data  = json_encode($json);
			$this->set(compact('data'));
		}
	}
}
