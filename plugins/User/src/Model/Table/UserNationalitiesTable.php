<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\OptionsTrait;
use Cake\Http\Client;
use Cake\Http\Session;
use Cake\Log\Log;
use Cake\ORM\Table; // POCOR-8989
use Cake\Utility\Inflector; // POCOR-8989

class UserNationalitiesTable extends ControllerActionTable {
    use OptionsTrait;
    use MessagesTrait;
    private $securityUserId = null; // POCOR-8989
    private $securityUser = null; // POCOR-8989
    private $identityIdRequired = false; // POCOR-8989

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('NationalitiesLookUp', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);

// POCOR-8989 removed for later

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
        $this->addBehavior('Institution.InstitutionTab',
            ['implementedMethods' => [
                'setUserTabElements' => 'setUserTabElements',
            ],
            ]);
        $this->addBehavior('User.SetupTab');
        $this->addBehavior('User.UserTab');
        $this->addBehavior('User.CreateUser');//POCOR-7727
        $this->addBehavior('CompositeKey');
    }

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Users.afterSave' => 'afterSaveUsers'
        ];

        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function afterSaveUsers(Event $event, Entity $entity)
    {
        $userID = $entity->security_user_id ?? $this->securityUserId; // POCOR-8989
        $nationalityID = $entity->nationality_id ?? -1;

        if ($nationalityID == -1 || $userID == -1) {
            return;
        }

        try {
            $query = $this->find()
                ->where([
                    $this->aliasField('security_user_id') => $userID,
                    $this->aliasField('nationality_id') => $nationalityID
                ]);
        } catch (\Exception $exception) {
            Log::debug('Query Exception: ' . $exception->getMessage()); // POCOR-8989
            return;
        }


        if ($query->count()) {
            $userNationalityEntity = $this->patchEntity($query->first(),
                ['preferred' => 1],
                ['validate' => false]);
            $this->save($userNationalityEntity);
        } else {
            $userNationalityEntity = $this->newEntity([
                'preferred' => 1,
                'nationality_id' => $nationalityID,
                'security_user_id' => $userID,
                'created_user_id' => 1,
                'created' => new Time()
            ]);
            $this->save($userNationalityEntity);
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $userID = $this->securityUserId;
            if ($userID) {
                $entity['security_user_id'] = $userID;
            }
            // POCOR-8989 make the only the preferred
            if (!$this->exists([
                $this->aliasField('security_user_id')
                => $entity->security_user_id])) {
                $entity->preferred = 1;
            }
        }

        if(isset($entity->identity_type_id) && isset($entity->number)) {
            $options['identity_type_id'] = $entity->identity_type_id;
            $options['identity_number'] = $entity->number;
            $options['nationality_id'] = $entity->nationality_id;
            $options['security_user_id'] = $entity->security_user_id;
            $message = $this->checkCustomIdentityNumber($options);
            if ($message != "") {
                $entity->setError('identity_type_id', $message);
                $entity->setError('number', $message);
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                $event->stopPropagation();
                return false;
            } else {
                $entity->validate_number = 1;
            }
        }
    }

    private function checkCustomIdentityNumber($options)
    {
        $pattern = '';

        if (isset($options['identity_type_id']) && !empty($options['identity_type_id'])) {
            $identityTypeId = $options['identity_type_id'];
        } else {
            return __("Please enter a valid Identity Type");
        }
        if (isset($options['nationality_id']) && !empty($options['nationality_id'])) {
            $nationalityId = $options['nationality_id'];
        } else {
            return __("Please enter a valid Nationality");
        }
        if (isset($options['identity_number']) && !empty($options['identity_number'])) {
            $identityNumber = $options['identity_number'];
        } else {
            return __("Please enter a valid Identity Number");
        }
        $securityUserId = $options['security_user_id'] ?? null;;
        $IdentityTypes = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $IdentityTypesData = $IdentityTypes
            ->find()
            ->where([$IdentityTypes->aliasField('id') => $identityTypeId])
            ->first();

        if (!empty($IdentityTypesData->validation_pattern)) {
            $pattern = '/' . $IdentityTypesData->validation_pattern . '/';
        }

        // custom validation is nullable, have to cater for the null pattern.
        if (!empty($pattern) && !preg_match($pattern, $identityNumber)) {
            return __("Please enter a valid Identity Number");
        }
        $exists = $this->findNumberExistInUserIdentityTable($identityTypeId, $nationalityId, $identityNumber, $securityUserId);
        if ($exists > 0) {
            return __("Please enter a unique Identity Number");
        }
        return "";
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // Handle preferred flag change
        if ($entity->getDirty('preferred') && $entity->preferred == 1) {
            $this->updateAll(
                ['preferred' => 0],
                [
                    'security_user_id' => $entity->security_user_id,
                    'id <>' => $entity->id
                ]
            );
            Log::debug('UserNationality Preferred Updated: ' . print_r($entity->toArray(), true));
            $listeners = [self::getDynamicTableInstance('User.Users')];
            $this->dispatchEventToModels('Model.UserNationalities.onChange',
                [$entity],
                $this,
                $listeners);
        }

        // Handle identity updates (POCOR-5668)
        $isAdd = isset($this->request) && ($this->request->getParam('pass')[0] ?? '') === 'add';

        $hasIdentityData = $entity->has('identity_type_id')
            && $entity->has('number')
            && $entity->has('validate_number');

        if ($hasIdentityData) {
            if ($entity->validate_number == 1) {
                if ($isAdd) {
                    $this->createUserIdentity($entity);
                } else {
                    $this->updateUserIdentityIfChanged($entity);
                }
            }
        }
    }

    protected function createUserIdentity(Entity $entity)
    {
        $UserIdentities = self::getDynamicTableInstance('User.Identities');
        $newIdentity = [
            'identity_type_id' => $entity->identity_type_id,
            'number' => $entity->number,
            'security_user_id' => $entity->security_user_id,
            'created_user_id' => $entity->created_user_id,
            'nationality_id' => $entity->nationality_id,
            'created' => Time::now()
        ];

        Log::debug('Creating new identity: ' . print_r($newIdentity, true));

        $newEntity = $UserIdentities->newEntity($newIdentity);
        $UserIdentities->save($newEntity);

    }

    protected function updateUserIdentityIfChanged(Entity $entity)
    {
        $UserIdentities = self::getDynamicTableInstance('User.Identities');
        $existing = $this->findDataExistInUserIdentityTable(
            $entity->identity_type_id,
            $entity->nationality_id,
            $entity->security_user_id
        );

        if ($existing && $existing->number !== $entity->number) {
            $updateData = [
                'id' => $existing->id,
                'identity_type_id' => $entity->identity_type_id,
                'number' => $entity->number,
                'security_user_id' => $entity->security_user_id,
                'modified_user_id' => $this->Auth->user('id'),
                'nationality_id' => $entity->nationality_id,
                'modified' => Time::now()
            ];

            Log::debug('Updating identity: ' . print_r($updateData, true));

            $newEntity = $UserIdentities->patchEntity($UserIdentities->newEntity([]), $updateData, ['validate' => false]);
            $UserIdentities->save($newEntity);

        }
    }

    public function beforeAction(Event $event) {
        $this->securityUserId =  $this->getQueryString('security_user_id');
        if($this->securityUserId == ''){
            $this->securityUserId = $this->getQueryString('user_id');
        }
        if(intval($this->securityUserId) > 0){
            $this->securityUser = $this->Users->get($this->securityUserId);
        }
        $this->fields['nationality_id']['type'] = 'select';
//        $this->fields['identity_type_id']['type'] = 'select';
        $this->setFieldOrder([
            'nationality_id', 'comment', 'preferred','identity_type_id','number','validate_number'
        ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->notEmptyString('nationality_id')
//            ->add('preferred', 'ruleValidatePreferredNationality', [
//                'rule' => ['validatePreferredNationality'],
//                'provider' => 'table'
//            ])
        ;
        // task POCOR-5668 starts
        $identityIdRequired = $this->identityIdRequired;
//        dd($identityIdRequired);
        if($identityIdRequired){
            $validator
                ->requirePresence('identity_type_id', ['create', 'update'], __('This field cannot be left empty'))
                ->notEmptyString('identity_type_id')
                ->requirePresence('number', ['create', 'update'], __('This field cannot be left empty'))
                ->notEmptyString('number')
                ->add('identity_type_id', 'ruleCustomIdentityType', [
                    'rule' => ['validateCustomIdentityType'],
                    'provider' => 'table',
                ])
                ->add('number', 'ruleCustomIdentityNumber', [
                    'rule' => ['validateCustomIdentityNumber'],
                    'provider' => 'table',
                    'last' => true
                ])
                ->add('number', [
                    'ruleUnique' => [
                        'rule' => ['validateUnique', ['scope' => 'identity_type_id']],
                        'provider' => 'table'
                    ]
                ])
                // POCOR-8989 removed external source validation

            ;
        }
//        dd($validator);
        return $validator;
    }

    public function validationNonMandatory(Validator $validator) {
        $validator = $this->validationDefault($validator);
        return $validator->allowEmptyString('nationality_id');
    }

    public function validationAddByAssociation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator->allowEmptyString('security_user_id');
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'NationalitiesLookUp'
        ]);
    }

    public function beforeDelete(Event $event, Entity $entity)
    {

        $UserNationalities = self::getDynamicTableInstance('User.Identities');
        $checkexistingNationalities = $UserNationalities->find()
            ->where([
                $UserNationalities->aliasField('nationality_id') => $entity->nationality_id,
                $UserNationalities->aliasField('security_user_id') => $entity->security_user_id,
            ])->count();
        // POCOR-7179[END]

        if ($checkexistingNationalities) {
            $this->Alert->warning('general.delete.checkIdentities', ['reset' => true]);
            // $this->Alert->warning('general.delete.NationalitiesRecordNoRemain', ['reset' => true]);
            // $this->Alert->warning('UserNationalities.noRecordRemain', ['reset'=>true]);
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
            // POCOR-7882:start
            if (empty($query)) {
                $security_user_id = $entity->security_user_id;
                $security_user = $this->Users->get($security_user_id);
                if(!empty($security_user)){
                    $security_user->nationality_id = null;
                    $this->Users->save($security_user);
                }
            }
            // POCOR-7882:end
        }
    }

    // task POCOR-5668 starts
    public function onUpdateFieldIdentityTypeId(Event $event, array $attr, $action, ServerRequest $request)
    {

        if (!in_array($action, ['add', 'edit'])){
            return $attr;
        }


        $entity = $attr['entity'] ?? null;
        if($entity == null){
            return $attr;
        }
        if(empty($this->security_user_id)) {

            $this->security_user_id = $entity->security_user_id ?? $this->getQueryString('security_user_id') ?? $this->getUserID();
        }

        $nationalityId = $entity->nationality_id ?? $this->getQueryString('nationality_id')
            ?? $this->request->getData('UserNationalities.nationality_id')
            ?? $this->request->getQuery('nationality_id');

        $identityTypesList = self::getDynamicTableInstance('identity_types')->find('list', [
            'keyField' => 'id',
            'valueField' => 'name'
        ])->toArray();

        if ($action === 'add') {
            $attr['options'] = $identityTypesList;

            if (!empty($nationalityId)) {
                $nationality = $this->getNationality($nationalityId);

                if (!empty($nationality?->identity_type_id)) {
                    if ($nationality->default == 1) {
                        $attr['type'] = 'readonly';
                        $attr['value'] = $nationality->identity_type_id;
                        $attr['attr']['value'] = $identityTypesList[$nationality->identity_type_id] ?? '';
                    } else {
                        $attr['type'] = 'select';
                        $attr['attr']['value'] = $nationality->identity_type_id;
                    }
                } else {
                    $attr['type'] = 'select';
                    $attr['attr']['value'] = '';
                }
            } else {
                $attr['type'] = 'select';
            }
        }

        if ($action === 'edit') {
            $nationality = $this->getNationality($entity->nationality_id);

            if (!empty($nationality?->identity_type_id)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $nationality->identity_type_id;
                $attr['attr']['value'] = $identityTypesList[$nationality->identity_type_id] ?? '';
            } else {
                $attr['options'] = $identityTypesList;
            }
        }

        return $attr;
    }


    public function onUpdateFieldNumber(Event $event, array $attr, $action, ServerRequest $request)
    {
        return $attr;
    }

    public function onUpdateFieldValidateNumber(Event $event, array $attr, $action, ServerRequest $request)
    {
        $userId = $this->getUserID();
        $validate_number = !empty($request->getQuery('validate_number')) ? $request->getQuery('validate_number') : 0;
        if ($action == 'add') {
            $attr['attr']['value'] = $validate_number;
        }  else if ($action == 'edit') {
            if(!empty($userId)){
                //first check nationality have default or not
                $nationalityTable = self::getDynamicTableInstance('FieldOption.Nationalities')
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

    public function onUpdateFieldNationalityId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $user_id = $this->getUserID();
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

                $nationalities = $this->NationalitiesLookUp->find('all')->find('list')/*->order(['order','name'])*/;

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
        $query = $request->getQuery(); // Get the query parameters as an array

        $alias = $this->getAlias();
        unset($query['nationality_id']); // Unset the specific parameter you want to remove
        if ($request->is(['post', 'put'])) {
            if (isset($data[$alias])) {
                if (isset($data[$alias]['nationality_id'])) {
                    $nationalityId = $data[$alias]['nationality_id']; // Get the 'nationality_id' value
                    $this->request = $this->request->withQueryParams(['nationality_id' => $nationalityId]); // Set the 'nationality_id' as a query parameter
                    $this->request = $this->request->withData('nationality_id', $nationalityId); // Set the 'nationality_id' as a query parameter
                }
            }
        }
    }
    // task POCOR-5668 ends
    public function onGetPreferred(Event $event, Entity $entity) {
        $preferredOptions = $this->getSelectOptions('general.yesno');
        return $preferredOptions[$entity->preferred];
    }

    public function onUpdateFieldPreferred(Event $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }




    public function getNationality($nationalityId){

        if (!is_numeric($nationalityId)) {
            return null;
        }
        $IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
        $Nationalities = self::getDynamicTableInstance('FieldOption.Nationalities');
        $Nationality = $Nationalities
            ->find()
            ->select([
                $Nationalities->aliasField('id'),
                $Nationalities->aliasField('name'),
                $Nationalities->aliasField('identity_type_id'),
                $Nationalities->aliasField('default'),
                $IdentityTypes->aliasField('id'),
                $IdentityTypes->aliasField('name')
            ])
            ->leftJoin(
                [$IdentityTypes->getAlias() => $IdentityTypes->getTable()], [
                    $IdentityTypes->aliasField('id = ') . $Nationalities->aliasField('identity_type_id')
                ]
            )
            ->where([
                'Nationalities.id' => $nationalityId
            ])
            ->first();
        return $Nationality;
    }

    public function findDataExistInUserIdentityTable($identity_type_id, $nationality_id, $userId){
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        $where = [
            $UserIdentities->aliasField('identity_type_id') => $identity_type_id,
            $UserIdentities->aliasField('nationality_id') => $nationality_id,
            $UserIdentities->aliasField('security_user_id') => $userId
        ];
//        Log::debug(print_r($where,true));
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
                [$IdentityTypes->getAlias() => $IdentityTypes->getTable()], [
                    $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
                ]
            )
            ->where($where)
            ->first();
        return $identityTypeData;
    }
    public function findNumberExistInUserIdentityTable($identity_type_id, $nationality_id, $number, $userId = null){
        $UserIdentities = TableRegistry::get('User.Identities');
        $where = [
            $UserIdentities->aliasField('identity_type_id') => $identity_type_id,
            $UserIdentities->aliasField('nationality_id') => $nationality_id,
            $UserIdentities->aliasField('number') => $number,
        ];

        if (!empty($userId)) {
            $where[$UserIdentities->aliasField('security_user_id') . ' !='] = $userId;
        }
//        Log::debug(print_r($where,true));
        $identityTypeCount = $UserIdentities
            ->find()
            ->select([
                $UserIdentities->aliasField('identity_type_id'),
                $UserIdentities->aliasField('number'),
                $UserIdentities->aliasField('nationality_id'),
            ])
            ->where($where)
            ->count();
        return $identityTypeCount;
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

        $identityIdRequired = $this->showIdentityTypeAndNumber($entity);
//        dd($identityIdRequired);
        $this->identityIdRequired = $identityIdRequired;
        if($identityIdRequired > 0){
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

    public function showIdentityTypeAndNumber($entity){
//        dd($entity);
//        dd($this->getQueryString());
        $entity->security_user_id = $this->securityUserId;
        $request = $this->request;

        $identityRequiredCode = '';
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        //$arr = array('StudentIdentities', 'StaffIdentities','GuardianIdentities','OtherIdentities');
        if(isset($request)){
            if($request->getParam('controller') == 'Students'){
                $identityRequiredCode = 'StudentIdentities';
            } elseif ($request->getParam('controller') == 'Staff') {
                $identityRequiredCode = 'StaffIdentities';
            } else {
                $securityUser = $this->securityUser;
                $isStudent = $securityUser->is_student;
                $isStaff = $securityUser->is_staff;
                $isGuardian = $securityUser->is_guardian;
                if ($request->getParam('controller') == 'Directories') {
                    if ($isStudent == 1) {
                        $identityRequiredCode = 'StudentIdentities';
                    } elseif ($isStaff == 1) {
                        $identityRequiredCode = 'StaffIdentities';
                    } elseif ($isGuardian == 1) {
                        $identityRequiredCode = 'GuardianIdentities';
                    } else {
                        $identityRequiredCode = 'OtherIdentities';
                    }
                }
            }

            $conditions = [
                'code' => $identityRequiredCode,
                'value' => 1,
            ];
            $count = $ConfigItems->find()
                ->where($conditions)
                ->count();
            //$count =1;//for testing purpose
            //check nationality has default 1 or 0, if 1 than show identity type/number
            $nationalityId = $this->getQueryString('nationality_id')
                ??  $request->getData('UserNationalities.nationality_id')
                ?? $request->getQuery('nationality_id');
//            dd($nationalityId);
            $nationalityId = intval($nationalityId);
            $nationalityData = $this->getNationality($nationalityId);
//            dd($nationalityData);
            if($nationalityData && $nationalityData->default == 1 && $count >= 1){
                return $count;
            } else{
                return 0;
            }
        }
        return 0;
    }
    // task POCOR-5668 ends

    /*POCOR-6267 Starts*/
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $securityUserId = $this->getUserID();
        if (!empty($securityUserId)) {
            $userId = $securityUserId;
        } else {
            // $userId = $session->read('Student.Students.id');
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        $query->where([$this->aliasField('security_user_id') => $userId]);
        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Nationalities','Staff - General');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        }elseif($this->request->getParam('controller') == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Nationalities','Students - General');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }elseif($this->request->getParam('controller') == 'Directories'){
            $is_manual_exist = $this->getManualUrl('Directory','Nationalities','General');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }elseif($this->request->getParam('controller') == 'Profiles'){
            $is_manual_exist = $this->getManualUrl('Personal','Nationalities','General');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }
        // End POCOR-5188
    }
    /*POCOR-6267 Ends*/
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if(isset($data['security_user_id']) && empty($data['security_user_id'])) {
            $userId = $this->getUserID();
            $data['security_user_id'] = $userId;
        }
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }
}
