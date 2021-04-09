<?php
namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;

class DirectoriesTable extends ControllerActionTable
{
    // public $InstitutionStudent;

    // these constants are being used in AdvancedPositionSearchBehavior as well
    // remember to check AdvancedPositionSearchBehavior if these constants are being modified
    const ALL = 0;
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    const STUDENTNOTINSCHOOL = 5;
    const STAFFNOTINSCHOOL = 6;

    private $dashboardQuery;

    public function initialize(array $config)
    {
        $this->table('security_users');
        $this->entityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $this->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserNationalities', ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);

        $this->addBehavior('User.User');
        $this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
        $this->addBehavior('User.AdvancedIdentitySearch');
        $this->addBehavior('User.AdvancedContactNumberSearch');
        $this->addBehavior('User.AdvancedPositionSearch');
        $this->addBehavior('User.AdvancedSpecificNameTypeSearch');
        $this->addBehavior('User.MoodleCreateUser');

        //specify order of advanced search fields
        $advancedSearchFieldOrder = [
            'user_type', 'first_name', 'middle_name', 'third_name', 'last_name',
            'openemis_no', 'gender_id', 'contact_number', 'birthplace_area_id', 'address_area_id', 'position',
            'identity_type', 'identity_number'
        ];
        $this->addBehavior('AdvanceSearch', [
            'include' =>[
                'openemis_no'
            ],
            'order' => $advancedSearchFieldOrder,
            'showOnLoad' => 1,
            'customFields' => ['user_type']
        ]);

        $this->addBehavior('HighChart', [
            'user_gender' => [
                '_function' => 'getNumberOfUsersByGender'
            ]
        ]);
        $this->addBehavior('Configuration.Pull');
        $this->addBehavior('Import.ImportLink', ['import_model'=>'ImportUsers']);
        $this->addBehavior('ControllerAction.Image');

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Directory.Directories.id']);
        $this->toggle('search', false);
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        if ($primary) {
            $schema = $this->schema();
            $fields = $schema->columns();
            foreach ($fields as $key => $field) {
                if ($schema->column($field)['type'] == 'binary') {
                    unset($fields[$key]);
                }
            }
            return $query->select($fields);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';
        $events['AdvanceSearch.onModifyConditions'] = 'onModifyConditions';
        $events['Model.AreaAdministrative.afterDelete'] = 'areaAdminstrativeAfterDelete';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ;
        $BaseUsers = TableRegistry::get('User.Users');
        return $BaseUsers->setUserValidation($validator, $this);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // POCOR-4035 Check when the submit is not save then will not add the validation.
        $submit = isset($data['submit']) ? $data['submit'] : 'save';

        if ($submit == 'save') {
            $nationalityValidation = 'AddByAssociation';
            $identityValidation = 'AddByAssociation';
        } else {
            $nationalityValidation = false;
            $identityValidation = false;
        }

        $options['associated']['Nationalities'] = [
            'validate' => $nationalityValidation
        ];
        $options['associated']['Identities'] = [
            'validate' => $identityValidation
        ];
        // end POCOR-4035
    }

    public function onModifyConditions(Event $events, $key, $value)
    {
        if ($key == 'user_type') {
            $conditions = [];
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
            return $conditions;
        }
    }


    public function areaAdminstrativeAfterDelete(Event $event, $areaAdministrative)
    {
        $subqueryOne = $this->AddressAreas
            ->find()
            ->select(1)
            ->where(function ($exp, $q) {
                return $exp->equalFields($this->AddressAreas->aliasField('id'), $this->aliasField('address_area_id'));
            });

        $query = $this->find()
            ->select('id')
            ->where(function ($exp, $q) use ($subqueryOne) {
                return $exp->notExists($subqueryOne);
            });


        foreach ($query as $row) {
            $this->updateAll(
                ['address_area_id' => null],
                ['id' => $row->id]
            );
        }

        $subqueryTwo = $this->BirthplaceAreas
            ->find()
            ->select(1)
            ->where(function ($exp, $q) {
                return $exp->equalFields($this->BirthplaceAreas->aliasField('id'), $this->aliasField('birthplace_area_id'));
            });


        $query = $this->find()
            ->select('id')
            ->where(function ($exp, $q) use ($subqueryTwo) {
                return $exp->notExists($subqueryTwo);
            });


        foreach ($query as $row) {
            $this->updateAll(
                ['birthplace_area_id' => null],
                ['id' => $row->id]
            );
        }

    }

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
        return $filters;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $options)
    {
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

        $this->dashboardQuery = clone $query;
    }

