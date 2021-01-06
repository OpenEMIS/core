<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\OptionsTrait;
use Cake\Http\Client;
use Cake\Network\Session;

class UserNationalitiesTable extends ControllerActionTable {
    use OptionsTrait;
    use MessagesTrait;
   
	public function initialize(array $config)
    {
        parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('NationalitiesLookUp', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);

        $this->securityUserId = $this->getQueryString('security_user_id');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
        $this->addBehavior('User.SetupTab');
        $this->addBehavior('CompositeKey');
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Users.afterSave' => 'afterSaveUsers'
        ];

        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function afterSaveUsers(Event $event, Entity $entity)
    {
        //check whether the combination user and nationality exist
        $query = $this->find()
                ->where([
                    $this->aliasField('security_user_id') => $entity->id,
                    $this->aliasField('nationality_id') => $entity->nationality_id
                ]);

        if ($query->count()) { //if exist then set as preferred.

            //use save instead of update to trigger after save events
            $userNationalityEntity = $this->patchEntity($query->first(), ['preferred' => 1], ['validate' =>false]);
            $this->save($userNationalityEntity);
            
        } else { //not exist then add new record and set as preferred.
            $userNationalityEntity = $this->newEntity([
                'preferred' => 1,
                'nationality_id' => $entity->nationality_id,
                'security_user_id' => $entity->id,
                'created_user_id' => 1,
                'created' => new Time()
            ]);
            $this->save($userNationalityEntity);
        }
    }

