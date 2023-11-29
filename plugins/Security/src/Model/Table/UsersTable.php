<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

use App\Model\Traits\OptionsTrait;

class UsersTable extends AppTable
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
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);
        $this->entityClass('User.User');

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

        $this->hasMany('ScholarshipApplications', ['className' => 'Scholarship.ScholarshipApplications', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipHistories', ['className' => 'Scholarship.Histories', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'foreignKey' => 'applicant_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
        $this->addBehavior('User.MoodleCreateUser');
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
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        if ($primary) {
            $schema = $this->schema();
            $fields = $schema->columns();
            foreach ($fields as $key => $field) {
                //POCOR-6380 - added OR condition to unset pre-defined fields only for Administration >> Security> Users listing
                //echo "<pre>";print_r($this->action);die();
                if ($schema->column($field)['type'] == 'binary') {
                    if ($this->table() == 'security_users' || $this->action == 'index') {
                        unset($fields[$key]);
                    }
                }
            }
            
            return $query->select($fields);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';//POCOR-6922
        $events['AdvanceSearch.onModifyConditions'] = 'onModifyConditions';//POCOR-6922
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    //POCOR-6922 starts
    public function getCustomFilter(Event $event)
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

    public function onModifyConditions(Event $events, $key, $value)
    {
        $conditions = [];
        if ($key == 'user_type') {
            switch ($value) {
                case self::STUDENT:
                    $conditions = [$this->aliasField('is_student') => 1];
                    break;

                case self::STAFF:
                    $conditions = [$this->aliasField('is_staff') => 1];
                    break;

                case self::GUARDIAN:
                    $conditions = [$this->aliasField('is_guardian') => 1];
                    break;

                case self::OTHER:
                    $conditions = [
                        $this->aliasField('is_student') => 0,
                        $this->aliasField('is_staff') => 0,
                        $this->aliasField('is_guardian') => 0
                    ];
                    break;
            }
        }

        if ($key == 'status') {
            switch ($value) {
                case self::ACTIVE:
                    $conditions = [$this->aliasField('status') => 1];
                    break;

                case self::INACTIVE:
                    $conditions = [$this->aliasField('status') => 0];
                    break;
            }
        }
        return $conditions;
    }

    public function indexAfterAction(Event $event)
    {
        $this->controller->set('ngController', 'AdvancedSearchCtrl');
    }//POCOR-6922 ends

    public function studentsAfterSave(Event $event, Entity $entity)
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

    public function beforeAction(Event $event)
    {
        $this->fields['photo_content']['visible'] = false;
        $this->fields['address']['visible'] = false;
        $this->fields['postal_code']['visible'] = false;
        $this->fields['address_area_id']['visible'] = false;
        $this->fields['birthplace_area_id']['visible'] = false;
        $this->fields['nationality_id']['type'] = 'readonly';
        $this->fields['identity_type_id']['type'] = 'readonly';

        if ($this->action == 'edit') {
            $this->fields['last_login']['visible'] = false;
        }

        $this->ControllerAction->field('status', ['visible' => true, 'options' => $this->getSelectOptions('general.active')]);
        $this->ControllerAction->setFieldOrder([
            'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'status', 'username', 'password'
        ]);
    }

    public function indexBeforeAction(Event $event)
    {
        $this->fields['first_name']['visible'] = false;
        $this->fields['middle_name']['visible'] = false;
        $this->fields['third_name']['visible'] = false;
        $this->fields['preferred_name']['visible'] = false;
        $this->fields['last_name']['visible'] = false;
        $this->fields['gender_id']['visible'] = false;
        $this->fields['date_of_birth']['visible'] = false;
        $this->fields['username']['visible'] = true;

        $this->ControllerAction->field('name');
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        //POCOR-6922 Start
        if (!$this->isAdvancedSearchEnabled()) {
            $event->stopPropagation();
            return [];
        } else {
            $this->behaviors()->get('AdvanceSearch')->config([
                'showOnLoad' => 0,
            ]);
        }

        $conditions = [];
        $notSuperAdminCondition = [
            $this->aliasField('super_admin') => 0
        ];
        $conditions = array_merge($conditions, $notSuperAdminCondition);

        // POCOR-2547 sort list of staff and student by name
        $orders = [];
        if (!isset($this->request->query['sort'])) {
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
            $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
            $UserIdentities = TableRegistry::get('User.Identities');
            $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
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
                                    [$UserIdentities->alias() => $UserIdentities->table()],
                                    [
                                        $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('id'),
                                        $UserIdentities->aliasField('identity_type_id = ') . $typesIdentity->id
                                    ]
                                )
                        ->LeftJoin(
                            [$IdentityTypes->alias() => $IdentityTypes->table()],
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
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
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
        return $query->where([$this->aliasField('super_admin') => 0]);
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'openemis_no';
        $searchableFields[] = 'username';
        $searchableFields[] = 'name';
        $searchableFields[] = 'identity_number';
    }

    public function viewBeforeAction(Event $event)
    {
        $this->ControllerAction->field('roles', [
            'type' => 'role_table',
            'order' => 69,
            'valueClass' => 'table-full-width',
            'visible' => ['index' => false, 'view' => true, 'edit' => false]
        ]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->find('notSuperAdmin');
        $query->select($this->aliasField('IdentityTypes.name'));
        $query->contain(['MainNationalities', 'IdentityTypes']);
    }

    public function viewBeforeQuery(Event $event, Query $query)
    {
        $options['auto_contain'] = false;
        $query->contain(['Roles', 'Nationalities']);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupTabElements(['id' => $entity->id]);
    }

    //POCOR-7736::Start
    public function onGetCreatedUserId(Event $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->created_user_id])
            ->first();

        return $entity->created_user_id = $result->first_name.' '.$result->last_name;
    }

    public function onGetModifiedUserId(Event $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->modified_user_id])
            ->first();

        return $entity->modified_user_id = $result->first_name.' '.$result->last_name;
    }
    //POCOR-7736::End
    
    public function onGetNationalityId(Event $event, Entity $entity){     
        if (!empty($entity->nationality_id)) {
           $nationalities = TableRegistry::get('Nationalities')->get($entity->nationality_id);
           $entity->nationality_name = $nationalities->name;
           return $entity->nationality_name;
        }
    }

    private function setupTabElements($options)
    {
        $this->controller->set('selectedAction', 'Securities');
        $this->controller->set('tabElements', $this->controller->getUserTabElements($options));
    }

    public function onGetRoleTableElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Groups'), __('Roles')];
        $tableCells = [];
        $alias = $this->alias();
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
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'view',
                    $this->paramsEncode(['id' => $obj->group_id])
                ];
                if (!empty($groupEntity->institution)) {
                    $url['action'] = 'SystemGroups';
                } else {
                    $url['action'] = 'UserGroups';
                }
                $rowData[] = $event->subject()->Html->link($obj->group_name, $url);

                $rowData[] = $obj->role_name; // role name
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $uniqueOpenemisId = $this->getUniqueOpenemisId(['model'=>Inflector::singularize('User')]);

        // first value is for the hidden field value, the second value is for the readonly value
        $this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'value' => $uniqueOpenemisId, 'attr' => ['value' => $uniqueOpenemisId]]);

        //this field value will be generated automatically when identity details changed.
        $this->ControllerAction->field('identity_number', ['type' => 'hidden']);

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

        // setting the tooltip message on password
        $tooltipMessagePassword = $this->getMessage($this->alias().'.tooltip_message_password');
        $this->fields['password']['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $this->fields['password']['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        $this->fields['password']['attr']['label']['text'] = __(Inflector::humanize($this->fields['password']['field'])) . $this->tooltipMessage($tooltipMessagePassword);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
        $this->fields['nationality_id']['attr']['value'] = $entity->has('main_nationality') ? $entity->main_nationality->name : '';
        $this->fields['identity_type_id']['attr']['value'] = $entity->has('identity_type') ? $entity->identity_type->name : '';
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // not saving empty passwords
        if (empty($data[$this->alias()]['password'])) {
            unset($data[$this->alias()]['password']);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $BaseUsers = TableRegistry::get('User.Users');
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

            ->hydrate(false)
            ;

        return $licenseData->toArray();
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    /*POCOR-6380 starts : overwrite view button as it was taking null id after selecting specific columns in indexing*/
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
       
        if (array_key_exists('view', $buttons)) {
            $buttons['view']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
        }

        return $buttons;
    }
    /*POCOR-6380 ends*/
}
