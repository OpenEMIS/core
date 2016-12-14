<?php
namespace Configuration\Model\Behavior;

use Exception;
use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Network\Http\Client;
use Cake\Network\Exception\NotFoundException;

class DataSynchronisationBehavior extends Behavior {
	private $type = 'None';
	private $attributes = [];
	private $firstNameMapping = null;
	private $middleNameMapping = null;
	private $thirdNameMapping = null;
	private $lastNameMapping = null;
	private $genderMapping = null;
	private $dateOfBirthMapping = null;
	private $countryMapping = null;
	private $identityTypeMapping = null;
	private $identityNameMapping = null;
	private $userEndpoint = null;
	private $authEndpoint = null;

	public function initialize(array $config) {
		parent::initialize($config);
		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
		$type = $ConfigItems->find()->where([
				$ConfigItems->aliasField('code') => 'external_data_source_type',
			])
			->first()
			->value;

		$this->type = $type;

		if ($this->type != 'None') {
			$ExternalDataSourceAttributesTable = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
			$this->attributes = $ExternalDataSourceAttributesTable
				->find('list', [
					'keyField' => 'attribute_field',
					'valueField' => 'value'
				])
				->where([
					$ExternalDataSourceAttributesTable->aliasField('external_data_source_type') => $this->type
				])
				->toArray();

			$this->firstNameMapping = $this->attributes['first_name_mapping'];
			$this->middleNameMapping = $this->attributes['middle_name_mapping'];
			$this->thirdNameMapping = $this->attributes['third_name_mapping'];
			$this->lastNameMapping = $this->attributes['last_name_mapping'];
			$this->genderMapping = $this->attributes['gender_mapping'];
			$this->dateOfBirthMapping = $this->attributes['date_of_birth_mapping'];
			$this->countryMapping = $this->attributes['nationality_mapping'];
			$this->identityTypeMapping = $this->attributes['identity_type_mapping'];
			$this->identityNameMapping = $this->attributes['identity_number_mapping'];
			$this->authEndpoint = $this->attributes['token_uri'];
			$this->userEndpoint = $this->attributes['user_endpoint_uri'];
		}

	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.pull'] = 'pull';
		return $events;
	}

	public function pull(Event $mainEvent, ArrayObject $extra)
	{

	}

	public function viewAfterAction(Event $event, Entity $entity)
	{
		$http = new Client();
		$externalReference = $entity->getOriginal('external_reference');
		$placeHolder = '{external_reference}';
		$url = str_replace($placeHolder, $externalReference, $this->userEndpoint);
		$credentialToken = TableRegistry::get('Configuration.ExternalDataSourceAttributes')->generateServerAuthorisationToken($this->type);
		$data = [
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion' => $credentialToken
		];
		try {
			$response = $http->post($this->authEndpoint, $data);
			if ($response->statusCode() != '200') {
				throw new NotFoundException('Not a successful response');
			}
			$body = json_decode($response->body(), true);
			if (!is_array($body) && !isset($body['access_token'])) {
				throw new NotFoundException('Response body is in wrong format');
			}
		} catch (NotFoundException $e) {
			$this->_table->Alert->error('general.failConnectToExternalSource');
		} catch (Exception $e) {
			$this->_table->Alert->error('general.failConnectToExternalSource');
		}


	}
}