    public function findStudentsInSchool(Query $query, array $options)
    {
        $institutionIds = (array_key_exists('institutionIds', $options))? $options['institutionIds']: [];
        if (!empty($institutionIds)) {
            $query
                ->join([
                    [
                        'type' => 'INNER',
                        'table' => 'institution_students',
                        'alias' => 'InstitutionStudents',
                        'conditions' => [
                            'InstitutionStudents.institution_id'.' IN ('.$institutionIds.')',
                            'InstitutionStudents.student_id = '. $this->aliasField('id')
                        ]
                    ]
                ])
                ->group('InstitutionStudents.student_id');
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStudentsNotInSchool(Query $query, array $options)
    {
        $InstitutionStudentTable = TableRegistry::get('Institution.Students');
        $allInstitutionStudents = $InstitutionStudentTable->find()
            ->select([
                $InstitutionStudentTable->aliasField('student_id')
            ])
            ->where([
                $InstitutionStudentTable->aliasField('student_id').' = '.$this->aliasField('id')
            ])
            ->bufferResults(false);
        $query->where(['NOT EXISTS ('.$allInstitutionStudents->sql().')', $this->aliasField('is_student') => 1]);
        return $query;
    }

    public function findStaffInSchool(Query $query, array $options)
    {
        $institutionIds = (array_key_exists('institutionIds', $options))? $options['institutionIds']: [];
        if (!empty($institutionIds)) {
            $query->join([
                [
                    'type' => 'INNER',
                    'table' => 'institution_staff',
                    'alias' => 'InstitutionStaff',
                    'conditions' => [
                        'InstitutionStaff.institution_id'.' IN ('.$institutionIds.')',
                        'InstitutionStaff.staff_id = '. $this->aliasField('id')
                    ]
                ]
            ]);
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStaffNotInSchool(Query $query, array $options)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $allInstitutionStaff = $InstitutionStaffTable->find()
            ->select([
                $InstitutionStaffTable->aliasField('staff_id')
            ])
            ->where([
                $InstitutionStaffTable->aliasField('staff_id').' = '.$this->aliasField('id')
            ])
            ->bufferResults(false);
        $query->where(['NOT EXISTS ('.$allInstitutionStaff->sql().')', $this->aliasField('is_staff') => 1]);
        return $query;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'add') {
            if ($this->controller->name != 'Students') {
                $this->field('user_type', ['type' => 'select', 'after' => 'photo_content']);
            } else {
                $this->request->query['user_type'] = self::GUARDIAN;
            }
            $userType = isset($this->request->data[$this->alias()]['user_type']) ? $this->request->data[$this->alias()]['user_type'] : $this->request->query('user_type');
            $this->field('openemis_no', ['user_type' => $userType]);
            switch ($userType) {
                case self::STUDENT:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts']]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Student.Students',
                        'behavior' => 'Student',
                        'fieldKey' => 'student_custom_field_id',
                        'tableColumnKey' => 'student_custom_table_column_id',
                        'tableRowKey' => 'student_custom_table_row_id',
                        'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
                        'formKey' => 'student_custom_form_id',
                        'filterKey' => 'student_custom_filter_id',
                        'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
                        'recordKey' => 'student_id',
                        'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::STAFF:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts']]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Staff.Staff',
                        'behavior' => 'Staff',
                        'fieldKey' => 'staff_custom_field_id',
                        'tableColumnKey' => 'staff_custom_table_column_id',
                        'tableRowKey' => 'staff_custom_table_row_id',
                        'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
                        'formKey' => 'staff_custom_form_id',
                        'filterKey' => 'staff_custom_filter_id',
                        'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
                        'recordKey' => 'staff_id',
                        'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::GUARDIAN:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Guardian', 'roleFields' =>['Identities', 'Nationalities']]);
                    break;
                case self::OTHER:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Other', 'roleFields' =>['Identities', 'Nationalities']]);
                    break;
            }
            $this->field('nationality_id', ['visible' => false]);
            $this->field('identity_type_id', ['visible' => false]);
        } elseif ($this->action == 'edit') {
            $this->hideOtherInformationSection($this->controller->name, 'edit');
            $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');
            $this->field('openemis_no', ['user_type' => $userType]);
            $this->addCustomUserBehavior($userType);
        } elseif ($this->action == 'view') {
            $encodedParam = $this->request->params['pass'][1];
            $securityUserId = $this->ControllerAction->paramsDecode($encodedParam)['id'];
            $userInfo = TableRegistry::get('Security.Users')->get($securityUserId);
            if ($userInfo->is_student) {
                $userType = self::STUDENT;
                $this->addCustomUserBehavior($userType);
            } elseif ($userInfo->is_staff) {
                $userType = self::STAFF;
                $this->addCustomUserBehavior($userType);
            } elseif ($userInfo->is_guardian) {
                $userType = self::GUARDIAN;
                $this->addCustomUserBehavior($userType);
            }
        }
    }
    
    // POCOR-5684
    public function onGetIdentityNumber(Event $event, Entity $entity){

        // Case 1: if user has only one identity, show the same, 
        // Case 2: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.
        // Case 3: if user has more than one identity (no one is linked to nationality), show the first

        $users_ids = TableRegistry::get('user_identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->all();
        
        $users_ids = TableRegistry::get('user_identities');
        $user_id_data = $users_ids->find()
        ->select(['number'])
        ->where([                
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->first();

        if(count($user_identities) == 1){
            // Case 1
            return $entity->identity_number = $user_id_data->number;
        }else{
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::get('nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }     

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('user_identities');
                $user_id_data_nat = $users_ids->find()
                ->select(['number'])
                ->where([                
                    $users_ids->aliasField('security_user_id') => $entity->id,
                    $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                ])
                ->first();
                if($user_id_data_nat != null){
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }
            
            if(count($nationality_based_ids) > 0){
                // Case 2 - returning value
                return $entity->identity_number = $nationality_based_ids[0]['number'];
            }else{
                // Case 3 - returning value, return again from Case 1
                return $entity->identity_number = $user_id_data->number;
            }
        }
    }

    // POCOR-5684
    public function onGetIdentityTypeID(Event $event, Entity $entity)
    {
        $users_ids = TableRegistry::get('user_identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->all();
        
        $users_ids = TableRegistry::get('user_identities');
        $user_id_data = $users_ids->find()
        ->select(['number', 'identity_type_id'])
        ->where([                
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->first();

        if(count($user_identities) == 1){
            // Case 1
            $users_id_type = TableRegistry::get('identity_types');
            $user_id_name = $users_id_type->find()
            ->select(['name'])
            ->where([
                $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
            ])
            ->first();
            return $entity->identity_type_id = $user_id_name->name;
        }else{
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::get('nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }     

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('user_identities');
                $user_id_data_nat = $users_ids->find()
                ->select(['number','identity_type_id'])
                ->where([                
                    $users_ids->aliasField('security_user_id') => $entity->id,
                    $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                ])
                ->first();
                if($user_id_data_nat != null){
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }
            if(count($nationality_based_ids) > 0){
                // Case 2 - returning value
                $users_id_type = TableRegistry::get('identity_types');
                $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                ])
                ->first();
                return $entity->identity_type_id = $user_id_name->name;
            }else{
                // Case 3 - returning value, return again from Case 1
                $users_id_type = TableRegistry::get('identity_types');
                $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
                ])
                ->first();
                return $entity->identity_type_id = $user_id_name->name;
            }
        }
    }

    // POCOR-5684
    // public function onGetIdentityNumber(Event $event, Entity $entity)
    // {
    //     // Get user identity number
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['number'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();
    //     return $entity->identity_number = $user_id_data->number;
    // }

    // // POCOR-5684
    // public function onGetIdentityTypeID(Event $event, Entity $entity)
    // {
    //     // Get User Identity Type id
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['identity_type_id'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();
        
    //     // Get Identity Type Name
    //     $users_id_type = TableRegistry::get('identity_types');
    //     $user_id_name = $users_id_type->find()
    //     ->select(['name'])
    //     ->where([
    //         $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
    //     ])
    //     ->first();
    //     return $entity->identity_type_id = $user_id_name->name;
    // }

    private function addCustomUserBehavior($userType) {
        switch ($userType) {
                case self::STUDENT:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts']]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Student.Students',
                        'behavior' => 'Student',
                        'fieldKey' => 'student_custom_field_id',
                        'tableColumnKey' => 'student_custom_table_column_id',
                        'tableRowKey' => 'student_custom_table_row_id',
                        'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
                        'formKey' => 'student_custom_form_id',
                        'filterKey' => 'student_custom_filter_id',
                        'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
                        'recordKey' => 'student_id',
                        'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::STAFF:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts']]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Staff.Staff',
                        'behavior' => 'Staff',
                        'fieldKey' => 'staff_custom_field_id',
                        'tableColumnKey' => 'staff_custom_table_column_id',
                        'tableRowKey' => 'staff_custom_table_row_id',
                        'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
                        'formKey' => 'staff_custom_form_id',
                        'filterKey' => 'staff_custom_filter_id',
                        'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
                        'recordKey' => 'staff_id',
                        'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::GUARDIAN:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Guardian', 'roleFields' =>['Identities', 'Nationalities']]);
                    break;
                case self::OTHER:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Other', 'roleFields' =>['Identities', 'Nationalities']]);
                    break;
            }
            
            return ;
    }

    public function hideOtherInformationSection($controller, $action)
    {
        if (($action=="add") || ($action=="edit")) { //hide "other information" section on add/edit guardian because there wont be any custom field.
            if (($controller=="Students") || ($controller=="Directories")) {
                $this->field('other_information_section', ['visible' => false]);
            }
        }
    }

    public function addBeforeAction(Event $event)
    {
        if (!isset($this->request->data[$this->alias()]['user_type'])) {
            $this->request->data[$this->alias()]['user_type'] = $this->request->query('user_type');
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // need to find out order values because recordbehavior changes it
        $allOrderValues = [];
        foreach ($this->fields as $key => $value) {
            $allOrderValues[] = (array_key_exists('order', $value) && !empty($value['order']))? $value['order']: 0;
        }
        $highestOrder = max($allOrderValues);

        $userType = $this->request->query('user_type');

        $openemisNo = $this->getUniqueOpenemisId();

        $this->fields['openemis_no']['value'] = $openemisNo;
        $this->fields['openemis_no']['attr']['value'] = $openemisNo;
        // pr($this->request->data[$this->alias()]['username']);
        if (!isset($this->request->data[$this->alias()]['username'])) {
            $this->request->data[$this->alias()]['username'] = $openemisNo;
        } elseif ($this->request->data[$this->alias()]['username'] == $this->request->data[$this->alias()]['openemis_no']) {
            $this->request->data[$this->alias()]['username'] = $openemisNo;
        } elseif (empty($this->request->data[$this->alias()]['username'])) {
            $entity->invalid('username', $openemisNo, true);
            $this->request->data[$this->alias()]['username'] = $openemisNo;
        }
        $this->field('username', ['order' => ++$highestOrder, 'visible' => true]);

        if (!isset($this->request->data[$this->alias()]['password'])) {
            $UsersTable = TableRegistry::get('User.Users');

            // Read the number of length of password from system config
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $this->request->data[$this->alias()]['password'] = $ConfigItems->getAutoGeneratedPassword();
        }
        $this->field('password', ['order' => ++$highestOrder, 'visible' => true, 'attr' => ['autocomplete' => 'off']]);
        $this->setFieldOrder([
                'information_section', 'photo_content', 'user_type', 'openemis_no', 'first_name', 'middle_name',
                'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'nationality_id',
                'identity_type_id', 'location_section', 'address', 'postal_code', 'address_area_section', 'address_area_id',
                'birthplace_area_section', 'birthplace_area_id', 'other_information_section', 'contact_type', 'contact_value', 'nationality',
                'identity_type', 'identity_number',
                'username', 'password'
            ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['advance_search'] = [
            'type' => 'button',
            'attr' => [
                'class' => 'btn btn-default btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'title' => __('Advanced Search'),
                'id' => 'search-toggle',
                'escape' => false,
                'ng-click'=> 'toggleAdvancedSearch()'
            ],
            'url' => '#',
            'label' => '<i class="fa fa-search-plus"></i>',
        ];
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            // history button need to check permission ??
            if ($this->AccessControl->check(['DirectoryHistories', 'index'])) {
                $userId = $entity->id;

                $icon = '<i class="fa fa-history"></i>';
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'DirectoryHistories',
                    'action' => 'index'
                ];

                $buttons['history'] = $buttons['view'];
                $buttons['history']['label'] = $icon . __('History');
                $buttons['history']['url'] = $this->ControllerAction->setQueryString($url, [
                    'security_user_id' => $userId,
                    'user_type' => $this->alias()
                ]);
            }
            // end history button
        }

        return $buttons;
    }

    public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request)
    {
        $options = [
            self::STUDENT => __('Student'),
            self::STAFF => __('Staff'),
            self::GUARDIAN => __('Guardian'),
            self::OTHER => __('Others')
        ];
        $attr['options'] = $options;
        $attr['onChangeReload'] = 'changeUserType';
        if (!$this->request->query('user_type')) {
            $this->request->query['user_type'] = key($options);
        }
        return $attr;
    }

    public function addOnChangeUserType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['user_type']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('user_type', $data[$this->alias()])) {
                    $this->request->query['user_type'] = $data[$this->alias()]['user_type'];
                }
            }

            if (isset($data[$this->alias()]['custom_field_values'])) {
                unset($data[$this->alias()]['custom_field_values']);
            }

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'Identities' => ['validate' => false],
                'Nationalities' => ['validate' => false],
                'SpecialNeeds' => ['validate' => false],
                'Contacts' => ['validate' => false]
            ];
        }
    }

    public function onUpdateFieldPassword(Event $event, array $attr, $action, Request $request)
    {
        // setting the tooltip message
        $tooltipMessagePassword = $this->getMessage('Users.tooltip_message_password');

        $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        $attr['attr']['label']['text'] = __(Inflector::humanize($attr['field'])) . $this->tooltipMessage($tooltipMessagePassword);

        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {
        $userType = $requestData[$this->alias()]['user_type'];
        $type = [
            'is_student' => '0',
            'is_staff' => '0',
            'is_guardian' => '0'
            // 'is_student' => intval(0),
            // 'is_staff' => intval(0),
            // 'is_guardian' => intval(0)
            // 'is_student' => 0,
            // 'is_staff' => 0,
            // 'is_guardian' => 0
        ];
        switch ($userType) {
            case self::STUDENT:
                $type['is_student'] = 1;
                break;
            case self::STAFF:
                $type['is_staff'] = 1;
                break;
            case self::GUARDIAN:
                $type['is_guardian'] = 1;
                break;
        }
        $directoryEntity = array_merge($requestData[$this->alias()], $type);
        $requestData[$this->alias()] = $directoryEntity;
    }

    public function indexAfterAction(Event $event)
    {
        $this->fields = [];
        $this->controller->set('ngController', 'AdvancedSearchCtrl');

        $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');

        switch ($userType) {
            case self::ALL:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::STUDENT:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                $this->field('student_status', ['order' => 52]);
                break;
            case self::STAFF:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::GUARDIAN:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::OTHER:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
        }
        $this->fields['date_of_birth']['type'] = 'date';
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index') {
            $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');

            switch ($userType) {
                case self::ALL:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                   break;
                case self::STUDENT:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth', 'student_status']);
                    break;
                case self::STAFF:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
                case self::GUARDIAN:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                case self::OTHER:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                   break;
            }
        }
    }

    public function onGetStudentStatus(Event $event, Entity $entity)
    {
        return __($entity->student_status_name);
    }

    public function getNumberOfUsersByGender($params = [])
    {
        $query = isset($params['query']) ? $params['query'] : null;
        if (!is_null($query)) {
            $userRecords = clone $query;
        } else {
            $userRecords = $this->find();
        }
        $genderCount = $userRecords
            ->contain(['Genders'])
            ->select([
                'count' => $userRecords->func()->count($this->aliasField('id')),
                'gender' => 'Genders.name'
            ])
            ->group('gender', true)
            ->bufferResults(false);

        // Creating the data set
        $dataSet = [];
        foreach ($genderCount as $value) {
            //Compile the dataset
            if (is_null($value['gender'])) {
                $value['gender'] = 'Not Defined';
            }
            $dataSet[] = [__($value['gender']), $value['count']];
        }
        $params['dataSet'] = $dataSet;
        return $params;
    }

    private function setSessionAfterAction($event, $entity)
    {
        $this->Session->write('Directory.Directories.id', $entity->id);
        $this->Session->write('Directory.Directories.name', $entity->name);

        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();
            $this->Session->write('AccessControl.Institutions.ids', $institutionIds);
        }

        $isStudent = $entity->is_student;
        $isStaff = $entity->is_staff;
        $isGuardian = $entity->is_guardian;
        $isSet = false;
        $this->Session->delete('Directory.Directories.is_student');
        $this->Session->delete('Directory.Directories.is_staff');
        $this->Session->delete('Directory.Directories.is_guardian');
        if ($isStudent) {
            $this->Session->write('Directory.Directories.is_student', true);
            $this->Session->write('Student.Students.id', $entity->id);
            $this->Session->write('Student.Students.name', $entity->name);
            $isSet = true;
        }

        if ($isStaff) {
            $this->Session->write('Directory.Directories.is_staff', true);
            $this->Session->write('Staff.Staff.id', $entity->id);
            $this->Session->write('Staff.Staff.name', $entity->name);
            $isSet = true;
        }

        if ($isGuardian) {
            $this->Session->write('Directory.Directories.is_guardian', true);
            $this->Session->write('Guardian.Guardians.id', $entity->id);
            $this->Session->write('Guardian.Guardians.name', $entity->name);
            $isSet = true;
        }

        return $isSet;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities' => [
                'fields' => [
                    'MainNationalities.id',
                    'MainNationalities.name'
                ]
            ],
            'MainIdentityTypes'  => [
                'fields' => [
                    'MainIdentityTypes.id',
                    'MainIdentityTypes.name'
                ]
            ],
            'Genders' => [
                'fields' => [
                    'Genders.id',
                    'Genders.name'
                ]
            ]
        ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $isSet = $this->setSessionAfterAction($event, $entity);

        if ($isSet) {
            $reload = $this->Session->read('Directory.Directories.reload');
            if (!isset($reload)) {
                $urlParams = $this->url('edit');
                $event->stopPropagation();
                return $this->controller->redirect($urlParams);
            }
        }

        $this->setupTabElements($entity);

        if ($entity->is_student) {
            /*$this->fields['gender_id']['type'] = 'readonly';
            $this->fields['gender_id']['attr']['value'] = $entity->has('gender') ? $entity->gender->name : '';
            $this->fields['gender_id']['value'] = $entity->has('gender') ? $entity->gender->id : '';*/
        }

        $this->fields['nationality_id']['type'] = 'readonly';
        if (!empty($entity->main_nationality)) {
            $this->fields['nationality_id']['attr']['value'] = $entity->main_nationality->name;
        }

        $this->fields['identity_type_id']['type'] = 'readonly';
        if (!empty($entity->main_identity_type)) {
            $this->fields['identity_type_id']['attr']['value'] = $entity->main_identity_type->name;
        }

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $isSet = $this->setSessionAfterAction($event, $entity);
        if ($isSet) {
            $reload = $this->Session->read('Directory.Directories.reload');
            if (!isset($reload)) {
                $urlParams = $this->url('view');
                $event->stopPropagation();
                return $this->controller->redirect($urlParams);
            }
        }

        $this->setupTabElements($entity);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->dirty('gender_id') && !$entity->is_student) {
            $entity->errors('gender_id', __('Gender is not editable in Directories'));
            return false;
        }
    }

    private function setupTabElements($entity)
    {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

        $options = [
            // 'userRole' => 'Student',
            // 'action' => $this->action,
            // 'id' => $id,
            // 'userId' => $entity->id
        ];

        $tabElements = $this->controller->getUserTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function onGetInstitution(Event $event, Entity $entity)
    {
        $userId = $entity->id;
        $isStudent = $entity->is_student;
        $isStaff = $entity->is_staff;
        $isGuardian = $entity->is_guardian;

        $studentInstitutions = [];
        if ($isStudent) {
            $InstitutionStudentTable = TableRegistry::get('Institution.Students');
            $studentInstitutions = $InstitutionStudentTable->find()
                ->matching('StudentStatuses')
                ->matching('Institutions')
                ->where([
                    $InstitutionStudentTable->aliasField('student_id') => $userId,
                ])
                ->select(['id' => $InstitutionStudentTable->aliasField('institution_id'), 'name' => 'Institutions.name', 'student_status_name' => 'StudentStatuses.name'])
                ->order([$InstitutionStudentTable->aliasField('start_date') => 'DESC'])
                ->first();

            $value = '';
            $name = '';
            if (!empty($studentInstitutions)) {
                $value = $studentInstitutions->student_status_name;
                $name = $studentInstitutions->name;
            }
            $entity->student_status_name = $value;

            return $name;
        }

        $staffInstitutions = [];
        if ($isStaff) {
            $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
            $today = date('Y-m-d');
            $staffInstitutions = $InstitutionStaffTable->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'institutionName'
                ])
                ->find('inDateRange', ['start_date' => $today, 'end_date' => $today])
                ->matching('Institutions')
                ->where([$InstitutionStaffTable->aliasField('staff_id') => $userId])
                ->select(['id' => 'Institutions.id', 'institutionName' => 'Institutions.name'])
                ->group(['Institutions.id'])
                ->order(['Institutions.name'])
                ->toArray();
            return implode('<BR>', $staffInstitutions);
        }
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }
}