	public function beforeAction(Event $event) {
        unset($this->request->query['nationality_id']);
        unset($this->request->query['validate_number']);
        unset($this->request->query['number']);

        $this->fields['nationality_id']['type'] = 'select';
        $this->fields['identity_type_id']['type'] = 'select';
        $this->setFieldOrder([
            'nationality_id', 'comment', 'preferred','identity_type_id','number','validate_number'
        ]);
	}

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		$validator
            ->add('nationality_id', 'notBlank', ['rule' => 'notBlank'])
            ->add('preferred', 'ruleValidatePreferredNationality', [
                'rule' => ['validatePreferredNationality'],
                'provider' => 'table'
            ]);
            // task POCOR-5668 starts
            $isFieldsShow = $this->showIdentityTypeAndNumber();
            if($isFieldsShow > 0){
                $validator
                    ->add('identity_type_id', 'notBlank', ['rule' => 'notBlank'])
                    ->requirePresence('number')
                    ->notEmpty('number')
                    ->add('number', [
                        'ruleNumber' => [
                            'rule' => ['check_validate_number'],
                            'message' => __('Please validate before saving.')
                        ]
                    ]);
            }
            // task POCOR-5668 ends
        return $validator;
	}

	public function validationNonMandatory(Validator $validator) {
		$validator = $this->validationDefault($validator);
		return $validator->allowEmpty('nationality_id');
	}

	public function validationAddByAssociation(Validator $validator)
	{
		$validator = $this->validationDefault($validator);
		return $validator->requirePresence('security_user_id', false);
	}

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'NationalitiesLookUp'
        ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            if (!$this->exists([$this->aliasField('security_user_id') => $entity->security_user_id])) { // user does not have existing nationality record
                $entity->preferred = 1;
            }
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->dirty('preferred')) {
            if ($entity->preferred == 1) { //if set as preferred
                // update the rest of user nationality to not preferred
                $this->updateAll(
                    ['preferred' => 0],
                    [
                        'security_user_id' => $entity->security_user_id,
                        'id <> ' => $entity->id
                    ]
                );

                //update information on security user table
                $listeners = [
                    TableRegistry::get('User.Users')
                ];
                $this->dispatchEventToModels('Model.UserNationalities.onChange', [$entity], $this, $listeners);
            }
        }
        // task POCOR-5668 starts
        if(isset($this->request->params['pass'][0]) && $this->request->params['pass'][0] == 'add'){ 
            if ($entity->has('identity_type_id') && $entity->has('number') && $entity->has('validate_number'))
            {
                if($entity->validate_number == 1){
                    $UserIdentities = TableRegistry::get('user_identities');
                    $newEntity = $UserIdentities->newEntity([
                        'identity_type_id' => $entity->identity_type_id,
                        'number' => $entity->number,
                        'security_user_id' => $entity->security_user_id,
                        'created_user_id' => $entity->created_user_id,
                        'nationality_id' => $entity->nationality_id,
                        'created' => Time::now()
                    ]);
                    $UserIdentities->save($newEntity);

                    //update identity_type_id in nationalities table
                    $Nationalities = TableRegistry::get('nationalities');
                    $updateEntity = $Nationalities->newEntity([
                        'id' => $entity->nationality_id,
                        'identity_type_id' => $entity->identity_type_id,
                        'modified_user_id' => $entity->created_user_id,
                        'modified' => Time::now()
                    ]);
                    $Nationalities->save($updateEntity);
                }
            }
        }else{ //after save edit nationality
            if ($entity->has('identity_type_id') && $entity->has('number') && $entity->has('validate_number'))
            {
                $UserIdentitiesData = $this->findDataExistInUserIdentityTable($entity->identity_type_id, $entity->nationality_id, $entity->security_user_id);
                if($UserIdentitiesData->number != $entity->number){

                    $UserIdentities = TableRegistry::get('user_identities');
                    $update_identity_data = [
                        'id' => $UserIdentitiesData->id,
                        'identity_type_id' => $entity->identity_type_id,
                        'number' => $entity->number,
                        'security_user_id' => $entity->security_user_id,
                        'modified_user_id' => $entity->created_user_id,
                        'nationality_id' => $entity->nationality_id,
                        'modified' => Time::now()
                    ];
                    $patchOptions = ['validate' => false];
                    $newEntity = $UserIdentities->newEntity();
                    $newEntity = $UserIdentities->patchEntity($newEntity, $update_identity_data, $patchOptions);
                    $UserIdentities->save($newEntity);

                    //update identity_type_id in nationalities table
                    $Nationalities = TableRegistry::get('nationalities');
                    $updateEntity = $Nationalities->newEntity([
                        'id' => $entity->nationality_id,
                        'identity_type_id' => $entity->identity_type_id,
                        'modified_user_id' => $entity->created_user_id,
                        'modified' => Time::now()
                    ]);
                    $Nationalities->save($updateEntity);
                }
            }
        }
        // task POCOR-5668 ends
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        //check whether has minimum one nationality record.
        $query = $this
                ->find()
                ->where([
                    $this->aliasfield('security_user_id') => $entity->security_user_id,
                    $this->aliasfield('id <> ') => $entity->id
                ])
                ->count();

        if (!$query) {
            $this->Alert->warning('UserNationalities.noRecordRemain', ['reset'=>true]);
            return false;
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->preferred == 1) { //if the preferred nationality deleted

            //get the next latest nationality to be set as preferred
            $query = $this->find()
                    ->where([
                        $this->aliasfield('security_user_id') => $entity->security_user_id
                    ])
                    ->order('created DESC')
                    ->first();

            if (!empty($query)) {
                $query->preferred = 1;
                $this->save($query);
                $entity->nationality_id = $query->nationality_id; //send the new preferred nationality

                //update information on security user table
                $listeners = [
                    TableRegistry::get('User.Users')
                ];
                $this->dispatchEventToModels('Model.UserNationalities.onChange', [$entity], $this, $listeners);
            }
        }
    }

    // task POCOR-5668 starts
    public function onUpdateFieldIdentityTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $userId = null;
            $queryString = $this->getQueryString();
            if (isset($queryString['security_user_id'])) {
                $userId = $queryString['security_user_id'];
            }
            if ($action == 'add') {
                if(!empty($userId)){
                    //nationality_id
                    if(!empty($request->query['nationality_id'])){
                        $nationalityId = $request->query['nationality_id'];
                    }else{
                        $nationalityId = $attr['entity']->nationality_id;
                    }
                    $IdentityTypes = TableRegistry::get('identity_types')->find('list',
                                        ['keyField' => 'id','valueField' => 'name'
                                    ])->toArray();

                    $attr['options'] = $IdentityTypes;
                    if(!empty($nationalityId)){
                        //get nationality using national id
                        $nationalityTable = $this->getNationalityTableData($nationalityId);

                        if(!empty($nationalityTable) && !empty($nationalityTable->identity_types['id']) && $nationalityTable->default == 1){
                            $attr['type'] = 'readonly';
                            $attr['value'] = $nationalityTable->identity_types['id'];
                            $attr['attr']['value'] = $nationalityTable->identity_types['name'];
                        }else if(!empty($nationalityTable) && !empty($nationalityTable->identity_types['id']) && $nationalityTable->default == 0){
                            $attr['type'] = 'select';
                            $attr['attr']['value'] = $nationalityTable->identity_types['id'];
                        }else{
                            $attr['type'] = 'select';
                            $attr['attr']['value'] = '';
                        }
                    }else{
                        $attr['type'] = 'select';
                    }
                }
            } else if ($action == 'edit') {
                if(!empty($userId)){
                    $IdentityTypes = TableRegistry::get('identity_types');
                    $nationality = TableRegistry::get('Nationalities');
                    $nationalityTable = $nationality
                                        ->find()
                                        ->select([
                                            $nationality->aliasField('id'),
                                            $nationality->aliasField('name'),
                                            $nationality->aliasField('identity_type_id'),
                                            $nationality->aliasField('default'),
                                            $IdentityTypes->aliasField('id'),
                                            $IdentityTypes->aliasField('name')
                                        ])
                                        ->leftJoin(
                                            [$IdentityTypes->alias() => $IdentityTypes->table()], [
                                                $IdentityTypes->aliasField('id = ') . $nationality->aliasField('identity_type_id')
                                            ]
                                        )
                                        ->where([
                                            'Nationalities.default' => 1,
                                            'Nationalities.id' => $attr['entity']->nationality_id
                                        ])
                                        ->first();
                    
                    if(!empty($nationalityTable) && !empty($nationalityTable->identity_type_id)){
                        // when default identity in nationality table regarding country  
                        $attr['type'] = 'readonly';
                        $attr['value'] = $nationalityTable->identity_types['id'];
                        $attr['attr']['value'] = $nationalityTable->identity_types['name'];
                    }else{
                        $IdentityTypes = TableRegistry::get('identity_types')->find('list',
                                            ['keyField' => 'id','valueField' => 'name'
                                        ])->toArray();
                        $attr['options'] = $IdentityTypes;
                    }
                }
            }
        }
        return $attr;
    }


    public function onUpdateFieldNumber(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $userId = null;
            $queryString = $this->getQueryString();
            if (isset($queryString['security_user_id'])) {
                $userId = $queryString['security_user_id'];
            }

            $identity_number = !empty($request->query['number']) ? trim($request->query['number']) : '';
            if ($action == 'add') {
                $attr['attr']['value'] = $identity_number;
            } else if ($action == 'edit') {
                if(!empty($userId)){
                    //first check nationality have default or not
                    $nationalityTable = TableRegistry::get('Nationalities')
                                    ->find()
                                    ->where([
                                        'Nationalities.default' => 1,
                                        'Nationalities.id' => $attr['entity']->nationality_id
                                    ])
                                    ->first();

                    if(!empty($nationalityTable) && !empty($nationalityTable->identity_type_id)){
                        // second check when user have identity in user identity table  
                        $identityTypeData = $this->findDataExistInUserIdentityTable($nationalityTable->identity_type_id, $nationalityTable->id, $userId);
                        if(!empty($identityTypeData)){
                            //$attr['type'] = 'readonly';
                            if($identity_number == $identityTypeData->number){
                                $attr['attr']['value'] = $identityTypeData->number;
                            }else if($identity_number == ''){
                                $attr['attr']['value'] = $identityTypeData->number;
                            }else{
                                $attr['attr']['value'] = $identity_number;
                            }
                        }else{
                            $attr['attr']['value'] = '';
                        }
                    }else{
                        $attr['attr']['value'] = '';
                    }
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldValidateNumber(Event $event, array $attr, $action, Request $request)
    {
        $userId = null;
        $queryString = $this->getQueryString();
        if (isset($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        }
        $validate_number = !empty($request->query['validate_number']) ? $request->query['validate_number'] : 0;
        if ($action == 'add') {
            $attr['attr']['value'] = $validate_number;
        }  else if ($action == 'edit') {
            if(!empty($userId)){
                //first check nationality have default or not
                $nationalityTable = TableRegistry::get('Nationalities')
                                ->find()
                                ->where([
                                    'Nationalities.default' => 1,
                                    'Nationalities.id' => $attr['entity']->nationality_id
                                ])
                                ->first();
                if(!empty($nationalityTable) && !empty($nationalityTable->identity_type_id)){
                    // second check when user have identity in user identity table 
                    $identityTypeData = $this->findDataExistInUserIdentityTable($nationalityTable->identity_type_id, $nationalityTable->id, $userId);

                    if(!empty($identityTypeData) && !empty($identityTypeData->number)){
                        $attr['type'] = 'hidden';
                        $attr['value'] = $validate_number;
                    }
                    $attr['attr']['value'] = $validate_number;
                }else{
                    $attr['attr']['value'] = $validate_number;
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldNationalityId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {
                $currentNationalities = $this
                                        ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                                        ->matching('NationalitiesLookUp')
                                        ->where([
                                            $this->aliasfield('security_user_id') => $this->securityUserId
                                        ])
                                        ->select([
                                            'id' => $this->NationalitiesLookUp->aliasfield('id')
                                        ])
                                        ->toArray();
               
                $nationalities = $this->NationalitiesLookUp->find('all')->find('list')->order(['order','name']);
                              
                if (!empty($currentNationalities)) {
                    $nationalities = $nationalities
                                    ->where([
                                        $this->NationalitiesLookUp->aliasfield('id NOT IN ') => $currentNationalities
                                    ]);
                }

                $nationalities = $nationalities->toArray();
                $attr['options'] = $nationalities;
                $attr['onChangeReload'] = 'changeIdentityTypeId';
            } else if ($action == 'edit') {
                $entity = $attr['entity'];
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->nationality_id;
                $attr['attr']['value'] = $entity->nationalities_look_up->name;
            }
        }
        return $attr;
    }

    public function addEditOnChangeIdentityTypeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['nationality_id']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('nationality_id', $request->data[$this->alias()])) {
                    $request->query['nationality_id'] = $request->data[$this->alias()]['nationality_id'];
                }
            }
        }
    }
    // task POCOR-5668 ends
    public function onGetPreferred(Event $event, Entity $entity) {
        $preferredOptions = $this->getSelectOptions('general.yesno');
        return $preferredOptions[$entity->preferred];
    }

    public function onUpdateFieldPreferred(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    // task POCOR-5668 starts
    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {   
        $nationalityId = '';
        if(array_key_exists('nationality_id',$this->request->query)){ //when add nationality
            $nationalityId = $this->request->query['nationality_id'];
        } else if(isset($this->request->params['pass'][0]) && $this->request->params['pass'][0] == 'edit'){ //when edit nationality
            $nationalityId = $this->paramsDecode($this->request->params['pass']['1'])['nationality_id'];
        }else { //when add nationality
            $nationalityId = $this->request->data['UserNationalities']['nationality_id'];
        } 
        $userId = null;
        $queryString = $this->getQueryString();
        if (isset($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        }

        $nationalityTable = TableRegistry::get('Nationalities')
                            ->find()
                            ->where([
                                'Nationalities.id' => $nationalityId
                            ])
                            ->first();
        // validate button when external validation is enable and it has identity link added                       
        if(!empty($nationalityId) && ($nationalityTable['external_validation'] == 1) && ($nationalityTable['identity_type_id'] != '')){
            if ($this->action == 'add') {
                $originalButtons = $buttons->getArrayCopy();
                $startTestButton = [
                    [
                        'name' => '<i class="fa fa-chain-broken"></i>' . __('Validate'),
                        'attr' => [
                            'class' => 'btn btn-default',
                            'name' => 'submit',
                            'value' => 'getExternalUsers',
                            'div' => false
                        ]
                    ]
                ];
                array_splice($originalButtons, 0, 0, $startTestButton);
                $buttons->exchangeArray($originalButtons);
            } else if($this->action == 'edit'){
                //In edit, check user have already a identity number or not, if yes than no need to show validate button
                $identityTypeData = $this->findDataExistInUserIdentityTable($nationalityTable['identity_type_id'], $nationalityTable['id'], $userId);
                if(!empty($identityTypeData)){
                    $originalButtons = $buttons->getArrayCopy();
                    $startTestButton = [
                        [
                            'name' => '<i class="fa fa-chain-broken"></i>' . __('Validate'),
                            'attr' => [
                                'class' => 'btn btn-default',
                                'name' => 'submit',
                                'value' => 'getExternalUsers',
                                'div' => false
                            ]
                        ]
                    ];
                    array_splice($originalButtons, 0, 0, $startTestButton);
                    $buttons->exchangeArray($originalButtons);
                }
            }
        }
    }

    //search external users
    public function addEditOnGetExternalUsers()
    {
        $userId = null;
        $queryString = $this->getQueryString();
        if (isset($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        }
        $userData = TableRegistry::get('security_users')
                    ->find()
                    ->where([
                        'id' => $userId
                    ])->first();
        $this->autoRender = false;
        $ExternalAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->innerJoin(['ConfigItems' => 'config_items'], [
                'ConfigItems.code' => 'external_data_source_type',
                $ExternalAttributes->aliasField('external_data_source_type').' = ConfigItems.value'
            ])
            ->toArray();

        $clientId = $attributes['client_id'];
        $scope = $attributes['scope'];
        $tokenUri = $attributes['token_uri'];
        $privateKey = $attributes['private_key'];
  
        $token = $ExternalAttributes->generateServerAuthorisationToken($clientId, $scope, $tokenUri, $privateKey);
        
        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $token
        ];

        $this->request->query['number'] = $this->request->data['UserNationalities']['number'];
        $this->request->query['identity_number'] =  trim($this->request->query['number']);

        if($this->request->query['identity_number'] == ''){
            $this->request->query['validate_number'] = 0;
            $this->Alert->error('UserNationalities.IdentityNumberNotExist', ['reset' => true]);
        } else {

            $fieldMapping = [
                '{page}' => 1,
                '{limit}' => trim($this->request->query('limit')),
                '{first_name}' => trim($this->request->query('first_name')),
                '{last_name}' => trim($this->request->query('last_name')),
                '{identity_number}' => trim($this->request->query('identity_number')),
                '{date_of_birth}' => trim($this->request->query('date_of_birth'))
            ];

            $http = new Client();
            $response = $http->post($attributes['token_uri'], $data);
            $noData = json_encode(['data' => [], 'total' => 0], JSON_PRETTY_PRINT);
            if ($response->isOK()) {
                $body = $response->body('json_decode');
                $recordUri = $attributes['record_uri'];

                foreach ($fieldMapping as $key => $map) {
                    $recordUri = str_replace($key, $map, $recordUri);
                }

                $http = new Client([
                    'headers' => ['Authorization' => $body->token_type.' '.$body->access_token]
                ]);

                $response = $http->get($recordUri);
                $resultArr = $response->body('json_decode')->data;
                
                if(!empty($resultArr)){
                    $countVal = 0;
                    foreach ($resultArr as $arr) {
                        //you can remove $arr->openemis_no == $userData->openemis_no this condition while testing
                        if($arr->openemis_no == $userData->openemis_no && $arr->identity_number == trim($this->request->query('identity_number'))){
                            $countVal++;
                        }
                    }

                    if($countVal > 0){
                        $this->request->query['validate_number'] = 1;
                        $this->Alert->success('UserNationalities.ValidateNumberSuccess', ['reset' => true]);
                    }else{
                        $this->request->query['validate_number'] = 0;
                        $this->Alert->error('UserNationalities.ValidateNumberFail', ['reset' => true]);
                    }
                }else{
                    $this->request->query['validate_number'] = 0;
                    $this->Alert->error('UserNationalities.ValidateNumberFail', ['reset' => true]);
                }    
            } else {
                $this->request->query['validate_number'] = 0;
                $this->Alert->error('UserNationalities.ValidateNumberFail', ['reset' => true]);
            }
        }
    }

    public function getNationalityTableData($nationalityId){
        $IdentityTypes = TableRegistry::get('identity_types');
        $nationality = TableRegistry::get('Nationalities');
        $nationalityTable = $nationality
                            ->find()
                            ->select([
                                $nationality->aliasField('id'),
                                $nationality->aliasField('name'),
                                $nationality->aliasField('identity_type_id'),
                                $nationality->aliasField('default'),
                                $IdentityTypes->aliasField('id'),
                                $IdentityTypes->aliasField('name')
                            ])
                            ->leftJoin(
                                [$IdentityTypes->alias() => $IdentityTypes->table()], [
                                    $IdentityTypes->aliasField('id = ') . $nationality->aliasField('identity_type_id')
                                ]
                            )
                            ->where([
                                'Nationalities.id' => $nationalityId
                            ])
                            ->first();
        return $nationalityTable;
    }

    public function findDataExistInUserIdentityTable($nationality_table_identity_type_id, $nationality_table_id, $userId){
        $IdentityTypes = TableRegistry::get('identity_types');
        $UserIdentities = TableRegistry::get('UserIdentities');
        $identityTypeData = $UserIdentities
                                ->find()
                                ->select([
                                    $UserIdentities->aliasField('id'),
                                    $UserIdentities->aliasField('identity_type_id'),
                                    $IdentityTypes->aliasField('name'),
                                    $UserIdentities->aliasField('number'),
                                    $UserIdentities->aliasField('nationality_id'),
                                ])
                                ->leftJoin(
                                    [$IdentityTypes->alias() => $IdentityTypes->table()], [
                                        $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
                                    ]
                                )
                                ->where([
                                    'UserIdentities.identity_type_id' => $nationality_table_identity_type_id,
                                    'UserIdentities.nationality_id' => $nationality_table_id,
                                    'UserIdentities.security_user_id' => $userId
                                ])
                                ->first();
        return $identityTypeData;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('nationality_id', [
            'type' => 'select',
            'entity' => $entity,
        ]);
                    
        $this->field('preferred', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity
        ]);

        $isFieldsShow = $this->showIdentityTypeAndNumber();
        if($isFieldsShow > 0){
            $this->field('identity_type_id', [
                'type' => 'select',
                'entity' => $entity
            ]);

            $this->field('number', [
                'entity' => $entity
            ]);

            $this->field('validate_number', [
                'type' => 'hidden',
                'entity' => $entity
            ]);
        }
    }
    
    public function showIdentityTypeAndNumber(){
        $count = 0;
        $nationalityId = $identityName ='';
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        //$arr = array('StudentIdentities', 'StaffIdentities','GuardianIdentities','OtherIdentities');
        if(isset($this->request)){
            if($this->request->params['controller'] == 'Students'){
                $identityName = 'StudentIdentities';
            } elseif ($this->request->params['controller'] == 'Staff') {
                $identityName = 'StaffIdentities';
            } else {
                $session = $this->request->session();
                $isStudent = $session->read('Directory.Directories.is_student');
                $isStaff = $session->read('Directory.Directories.is_staff');
                $isGuardian = $session->read('Directory.Directories.is_guardian');
                if($this->request->params['controller'] == 'Directories'){
                    if($isStudent == 1){
                        $identityName = 'StudentIdentities';    
                    }elseif ($isStaff == 1) {
                        $identityName = 'StaffIdentities'; 
                    }elseif ($is_guardian == 1) {
                        $identityName = 'GuardianIdentities'; 
                    }else{
                        $identityName = 'OtherIdentities';
                    }
                }
            }
            
            $conditions = [
                    'code' => $identityName,
                    'value' => 1,
                ];
            $count = $ConfigItems->find()
                ->where($conditions)
                ->count();

            //$count =1;//for testing purpose   
            //check nationality has default 1 or 0, if 1 than show identity type/number
            if(isset($this->request->params['pass'][0]) && $this->request->params['pass'][0] == 'edit'){ //when edit nationality
                $nationalityId = $this->paramsDecode($this->request->params['pass']['1'])['nationality_id'];
            }else if(isset($this->request['data']['UserNationalities']['nationality_id'])){
                $nationalityId = $this->request['data']['UserNationalities']['nationality_id'];
            } 
            $nationalityData = $this->getNationalityTableData($nationalityId);  
            if($nationalityData->default == 1 && $count >= 1){
                return $count; 
            } else{
                return 0;   
            }    
        }
        return 0;
    }
    // task POCOR-5668 ends
}
