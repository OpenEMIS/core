<?php
namespace API\Controller;

use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Exception\BadRequestException;
use App\Controller\AppController;

/**
 * Previous API url: 'http://<CORE_WEBSITE_BASE_URL>/api/<APP_ID>/<APP_KEY>?<QUERIES_NAME_VALUE_PAIR_STRING>';
 * example: 'http://phpoev3.dev/api/1234/acd87adcas9d8cad?user_id=4536&ss_id=S286812264P';
 *
 * 
 * ===================================
 * Quandl.com API url implementations
 * ===================================
 * Version 1 API: 'https://www.quandl.com/api/v1/datasets/<DATA_SOURCE>/<DATA_CODE>.<DATA_OUTPUT_FORMAT>?<QUERIES_NAME_VALUE_PAIR_STRING>&auth_token=<YOUR_TOKEN_KEY>';
 * example: 'https://www.quandl.com/api/v1/datasets/WORLDBANK/WLD_TEA_MOMBASA.json?rows=1&auth_token=asf8asd76asdf7asdfasdc89a8sd';
 *  
 * Version 2 API: 'https://www.quandl.com/api/v2/datasets/<DATA_SOURCE>/<DATA_CODE>.<DATA_OUTPUT_FORMAT>?<QUERIES_NAME_VALUE_PAIR_STRING>&auth_token=<YOUR_TOKEN_KEY>';
 * example: 'https://www.quandl.com/api/v2/datasets/WORLDBANK/WLD_TEA_MOMBASA.json?rows=1&auth_token=asf8asd76asdf7asdfasdc89a8sd';
 *  
 *
 * =======================================================================================
 * Decided API url: 'http://<CORE_WEBSITE_BASE_URL>/api?<QUERIES_NAME_VALUE_PAIR_STRING>';
 * =======================================================================================
 * External application access will be defined by a get NVP (name-value-pair) string:
 * 	@name: 	security_token
 * 	@value: as assigned in the api_authorizations table (security_token column)
 * 
 * API version will be defined by a get NVP string:
 * 	@name: 	version
 * 	@value: numerical value
 * If not defined, default version will be SISB.
 * If version does not exists, default version will be used instead.
 * 
 * Output format will be defined by a get NVP string:
 * 	@name: 	format
 * 	@value: one of these array values ['json', 'soap']
 * If not defined, default output format will be json string
 * 
 * example: 'http://phpoev3.dev/api?security_token=acd87adcas9d8cad&version=1&user_id=4536&ss_id=S286812264P';
 *
 */
class ApiController extends AppController
{
	private $_requestParams = null;
	private $_errorCodes = [
		0 => [
			'code' => null,
			'description' => null
		],
		1 => [
			'code' => '0x0001',
			'description' => 'Invalid Token'
		],
		2 => [
			'code' => '0x0002',
			'description' => 'Invalid Token Format'
		],
		3 => [
			'code' => '0x0003',
			'description' => 'Account is inactive - Please contact MOEYS PPRE for details'
		],
		4 => [
			'code' => '0x0004',
			'description' => 'Invalid Social Security Number'
		],
		5 => [
			'code' => true,
			'description' => 'Unable to log in to OpenEMIS server. Please contact OpenEMIS server administrator.'
		]
	];
	private $_externalApplication = null;

