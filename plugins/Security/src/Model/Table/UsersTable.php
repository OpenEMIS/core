<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\FrozenTime;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Table;
use Cake\Log\Log;
use Cake\Event\EventInterface;

class UsersTable extends ControllerActionTable
{
    use OptionsTrait;
    //PCOOR-6922 Starts
    const ALL = 0;
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    const ACTIVE = 1;
    const INACTIVE = 2;//PCOOR-6922 Ends
    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);
        $this->setEntityClass('User.User');

        // $this->belongsTo('Students', [
        //     'foreignKey' => 'student_id', // Replace with your actual foreign key field
        // ]);
        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->belongsToMany('Roles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_user_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $this->hasMany('Identities', ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts', ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Attachments', ['className' => 'User.Attachments',         'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('BankAccounts', ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Comments', ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Languages', ['className' => 'User.UserLanguages',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Awards', ['className' => 'User.Awards',          'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Logins', ['className' => 'SSO.SecurityUserLogins', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments',    'foreignKey' => 'security_user_id', 'dependent' => true]);

        $this->hasMany('Counsellings', ['className' => 'Counselling.Counsellings', 'foreignKey' => 'counselor_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('BodyMasses', ['className' => 'User.UserBodyMasses', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Insurances', ['className' => 'User.UserInsurances', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('ScholarshipApplications', ['className' => 'Report.ScholarshipApplications', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipHistories', ['className' => 'Scholarship.Histories', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'security_user_create',
                'entity_delete' => 'security_user_delete',
                'entity_update' => 'security_user_update',
                'table_alias' => 'User.Users'
            ]
        ); // for webhook
        $this->addBehavior('User.MoodleCreateUser');
        $this->addBehavior('OpenEmis.Section');
        //POCOR-6922 starts
        $this->addBehavior('User.AdvancedIdentitySearch');
        $this->addBehavior('User.AdvancedContactNumberSearch');
        $this->addBehavior('User.AdvancedPositionSearch');
        $this->addBehavior('User.AdvancedSpecificNameTypeSearch');


        //specify order of advanced search fields
        $advancedSearchFieldOrder = [
            'user_type', 'first_name', 'middle_name', 'third_name', 'last_name',
            'openemis_no', 'gender_id', 'contact_number', 'username', 'status', 'identity_type', 'identity_number'
        ];

        $this->addBehavior('AdvanceSearch', [
            'include' =>[
                'openemis_no', 'username'
            ],
            'order' => $advancedSearchFieldOrder,
            'showOnLoad' => 1,
            'customFields' => ['user_type','status']
        ]);
        //POCOR-6922 ends

        $this->setDisplayField('name_with_id_role');

    }

    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary)
    {
        if ($primary) {
            $schema = $this->getSchema();
            $fields = $schema->columns();
            foreach ($fields as $key => $field) {
                //POCOR-6380 - added OR condition to unset pre-defined fields only for Administration >> Security> Users listing
                if ($schema->getColumn($field)['type'] == 'binary') {
                    if ($this->getTable() == 'security_users' || $this->action == 'index') {
                        unset($fields[$key]);
                    }
                }
            }

            return $query->select($fields);
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';//POCOR-6922
        $events['AdvanceSearch.onModifyConditions'] = 'onModifyConditions';//POCOR-6922
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    //POCOR-6922 starts
    public function getCustomFilter(EventInterface $event)
    {
        $filters['user_type'] = [
            'label' => __('User Type'),
            'options' => [
                self::STAFF => __('Staff'),
                self::STUDENT => __('Students'),
                self::GUARDIAN => __('Guardians'),
                self::OTHER => __('Others')
            ]
        ];

        $filters['status'] = [
            'label' => __('Status'),
            'options' => [
                self::ACTIVE => __('Active'),
                self::INACTIVE => __('Inactive')
            ]
        ];

        return $filters;
    }

    public function onModifyConditions(EventInterface $events, $key, $value)
    {
        $conditions = [];
        if ($key == 'user_type') {

            switch ($value) {
                case self::STUDENT:
                    $conditions[] = $this->aliasField('is_student = 1');
                    break;

                case self::STAFF:
                    $conditions[] = $this->aliasField('is_staff = 1');
                    break;

                case self::GUARDIAN:
                    $conditions[] = $this->aliasField('is_guardian = 1');
                    break;

                case self::OTHER:
                    $conditions = [
                        $this->aliasField('is_student = 0'),
                        $this->aliasField('is_staff = 0'),
                        $this->aliasField('is_guardian = 0')
                    ];
                    break;
            }
        }

        if ($key == 'status') {
            switch ($value) {
                case self::ACTIVE:
                    $conditions[] = $this->aliasField('status = 1');
                    break;

                case self::INACTIVE:
                    $conditions[] = $this->aliasField('status = 0');
                    break;
            }
        }
        return $conditions;
    }

    public function indexAfterAction(EventInterface $event)
    {
        $this->controller->set('ngController', 'AdvancedSearchCtrl');
    }//POCOR-6922 ends

    public function studentsAfterSave(EventInterface $event, Entity $entity)
    {
        if ($entity->isNew()) {
            $this->updateAll(['is_student' => 1], ['id' => $entity->student_id]);
        }
    }

    // autocomplete used for UserGroups
    // the same function is found in User.Users
    public function autocomplete($search)
    {
        $data = [];
        if (!empty($search)) {
            $query = $this
                ->find()
                ->select([
                    $this->aliasField('openemis_no'),
                    $this->aliasField('first_name'),
                    $this->aliasField('middle_name'),
                    $this->aliasField('third_name'),
                    $this->aliasField('last_name'),
                    $this->aliasField('preferred_name'),
                    $this->aliasField('id')
                ])
                ->order([
                    $this->aliasField('first_name'),
                    $this->aliasField('last_name')
                ])
                ->limit(100);

            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['searchTerm' => $search]);
            $list = $query->toArray();

            foreach ($list as $obj) {
                $data[] = [
                    'label' => sprintf('%s - %s', $obj->openemis_no, $obj->name),
                    'value' => $obj->id
                ];
            }
        }
        return $data;
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields['photo_content']['visible'] = false;
        $this->fields['password']['visible'] = false; //POCOR-9666
        $this->fields['address']['visible'] = false;
        $this->fields['postal_code']['visible'] = false;
        $this->fields['address_area_id']['visible'] = false;
        $this->fields['birthplace_area_id']['visible'] = false;
        $this->fields['nationality_id']['type'] = 'readonly';
        $this->fields['identity_type_id']['type'] = 'readonly';
        $this->fields['status']['visible'] = true;
        $this->fields['failed_logins']['visible'] = false;
        if ($this->action == 'edit') {
            $this->fields['last_login']['visible'] = false;
        }

        $this->field('status', ['visible' => true, 'options' => $this->getSelectOptions('general.active')]);
        $this->setFieldOrder([
            'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'status', 'username', 'password'
        ]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        $this->fields['first_name']['visible'] = false;
        $this->fields['middle_name']['visible'] = false;
        $this->fields['third_name']['visible'] = false;
        $this->fields['preferred_name']['visible'] = false;
        $this->fields['last_name']['visible'] = false;
        $this->fields['gender_id']['visible'] = false;
        $this->fields['date_of_birth']['visible'] = false;
        $this->fields['username']['visible'] = true;
        $this->fields['name']['visible'] = true;
    }

    public function _indexBeforePaginate(EventInterface $event, ServerRequest $request, Query $query, ArrayObject $options)
    {
        //POCOR-6922 Start
        if (!$this->isAdvancedSearchEnabled()) {
            $event->stopPropagation();
            return [];
        } else {
            $this->behaviors()->get('AdvanceSearch')->setConfig([
                'showOnLoad' => 0,
            ]);
        }

        $conditions = [];
        $notSuperAdminCondition = [
            $this->aliasField('super_admin != 1')
        ];
        $conditions = array_merge($conditions, $notSuperAdminCondition);

        // POCOR-2547 sort list of staff and student by name
        $orders = [];
        if (!isset($this->request->getQuery['sort'])) {
            $orders = [
                $this->aliasField('first_name'),
                $this->aliasField('last_name')
            ];
        }

        $query->where($conditions)
            ->order($orders);
        $options['auto_search'] = true;
        $userType = $this->Session->read('Users.advanceSearch.belongsTo.user_type');
        if ($userType == self::STAFF || $userType == self::STUDENT) {
            $IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
            $UserIdentities = self::getDynamicTableInstance('User.Identities');
            $ConfigItemTable = self::getDynamicTableInstance('Configuration.ConfigItems');
            if($userType == self::STAFF){
                $ConfigItem =   $ConfigItemTable
                                ->find()
                                ->where([
                                    $ConfigItemTable->aliasField('code') => 'staff_identity_number',
                                    $ConfigItemTable->aliasField('value') => 1
                                ])
                                ->first();
            }else if($userType == self::STUDENT){
                $ConfigItem =   $ConfigItemTable
                                ->find()
                                ->where([
                                    $ConfigItemTable->aliasField('code') => 'student_identity_number',
                                    $ConfigItemTable->aliasField('value') => 1
                                ])
                                ->first();
            }else{
                $ConfigItem =   $ConfigItemTable
                                ->find()
                                ->where([
                                    $ConfigItemTable->aliasField('code') => 'directory_identity_number',
                                    $ConfigItemTable->aliasField('value') => 1
                                ])
                                ->first();
            }

            if(!empty($ConfigItem)){
                //value_selection
                //get data from Identity Type table
                $typesIdentity = $this->getIdentityTypeData($ConfigItem->value_selection);
                if(!empty($typesIdentity)){
                    $query
                        ->select([
                            'identity_type' => $IdentityTypes->aliasField('name'),
                            // for POCOR-6561 changed $typesIdentity->identity_type to $typesIdentity->id below
                            $typesIdentity->id => $UserIdentities->aliasField('number')
                        ])
                        ->LeftJoin(
                                    [$UserIdentities->getAlias() => $UserIdentities->getTable()],
                                    [
                                        $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('id'),
                                        $UserIdentities->aliasField('identity_type_id = ') . $typesIdentity->id
                                    ]
                                )
                        ->LeftJoin(
                            [$IdentityTypes->getAlias() => $IdentityTypes->getTable()],
                            [
                                $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id'),
                                $IdentityTypes->aliasField('id = ') . $typesIdentity->id
                            ]
                        );
                }
            }
        }//POCOR-6922 ends
    }

    //POCOR-6922 starts
    public function getIdentityTypeData($value_selection)
    {
        $IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
        $typesIdentity =   $IdentityTypes
                            ->find()
                            ->select([
                                'id' => $IdentityTypes->aliasField('id'),
                                'identity_type' => $IdentityTypes->aliasField('name')
                            ])
                            ->where([
                                $IdentityTypes->aliasField('id') => $value_selection
                            ])
                            ->first();
        return  $typesIdentity;
    }//POCOR-6922 ends

    public function findNotSuperAdmin(Query $query, array $options)
    {
        return $query->where([$this->aliasField('super_admin != 1')]);
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'openemis_no';
        $searchableFields[] = 'username';
        $searchableFields[] = 'name';
        $searchableFields[] = 'identity_number';
    }

    public function viewBeforeAction(EventInterface $event)
    {
        $this->field('roles', [
            'type' => 'role_table',
            'order' => 69,
            'valueClass' => 'table-full-width',
            'visible' => ['index' => false, 'view' => true, 'edit' => false]
        ]);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query)
    {
        $query->find('notSuperAdmin');
        $query->select($this->aliasField('IdentityTypes.name'));
        $query->contain(['MainNationalities', 'IdentityTypes']);

    }

    public function viewBeforeQuery(EventInterface $event, Query $query)
    {
        $options['auto_contain'] = false;
        $query->contain(['Roles', 'Nationalities']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity)
    {
        $this->setupTabElements(['id' => $entity->id]);
    }

    //POCOR-7736::Start
    public function onGetCreatedUserId(EventInterface $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->created_user_id])
            ->first();

        return $entity->created_user_id = $result->first_name.' '.$result->last_name;
    }

    public function onGetModifiedUserId(EventInterface $event, Entity $entity)
    {
        if(!empty($entity->modified_user_id)) {
            $Users = TableRegistry::get('User.Users');
            $result = $Users
                ->find()
                ->select(['first_name','last_name'])
                ->where(['id' => $entity->modified_user_id])
                ->first();

            return $entity->modified_user_id = $result->first_name.' '.$result->last_name;
        }
    }
    //POCOR-7736::End

    public function onGetNationalityId(EventInterface $event, Entity $entity){
        if (!empty($entity->nationality_id)) {
           $nationalities = TableRegistry::get('User.Nationalities')->get($entity->nationality_id);
           $entity->nationality_name = $nationalities->name;
           return $entity->nationality_name;
        }
    }

    private function setupTabElements($options)
    {
        $this->controller->set('selectedAction', 'Securities');
        $this->controller->set('tabElements', $this->controller->getUserTabElements($options));
    }

    public function onGetRoleTableElement(EventInterface $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Groups'), __('Roles')];
        $tableCells = [];
        $alias = $this->getAlias();
        $key = 'roles';

        if ($action == 'view') {
            $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
            $groupUserRecords = $GroupUsers->find()
                ->matching('SecurityGroups')
                ->matching('SecurityRoles')
                ->where([$GroupUsers->aliasField('security_user_id') => $entity->id])
                ->group([
                    $GroupUsers->aliasField('security_group_id'),
                    $GroupUsers->aliasField('security_role_id')
                ])
                ->select(['group_name' => 'SecurityGroups.name', 'role_name' => 'SecurityRoles.name', 'group_id' => 'SecurityGroups.id'])
                ->all();
            foreach ($groupUserRecords as $obj) {
                $rowData = [];
                $url = [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'view',
                    $this->paramsEncode(['id' => $obj->group_id])
                ];
                if (!empty($groupEntity->institution)) {
                    $url['action'] = 'SystemGroups';
                } else {
                    $url['action'] = 'UserGroups';
                }
                $rowData[] = $event->getSubject()->Html->link($obj->group_name, $url);

                $rowData[] = $obj->role_name; // role name
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $uniqueOpenemisId = $this->getUniqueOpenemisId(['model'=>Inflector::singularize('User')]);

        $this->fields['openemis_no']['type'] = 'readonly';
        $this->fields['openemis_no']['value'] = $uniqueOpenemisId;
        $this->fields['openemis_no']['attr']['value'] = $uniqueOpenemisId;

        $this->fields['identity_number']['type'] = 'hidden';

        // username field will be the same as uniqueOpenemisId by default
        $this->fields['username']['visible'] = true;
        $this->fields['username']['value'] = $uniqueOpenemisId;
        $this->fields['username']['attr']['value'] = $uniqueOpenemisId;

        // password will be auto generate password
        $generatePassword = $ConfigItems->getAutoGeneratedPassword();
        $this->fields['password']['visible'] = true;
        $this->fields['password']['value'] = $generatePassword;
        $this->fields['password']['attr']['value'] = $generatePassword;
        $this->fields['password']['attr']['autocomplete'] = 'off';
        $this->fields['password']['type'] = 'string';

        // setting the tooltip message on password
        $tooltipMessagePassword = $this->getMessage($this->getAlias().'.tooltip_message_password');
        $this->fields['password']['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $this->fields['password']['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        $this->fields['password']['attr']['label']['text'] = __(Inflector::humanize($this->fields['password']['field'])) . $this->tooltipMessage($tooltipMessagePassword);
    }

    public function editAfterAction(EventInterface $event, Entity $entity)
    {
        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
        $this->fields['nationality_id']['attr']['value'] = $entity->has('main_nationality') ? $entity->main_nationality->name : '';
        $this->fields['identity_type_id']['attr']['value'] = $entity->has('identity_type') ? $entity->identity_type->name : '';
    }

    public function editBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // not saving empty passwords
        if (empty($data[$this->getAlias()]['password'])) {
            unset($data[$this->getAlias()]['password']);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $BaseUsers = TableRegistry::get('User.Users');
        $validator->requirePresence('gender_id', 'create'); //POCOR-8752 name,gender and dob should be mandatory
        return $BaseUsers->setUserValidation($validator, $this);
    }

    public function isAdmin($userId)
    {
        return $this->get($userId)->super_admin;
    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);

        $conditions = [
            // only cater for age is more than and equal to threshold value.
            2 => ('TIMESTAMPDIFF(YEAR, ' . $this->aliasField('date_of_birth') . ', NOW())' . ' >= ' . $thresholdArray['value']), // after
        ];

        // will do the comparison with threshold when retrieving the absence data
        $licenseData = $this->find()
            ->select([
                'id',
                'openemis_no',
                'first_name',
                'middle_name',
                'third_name',
                'last_name',
                'preferred_name',
                'email',
                'address',
                'postal_code',
                'date_of_birth',
            ])
            ->where([
                $this->aliasField('date_of_birth') . ' IS NOT NULL',
                $conditions[$thresholdArray['condition']]
            ])

            ->enableHydration(false);
            ;

        return $licenseData->toArray();
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

     /**
      * POCOR-6380 starts : overwrite view button as
      * it was taking null id after selecting specific columns in indexing*
      * add change password button
      * POCOR-9370
     **/
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            $buttons['view']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
        }
         $currentUser = $this->Auth->user();
       if (!empty($currentUser['super_admin']) && $currentUser['super_admin'] == 1 && $entity->super_admin) {
            $params = ['id' => $entity->id];
            $manageUsersBtn = ['manage_users' => $buttons['edit']];
            $manageUsersBtn['manage_users']['url'] = [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => 'Accounts',
                0 => 'edit',
                1  => $this->ControllerAction->paramsEncode($params)
            ];
            $manageUsersBtn['manage_users']['label'] = '<i class="fa fa-key"></i>' . __('Change Password');
            unset($buttons['view'], $buttons['remove'], $buttons['edit']);
            $buttons = array_merge($manageUsersBtn, $buttons);
        }
        return $buttons;

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $options)
    {
        if (!$this->isAdvancedSearchEnabled()) {
            $event->stopPropagation();
            return [];
        } else {
            $this->behaviors()->get('AdvanceSearch')->setConfig([
                'showOnLoad' => 0,
            ]);
        }

        $conditions = [];
        $orders = [];

        /**
         * Only hide super_admin users if current login is NOT super_admin POCOR-9370
         */
        $currentUser = $this->Auth->user(); // or $this->Session->read('Auth.User') depending on your project
        if (empty($currentUser['super_admin']) || $currentUser['super_admin'] != 1) {
            $conditions[] = $this->aliasField('super_admin != 1');
        }

        if ($this->request->getQuery('sort') === null) {
            $orders = [
                $this->aliasField('first_name'),
                $this->aliasField('last_name')
            ];
        }

        $query->where($conditions)
              ->order($orders);

        if (!empty($this->request->getQueryParams())) {
            $options['auto_search'] = true;
        } else {
            $options['auto_search'] = false;
        }

        $userType = $this->Session->read('Users.advanceSearch.belongsTo.user_type');
        if ($userType == self::STAFF || $userType == self::STUDENT) {
            $IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
            $UserIdentities = self::getDynamicTableInstance('User.Identities');
            $ConfigItemTable = self::getDynamicTableInstance('Configuration.ConfigItems');

            if ($userType == self::STAFF) {
                $ConfigItem = $ConfigItemTable
                    ->find()
                    ->where([
                        $ConfigItemTable->aliasField('code') => 'staff_identity_number',
                        $ConfigItemTable->aliasField('value') => 1
                    ])
                    ->first();
            } else if ($userType == self::STUDENT) {
                $ConfigItem = $ConfigItemTable
                    ->find()
                    ->where([
                        $ConfigItemTable->aliasField('code') => 'student_identity_number',
                        $ConfigItemTable->aliasField('value') => 1
                    ])
                    ->first();
            } else {
                $ConfigItem = $ConfigItemTable
                    ->find()
                    ->where([
                        $ConfigItemTable->aliasField('code') => 'directory_identity_number',
                        $ConfigItemTable->aliasField('value') => 1
                    ])
                    ->first();
            }

            if (!empty($ConfigItem)) {
                // value_selection
                // get data from Identity Type table
                $typesIdentity = $this->getIdentityTypeData($ConfigItem->value_selection);

                if (!empty($typesIdentity)) {
                    $query
                        ->select([
                            'identity_type' => $IdentityTypes->aliasField('name'),
                            // for POCOR-6561 changed $typesIdentity->identity_type to $typesIdentity->id below
                            $typesIdentity->id => $UserIdentities->aliasField('number')
                        ])
                        ->leftJoin(
                            [$UserIdentities->getAlias() => $UserIdentities->getTable()],
                            [
                                $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('id'),
                                $UserIdentities->aliasField('identity_type_id = ') . $typesIdentity->id
                            ]
                        )
                        ->leftJoin(
                            [$IdentityTypes->getAlias() => $IdentityTypes->getTable()],
                            [
                                $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id'),
                                $IdentityTypes->aliasField('id = ') . $typesIdentity->id
                            ]
                        );
                }
            }
        }

        return $options;
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
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

    //POCOR-8341[START]
    public function findEmailList(Query $query, array $options) {
        $conditions = [
            $this->aliasField('id') => $options['id']
        ];

        return $query
                ->where($conditions);
    }
    //POCOR-8341[START]
    public function findSystemUpdateEmailList(Query $query, array $options) {
        $conditions = [
            $this->aliasField('id') => $options['securityRoleId']
        ];

        return $query->where($conditions);
    }

    public function findRecipientList(Query $query, array $options)
    {
        $recipients = $options['recipients'] ?? null;

        if (empty($recipients)) {
            // No recipients: force to return no result
            $conditions = [$this->aliasField('id') => -1];
        } elseif (is_array($recipients)) {
            // Non-empty array: use IN
            $conditions = [$this->aliasField('id') . ' IN' => $recipients];
        } else {
            // Single numeric ID
            $conditions = [$this->aliasField('id') => $recipients];
        }

        return $query->where($conditions);
    }

    /*POCOR-6380 ends*/

    public function indexBeforeQuerybkp(EventInterface $event, Query $query, ArrayObject $options)
    {


        if (!$this->isAdvancedSearchEnabled()) {
            $event->stopPropagation();
            return [];
        } else {
            $this->behaviors()->get('AdvanceSearch')->setConfig([
                'showOnLoad' => 0,
            ]);
        }

        $conditions = [];
        $notSuperAdminCondition = [
            $this->aliasField('super_admin != 1')
        ];
        $conditions = array_merge($conditions, $notSuperAdminCondition);

        $orders = [];
        if (!isset($this->request->getQuery['sort'])) {
            $orders = [
                $this->aliasField('first_name'),
                $this->aliasField('last_name')
            ];
        }

        $query->where($conditions)
            ->order($orders);
        $options['auto_search'] = true;
        $userType = $this->Session->read('Users.advanceSearch.belongsTo.user_type');
        if ($userType == self::STAFF || $userType == self::STUDENT) {
            $IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
            $UserIdentities = self::getDynamicTableInstance('User.Identities');
            $ConfigItemTable = self::getDynamicTableInstance('Configuration.ConfigItems');
            if($userType == self::STAFF){
                $ConfigItem =   $ConfigItemTable
                                ->find()
                                ->where([
                                    $ConfigItemTable->aliasField('code') => 'staff_identity_number',
                                    $ConfigItemTable->aliasField('value') => 1
                                ])
                                ->first();
            }else if($userType == self::STUDENT){
                $ConfigItem =   $ConfigItemTable
                                ->find()
                                ->where([
                                    $ConfigItemTable->aliasField('code') => 'student_identity_number',
                                    $ConfigItemTable->aliasField('value') => 1
                                ])
                                ->first();
            }else{
                $ConfigItem =   $ConfigItemTable
                                ->find()
                                ->where([
                                    $ConfigItemTable->aliasField('code') => 'directory_identity_number',
                                    $ConfigItemTable->aliasField('value') => 1
                                ])
                                ->first();
            }

            if(!empty($ConfigItem)){
                //value_selection
                //get data from Identity Type table
                $typesIdentity = $this->getIdentityTypeData($ConfigItem->value_selection);
                if(!empty($typesIdentity)){
                    $query
                        ->select([
                            'identity_type' => $IdentityTypes->aliasField('name'),
                            // for POCOR-6561 changed $typesIdentity->identity_type to $typesIdentity->id below
                            $typesIdentity->id => $UserIdentities->aliasField('number')
                        ])
                        ->LeftJoin(
                                    [$UserIdentities->getAlias() => $UserIdentities->getTable()],
                                    [
                                        $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('id'),
                                        $UserIdentities->aliasField('identity_type_id = ') . $typesIdentity->id
                                    ]
                                )
                        ->LeftJoin(
                            [$IdentityTypes->getAlias() => $IdentityTypes->getTable()],
                            [
                                $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id'),
                                $IdentityTypes->aliasField('id = ') . $typesIdentity->id
                            ]
                        );
                }
            }
        } // POCOR-8446


        return $options;
    }

    /*POCOR-6380 starts : overwrite view button as it was taking null id after selecting specific columns in indexing*/
    public function onUpdateActionButtonsbkp(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            $buttons['view']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
        }
        $buttons['edit']['label'] = '<i class="fa fa-edit"></i> Change Password ';
        $manageUsersBtn = ['manage_users' => $buttons['edit']];
        $manageUsersBtn['manage_users']['url'] = [
            'plugin' => 'Security',
            'controller' => 'Securities',
            'action' => 'Accounts',
            0 => 'edit',
            'id' => $entity->id,
        ];
        $manageUsersBtn['manage_users']['label'] = '<i class="fa fa-key"></i>' . __('Change Password');
        $buttons = array_merge($manageUsersBtn, $buttons);
        return $buttons;


    }

}
