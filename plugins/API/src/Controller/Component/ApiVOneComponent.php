<?php
namespace API\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Collection\Collection;

class ApiVOneComponent extends Component {

	public function process($externalApplication) {

		$params = $this->request->query;
		$data = false;

		// for extracting error codes
		// couldn't add the global codes in app table or controller since it is being used in both controller, components & table
		$ApiAuthorizations = TableRegistry::get('API.ApiAuthorizations');
		// for extracting error codes

		if (!array_key_exists('user_id', $params) || empty($params['user_id'])) {
			$error = $ApiAuthorizations->getErrorMessage(3, ['organisation_administrator'=>'MOEYS PPRE']);
			$message = 'external user_id is missing, shown "' . $error['description'] . '( ' .$error['code'] . ' )" error message to requestor';
			Log::info($message, ['scope' => ['api']]);
			// $result = ['error' => $error];
		}

		$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
		$combineClosure = function ($record) {
			return [
				'name'=>$record->name,
				'national_code'=>$record->national_code
			];
		};
		$identity_types = $IdentityTypes->getList($IdentityTypes->find())->combine('id', $combineClosure);
		$paramIdentityType = (array_key_exists('identity_type', $params) && $params['identity_type']!='') ? strtolower($params['identity_type']) : 'openemis_no';
		$identity_type = $identity_types->reject(function ($record, $key) use ($paramIdentityType) {
		    return $record['national_code'] !== $paramIdentityType;
		})->toArray();

		if (array_key_exists('id', $params) && $params['id']!='') {

			$message = 'User ' . $params['user_id'] . ' from ' . $externalApplication->name . ' queries for ' . $params['id'];
			Log::info($message, ['scope' => ['api']]);

			if (array_key_exists('persona', $params) && $params['persona']!='') {
				$persona = ucwords(strtolower($params['persona']));
				$PersonaIdentities = TableRegistry::get('API.'.$persona.'Identities');
				if (!method_exists($PersonaIdentities, 'search')) {
					$result = [
						'error' => $ApiAuthorizations->getErrorMessage('openemis_persona_type_error'),
					];
				}
			} else {
				$PersonaIdentities = TableRegistry::get('API.StudentIdentities');
			}

			$conditions = [];
			if (!isset($result)) {
				if ($paramIdentityType=='openemis_no') {
					$conditions[] = ['Student.openemis_no' => $params['id']];
				} else {
					if (empty($identity_type)) {
						$result = [
							'error' => $ApiAuthorizations->getErrorMessage('openemis_identity_type_not_found'),
						];
					} else {
						$conditions[] = [
							$PersonaIdentities->aliasField('number') => $params['id'],
							$PersonaIdentities->aliasField('identity_type_id') => key($identity_type)
						];
					}
				}
			}

			if (!isset($result)) {
				$result = $PersonaIdentities->search($conditions);
				if (empty($result)) {
					$result = [
						'id' => [
							'label' => ($paramIdentityType=='openemis_no') ? 'OpenEMIS ID#' : trim($identity_type[key($identity_type)]['name']) . '#',
							'value' =>  $params['id'],
						],
						'name' => [
							'label' => 'Name',
							'value' =>  'Not Available' ,
						],
						'status' => [
							'label' => 'Currently in school',
							'value' =>  'Not Available',
						],
						'school_name' => [
							'label' => 'Last known school',
							'value' =>  'Not Available',
						],
						'school_code' => [
							'label' => 'Last known school #',
							'value' =>  'Not Available',
						],
						'level' => [
							'label' => 'Highest completed level',
							'value' =>  'Not Available',
						],
						'openemis_id' => [
							'label' => 'OpenEMIS ID#',
							'value' =>  'Not Available',
						],
						'error' => $ApiAuthorizations->getErrorMessage(0),
					];
				}
			}

		} else {
			$message = 'id is missing, shown "' . $this->_errorCodes[4]['description'] . '( ' .$this->_errorCodes[4]['code'] . ' )" error message to requestor';
			Log::info($message, ['scope' => ['api']]);
			$result = [
				'error' => $ApiAuthorizations->getErrorMessage(4, ['identity_type' => $identity_type]),
			];
		}

		return $result;

	}

}
