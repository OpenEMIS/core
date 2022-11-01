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
use Cake\Http\Client;
use Cake\Network\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Datasource\ConnectionManager;

class PullBehavior extends Behavior
{
    private $type = 'None';
    private $attributes = [];
    private $firstNameMapping = null;
    private $middleNameMapping = null;
    private $thirdNameMapping = null;
    private $lastNameMapping = null;
    private $genderMapping = null;
    private $dateOfBirthMapping = null;
    private $nationalityMapping = null;
    private $identityTypeMapping = null;
    private $identityNumberMapping = null;
    private $addressMapping = null;
    private $postalMapping = null;
    private $userEndpoint = null;
    private $authEndpoint = null;
    private $changes = false;
    private $newValues = [];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $type = $ConfigItems->value('external_data_source_type');
      
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
            $this->nationalityMapping = $this->attributes['nationality_mapping'];
            $this->identityTypeMapping = $this->attributes['identity_type_mapping'];
            $this->identityNumberMapping = $this->attributes['identity_number_mapping'];
            $this->addressMapping = $this->attributes['address_mapping'];
            $this->postalMapping = $this->attributes['postal_mapping'];
            $this->authEndpoint = $this->attributes['token_uri'];
            $this->userEndpoint = $this->attributes['user_endpoint_uri'];
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 3];
        $events['ControllerAction.Model.pull'] = 'pull';
        $events['ControllerAction.Model.pull.afterAction'] = 'pullAfterAction';
        $events['ControllerAction.Model.pull.beforePatch'] = 'pullBeforePatch';
        $events['ControllerAction.Model.pull.afterSave'] = 'pullAfterSave';
        $events['ControllerAction.Model.onGetIdentityNumber'] = ['callable' => 'onGetIdentityNumber', 'priority' => 15];
        $events['ControllerAction.Model.onGetIdentityTypeId'] = ['callable' => 'onGetIdentityTypeId', 'priority' => 15];
        $events['ControllerAction.Model.onGetNationalityId'] = ['callable' => 'onGetNationalityId', 'priority' => 15];
        $events['ControllerAction.Model.onGetFirstName'] = ['callable' => 'onGetFirstName', 'priority' => 15];
        $events['ControllerAction.Model.onGetMiddleName'] = ['callable' => 'onGetMiddleName', 'priority' => 15];
        $events['ControllerAction.Model.onGetThirdName'] = ['callable' => 'onGetThirdName', 'priority' => 15];
        $events['ControllerAction.Model.onGetLastName'] = ['callable' => 'onGetLastName', 'priority' => 15];
        $events['ControllerAction.Model.onGetGenderId'] = ['callable' => 'onGetGenderId', 'priority' => 15];
        $events['ControllerAction.Model.onGetDateOfBirth'] = ['callable' => 'onGetDateOfBirth', 'priority' => 15];
        $events['ControllerAction.Model.onGetAddress'] = ['callable' => 'onGetAddress', 'priority' => 15];
        $events['ControllerAction.Model.onGetPostal'] = ['callable' => 'onGetPostal', 'priority' => 15];
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        if ($model->action == 'pull') {
            $this->newValues = $model->getQueryString(null, 'display');
            $fieldOrder = $model->fields;
            foreach ($model->fields as $key => $field) {
                if (!array_key_exists($key, $this->newValues)) {
                    $model->fields[$key]['visible'] = false;
                }
            }
            $extra['elements']['view'] = ['name' => 'OpenEmis.ControllerAction/view', 'order' => 5];
            $url = $this->_table->url('view', 'QUERY');
            $url[] = $model->paramsPass(0);
            $extra['back'] = $url;
        }
    }

    public function pull(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $request = $model->request;
        $extra['config']['form'] = true;
        $extra['patchEntity'] = true;

        $event = $model->dispatchEvent('ControllerAction.Model.pull.beforeAction', [$extra], $this);
        if ($event->isStopped()) { return $event->result; }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }

        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
        $sessionKey = $model->registryAlias() . '.primaryKey';
        $contain = [];

        if (empty($ids)) {
            if ($model->Session->check($sessionKey)) {
                $ids = $model->Session->read($sessionKey);
            } else if (!empty($model->getQueryString(null, 'data'))) {
                // Query string logic not implemented yet, will require to check if the query string contains the primary key
                $primaryKey = $model->primaryKey();
                $ids = $model->getQueryString($primaryKey, 'data');
            }
        }

        $idKeys = $model->getIdKeys($model, $ids);

        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
                $contain[] = $assoc->name();
            }
        }

        $entity = false;

        if ($model->exists($idKeys)) {
            $query = $model->find()->where($idKeys)->contain($contain);

            $event = $model->dispatchEvent('ControllerAction.Controller.beforeQuery', [$model, $query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.pull.beforeQuery', [$query, $extra], $this);

            $entity = $query->first();
        }

        $event = $model->dispatchEvent('ControllerAction.Model.pull.afterQuery', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }

        if ($entity) {
            if ($request->is(['post', 'put'])) {
                $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
                $patchOptions = new ArrayObject([]);
                $queryStringData = new ArrayObject($model->getQueryString(null, 'data'));
                $params = [$entity, $queryStringData, $patchOptions, $extra];

                if ($submit == 'save') {
                    $event = $model->dispatchEvent('ControllerAction.Model.pull.beforePatch', $params, $this);
                    if ($event->isStopped()) { return $event->result; }

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $queryString = $queryStringData->getArrayCopy();
                    if ($extra['patchEntity']) {
                        $entity = $model->patchEntity($entity, $queryString, $patchOptionsArray);
                        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterPatch', $params, $this);
                        if ($event->isStopped()) { return $event->result; }
                    }
                    $process = function ($model, $entity) {
                        return $model->save($entity);
                    };
                    $event = $model->dispatchEvent('ControllerAction.Model.pull.beforeSave', [$entity, $queryStringData, $extra], $this);
                    if ($event->isStopped()) { return $event->result; }
                    if (is_callable($event->result)) {
                        $process = $event->result;
                    }
                    $result = $process($model, $entity);

                    if (!$result) {
                        Log::write('debug', $entity->errors());
                    }

                    $event = $model->dispatchEvent('ControllerAction.Model.pull.afterSave', $params, $this);
                    if ($event->isStopped()) { return $event->result; }

                    if ($result) {
                        $mainEvent->stopPropagation();
                        return $model->controller->redirect($model->url('view'));
                    }
                }
            }
            $model->controller->set('data', $entity);
        }

        $event = $model->dispatchEvent('ControllerAction.Model.pull.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }

        if (!$entity) {
            $mainEvent->stopPropagation();
            return $model->controller->redirect($model->url('index', 'QUERY'));
        }
        return $entity;
    }

    public function pullAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->_table->Alert->info('general.reconfirm');
    }

    public function pullBeforePatch(Event $event, Entity $entity, ArrayObject $queryString, ArrayObject $patchOption, ArrayObject $extra)
    {
    	$model = $this->_table;
    	$schema = $model->schema();
    	$dateFields = [];
    	foreach ($schema->columns() as $column) {
    		if ($schema->columnType($column) == 'date' || $schema->columnType($column) == 'time' || $schema->columnType($column) == 'datetime') {
    			$dateFields[] = $column;
    		}
    	}
    	foreach ($queryString as $key => $value) {
    		if (in_array($key, $dateFields)) {
    			$queryString[$key] = new Time($value);
    		}
    	}
    	$UserNationalitiesTable = TableRegistry::get('User.UserNationalities');
    	$userNationalities = $UserNationalitiesTable->find()->where([
    		$UserNationalitiesTable->aliasField('security_user_id') => $entity->getOriginal('id'),
    		$UserNationalitiesTable->aliasField('nationality_id') => $queryString['nationality_id']
    	])
    	->first();

    	if (empty($userNationalities) && $queryString['nationality_id']) {
    		$patchOption['associated'][] = 'Nationalities';
    		$entity->dirty('nationalities', true);
    		$queryString['nationalities'] = [
	    		[
                    'nationality_id' => $queryString['nationality_id'],
                    'preferred' => 1
                ]
	    	];
    	}

    	$UserIdentitiesTable = TableRegistry::get('User.Identities');
    	$userIdentity = $UserIdentitiesTable->find()->where([
    		$UserIdentitiesTable->aliasField('security_user_id') => $entity->getOriginal('id'),
    		$UserIdentitiesTable->aliasField('identity_type_id') => $queryString['identity_type_id'],
    		$UserIdentitiesTable->aliasField('number') => $queryString['identity_number']
    	])
    	->first();
    	if (empty($userIdentity) && $queryString['identity_type_id'] && $queryString['identity_number']) {
    		$patchOption['associated'][] = 'Identities';
    		$entity->dirty('identities', true);
    		$patchOption['associated'][] = 'Identities';
    		$queryString['identities'] = [
	    		[
	    			'identity_type_id' => $queryString['identity_type_id'],
	    			'number' => $queryString['identity_number']
	    		]
	    	];
    	}
        if (isset($queryString['identity_number'])) {
            unset($queryString['identity_number']);
        }
        if (isset($queryString['identity_type_id'])) {
            unset($queryString['identity_type_id']);
        }
    }

    public function pullAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
        $model = $this->_table;
        $errors = $entity->errors();
        if (empty($errors)) {
            $model->Alert->success('general.edit.success');

            //to update nationality from external source as preferred
            $nationalityId = $entity->nationality_id;

            if (!empty($nationalityId)) {
                $UserNationalitiesTable = TableRegistry::get('User.UserNationalities');
                //unset all existing record
                $UserNationalitiesTable->updateAll(
                    ['preferred' => 0],
                    ['security_user_id' => $entity->id]
                );

                //set as preferred
                $userNationality = $UserNationalitiesTable
                                    ->find()
                                    ->where([
                                        'nationality_id' => $entity->nationality_id,
                                        'security_user_id' => $entity->id
                                    ])
                                    ->first();

                $userNationality->preferred = 1;
                $UserNationalitiesTable->save($userNationality); //save() to trigger after save
            }
        } else {
            $model->Alert->error('general.edit.failed');
            $errors = Hash::flatten($errors);
            foreach ($errors as $error) {
                $model->Alert->error(__($error), ['type' => 'text']);
            }
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, $extra)
    {
        $externalReference = $entity->getOriginal('external_reference');

        if (!empty($externalReference)) {
            if ($this->type != 'None') {
                $http = new Client();
                $clientId = $this->attributes['client_id'];
                $scope = $this->attributes['scope'];
                $tokenUri = $this->attributes['token_uri'];
                $privateKey = $this->attributes['private_key'];

	            $credentialToken = TableRegistry::get('Configuration.ExternalDataSourceAttributes')->generateServerAuthorisationToken($clientId, $scope, $tokenUri, $privateKey);
	            $data = [
	                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
	                'assertion' => $credentialToken
	            ];

	            try {
	                // Getting access token
	                $response = $http->post($this->authEndpoint, $data);
	                if ($response->getStatusCode() != '200') {
	                    throw new Exception('Not a successful response');
	                }
	                $body = json_decode($response->body(), true);
	                if (!is_array($body) && !isset($body['access_token']) && !isset($body['token_type'])) {
	                    throw new NotFoundException('Response body is in wrong format');
	                }

	                $placeHolder = '{external_reference}';
	                $url = str_replace($placeHolder, $externalReference, $this->userEndpoint);

	                // Getting data
	                // Getting access token
	                $http = new Client([
	                    'headers' => ['Authorization' => $body['token_type'].' '.$body['access_token']]
	                ]);
	                $response = $http->get($url);
	                if ($response->getStatusCode() != '200') {
	                    throw new Exception('Not a successful response');
	                }
	                $body = json_decode($response->body(), true);
	                if (!is_array($body) || !isset($body['data'])) {
	                    throw new NotFoundException('Response body is in wrong format');
	                }

	                $fieldOrder = array_keys($this->_table->fields);
	                $this->newValues['first_name'] = $this->setChanges($entity->first_name, $this->getValue($body['data'], $this->firstNameMapping), $this->firstNameMapping);
	                $this->newValues['middle_name'] = $this->setChanges($entity->middle_name, $this->getValue($body['data'], $this->middleNameMapping), $this->middleNameMapping);
	                $this->newValues['third_name'] = $this->setChanges($entity->third_name, $this->getValue($body['data'], $this->thirdNameMapping), $this->thirdNameMapping);
	                $this->newValues['last_name'] = $this->setChanges($entity->last_name, $this->getValue($body['data'], $this->lastNameMapping), $this->lastNameMapping);
	                $this->newValues['identity_number'] = $this->setChanges($entity->identity_number, $this->getValue($body['data'], $this->identityNumberMapping), $this->identityNumberMapping);
	                $this->newValues['date_of_birth'] = $this->setDateChanges($entity->date_of_birth, $this->getValue($body['data'], $this->dateOfBirthMapping), $this->dateOfBirthMapping);
                    $this->newValues['address'] = $this->setChanges($entity->address, $this->getValue($body['data'], $this->addressMapping), $this->addressMapping);
                    $this->newValues['postal_code'] = $this->setChanges($entity->postal_code, $this->getValue($body['data'], $this->postalMapping), $this->postalMapping);
	                $NationalitiesTable = TableRegistry::get('FieldOption.Nationalities');
	                $nationalityName = trim($this->getValue($body['data'], $this->nationalityMapping));
	                $nationalityArr = [
	                    'id' => null,
	                    'name' => ''
	                ];
	                if ($nationalityName) {
	                    $nationality = $NationalitiesTable
	                        ->find()
	                        ->where([$NationalitiesTable->aliasField('name') => $nationalityName])
	                        ->first();
	                    if (empty($nationality)) {
	                        $nationality = $NationalitiesTable->newEntity([
	                            'name' => $nationalityName,
	                            'visible' => 1,
	                            'editable' => 1,
	                            'default' => 0
	                        ]);
	                        $nationality = $NationalitiesTable->save($nationality);
	                    }

	                    $nationalityArr['id'] = $nationality->id;
	                    $nationalityArr['name'] = $nationality->name;
	                }
	                $this->newValues['nationality_id'] = $this->setChanges($entity->main_nationality, $nationalityArr, $this->nationalityMapping);

	                $IdentityTypesTable = TableRegistry::get('FieldOption.IdentityTypes');
	                $identityTypeName = trim($this->getValue($body['data'], $this->identityTypeMapping));
	                $identityTypeArr = [
	                    'id' => null,
	                    'name' => ''
	                ];
	                if ($identityTypeName) {
	                    $identityType = $IdentityTypesTable
	                        ->find()
	                        ->where([$IdentityTypesTable->aliasField('name') => $identityTypeName])
	                        ->first();
	                    if (empty($identityType)) {
	                        $identityType = $IdentityTypesTable->newEntity([
	                            'name' => $identityTypeName,
	                            'visible' => 1,
	                            'editable' => 1,
	                            'default' => 0
	                        ]);
	                        $identityType = $IdentityTypesTable->save($identityType);
	                    }
	                    $identityTypeArr['id'] = $identityType->id;
	                    $identityTypeArr['name'] = $identityType->name;
	                }
	                $this->newValues['identity_type_id'] = $this->setChanges($entity->main_identity_type, $identityTypeArr, $this->identityTypeMapping);

	                $genders = TableRegistry::get('User.Genders')->find()->select(['id', 'name'])->hydrate(false)->toArray();
	                $genderName = __(trim($this->getValue($body['data'], $this->genderMapping)));
	                $genderArr = current($genders);
	                foreach ($genders as $key => $value) {
	                	if ($genderName == __($value['name'])) {
	                		$genderArr = $genders[$key];
	                		break;
	                	}
	                }
	                $this->newValues['gender_id'] = $this->setChanges($entity->gender, $genderArr, $this->genderMapping);
                    if ($this->changes) {
    	                $toolbarButton = [
    	                    'type' => 'button',
    	                    'label' => '<i class="fa fa-refresh"></i>',
    	                    'attr' => [
    	                        'class' => 'btn btn-xs btn-default',
    	                        'data-toggle' => 'tooltip',
    	                        'data-placement' => 'bottom',
    	                        'escape' => false,
    	                        'title' => __('Synchronisation')
    	                    ],
    	                    'url' => [
    	                        'plugin' => $this->_table->controller->plugin,
    	                        'controller' => $this->_table->controller->name,
    	                        'action' => $this->_table->alias(),
    	                        '0' => 'pull',
    	                        '1' => $this->_table->paramsEncode(['id' => $entity->getOriginal('id')])
    	                    ]
    	                ];

                        $externalDataValue = new ArrayObject();
                        $this->setExternalDataValue($externalDataValue, 'first_name', $this->getValue($body['data'], $this->firstNameMapping), $this->firstNameMapping);
                        $this->setExternalDataValue($externalDataValue, 'middle_name', $this->getValue($body['data'], $this->middleNameMapping), $this->middleNameMapping);
                        $this->setExternalDataValue($externalDataValue, 'third_name', $this->getValue($body['data'], $this->thirdNameMapping), $this->thirdNameMapping);
                        $this->setExternalDataValue($externalDataValue, 'last_name', $this->getValue($body['data'], $this->lastNameMapping), $this->lastNameMapping);
                        $this->setExternalDataValue($externalDataValue, 'gender_id', $genderArr['id'], $this->genderMapping);
                        $this->setExternalDataValue($externalDataValue, 'date_of_birth', new Time($this->getValue($body['data'], $this->dateOfBirthMapping)), $this->dateOfBirthMapping);
                        $this->setExternalDataValue($externalDataValue, 'address', $this->getValue($body['data'], $this->addressMapping), $this->addressMapping);
                        $this->setExternalDataValue($externalDataValue, 'postal_code', $this->getValue($body['data'], $this->postalMapping), $this->postalMapping);
                        $this->setExternalDataValue($externalDataValue, 'identity_number', $this->getValue($body['data'], $this->identityNumberMapping), $this->identityNumberMapping);
                        $this->setExternalDataValue($externalDataValue, 'identity_type_id', $identityTypeArr['id'], $this->identityTypeMapping);
                        $this->setExternalDataValue($externalDataValue, 'nationality_id', $nationalityArr['id'], $this->nationalityMapping);

    	                $toolbarButton['url'] = $this->_table->setQueryString($toolbarButton['url'], $externalDataValue->getArrayCopy(), 'data');
    	                $toolbarButton['url'] = $this->_table->setQueryString($toolbarButton['url'], $this->newValues, 'display');

                        $extra['toolbarButtons']['synchronise'] = $toolbarButton;
                    }


                    $this->_table->field('first_name');
                    $this->_table->field('middle_name');
                    $this->_table->field('third_name');
                    $this->_table->field('last_name');
                    $this->_table->field('identity_number');
                    $this->_table->field('date_of_birth');
                    $this->_table->field('nationality_id');
                    $this->_table->setFieldOrder($fieldOrder);
                } catch (NotFoundException $e) {
                    $this->_table->Alert->error('general.notExistsInExternalSource');
                } catch (Exception $e) {
                    $this->_table->Alert->error('general.failConnectToExternalSource');
                }
            }
        }
    }

    private function setExternalDataValue(ArrayObject $externalDataValue, $field, $value, $mapping)
    {
        if (!empty($mapping)) {
            $externalDataValue[$field] = $value;
        }
    }

    public function onGetFirstName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['first_name'])) {
            return $this->newValues['first_name'];
        }
    }

    public function onGetMiddleName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['middle_name'])) {
            return $this->newValues['middle_name'];
        }
    }

    public function onGetThirdName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['third_name'])) {
            return $this->newValues['third_name'];
        }
    }

    public function onGetLastName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['last_name'])) {
            return $this->newValues['last_name'];
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        if (isset($this->newValues['identity_number'])) {
            return $this->newValues['identity_number'];
        }
    }

    public function onGetDateOfBirth(Event $event, Entity $entity)
    {
        if (isset($this->newValues['date_of_birth'])) {
            return $this->newValues['date_of_birth'];
        }
    }

    public function onGetNationalityId(Event $event, Entity $entity)
    {
        if (isset($this->newValues['nationality_id'])) {
            return $this->newValues['nationality_id'];
        }
    }

    public function onGetIdentityTypeId(Event $event, Entity $entity)
    {
        if (isset($this->newValues['identity_type_id'])) {
            return $this->newValues['identity_type_id'];
        }
    }

    public function onGetGenderId(Event $events, Entity $entity)
    {
        if (isset($this->newValues['gender_id'])) {
            return $this->newValues['gender_id'];
        }
    }

    public function onGetAddress(Event $events, Entity $entity)
    {
        if (isset($this->newValues['address'])) {
            return $this->newValues['address'];
        }
    }

    public function onGetPostalCode(Event $events, Entity $entity)
    {
        if (isset($this->newValues['postal_code'])) {
            return $this->newValues['postal_code'];
        }
    }

    private function setDateChanges($oldDate, $newDate, $mapping)
    {
        $oldValue = $this->_table->formatDate(new Time($oldDate));
        $newValue = $this->_table->formatDate(new Time($newDate));

        if (empty($mapping)) {
            return null;
        } else if ($oldValue != $newValue) {
            $this->changes = true;
            return '<span class="status past">'.$oldValue.'</span> <span class="transition-arrow"></span> <span class="status highlight">'.$newValue.'</span>';
        } else {
            return null;
        }
    }

    private function setChanges($oldValue, $newValue, $mapping)
    {
        if (empty($mapping)) {
            return null;
        } else if (is_array($newValue)) {
            $oldValueId = isset($oldValue['id']) ? $oldValue['id'] : null;
            $oldValueName = isset($oldValue['name']) ? $oldValue['name'] : '';
            if ($oldValueId != $newValue['id']) {
                $this->changes = true;
                return '<span class="status past">'.__($oldValueName).'</span> <span class="transition-arrow"></span> <span class="status highlight">'.__($newValue['name']).'</span>';
            } else {
                return null;
            }
        } else if ($oldValue != $newValue) {
            $this->changes = true;
            return '<span class="status past">'.$oldValue.'</span> <span class="transition-arrow"></span> <span class="status highlight">'.$newValue.'</span>';
        } else {
            return null;
        }
    }

    private function getValue($body, $value)
    {
        return isset($body[$value]) ? $body[$value] : '';
    }
}