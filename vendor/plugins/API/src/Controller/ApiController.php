<?php
namespace API\Controller;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;
use Cake\Utility\Xml;
use Cake\Utility\Exception\XmlException;

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
 * example: 'http://phpoev3.dev/api?security_token=acd87adcas9d8cad&version=1&format=soap&user_id=4536&ss_id=S286812264P';
 *
 */

class ApiController extends AppController
{
	public $components = [
		'API.ApiVOne',
		'API.ApiSISB'
	];

	private $_externalApplication = null;

/******************************************************************************************************************
**
** plugin gateway
**
******************************************************************************************************************/
	public function initialize() {
		parent::initialize();

		/**
		 * Allow public access to extract action
		 */
		$this->Auth->allow(['extract']);

		Log::config('api', [
		    'className' => 'Cake\Log\Engine\FileLog',
		    'path' => LOGS,
		    'levels' => [],
		    'scopes' => ['api'],
		    'file' => 'api_authorizations.log',
		]);
		
		$message = 'Receives request from ' . $this->request->referer() . ' ( ' . $this->request->clientIp() . ' ) trying to access OpenEMIS system.';
		Log::info($message, ['scope' => ['api']]);

		$securityToken = $this->request->query('security_token');
		if ($this->request->isGet() && !empty($this->request->query) && !empty($securityToken)) {
			$this->ApiAuthorizations = TableRegistry::get('API.ApiAuthorizations');
			$this->_externalApplication = $this->ApiAuthorizations->find()
					->where([
						$this->ApiAuthorizations->aliasField('security_token') => $securityToken
					])
					->first()
					;

			if ($this->_externalApplication) {
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
			$message = 'missing app_key, shown "' . $this->_errorCodes[1]['description'] . '( ' .$this->_errorCodes[1]['code'] . ' )" error message to requestor';
			Log::info($message, ['scope' => ['api']]);
			$json = [
				'error' => $this->_errorCodes[1],
			];
			$this->response->body(json_encode($json, JSON_UNESCAPED_UNICODE));
			$this->response->type('json');
			die($this->response);
		}

	}


/******************************************************************************************************************
**
** action methods
**
******************************************************************************************************************/
	private $_allowableFormats = [
		'json',
		'soap'
	];
	public function extract() {
		$this->autoRender = false;
		$result = [];

		$format = 'json';
		$debug = false;
		if ($this->request->query) {
			$params = $this->request->query;

			$versionFunction = $this->_versionFunctions[0];
			if (array_key_exists('version', $params) && $params['version']!='') {
				if (array_key_exists($params['version'], $this->_versionFunctions)) {
					$versionFunction = $this->_versionFunctions[$params['version']];
				}
			}
			if (array_key_exists('format', $params) && $params['format']!='') {
				if (in_array($params['format'], $this->_allowableFormats)) {
					$format = $params['format'];
				}
			}
			if (array_key_exists('debug', $params) && $params['debug']!='' && $params['debug']) {
				$debug = true;
			}

			$result = $this->$versionFunction();

		} else {
			$message = 'request query is missing, shown "' . $this->_errorCodes[2]['description'] . '( ' .$this->_errorCodes[2]['code'] . ' )" error message to requestor';
			Log::info($message, ['scope' => ['api']]);
			$result = [
				'error' => $this->_errorCodes[2],
			];
		}

		if ($debug) {
			$this->response->body(pr($result));
			$this->response->type('html');
		} else {
			if ($format == 'json') {
				$this->response->body(json_encode($result, JSON_UNESCAPED_UNICODE));
				$this->response->type('json');
			} else if ($format == 'soap') {
				$result = $this->buildXml($result);
				// pr($result->asXML());die;
				$this->response->body($result->asXML());
				$this->response->type('xml');
				// $this->response->type('soap');
			} else {
				$this->response->body($result);
				// $this->response->type('html');
			}
		}
		return $this->response;
	}
	
	private function buildXML($result) {
		if (is_null($result['error']['code'])) {

			$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
				<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
					<soap:Header/>
					<soap:Body>

						<ns2:getStudentResponse xmlns:ns2="https://' . $this->request->host() . '/api/OEQueryResult.xsd">
					      <ns2:student>
					        <ns2:ss_id>' . $result['id']['value'] . '</ns2:ss_id>
					        <ns2:name>' . $result['name']['value'] . '</ns2:name>
					        <ns2:status>' . $result['status']['value'] . '</ns2:status>
					        <ns2:school_name>' . $result['school_name']['value'] . '</ns2:school_name>
					        <ns2:school_code>' . $result['school_code']['value'] . '</ns2:school_code>
					        <ns2:level>' . $result['level']['value'] . '</ns2:level>
					        <ns2:openemis_id>' . $result['openemis_id']['value'] . '</ns2:openemis_id>
					      </ns2:student>
					    </ns2:getStudentResponse>

					</soap:Body>
				</soap:Envelope>';

		} else {

			$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
					<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
						<soap:Header/>
						<soap:Body>
							<soap:Fault>

								<soap:Code>
									<soap:Value>
										' . $result['error']['code'] . '
									</soap:Value>
								</soap:Code>
								<soap:Reason>
									<soap:Text xml:lang="en-US">
										' . $result['error']['description'] . '
									</soap:Text>
								</soap:Reason>
								<soap:Role>https://' . $this->request->host() . '/api</soap:Role>
								
							</soap:Fault>
						</soap:Body>
					</soap:Envelope>';

					// <soap:Detail>
					// 	<PO:order xmlns:PO="http://gizmos.com/orders/">
					// 		Quantity element does not have a value
					// 	</PO:order>
					// 	<PO:confirmation xmlns:PO="http://gizmos.com/confirm">
					// 		Incomplete address: no zip code
					// 	</PO:confirmation>
					// </soap:Detail>

			}

		$xml = Xml::build($xmlstr);
		return $xml;
	}


/********************************************************************************************************************
*
* individual version functions (@todo port each function to individual component files)
*
******************************************************************************************************************/
	private $_versionFunctions = [
		0 => 'versionSISB',
		1 => 'versionOne'
	];
	private function versionSISB() {

		return $this->ApiSISB->process($this->_externalApplication);

	}

	private function versionOne() {

		return $this->ApiVOne->process($this->_externalApplication);

	}
}
