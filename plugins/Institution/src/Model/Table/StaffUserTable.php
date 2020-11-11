<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Network\Session;
use App\Model\Table\ControllerActionTable;

class StaffUserTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        $this->entityClass('User.User');
        parent::initialize($config);
        self::handleAssociations($this);
        // Behaviors
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts']]);
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
            'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian', 'super_admin', 'date_of_death' ],
            'filename' => 'Staff',
            'pages' => ['view']
        ]);

        $this->addBehavior('HighChart', [
            'count_by_gender' => [
                '_function' => 'getNumberOfStaffByGender'
            ]
        ]);
        $this->addBehavior('Configuration.Pull');
        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Staff.Staff.id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index', 'add', 'edit'],
            'ReportCardComments' => ['view']
        ]);
        $this->toggle('index', false);
        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public static function handleAssociations($model)
    {
        $model->belongsTo('Genders', ['className' => 'User.Genders']);
        $model->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $model->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $model->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $model->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $model->hasMany('Identities', ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Nationalities', ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Contacts', ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Attachments', ['className' => 'User.Attachments',     'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('BankAccounts', ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Comments', ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Languages', ['className' => 'User.UserLanguages',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Awards', ['className' => 'User.Awards',          'foreignKey' => 'security_user_id', 'dependent' => true]);

        $model->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments',    'foreignKey' => 'security_user_id', 'dependent' => true]);

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
        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students',    'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('InstitutionStaff', ['className' => 'Institution.Staff',    'foreignKey' => 'staff_id', 'dependent' => true]);

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

    public function implementedEvents()
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
        $this->field('username', ['visible' => false]);
        $toolbarButtons = $extra['toolbarButtons'];
        if ($this->action == 'view') {
            $id = $this->request->query('id');
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

            $nat_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('user_identities');
                $user_id_data = $users_ids->find()
                ->select(['number'])
                ->where([                
                    $users_ids->aliasField('security_user_id') => $entity->id,
                    $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                ])
                ->first();
                if($user_id_data != null){
                    array_push($nat_based_ids, $user_id_data);
                }
            }
            
            if(count($nat_based_ids) > 0){
                // Case 2 - returning value
                return $entity->identity_number = $nat_based_ids[0]['number'];
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
        ->select(['number'])
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

            $nat_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('user_identities');
                $user_id_data = $users_ids->find()
                ->select(['number','identity_type_id'])
                ->where([                
                    $users_ids->aliasField('security_user_id') => $entity->id,
                    $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                ])
                ->first();
                if($user_id_data != null){
                    array_push($nat_based_ids, $user_id_data);
                }
            }
            if(count($nat_based_ids) > 0){
                // Case 2 - returning value
                $users_id_type = TableRegistry::get('identity_types');
                $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $nat_based_ids[0]['identity_type_id'],
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


    public function validationDefault(Validator $validator)
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
            ->add('start_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                'on' => function ($context) {
                    // check for staff add wizard on create operations - where academic_period_id exist in the context data - POCOR-4576
                    return ($context['newRecord'] && array_key_exists('academic_period_id', $context['data']));
                }
            ])
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

    private function addReleaseButton(Entity $entity, ArrayObject $extra)
    {
        if($this->AccessControl->check([$this->controller->name, 'StaffRelease', 'add'])) {

            $session = $this->request->session();
            $toolbarButtons = $extra['toolbarButtons'];
            $StaffTable = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $ConfigStaffReleaseTable = TableRegistry::get('Configuration.ConfigStaffReleases');

            $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
            $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');
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
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                    'action' => 'StaffRelease',
                    'add'
                ];

                $releaseButton = $toolbarButtons['back'];
                $releaseButton['type'] = 'button';
                $releaseButton['label'] = '<i class="fa kd-release"></i>';
                $releaseButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $releaseButton['attr']['title'] = __('Release');
                $releaseButton['url'] = $this->setQueryString($url, ['user_id' => $userId]);

                $toolbarButtons['release'] = $releaseButton;
            }
        }
    }

    private function addTransferButton(Entity $entity, ArrayObject $extra)
    {
        if ($this->AccessControl->check([$this->controller->name, 'StaffTransferOut', 'add'])) {
            $session = $this->request->session();
            $toolbarButtons = $extra['toolbarButtons'];
            $StaffTable = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $ConfigStaffTransfersTable = TableRegistry::get('Configuration.ConfigStaffTransfers');

            $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
            $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');
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
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                    'action' => 'StaffTransferOut',
                    'add'
                ];

                $transferButton = $toolbarButtons['back'];
                $transferButton['type'] = 'button';
                $transferButton['label'] = '<i class="fa kd-transfer"></i>';
                $transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $transferButton['attr']['title'] = __('Transfer');
                $transferButton['url'] = $this->setQueryString($url, ['user_id' => $userId]);

                $toolbarButtons['transfer'] = $transferButton;
            }
        }
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->Session->write('Staff.Staff.id', $entity->id);
        $this->Session->write('Staff.Staff.name', $entity->name);
        $this->setupTabElements($entity);

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.

        $this->fields['nationality_id']['type'] = 'readonly';
        $this->fields['nationality_id']['attr']['value'] = $entity->has('main_nationality') ? $entity->main_nationality->name : '';

        $this->fields['identity_type_id']['type'] = 'readonly';
        $this->fields['identity_type_id']['attr']['value'] = $entity->has('main_identity_type') ? $entity->main_identity_type->name : '';
    }

    private function setupTabElements($entity)
    {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;
        $options = [
            'userRole' => 'Staff',
            'action' => $this->action,
            'id' => $id,
            'userId' => $entity->id
        ];

        $tabElements = $this->controller->getUserTabElements($options);

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
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

        foreach ($fields as $key => $field) {
            //get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_number') {
                $fields[$key] = [
                    'key' => 'StudentUser.identity_number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __($identity->name)
                ];
                break;
            }
        }
    }

    public function findStaff(Query $query, array $options = [])
    {
        $query->where([$this->aliasField('super_admin').' <> ' => 1]);

        $limit = (array_key_exists('limit', $options))? $options['limit']: null;
        $page = (array_key_exists('page', $options))? $options['page']: null;

        // conditions
        $firstName = (array_key_exists('first_name', $options))? $options['first_name']: null;
        $lastName = (array_key_exists('last_name', $options))? $options['last_name']: null;
        $openemisNo = (array_key_exists('openemis_no', $options))? $options['openemis_no']: null;
        $identityNumber = (array_key_exists('identity_number', $options))? $options['identity_number']: null;
        $dateOfBirth = (array_key_exists('date_of_birth', $options))? $options['date_of_birth']: null;

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

        $identityJoinType = (empty($identityNumber))? 'LEFT': 'INNER';
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
}