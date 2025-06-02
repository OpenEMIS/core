<?php

namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class StaffUserTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        ini_set('memory_limit', '-1');
        $this->setTable('security_users');
        $this->setEntityClass('User.User');
        parent::initialize($config);
        self::handleAssociations($this);
        // Behaviors
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('User.Mandatory',
            ['userRole' => 'Staff',
                'roleFields' => ['Identities', 'Nationalities']]); // POCOR-9123
        $this->addBehavior('AdvanceSearch');

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

        $this->addBehavior('Excel', [
            'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian', 'super_admin', 'date_of_death'],
            'filename' => 'Staff',
            'pages' => ['view']
        ]);

        $this->addBehavior('HighChart', [
            'count_by_gender' => [
                '_function' => 'getNumberOfStaffByGender'
            ]
        ]);
//        POCOR-8334 @todo further
//        $this->addBehavior('Configuration.Pull'); // POCOR-8039
        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Staff.Staff.id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index', 'add', 'edit'],
            'ReportCardComments' => ['view']
        ]);
        $this->toggle('index', false);
        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab',
            ['appliedAction' => ['Staff' =>
                ['staff_status_id', 'academic_period_id', 'staff_id'],
                'StaffUser' =>
                    ['id', 'staff_status_id',
                        'academic_period_id',
                        ]
            ]
            ]
        );
    }

    public static function handleAssociations($model)
    {
        $model->belongsTo('Genders', ['className' => 'User.Genders']);
        $model->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $model->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $model->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $model->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $model->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Nationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Attachments', ['className' => 'User.Attachments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('BankAccounts', ['className' => 'User.BankAccounts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Comments', ['className' => 'User.Comments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Languages', ['className' => 'User.UserLanguages', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Awards', ['className' => 'User.Awards', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $model->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $model->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'foreignKey' => 'security_role_id',
            'targetForeignKey' => 'security_user_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $model->belongsToMany('Institutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'institution_staff', // will need to change to institution_staff
            'foreignKey' => 'staff_id', // will need to change to staff_id
            'targetForeignKey' => 'institution_id', // will need to change to institution_id
            'through' => 'Institution.Staff',
            'dependent' => true
        ]);

        // class should never cascade delete
        $model->hasMany('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'staff_id']);
        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true]);

        $model->belongsToMany('Subjects', [
            'className' => 'Institution.InstitutionSubject',
            'joinTable' => 'institution_subject_staff',
            'foreignKey' => 'staff_id',
            'targetForeignKey' => 'institution_subject_id',
            'through' => 'Institution.InstitutionSubjectStaff',
            'dependent' => true
        ]);

        $model->hasMany('StaffActivities', ['className' => 'Staff.StaffActivities', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $model->hasMany('InstitutionRubrics', ['className' => 'Institution.InstitutionRubrics', 'foreignKey' => 'staff_id', 'dependent' => true]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Staff.afterSave'] = 'staffAfterSave';
        return $events;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $options['associated']['Nationalities'] = [
            'validate' => 'AddByAssociation'
        ];
        $options['associated']['Identities'] = [
            'validate' => 'AddByAssociation'
        ];
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $id = $this->getQueryString('id');

        $this->field('username', ['visible' => false]);
        $toolbarButtons = $extra['toolbarButtons'];
        if ($this->action == 'view') {
            // $id = $this->request->getQuery('id');
            $this->Session->write('Institution.Staff.id', $id);
            if ($toolbarButtons->offsetExists('back')) {
                $toolbarButtons['back']['url']['action'] = 'Staff';
            }
        } else {
            if ($toolbarButtons->offsetExists('back')) {
                $toolbarButtons['back']['url'][1] = $this->paramsPass(0);
            }
        }
    }

    //POCOR-7982
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!empty($entity->date_of_death)) { //POCOR-8059
            if (isset($entity->dod_range)) {
                $event->stopPropagation();
                $this->Alert->warning('general.dodmsg', ['reset' => true]);
                $url = $this->url('edit');
                return $this->controller->redirect($url);
            }
        }
    }
    //POCOR-7982

    // POCOR-5684
    public function onGetIdentityNumber(Event $event, Entity $entity)
    {

        // Case 1: if user has only one identity, show the same,
        // Case 2: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.
        // Case 3: if user has more than one identity (no one is linked to nationality), show the first

        $users_ids = TableRegistry::get('User.UserIdentities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();

        $users_ids = TableRegistry::get('User.UserIdentities');
        $user_id_data = $users_ids->find()
            ->select(['number'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            return $entity->identity_number = $user_id_data->number;
        } else {
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::get('FieldOption.Nationalities');
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
                $users_ids = TableRegistry::get('User.UserIdentities');
                $user_id_data_nat = $users_ids->find()
                    ->select(['number'])
                    ->where([
                        $users_ids->aliasField('security_user_id') => $entity->id,
                        $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                    ])
                    ->first();
                if ($user_id_data_nat != null) {
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }

            if (count($nationality_based_ids) > 0) {
                // Case 2 - returning value
                return $entity->identity_number = $nationality_based_ids[0]['number'];
            } else {
                // Case 3 - returning value, return again from Case 1
                return $entity->identity_number = $user_id_data->number;
            }
        }
    }

    // POCOR-5684
    public function onGetIdentityTypeID(Event $event, Entity $entity)
    {
        $users_ids = TableRegistry::get('User.UserIdentities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();

        $users_ids = TableRegistry::get('User.UserIdentities');
        $user_id_data = $users_ids->find()
            ->select(['number', 'identity_type_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            $users_id_type = TableRegistry::get('FieldOption.IdentityTypes');
            $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
                ])
                ->first();
            return $entity->identity_type_id = $user_id_name->name;
        } else {
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
                $users_ids = TableRegistry::get('User.UserIdentities');
                $user_id_data_nat = $users_ids->find()
                    ->select(['number', 'identity_type_id'])
                    ->where([
                        $users_ids->aliasField('security_user_id') => $entity->id,
                        $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                    ])
                    ->first();
                if ($user_id_data_nat != null) {
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }
            if (count($nationality_based_ids) > 0) {
                // Case 2 - returning value
                $users_id_type = TableRegistry::get('FieldOption.IdentityTypes');
                $user_id_name = $users_id_type->find()
                    ->select(['name'])
                    ->where([
                        $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                    ])
                    ->first();
                return $entity->identity_type_id = $user_id_name->name;
            } else {
                // Case 3 - returning value, return again from Case 1
                $users_id_type = TableRegistry::get('FieldOption.IdentityTypes');
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


    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $BaseUsers = TableRegistry::get('User.Users');
        $validator = $BaseUsers->setUserValidation($validator, $this);
        $validator
            ->allowEmpty('username')
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ->allowEmpty('photo_content')
            ->add('staff_name', 'ruleInstitutionStaffId', [
                'rule' => ['institutionStaffId'],
                'on' => 'create'
            ])
            ->add('staff_assignment', 'ruleTransferRequestExists', [
                'rule' => ['checkPendingStaffTransfer'],
                'on' => 'create'
            ])
            ->add('staff_assignment', 'ruleCheckStaffAssignment', [
                'rule' => ['checkStaffAssignment'],
                'on' => 'create'
            ])
            ->notEmpty('FTE', null, 'create')
            ->notEmpty('position_type', null, 'create')
            ->notEmpty('institution_position_id', null, 'create')
            ->notEmpty('staff_type_id', null, 'create')
            ->requirePresence('FTE', 'create')
            ->requirePresence('position_type', 'create')
            ->requirePresence('institution_position_id', 'create')
            ->requirePresence('staff_type_id', 'create')
            ->notEmpty('start_date', null, 'create')
            ->requirePresence('start_date', 'create')
            ->notEmpty('staff_shifts_id', null, 'create')
            ->requirePresence('staff_shifts_id', 'create')/* ->add('start_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                'on' => function ($context) {
                    // check for staff add wizard on create operations - where academic_period_id exist in the context data - POCOR-4576
                    return ($context['newRecord'] && array_key_exists('academic_period_id', $context['data']));
                }
            ])*/
        ;
        return $validator;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities', 'MainIdentityTypes'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();
            $this->Session->write('AccessControl.Institutions.ids', $institutionIds);
        }
        $this->Session->write('Staff.Staff.id', $entity->id);
        $this->Session->write('Staff.Staff.name', $entity->name);
        $this->setupTabElements($entity);

        $this->addTransferButton($entity, $extra);
        $this->addReleaseButton($entity, $extra);
    }

    private function setupTabElements($entity)
    {
        $options = [
            'userRole' => 'Staff',
        ];
        $tabElements = $this->setUserTabElements($options);
    }

    private function addTransferButton(Entity $entity, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        if ($this->AccessControl->check([$this->controller->getName(), 'StaffTransferOut', 'add'])) {
            $session = $this->request->getSession();
            $toolbarButtons = $extra['toolbarButtons'];
            $StaffTable = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $ConfigStaffTransfersTable = TableRegistry::get('Configuration.ConfigStaffTransfers');

            $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
            $institutionId = !is_null($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->getInstitutionID();
            $userId = $entity->id;

            $enableStaffTransfer = $ConfigStaffTransfersTable->checkIfTransferEnabled($institutionId);

            $assignedStaffRecords = $StaffTable->find()
                ->where([
                    $StaffTable->aliasField('staff_id') => $userId,
                    $StaffTable->aliasField('institution_id') => $institutionId,
                    $StaffTable->aliasField('staff_status_id') => $assignedStatus
                ])
                ->count();

            if ($enableStaffTransfer && $assignedStaffRecords > 0) {
                $url = [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    //'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                    'action' => 'StaffTransferOut',
                    '0' => 'add',
                    '1' => $encodedQueryString
                ];

                $transferButton = $toolbarButtons['back'];
                $transferButton['type'] = 'button';
                $transferButton['label'] = '<i class="fa kd-transfer"></i>';
                $transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $transferButton['attr']['title'] = __('Transfer');
                $transferButton['url'] = $this->setQueryString($url, ['user_id' => $userId, 'institution_id' => $institutionId]);

                //echo "<pre>"; print_r($transferButton); die;
                $toolbarButtons['transfer'] = $transferButton;
            }
        }
    }

    private function addReleaseButton(Entity $entity, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if ($this->AccessControl->check([$this->controller->getName(), 'StaffRelease', 'add'])) {

            $toolbarButtons = $extra['toolbarButtons'];
            $StaffTable = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $ConfigStaffReleaseTable = TableRegistry::get('Configuration.ConfigStaffReleases');

            $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
            $institutionId = $this->getInstitutionID();
            $userId = $entity->id;

            $enableStaffRelease = $ConfigStaffReleaseTable->checkIfReleaseEnabled($institutionId);

            $assignedStaffRecords = $StaffTable->find()
                ->where([
                    $StaffTable->aliasField('staff_id') => $userId,
                    $StaffTable->aliasField('institution_id') => $institutionId,
                    $StaffTable->aliasField('staff_status_id') => $assignedStatus
                ])
                ->count();

            if ($enableStaffRelease && $assignedStaffRecords > 0) {
                $url = [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    //'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                    'action' => 'StaffRelease',
                    0 =>'add',
                    1 => $encodedQueryString
                ];
                $getInstitutionId = $this->getQueryString('institution_id');
                $releaseButton = $toolbarButtons['back'];
                $releaseButton['type'] = 'button';
                $releaseButton['label'] = '<i class="fa kd-release"></i>';
                $releaseButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $releaseButton['attr']['title'] = __('Release');
                $releaseButton['url'] = $this->setQueryString($url, ['user_id' => $userId, 'institution_id' => $getInstitutionId, 'staff_id' => $userId]);

                $toolbarButtons['release'] = $releaseButton;
            }
        }
    }

    //POCOR-5070:Start

    public function editAfterAction(Event $event, Entity $entity)
    {
        //POCOR-5070:Start
        if ($entity->id) {
            $staff = TableRegistry::get('Institution.Staff');
            $staffIsHomeroom = $staff->find('all', ['conditions' => ['staff_id' => $entity->id]])->first();
            $entity->is_homeroom = $staffIsHomeroom->is_homeroom;
        }
        //POCOR-5070:end
        $this->Session->write('Staff.Staff.id', $entity->id);
        $this->Session->write('Staff.Staff.name', $entity->name);
        $this->setupTabElements($entity);

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
        //POCOR-5070:start
        $this->field('is_homeroom', [
            'type' => 'select'
        ]);
        $this->fields['is_homeroom']['options'] = [0 => 'No', 1 => 'Yes'];
        $this->fields['is_homeroom']['value'] = $entity->is_homeroom;
        //POCOR-5070:end

        $this->fields['nationality_id']['type'] = 'readonly';
        $this->fields['nationality_id']['attr']['value'] = $entity->has('main_nationality') ? $entity->main_nationality->name : '';

        $this->fields['identity_type_id']['type'] = 'readonly';
        $this->fields['identity_type_id']['attr']['value'] = $entity->has('main_identity_type') ? $entity->main_identity_type->name : '';
    }

    public function onGetIsHomeroom(Event $event, Entity $entity)
    {
        return ($entity->is_homeroom) ? __('Yes') : __('No');
    }

    //POCOR-5070:end

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->field('is_homeroom', [
            'type' => 'select'
        ]);
        $this->fields['is_homeroom']['options'] = [0 => 'No', 1 => 'Yes'];
    }

    public function editAfterSave(Event $event, Entity $entity)
    {
        //POCOR-5070:start
        $staffT = TableRegistry::get('Institution.Staff');//POCOR-8364
        $entityData = $staffT->find('all', ['conditions' => ['staff_id' => $entity->id]])->first();
        $entityData->is_homeroom = $entity->is_homeroom;
        $saveData = $staffT->save($entityData);
        //POCOR-5070:end
        if ($this->action == 'edit') {
            $staff = TableRegistry::get('Institution.Staff');
            $bodyData = $staff->find('all',
                ['contain' => [
                    'Institutions',
                    'StaffTypes',
                    'StaffPositionProfiles',
                    'Positions',
                    'Positions.StaffPositionTitles',
                    'Users',
                    'Users.Genders',
                    'Users.MainNationalities',
                    'Users.Identities.IdentityTypes',
                    'Users.AddressAreas',
                    'Users.BirthplaceAreas',
                    'Users.Contacts.ContactTypes'
                ],
                ])->where([
                $staff->aliasField('staff_id') => $entity->id
            ]);


            if (!empty($bodyData)) {
                foreach ($bodyData as $key => $value) {
                    $user_id = $value->user->id;
                    $openemis_no = $value->user->openemis_no;
                    $first_name = $value->user->first_name;
                    $middle_name = $value->user->middle_name;
                    $third_name = $value->user->third_name;
                    $last_name = $value->user->last_name;
                    $preferred_name = $value->user->preferred_name;
                    $gender = $value->user->gender->name;
                    $nationality = $value->user->main_nationality->name;
                    $dateOfBirth = $value->user->date_of_birth;

                    $address = $value->user->address;
                    $postalCode = $value->user->postal_code;
                    $addressArea = $value->user->address_area->name;
                    $birthplaceArea = $value->user->birthplace_area->name;
                    $role = $value->user->is_staff;
                    $contactValue = [];
                    $contactType = [];
                    if (!empty($value->user['contacts'])) {
                        foreach ($value->user['contacts'] as $key => $contact) {
                            $contactValue[] = $contact->value;
                            $contactType[] = $contact->contact_type->name;
                        }
                    }

                    $identityNumber = [];
                    $identityType = [];
                    if (!empty($value->user['identities'])) {
                        foreach ($value->user['identities'] as $key => $identity) {
                            $identityNumber[] = $identity->number;
                            $identityType[] = $identity->identity_type->name;
                        }
                    }

                    $username = $value->user->username;
                    $institution_id = $value->institution->id;
                    $institutionName = $value->institution->name;
                    $institutionCode = $value->institution->code;

                    $position_no = $value->position->position_no;
                    $staff_position_titles_type = $value->position->staff_position_title->type;
                    $staff_types_name = $value->staff_type->name;

                    if ($staff_position_titles_type == 1) {
                        $class = 'Teaching';
                    } else {
                        $class = 'Non-Teaching';
                    }
                    $staff_position_titles_name = $value->position->staff_position_title->name;
                    $institutionStaffId = $value->id;
                    $startDate = $value->start_date;
                    $endDate = $value->end_date;

                }
            }
            //POCOR-8364
            $institutionShifts = TableRegistry::get('Institution.InstitutionShifts');
            $shiftOptions = TableRegistry::get('Institution.ShiftOptions');
            $institutionStaffShifts = TableRegistry::get('Institution.InstitutionStaffShifts');
            //POCOR-8364
            $res = $institutionShifts->find()->select(['name' => 'name'])
                ->leftJoin(
                    [$shiftOptions->getAlias() => $shiftOptions->getTable()],
                    [
                        $shiftOptions->aliasField('id = ') . $institutionShifts->aliasField('shift_option_id')
                    ]
                )
                ->leftJoin(
                    [$institutionStaffShifts->getAlias() => $institutionStaffShifts->getTable()],
                    [
                        $institutionStaffShifts->aliasField('shift_id = ') . $institutionShifts->aliasField('id')
                    ]
                )
                ->where([$institutionStaffShifts->aliasField('staff_id') => $entity->id])->order($institutionShifts->aliasField('id'))->group('name')->order('name')->toArray();
            $shift = '';
            foreach ($res as $key => $value) {
                $shift .= $value['name'] . ',';
            }
            $shiftName = rtrim($shift, ',');
            if (!empty($shiftData)) {
                foreach ($shiftData as $k => $val) {
                    $shiftName = $val->shift_option->name;
                }
            }
            $bodys = array();

            $bodys = [
                'security_users_id' => !empty($user_id) ? $user_id : NULL,
                'security_users_openemis_no' => !empty($openemis_no) ? $openemis_no : NULL,
                'security_users_first_name' => !empty($first_name) ? $first_name : NULL,
                'security_users_middle_name' => !empty($middle_name) ? $middle_name : NULL,
                'security_users_third_name' => !empty($third_name) ? $third_name : NULL,
                'security_users_last_name' => !empty($last_name) ? $last_name : NULL,
                'security_users_preferred_name' => !empty($preferred_name) ? $preferred_name : NULL,
                'security_users_gender' => !empty($gender) ? $gender : NULL,
                'security_users_date_of_birth' => !empty($dateOfBirth) ? date("d-m-Y", strtotime($dateOfBirth)) : NULL,
                'security_users_address' => !empty($address) ? $address : NULL,
                'security_users_postal_code' => !empty($postalCode) ? $postalCode : NULL,
                'area_administrative_name_birthplace' => !empty($addressArea) ? $addressArea : NULL,
                'area_administrative_name_address' => !empty($birthplaceArea) ? $birthplaceArea : NULL,
                'contact_type_name' => !empty($contactType) ? $contactType : NULL,
                'user_contact_type_value' => !empty($contactValue) ? $contactValue : NULL,
                'nationality_name' => !empty($nationality) ? $nationality : NULL,
                'identity_type_name' => !empty($identityType) ? $identityType : NULL,
                'user_identities_number' => !empty($identityNumber) ? $identityNumber : NULL,
                'security_user_username' => !empty($username) ? $username : NULL,
                'institutions_id' => !empty($institution_id) ? $institution_id : NULL,
                'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
                'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
                //'institution_staff_id' => !empty($institutionStaffId) ? $institutionStaffId : NULL,
                'institution_staff_start_date' => !empty($startDate) ? date("d-m-Y", strtotime($startDate)) : NULL,
                'institution_staff_end_date' => !empty($endDate) ? date("d-m-Y", strtotime($endDate)) : NULL,
                'institution_positions_position_no' => !empty($position_no) ? $position_no : NULL,
                'staff_position_titles_type' => !empty($class) ? $class : NULL,
                'staff_position_titles_name' => !empty($staff_position_titles_name) ? $staff_position_titles_name : NULL,
                'staff_types_name' => !empty($staff_types_name) ? $staff_types_name : NULL,
                'shift_options_name' => !empty($shiftName) ? $shiftName : NULL,
                'role_name' => ($role == 1) ? 'staff' : NULL
            ];
            //POCOR-6805 start
            //POCOR-8364
            $Guardians = TableRegistry::get('StaffCustomField.StaffCustomFieldValues');
            $staffCustomFieldOptions = TableRegistry::get('StaffCustomField.StaffCustomFieldOptions');
            $staffCustomFields = TableRegistry::get('StaffCustomField.StaffCustomFields');
            $staffCustomFormsFields = TableRegistry::get('StaffCustomField.StaffCustomFormsFields');
            //POCOR-8364
            //POCOR-6805 start

            $guardianData = $Guardians->find()
                ->select([
                    'id' => $Guardians->aliasField('id'),
                    'staff_id' => $Guardians->aliasField('staff_id'),
                    'staff_custom_field_id' => $Guardians->aliasField('staff_custom_field_id'),
                    'text_value' => $Guardians->aliasField('text_value'),
                    'number_value' => $Guardians->aliasField('number_value'),
                    'decimal_value' => $Guardians->aliasField('decimal_value'),
                    'textarea_value' => $Guardians->aliasField('textarea_value'),
                    'date_value' => $Guardians->aliasField('date_value'),
                    'time_value' => $Guardians->aliasField('time_value'),
                    'checkbox_value_text' => 'staffCustomFieldOptions.name',
                    'name' => 'staffCustomField.name',
                    'staff_custom_id' => 'staffCustomField.id',
                    'field_type' => 'staffCustomField.field_type',
                ])
                ->leftJoin(
                    ['staffCustomField' => 'staff_custom_fields'],
                    [
                        'staffCustomField.id = ' . $Guardians->aliasField('staff_custom_field_id')
                    ]
                )
                ->leftJoin(
                    ['staffCustomFieldOptions' => 'staff_custom_field_options'],
                    [
                        'staffCustomFieldOptions.id = ' . $Guardians->aliasField('number_value')
                    ]
                )
                ->where([
                    $Guardians->aliasField('staff_id') => $user_id,
                ])
                ->toArray();

            $custom_field = array();
            $count = 0;
            if (!empty($guardianData)) {
                foreach ($guardianData as $val) {
                    $custom_field['custom_field'][$count]["id"] = (!empty($val['staff_custom_id']) ? $val['staff_custom_id'] : '');
                    $custom_field['custom_field'][$count]["name"] = (!empty($val['name']) ? $val['name'] : '');
                    $fieldTypes[$count] = (!empty($val['field_type']) ? $val['field_type'] : '');
                    $fieldType = $fieldTypes[$count];
                    if ($fieldType == 'TEXT') {
                        $custom_field['custom_field'][$count]["text_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                    } else if ($fieldType == 'CHECKBOX') {
                        $custom_field['custom_field'][$count]["checkbox_value"] = (!empty($val['checkbox_value_text']) ? $val['checkbox_value_text'] : '');
                    } else if ($fieldType == 'NUMBER') {
                        $custom_field['custom_field'][$count]["number_value"] = (!empty($val['number_value']) ? $val['number_value'] : '');
                    } else if ($fieldType == 'DECIMAL') {
                        $custom_field['custom_field'][$count]["decimal_value"] = (!empty($val['decimal_value']) ? $val['decimal_value'] : '');
                    } else if ($fieldType == 'TEXTAREA') {
                        $custom_field['custom_field'][$count]["textarea_value"] = (!empty($val['textarea_value']) ? $val['textarea_value'] : '');
                    } else if ($fieldType == 'DROPDOWN') {
                        $custom_field['custom_field'][$count]["dropdown_value"] = (!empty($val['checkbox_value_text']) ? $val['checkbox_value_text'] : '');
                    } else if ($fieldType == 'DATE') {
                        $custom_field['custom_field'][$count]["date_value"] = date('Y-m-d', strtotime($val->date_value));
                    } else if ($fieldType == 'TIME') {
                        $custom_field['custom_field'][$count]["time_value"] = date('h:i A', strtotime($val->time_value));
                    } else if ($fieldType == 'COORDINATES') {
                        $custom_field['custom_field'][$count]["cordinate_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                    }
                    $count++;
                }
            }
            $body = array_merge($bodys, $custom_field); //POCOR-6805 end
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            $Webhooks->triggerShell('staff_update', ['username' => ''], $body);
        }
    }

    public function staffAfterSave(Event $event, $staff)
    {
        if ($staff->isNew()) {
            $this->updateAll(['is_staff' => 1], ['id' => $staff->staff_id]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $extraField[] = [
            "key" => "StaffUser.username",
            "field" => "username",
            "type" => "string",
            "label" => "Username"
        ];

        $extraField[] = [
            "key" => "StaffUser.openemis_no",
            "field" => "openemis_no",
            "type" => "string",
            "label" => "OpenEMIS ID"
        ];

        $extraField[] = [
            'key' => 'StaffUser.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => 'First Name'
        ];

        $extraField[] = [
            'key' => 'StaffUser.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => 'Middle Name'
        ];

        $extraField[] = [
            'key' => 'StaffUser.third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => 'Third Name'
        ];

        $extraField[] = [
            'key' => 'StaffUser.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => 'Last Name'
        ];

        $extraField[] = [
            'key' => 'StaffUser.preferred_name',
            'field' => 'preferred_name',
            'type' => 'string',
            'label' => __('Preferred Name')
        ];

        $extraField[] = [
            'key' => 'StaffUser.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];

        $extraField[] = [
            'key' => 'StaffUser.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];

        $extraField[] = [
            'key' => 'StaffUser.postal_code',
            'field' => 'postal_code',
            'type' => 'string',
            'label' => __('Postal Code')
        ];

        $extraField[] = [
            'key' => 'StaffUser.address_area_id',
            'field' => 'address_area_id',
            'type' => 'string',
            'label' => __('Address Area')
        ];

        $extraField[] = [
            'key' => 'StaffUser.birthplace_area_id',
            'field' => 'birthplace_area_id',
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];

        $extraField[] = [
            'key' => 'StaffUser.gender_id',
            'field' => 'gender_id',
            'type' => 'integer',
            'label' => 'Gender'
        ];

        $extraField[] = [
            'key' => 'StaffUser.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => 'Date Of Birth'
        ];

        $extraField[] = [
            'key' => 'StaffUser.nationality_id',
            'field' => 'nationality_id',
            'type' => 'integer',
            'label' => __('Nationality')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'number',
            'type' => 'string',
            'label' => __($identity->name)
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'contact_number',
            'type' => 'string',
            'label' => __('Contact Number')
        ];

        /* $extraField[] = [
            'key' => 'StaffUser.external_reference',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => __('External Reference')
        ]; */
        $extraField[] = [
            'key' => 'StaffUser.status',
            'field' => 'status',
            'type' => 'integer',
            'label' => __('Status')
        ];

        $extraField[] = [
            'key' => 'StaffUser.last_login',
            'field' => 'last_login',
            'type' => 'datetime',
            'label' => __('Last Login')
        ];
        $extraField[] = [
            'key' => 'StaffUser.preferred_language',
            'field' => 'preferred_language',
            'type' => 'string',
            'label' => __('Preferred Language')
        ];

        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession();
        $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        $userNationalities = TableRegistry::get('User.userNationalities');
        $userContacts = TableRegistry::get('UserContacts');
        $contactTypes = TableRegistry::get('User.ContactTypes');
        $contactOptions = TableRegistry::get('User.ContactOptions');
        $institutionStaff = TableRegistry::get('Institution.InstitutionStaff');


        $query
            ->select([
                'staff_id' => $this->aliasField('id'),
            ])
            ->leftjoin(
                [$institutionStaff->getAlias() => $institutionStaff->getTable()],
                [$institutionStaff->aliasField('staff_id = ') . $this->aliasField('id')]
            )
            ->where([
                $this->aliasField('id = ') . $staffUserId
            ]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $userContacts = TableRegistry::get('UserContacts');
                $contactTypes = TableRegistry::get('User.ContactTypes');
                $contactOptions = TableRegistry::get('User.ContactOptions');

                $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');

                $userContactsData = $userContacts
                    ->find()
                    ->select([
                        'contact_number' => 'userContacts.value', 'userContacts.preferred', 'userContacts.contact_type_id',
                    ])
                    ->leftjoin(
                        [$contactTypes->getAlias() => $contactTypes->getTable()],
                        [$contactTypes->aliasField('id=') . $userContacts->aliasField('contact_type_id')]
                    )
                    ->leftjoin(
                        [$contactOptions->getAlias() => $contactOptions->getTable()],
                        [$contactOptions->aliasField('id=') . $contactTypes->aliasField('contact_option_id')]
                    )
                    ->where([
                        $userContacts->aliasField('security_user_id') => $row->staff_id, 'userContacts.preferred' => 1
                    ]);

                $arr = $userContactsData->toArray();

                $contacct = array_filter($arr, function ($var) {
                    return ($var['preferred'] == 1);
                });

                $row['contact_number'] = '';
                if ($contacct) {
                    $d = implode(', ', array_column($contacct, 'contact_number'));

                    $row['contact_number'] = $d;
                }

                $userIdentities = TableRegistry::get('User.Identities');
                $identityType = TableRegistry::get('FieldOption.IdentityTypes');
                $nationalities = TableRegistry::get('FieldOption.Nationalities');

                $userIdentitiesData = $userIdentities
                    ->find()
                    ->select([
                        'identity_type' => 'IdentityTypes.name',
                        'nationality' => 'Nationalities.name',
                        'number' => 'Identities.number',
                        'issue_date' => 'Identities.issue_date',
                        'expiry_date' => 'Identities.expiry_date',
                        'issuer' => 'Identities.issue_location',
                    ])
                    ->leftjoin(
                        [$identityType->getAlias() => $identityType->getTable()],
                        [$identityType->aliasField('id = ') . $userIdentities->aliasField('identity_type_id')]
                    )
                    ->leftjoin(
                        [$nationalities->getAlias() => $nationalities->getTable()],
                        [$userIdentities->aliasField('nationality_id = ') . $nationalities->aliasField('id')]
                    )
                    ->where([
                        $userIdentities->aliasField('security_user_id') => $row->staff_id,
                    ]);

                $arr1 = $userIdentitiesData->toArray();

                $identy_num = array_filter($arr1, function ($var) {
                    return ($var['identity_type'] == 'Birth Certificate');
                });

                $row['number'] = '';
                if ($identy_num) {
                    $array_nums = array_values($identy_num); // POCOR-9123
                    $d = array_shift($array_nums);

                    $row['number'] = $d->number;
                } else {
                    $row['number'] = $arr1[0]->number;
                }
                return $row;
            });
        });

    }

    public function findStaff(Query $query, array $options = [])
    {
        $query->where([$this->aliasField('super_admin') . ' <> ' => 1]);

        $limit = (isset($options['limit'])) ? $options['limit'] : null;
        $page = (isset($options['page'])) ? $options['page'] : null;

        // conditions
        $firstName = (isset($options['first_name'])) ? $options['first_name'] : null;
        $lastName = (isset($options['last_name'])) ? $options['last_name'] : null;
        $openemisNo = (isset($options['openemis_no'])) ? $options['openemis_no'] : null;
        $identityNumber = (isset($options['identity_number'])) ? $options['identity_number'] : null;
        $dateOfBirth = (isset($options['date_of_birth'])) ? $options['date_of_birth'] : null;

        if (is_null($firstName) && is_null($lastName) && is_null($openemisNo) && is_null($identityNumber) && is_null($dateOfBirth)) {
            return $query->where(['1 = 0']);
        }

        $conditions = [];
        if (!empty($firstName)) {
            $conditions['first_name LIKE'] = $firstName . '%';
        }
        if (!empty($lastName)) {
            $conditions['last_name LIKE'] = $lastName . '%';
        }
        if (!empty($openemisNo)) {
            $conditions['openemis_no LIKE'] = $openemisNo . '%';
        }
        if (!empty($dateOfBirth)) {
            $conditions['date_of_birth'] = date_create($dateOfBirth)->format('Y-m-d');
        }

        $identityConditions = [];
        if (!empty($identityNumber)) {
            $identityConditions['Identities.number LIKE'] = $identityNumber . '%';
        }

        $identityJoinType = (empty($identityNumber)) ? 'LEFT' : 'INNER';
        $query->join([
            [
                'type' => $identityJoinType,
                'table' => 'user_identities',
                'alias' => 'Identities',
                'conditions' => array_merge([
                    'Identities.security_user_id = ' . $this->aliasField('id')
                ], $identityConditions)
            ]
        ]);

        $query->group([$this->aliasField('id')]);

        if (!empty($conditions)) {
            $query->where($conditions);
        }
        if (!is_null($limit)) {
            $query->limit($limit);
        }
        if (!is_null($page)) {
            $query->page($page);
        }

        return $query;
    }

    public function findAssignedInstitutionStaff(Query $query, array $options = [])
    {
        $institutionId = $options['institution_id'];
        $startDate = $options['start_date'];
        //POCOR-6704 add code_name, id
        // $query->select(['code_name'=>'InstitutionStaff.Institution.code','id'=>'InstitutionStaff.Institution.id'])->contain([
        //POCOR-6901 remove the select
        $query->contain([
            'InstitutionStaff' => function ($q) use ($institutionId, $startDate) {
                return $q->where([
                    'InstitutionStaff.institution_id <>' => $institutionId,
                    'InstitutionStaff.start_date < ' => $startDate,
                    'OR' => [
                        ['InstitutionStaff.end_date >= ' => $startDate],
                        ['InstitutionStaff.end_date IS NULL']
                    ]
                ])
                    ->order(['InstitutionStaff.created' => 'desc']);
            },
            'InstitutionStaff.Institutions.Areas'
        ]);
        return $query;
    }

    public function afterAction(Event $event, ArrayObject $options)
    {
        $users = TableRegistry::get('Security.Users');
        $plugin = __($this->controller->getPlugin());
        $id = $this->request->getAttribute('params')['pass'][1];
        $DecodedQueryString = $this->paramsDecode($id);
        $staffId = $DecodedQueryString['staff_id'];
        $data = $users->find()->select(['first_name'=>$users->aliasField('first_name'),'middle_name'=>$users->aliasField('middle_name'),'third_name'=>$users->aliasField('third_name'),'last_name'=>$users->aliasField('last_name')])
                ->where([$users->aliasField('id') => $staffId ])->first();
        $StaffName = $data->first_name.' '.$data->middle_name.' '.$data->third_name.' '.$data->last_name;
        try {

            $this->controller->set('contentHeader', $StaffName . ' - ' . 'Overview');
        } catch (RecordNotFoundException $e) {
            Log::write('error', $e->getMessage());
        }
    }

}