	public function initialize() {
		parent::initialize();

		/**
		 * register a specific log for this plugin
		 */
		Log::config('api', [
		    'className' => 'Cake\Log\Engine\FileLog',
		    'path' => LOGS,
		    'levels' => [],
		    'scopes' => ['api'],
		    'file' => 'api_authorizations.log',
		]);

		$message = 'Receives request from ' . $this->request->referer() . ' ( ' . $this->request->clientIp() . ' ) trying to access OpenEMIS system.';
		Log::info($message, ['scope' => ['api']]);

		if ($this->request->isGet() && !empty($this->request->query) && !empty($this->request->query('security_token'))) {
		// if (array_key_exists('0', $this->request->pass)) {

			$user = $this->login();
			
			if ($user) {
				
				$ApiAuthorizations = TableRegistry::get('ApiAuthorizations');
				$this->_externalApplication = $ApiAuthorizations->find()
						->where([
							$ApiAuthorizations->aliasField('security_token') => $this->request->query('security_token')
						])
						->first()
						;

				if ($this->_externalApplication) {
					$this->_requestParams = $this->request->query;
					$this->request->params['action'] = 'extract';
				} else {
					$this->autoRender = false;
					$message = 'the given app_id and app_key has no matches, shown "' . $this->_errorCodes[2]['description'] . '( ' .$this->_errorCodes[2]['code'] . ' )" error message to requestor';
					Log::info($message, ['scope' => ['api']]);
					$json = [
						'error' => $this->_errorCodes[2],
					];
					$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
					$this->response->type('json');
					die($this->response);
				}
			} else {
				$this->autoRender = false;
				$message = 'unable to login using hard-coded user info, shown "' . $this->_errorCodes[5]['description'] . '( ' .$this->_errorCodes[5]['code'] . ' )" error message to requestor';
				Log::info($message, ['scope' => ['api']]);
				$json = [
					'error' => $this->_errorCodes[5],
				];
				$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
				$this->response->type('json');
				die($this->response);
			}

		} else {
			$this->autoRender = false;
			$message = 'missing app_key, shown "' . $this->_errorCodes[1]['description'] . '( ' .$this->_errorCodes[1]['code'] . ' )" error message to requestor';
			Log::info($message, ['scope' => ['api']]);
			$json = [
				'error' => $this->_errorCodes[1],
			];
			$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
			$this->response->type('json');
			die($this->response);
		}

		$this->StudentIdentities = TableRegistry::get('User.Identities');
		$this->StudentStatuses = TableRegistry::get('Student.StudentStatuses');
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

	private $_versionFunctions = [
		0 => 'versionSISB',
		1 => 'versionOne'
	];
	public function extract() {
		$this->autoRender = false;
		$result = [];

		if ($this->_requestParams) {
			$params = $this->_requestParams;

			$versionFunction = $this->_versionFunctions[0];
			if (array_key_exists('version', $params) || $params['version']!='') {
				if (array_key_exists($params['version'], $this->_versionFunctions)) {
					$versionFunction = $this->_versionFunctions[$params['version']];
				}
			}

			$result = $this->$versionFunction();

		} else {
			$message = 'request query is missing, shown "' . $this->_errorCodes[2]['description'] . '( ' .$this->_errorCodes[2]['code'] . ' )" error message to requestor';
			Log::info($message, ['scope' => ['api']]);
			$result = [
				'error' => $this->_errorCodes[2],
			];
		}

		$this->response->body(json_encode($result, JSON_UNESCAPED_UNICODE));
		$this->response->type('json');

		return $this->response;
	}
	
	private function versionSISB() {
		$params = $this->_requestParams;
		$data = false;

		if (array_key_exists('user_id', $params) || $params['user_id']!='') {
			if (array_key_exists('ss_id', $params) && $params['ss_id']!='') {

				$message = 'User ' . $params['user_id'] . ' from ' . $this->_externalApplication->name . ' queries for ' . $params['ss_id'];
				Log::info($message, ['scope' => ['api']]);

				// $studentStatuses = $this->StudentStatuses->find('list', [
				// 	'keyField' => 'id',
				// 	'valueField' => 'name'
				// ])->toArray();

				$query = $this->StudentIdentities->find('all');
				$data = $query
							->join([
								'Student' => [
									'table' => 'security_users',
									'conditions' => [
										'Student.id = '.$this->StudentIdentities->aliasField('security_user_id')
									]
								],
								'InstitutionStudent' => [
									'type' => 'left',
									'table' => 'institution_students',
									'conditions' => [
										'Student.id = InstitutionStudent.student_id'
									]
								],
								'Institution' => [
									'type' => 'left',
									'table' => 'institution_sites',
									'conditions' => [
										'Institution.id = InstitutionStudent.institution_id'
									]
								],
								'AcademicPeriod' => [
									'type' => 'left',
									'table' => 'academic_periods',
									'conditions' => [
										'AcademicPeriod.id = InstitutionStudent.academic_period_id'
									]
								],
								'EducationGrade' => [
									'type' => 'left',
									'table' => 'education_grades',
									'conditions' => [
										'EducationGrade.id = InstitutionStudent.education_grade_id'
									]
								]
							])
							->select([
								'openemis_id' => 'Student.openemis_no', 
								'Student.first_name', 'Student.middle_name', 'Student.third_name', 'Student.last_name',
								'InstitutionStudent.student_status_id',
								
								'Institution.name', 'Institution.code',
								'AcademicPeriod.name', 'AcademicPeriod.start_date', 'AcademicPeriod.end_date',
								'education_level' => 'EducationGrade.name',
							])
							->where([
								$this->StudentIdentities->aliasField('number') => $params['ss_id'],
								'InstitutionStudent.institution_id IS NOT NULL'
							])
							->order(['AcademicPeriod.end_date DESC'])
							->first()
							;

				if ($data) {
					$data->toArray();
					if ($data['InstitutionStudent']['student_status_id']=='1') {
					/**
					 * SS#: 000213123
					 * Name: Lina Marcela Tovar Velez
					 * Currently in school: Yes
					 * Current school: Pallotti High School
					 * Current school #: 251589
					 * Current level: Form 2
					 * OpenEMIS ID#: ????
					 */
						$json = [
							'ss_id' => [	
								'label' => 'SS#',
								'value' =>  $params['ss_id'],
							],
							'name' => [
								'label' => 'Name',
								'value' =>  implode(' ', $data['Student']) ,
							],
							'status' => [
								'label' => 'Currently in school',
								'value' =>  'Yes',
							],
							'school_name' => [
								'label' => 'Current school',
								'value' =>  $data['Institution']['name'],
							],
							'school_code' => [
								'label' => 'Current school #',
								'value' =>  $data['Institution']['code'],
							],
							'level' => [
								'label' => 'Current level',
								'value' =>  $data['education_level'],
							],
							'error' => $this->_errorCodes[0],
							'openemis_id' => [
								'label' => 'OpenEMIS ID#',
								'value' =>  $data['openemis_id'],
							],
						];
					} else {
					/**
					 * SS#: 000213123
					 * Name: Mickey Mouse
					 * Currently in school: No
					 * Last known school: None
					 * Last known school #: 0
					 * Highest completed level: 0
					 * OpenEMIS ID#: ????
					 */
						$json = [
							'ss_id' => [	
								'label' => 'SS#',
								'value' =>  $params['ss_id'],
							],
							'name' => [
								'label' => 'Name',
								'value' =>  implode(' ', $data['Student']) ,
							],
							'status' => [
								'label' => 'Currently in school',
								'value' =>  'No',
							],
							'school_name' => [
								'label' => 'Last known school',
								'value' =>  ($data['Institution']['name']) ? $data['Institution']['name'] : 'None',
							],
							'school_code' => [
								'label' => 'Last known school #',
								'value' =>  ($data['Institution']['code']) ? $data['Institution']['code'] : '0',
							],
							'level' => [
								'label' => 'Highest completed level',
								'value' =>  ($data['education_level']) ? $data['education_level'] : '0',
							],
							'error' => $this->_errorCodes[0],
							'openemis_id' => [
								'label' => 'OpenEMIS ID#',
								'value' =>  $data['openemis_id'],
							],
						];
					}
				} else {
					/**
					 * no record
					 */
					$json = [
						'ss_id' => [	
							'label' => 'SS#',
							'value' =>  $params['ss_id'],
						],
						'name' => [
							'label' => 'Name',
							'value' =>  'Not Available' ,
						],
						'status' => [
							'label' => 'Currently in school',
							'value' =>  'No',
						],
						'school_name' => [
							'label' => 'Last known school',
							'value' =>  'None',
						],
						'school_code' => [
							'label' => 'Last known school #',
							'value' =>  '0',
						],
						'level' => [
							'label' => 'Highest completed level',
							'value' =>  '0',
						],
						'error' => $this->_errorCodes[0],
						'openemis_id' => [
							'label' => 'OpenEMIS ID#',
							'value' =>  'None',
						],
					];
				}
			} else {
				$message = 'ss_id is missing, shown "' . $this->_errorCodes[4]['description'] . '( ' .$this->_errorCodes[4]['code'] . ' )" error message to requestor';
				Log::info($message, ['scope' => ['api']]);
				$json = [
					'error' => $this->_errorCodes[4],
				];
			}
		} else {
			$message = 'external user_id is missing, shown "' . $this->_errorCodes[3]['description'] . '( ' .$this->_errorCodes[3]['code'] . ' )" error message to requestor';
			Log::info($message, ['scope' => ['api']]);
			$json = [
				'error' => $this->_errorCodes[3],
			];
		}

		return $json;
	}

	private function versionOne() {
		$params = $this->_requestParams;
		return [
			'api_version' => [	
				'label' => 'API Version',
				'value' =>  1,
			]
		];
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
