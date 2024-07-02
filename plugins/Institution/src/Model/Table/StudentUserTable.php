<?php

namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Exception as DatabaseException;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class StudentUserTable extends ControllerActionTable
{
    private $studentsTabsData = [
        0 => "General",
        1 => "Academic",
        2 => "Assessment",
        3 => "Absence"
    ];
    // POCOR-6130 custome fields code
    private $_dynamicFieldName = 'custom_field_data';

    // POCOR-6130 custome fields code

    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        $this->setEntityClass('User.User');
        parent::initialize($config);

        // Associations
        self::handleAssociations($this);

        // Behaviors
        $this->addBehavior('User.User');
        // this code is commented in POCOR-6130 because custome fields were coming in every tab so now custome fields function has been changed to custome
        if (!in_array('Custom Fields', (array)Configure::read('School.excludedPlugins'))) {
//            $this->addBehavior('CustomField.Record', [
//                'model' => 'Student.Students',
//                'behavior' => 'Student',
//                'fieldKey' => 'student_custom_field_id',
//                'tableColumnKey' => 'student_custom_table_column_id',
//                'tableRowKey' => 'student_custom_table_row_id',
//                'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
//                'formKey' => 'student_custom_form_id',
//                'filterKey' => 'student_custom_filter_id',
//                'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
//                // 'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
//                'recordKey' => 'student_id',
////                'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
//                'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
//            ]);
        }

        $this->addBehavior('Excel', [
            'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian', 'super_admin', 'date_of_death'],
            'filename' => 'Students',
            'pages' => ['view']
        ]);

        // $this->addBehavior('Configuration.Pull');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add', 'edit']
        ]);
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }

        $this->toggle('index', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab');
        
        $studentID = $this->getStudentID();
        //$this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Student.Students.id']);
        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => $studentID]);
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

        $model->hasMany('ClassStudents', [
            'className' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'student_id'
        ]);

        // remove all student records from institution_students, institution_site_student_absences, student_behaviours, assessment_item_results, student_guardians, institution_student_admission, student_custom_field_values, student_custom_table_cells, student_fees, student_extracurriculars


        $model->belongsToMany('Institutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'institution_students',
            'foreignKey' => 'student_id',
            'targetForeignKey' => 'institution_id',
            'through' => 'Institution.Students',
            'dependent' => true
        ]);

        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $model->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentBehaviours', ['className' => 'Institution.StudentBehaviours', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->belongsToMany('Guardians', [
            'className' => 'Student.Guardians',
            'foreignKey' => 'student_id',
            'targetForeignKey' => 'guardian_id',
            'through' => 'Student.StudentGuardians',
            'dependent' => true
        ]);
        $model->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('StudentCustomFieldValues', ['className' => 'CustomField.StudentCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentCustomTableCells', ['className' => 'CustomField.StudentCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('Extracurriculars', ['className' => 'Student.Extracurriculars', 'foreignKey' => 'security_user_id', 'dependent' => true]);
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

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['ControllerAction.Model.pull.beforePatch'] = 'pullBeforePatch';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $BaseUsers = TableRegistry::get('User.Users');
        $validator = $BaseUsers->setUserValidation($validator, $this);
        $validator->setProvider('custom', $this);
        $validator
            ->allowEmpty('student_name')
            ->add('student_name', 'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleStudentNotCompletedGrade', [
                'rule' => ['studentNotCompletedGrade', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
                'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                'on' => 'create'
            ])
            ->allowEmpty('class')
            ->add('class', 'ruleClassMaxLimit', [
                'rule' => ['checkInstitutionClassMaxLimit'],
                'on' => function ($context) {
                    return (!empty($context['data']['class']) && $context['newRecord']);
                }
            ])
            ->add('date_of_birth', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
                'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                'on' => 'create'
            ])
            // ->add('gender_id', 'ruleCompareStudentGenderWithInstitution', [
            //     'rule' => ['compareStudentGenderWithInstitution']
            // ])
            ->requirePresence('start_date', 'create')
            ->add('start_date', 'ruleCheckProgrammeEndDateAgainstStudentStartDate', [
                'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date'],
                'on' => 'create'
            ])
            ->requirePresence('education_grade_id', 'create')
            ->add('education_grade_id', 'ruleCheckProgrammeEndDate', [
                'rule' => ['checkProgrammeEndDate', 'education_grade_id'],
                'on' => 'create'
            ])
            ->requirePresence('academic_period_id', 'create')
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])//POCOR-5924 starts
            ->allowEmpty('identity_number')
            ->add('identity_number', 'ruleCheckUniqueIdentityNumber', [
                'rule' => ['checkUniqueIdentityNumber'],
                'on' => 'create'
            ])//POCOR-5924 ends
        ;
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('username', ['visible' => false]);
        $toolbarButtons = $extra['toolbarButtons'];

        // Back button does not contain the pass
        if ($this->action == 'edit' && !empty($this->paramsPass(0))) {
            $toolbarButtons['back']['url'][1] = $this->paramsPass(0);
        }
        
        // this value comes from the list page from StudentsTable->onUpdateActionButtons
        $institutionStudentId = $this->getQueryString('institution_student_id');
        $studentId = $this->getStudentID();
        $institutionId = $this->getInstitutionID();
        $extra['institutionId'] = $institutionId;
        
        // this is required if the student link is clicked from the Institution Classes or Subjects
        if (empty($institutionStudentId)) {
            $params = [];
            //$studentId = isset($params['id']) ? $params['id'] : $this->Session->read('Institution.StudentUser.primaryKey.id');

            // get the id of the latest student record in the current institution
            $InstitutionStudentsTable = TableRegistry::get('Institution.Students');
            $institutionStudentId = $InstitutionStudentsTable->find()
                ->where([
                    $InstitutionStudentsTable->aliasField('student_id') => $studentId,
                    $InstitutionStudentsTable->aliasField('institution_id') => $institutionId,
                ])
                ->order([$InstitutionStudentsTable->aliasField('created') => 'DESC'])
                ->extract('id')
                ->first();
            if (empty($institutionStudentId)) { // if value is empty, redirect back to the list page
                $event->stopPropagation();
                return $this->controller->redirect(['action' => 'Students', 'index']);
            } else {
                //$this->request->query['id'] = $institutionStudentId;
                $this->request = $this->request->withQueryParams(['id' => $institutionStudentId]);
                $extra['institutionStudentId'] = $institutionStudentId;
            }        
        }else{
            $extra['institutionStudentId'] = $institutionStudentId;
        }
//         echo "<pre>"; print_r($extra);
// die;
        // this is required if the student link is clicked from the Institution Classes or Subjects
        if (empty($studentId)) { // if value is empty, redirect back to the list page
            $event->stopPropagation();
            return $this->controller->redirect(['action' => 'Students', 'index']);
        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions', 'Overview', 'Students - General');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    // POCOR-5684
    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        $users_ids = TableRegistry::get('User.UserIdentities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])->all();

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
        $users_ids = TableRegistry::get('User.Identities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();
        $users_ids = TableRegistry::get('User.Identities');
        $user_id_data = $users_ids->find()
            ->select(['number', 'identity_type_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            $users_id_type = TableRegistry::get('User.IdentityTypes');
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
            // echo '<pre>'; print_r($nationality_based_ids); die;
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

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $entity = $extra['entity'];
        $userId = $this->Auth->user('id');
        $studentID = $this->getStudentID();
        $institutionID = $this->getInstitutionID();
        $queryString = $this->getQueryString();
        $queryString['id'] = $studentID;
        $encodedQueryString = $this->paramsEncode($queryString);
        $StudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
        $userId = $this->Auth->user('id');
        $isStudentEnrolled = $StudentsTable->checkEnrolledInInstitution($studentID, $institutionID); // PHPOE-1897
        $isAllowedByClass = $this->checkClassPermission($studentID, $userId); // POCOR-3010
        if (isset($extra['toolbarButtons']['edit']['url'])) {
            $extra['toolbarButtons']['edit']['url'][1] = $encodedQueryString;
        }
        if (!$isStudentEnrolled || !$isAllowedByClass) {
            $this->toggle('edit', false);
        }

    }

    private function checkClassPermission($studentId, $userId)
    {
        $permission = false;
        if (!$this->AccessControl->isAdmin()) {
            $event = $this->controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
            $roles = [];
            if (is_array($event->getResult())) {
                $roles = $event->getResult();
            }
            if (!$this->AccessControl->check(['Institutions', 'AllClasses', $permission], $roles)) {
                $Class = TableRegistry::get('Institution.InstitutionClasses');
                $classStudentRecord = $Class
                    ->find('ByAccess', [
                        'accessControl' => $this->AccessControl,
                        'controller' => $this->controller,
                        'userId' => $userId,
                        'permission' => 'edit'
                    ])
                    ->innerJoinWith('ClassStudents')
                    ->where(['ClassStudents.student_id' => $studentId])
                    ->toArray();
                if (!empty($classStudentRecord)) {
                    $permission = true;
                }
            } else {
                $permission = true;
            }
        } else {
            $permission = true;
        }
        return $permission;
    }

    //POCOR-7982

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities', 'MainIdentityTypes', 'Genders'
        ]);
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {

        $this->setupTabElements($entity);
        $this->setupToolbarButtons($entity, $extra);

        $btnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $institutionId = $this->getInstitutionID();
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $extraButtons = [
            'export' => [
                'Institution' => ['Institutions', 'StudentUserExport', $institutionId],
                'action' => 'StudentUserExport',
                'icon' => '<i class="fa kd-export"></i>',
                'title' => __('Export')
            ]
        ];
        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'button',
                    'attr' => $btnAttr,
                    'url' => [0 => 'excel', 1 => $encodedQueryString]
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }
    }

    private function setupTabElements($entity)
    {
        $tabElements = $this->setUserTabElements($options);
    }

    private function setupToolbarButtons(Entity $entity, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['back']['url']['action'] = 'Students';

        // Export execute permission.
        if (!$this->AccessControl->check(['Institutions', 'StudentUser', 'excel'])) {
            if (isset($toolbarButtons['export'])) {
                unset($toolbarButtons['export']);
            }
        }
        $status_can_be_changed = $this->checkStatusCanBeChanged($extra); //        POCOR-8003 refactured
        if ($status_can_be_changed) {
            $this->addPromoteButton($entity, $extra);
            $this->addTransferButton($entity, $extra);
            $this->addWithdrawButton($entity, $extra);
        }

    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->Session->write('Student.Students.id', $entity->id);
        $this->Session->write('Student.Students.name', $entity->name);
        $this->setupTabElements($entity);

        // POCOR-3010
        $userId = $this->Auth->user('id');
        if (!$this->checkClassPermission($entity->id, $userId)) {
            $this->Alert->error('security.noAccess');
            $event->stopPropagation();
            $url = $this->url('view');
            return $this->controller->redirect($url);
        }
        // End POCOR-3010

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
        $this->fields['email']['type'] = 'string'; //POCOR-7056
        $this->fields['nationality_id']['type'] = 'readonly';
        $this->fields['nationality_id']['attr']['value'] = $entity->has('main_nationality') ? $entity->main_nationality->name : '';

        $this->fields['identity_type_id']['type'] = 'readonly';
        $this->fields['identity_type_id']['attr']['value'] = $entity->has('main_identity_type') ? $entity->main_identity_type->name : '';

        $this->field('institution_id', ['type' => 'hidden']);
        $this->fields['institution_id']['value'] = $extra['institutionId'];
    }

    public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['fieldName'] = $this->getAlias() . '.identities.0.number';
            $attr['attr']['label'] = __('Identity Number');
        }
        return $attr;
    }

    public function studentsAfterSave(Event $event, $student)
    {
        if ($student->isNew()) {
            $this->updateAll(['is_student' => 1], ['id' => $student->student_id]);
        }
    }

    //to handle identity_number field that is automatically created by mandatory behaviour.

    public function pullBeforePatch(Event $event, Entity $entity, ArrayObject $queryString, ArrayObject $patchOption, ArrayObject $extra)
    {
        if (!array_key_exists('institution_id', $queryString)) {
            $session = $this->request->getSession();
            $queryString['institution_id'] = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->getInstitutionID();
        }
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $studentsTabsData = $this->studentsTabsData;
        $InstitutionStudents = TableRegistry::get('User.InstitutionStudents');
        $institutionStudentId = $settings['id'];

        foreach ($studentsTabsData as $key => $val) {
            $tabsName = $val . 's';
            $sheets[] = [
                'sheetData' => [
                    'student_tabs_type' => $val
                ],
                'name' => $tabsName,
                'table' => $this,
                'query' => $this
                    ->find()
                /* ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],[
                    $this->aliasField('id = ').$InstitutionStudents->aliasField('student_id')
                ])
                ->where([
                    $InstitutionStudents->aliasField('student_id = ').$institutionStudentId,
                ]) */,
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $StudentType = $sheetData['student_tabs_type'];

        $newFields = [];
        if ($StudentType == 'General') {
            $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
            $identity = $IdentityType->getDefaultEntity();

            $extraField[] = [
                "key" => "StudentUser.username",
                "field" => "username",
                "type" => "string",
                "label" => "Username"
            ];

            $extraField[] = [
                "key" => "StudentUser.openemis_no",
                "field" => "openemis_no",
                "type" => "string",
                "label" => "OpenEMIS ID"
            ];

            $extraField[] = [
                'key' => 'StudentUser.first_name',
                'field' => 'first_name',
                'type' => 'string',
                'label' => 'First Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.middle_name',
                'field' => 'middle_name',
                'type' => 'string',
                'label' => 'Middle Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.third_name',
                'field' => 'third_name',
                'type' => 'string',
                'label' => 'Third Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.last_name',
                'field' => 'last_name',
                'type' => 'string',
                'label' => 'Last Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.preferred_name',
                'field' => 'preferred_name',
                'type' => 'string',
                'label' => __('Preferred Name')
            ];

            $extraField[] = [
                'key' => 'StudentUser.email',
                'field' => 'email',
                'type' => 'string',
                'label' => __('Email')
            ];

            $extraField[] = [
                'key' => 'StudentUser.address',
                'field' => 'address',
                'type' => 'string',
                'label' => __('Address')
            ];

            $extraField[] = [
                'key' => 'StudentUser.postal_code',
                'field' => 'postal_code',
                'type' => 'string',
                'label' => __('Postal Code')
            ];

            $extraField[] = [
                'key' => 'StudentUser.address_area_id',
                'field' => 'address_area_id',
                'type' => 'string',
                'label' => __('Address Area')
            ];

            $extraField[] = [
                'key' => 'StudentUser.birthplace_area_id',
                'field' => 'birthplace_area_id',
                'type' => 'string',
                'label' => __('Birthplace Area')
            ];

            $extraField[] = [
                'key' => 'StudentUser.gender_id',
                'field' => 'gender_id',
                'type' => 'integer',
                'label' => 'Gender'
            ];

            $extraField[] = [
                'key' => 'StudentUser.date_of_birth',
                'field' => 'date_of_birth',
                'type' => 'date',
                'label' => 'Date Of Birth'
            ];

            $extraField[] = [
                'key' => 'StudentUser.nationality_id',
                'field' => 'nationality_id',
                'type' => 'integer',
                'label' => __('Nationality')
            ];

            $extraField[] = [
                'key' => 'StudentUser.identity_number',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __($identity->name)
            ];

            $extraField[] = [
                'key' => 'StudentUser.external_reference',
                'field' => 'external_reference',
                'type' => 'string',
                'label' => __('External Reference')
            ];
            $extraField[] = [
                'key' => 'StudentUser.status',
                'field' => 'status',
                'type' => 'integer',
                'label' => __('Status')
            ];

            $extraField[] = [
                'key' => 'StudentUser.last_login',
                'field' => 'last_login',
                'type' => 'datetime',
                'label' => __('Last Login')
            ];
            $extraField[] = [
                'key' => 'StudentUser.preferred_language',
                'field' => 'preferred_language',
                'type' => 'string',
                'label' => __('Preferred Language')
            ];

            // POCOR-6129 custome fields code
            $InfrastructureCustomFields = TableRegistry::get('student_custom_fields');
            $customFieldData = $InfrastructureCustomFields->find()->select([
                'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                'custom_field' => $InfrastructureCustomFields->aliasfield('name')
            ])->group($InfrastructureCustomFields->aliasfield('id'))->toArray();

            if (!empty($customFieldData)) {
                foreach ($customFieldData as $data) {
                    $custom_field_id = $data->custom_field_id;
                    $custom_field = $data->custom_field;
                    $extraField[] = [
                        'key' => '',
                        'field' => $this->_dynamicFieldName . '_' . $custom_field_id,
                        'type' => 'string',
                        'label' => __($custom_field)
                    ];
                }
            }
            // POCOR-6129 custome fields code

            $fields->exchangeArray($extraField);
        }

        if ($StudentType == 'Academic') {
            $newFields[] = [
                'key' => '',
                'field' => 'academic_period_name',
                'type' => 'string',
                'alias' => 'academic_period_name',
                'label' => __('Academic Period')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'education_programme',
                'type' => 'string',
                'label' => __('Education Programme')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'education_grade_name',
                'type' => 'string',
                'label' => __('Education Grade')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'start_date_name',
                'type' => 'date',
                'label' => __('Start Date')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'end_date_name',
                'type' => 'date',
                'label' => __('End Date')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'current_class_name',
                'type' => 'string',
                'label' => __('Current Class')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'student_status_name',
                'type' => 'string',
                'label' => __('Student Status')
            ];

            $fields->exchangeArray($newFields);
        }

        if ($StudentType == 'Assessment') {
            $newFields[] = [
                'key' => '',
                'field' => 'asses_academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'asses_institution_name',
                'type' => 'string',
                'label' => __('Institution')
            ];
            $newFields[] = [
                'key' => '',
                //POCOR-7474-HINDOL TYPO FIX
                'field' => 'assessment_period',
                'type' => 'string',
                'label' => __('Assessment Periods')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'education_subject',
                'type' => 'string',
                'label' => __('Subject')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'marks',
                'type' => 'string',
                'label' => __('Mark')
            ];

            $fields->exchangeArray($newFields);
        }

        if ($StudentType == 'Absence') {
            $newFields[] = [
                'key' => '',
                'field' => 'absense_date',
                'type' => 'date',
                'label' => __('Date')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'absense',
                'type' => 'string',
                'label' => __('Absense')
            ];

            $fields->exchangeArray($newFields);
        }

    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $InstitutionStudents = TableRegistry::get('User.InstitutionStudents');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $studentAbsenceDays = TableRegistry::get('InstitutionStudentAbsenceDays');
        $Subjects = TableRegistry::get('Institution.InstitutionSubjects');
        $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $institutionStudentId = $settings['id'];
        $institutionId = $this->getInstitutionID();
        $periodId = $this->request->getQuery('academic_period_id');
        $currDateTime = date("Y-m-d");

        // for Academic Tab
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $institutions = TableRegistry::get('Institution.Institutions');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        // for Academic Tab

        // for abesense
        $institutionStudentAbsenses = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $absensesTypes = TableRegistry::get('absence_types');
        // for abesense

        // for assessments
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        // for assessments

        $sheetData = $settings['sheet']['sheetData'];
        $StudentType = $sheetData['student_tabs_type'];

        // for Generals Tab
        if ($StudentType == 'General') {
            $query->formatResults(function (CollectionInterface $results) {
                return $results->map(function ($row) {
                    // POCOR-6130 custome fields code
                    $Guardians = TableRegistry::get('StudentCustomField.StudentCustomFieldValues');
                    $studentCustomFieldOptions = TableRegistry::get('StudentCustomField.StudentCustomFieldOptions');
                    $studentCustomFields = TableRegistry::get('StudentCustomField.StudentCustomFields');

                    $guardianData = $Guardians->find()
                        ->select([
                            'id' => $Guardians->aliasField('id'),
                            'student_id' => $Guardians->aliasField('student_id'),
                            'student_custom_field_id' => $Guardians->aliasField('student_custom_field_id'),
                            'text_value' => $Guardians->aliasField('text_value'),
                            'number_value' => $Guardians->aliasField('number_value'),
                            'decimal_value' => $Guardians->aliasField('decimal_value'),
                            'textarea_value' => $Guardians->aliasField('textarea_value'),
                            'date_value' => $Guardians->aliasField('date_value'),
                            'time_value' => $Guardians->aliasField('time_value'),
                            'checkbox_value_text' => 'studentCustomFieldOptions.name',
                            'question_name' => 'studentCustomField.name',
                            'field_type' => 'studentCustomField.field_type',
                            'field_description' => 'studentCustomField.description',
                            'question_field_type' => 'studentCustomField.field_type',
                        ])->leftJoin(
                            ['studentCustomField' => 'student_custom_fields'],
                            [
                                'studentCustomField.id = ' . $Guardians->aliasField('student_custom_field_id')
                            ]
                        )->leftJoin(
                            ['studentCustomFieldOptions' => 'student_custom_field_options'],
                            [
                                'studentCustomFieldOptions.id = ' . $Guardians->aliasField('number_value')
                            ]
                        )
                        ->where([
                            $Guardians->aliasField('student_id') => $row['id'],
                        ])->toArray();

                    $existingCheckboxValue = '';
                    foreach ($guardianData as $guadionRow) {
                        $fieldType = $guadionRow->field_type;
                        if ($fieldType == 'TEXT') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->text_value;
                        } else if ($fieldType == 'CHECKBOX') {
                            $existingCheckboxValue = trim($row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id], ',') . ',' . $guadionRow->checkbox_value_text;
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = trim($existingCheckboxValue, ',');
                        } else if ($fieldType == 'NUMBER') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->number_value;
                        } else if ($fieldType == 'DECIMAL') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->decimal_value;
                        } else if ($fieldType == 'TEXTAREA') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->textarea_value;
                        } else if ($fieldType == 'DROPDOWN') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->checkbox_value_text;
                        } else if ($fieldType == 'DATE') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = date('Y-m-d', strtotime($guadionRow->date_value));
                        } else if ($fieldType == 'TIME') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = date('h:i A', strtotime($guadionRow->time_value));
                        } else if ($fieldType == 'COORDINATES') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->text_value;
                        } else if ($fieldType == 'NOTE') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->field_description;
                        }
                    }
                    // POCOR-6130 custome fields code

                    return $row;
                });
            });
        }
        // for Generals Tab
        // for Academics Tab
        if ($StudentType == 'Academic') {
            $query
                ->select([
                    'id' => $InstitutionStudents->aliasField('id'),
                    'academic_period_name' => $AcademicPeriods->aliasField('name'),
                    'institution_name' => $institutions->find()->func()->concat([
                        $institutions->aliasField('code') => 'literal',
                        " - ",
                        $institutions->aliasField('name') => 'literal'
                    ]),
                    'education_programme' => $EducationProgrammes->aliasField('name'),
                    'education_grade_name' => $EducationGrades->aliasField('name'),
                    'start_date_name' => $InstitutionStudents->aliasField('start_date'),
                    'end_date_name' => $InstitutionStudents->aliasField('end_date'),
                    'student_status_name' => $StudentStatuses->aliasField('name'),
                    'current_class_name' => $Classes->aliasField('name'),
                ])
                ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()], [
                    $this->aliasField('id = ') . $InstitutionStudents->aliasField('student_id')
                ])
                ->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()], [
                    $InstitutionStudents->aliasField('academic_period_id = ') . $AcademicPeriods->aliasField('id')
                ])
                ->innerJoin([$institutions->getAlias() => $institutions->getTable()], [
                    $InstitutionStudents->aliasField('institution_id = ') . $institutions->aliasField('id')
                ])
                ->innerJoin([$EducationGrades->getAlias() => $EducationGrades->getTable()], [
                    $InstitutionStudents->aliasField('education_grade_id = ') . $EducationGrades->aliasField('id')
                ])
                ->innerJoin([$EducationProgrammes->getAlias() => $EducationProgrammes->getTable()], [
                    $EducationGrades->aliasField('education_programme_id = ') . $EducationProgrammes->aliasField('id')
                ])
                ->innerJoin([$StudentStatuses->getAlias() => $StudentStatuses->getTable()], [
                    $InstitutionStudents->aliasField('student_status_id = ') . $StudentStatuses->aliasField('id')
                ])
                ->leftJoin([$ClassStudents->getAlias() => $ClassStudents->getTable()], [
                    $this->InstitutionStudents->aliasField('student_id = ') . $ClassStudents->aliasField('student_id')
                ])
                ->leftJoin([$Classes->getAlias() => $Classes->getTable()], [
                    $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                ->where([
                    $InstitutionStudents->aliasField('student_id =') . $institutionStudentId,
                ]);
        }
        // for Academic Tab
        // for Assessments Tab
        if ($StudentType == 'Assessment') {
            $query
                ->select([
                    'asses_academic_period' => $AcademicPeriods->aliasField('name'),
                    'asses_institution_name' => $institutions->aliasField('name'),
                    //POCOR-7474-HINDOL TYPO FIX
                    'assessment_period' => $AssessmentPeriods->find()->func()->concat([
                        $AssessmentPeriods->aliasField('code') => 'literal',
                        " - ",
                        $AssessmentPeriods->aliasField('name') => 'literal'
                    ]),
                    'education_subject' => $EducationSubjects->aliasField('name'),
                    'marks' => $AssessmentItemResults->aliasField('marks'),
                ])
                ->leftJoin([$AssessmentItemResults->getAlias() => $AssessmentItemResults->getTable()], [
                    $this->aliasField('id = ') . $AssessmentItemResults->aliasField('student_id')
                ])
                ->leftJoin([$Assessments->getAlias() => $Assessments->getTable()], [
                    $AssessmentItemResults->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                ])
                ->leftJoin([$EducationSubjects->getAlias() => $EducationSubjects->getTable()], [
                    $AssessmentItemResults->aliasField('education_subject_id = ') . $EducationSubjects->aliasField('id')
                ])
                ->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()], [
                    $AssessmentItemResults->aliasField('academic_period_id = ') . $AcademicPeriods->aliasField('id')
                ])
                ->innerJoin([$AssessmentPeriods->getAlias() => $AssessmentPeriods->getTable()], [
                    $AssessmentItemResults->aliasField('assessment_period_id = ') . $AssessmentPeriods->aliasField('id')
                ])
                ->innerJoin([$institutions->getAlias() => $institutions->getTable()], [
                    $AssessmentItemResults->aliasField('institution_id = ') . $institutions->aliasField('id')
                ])
                ->where([
                    $AssessmentItemResults->aliasField('student_id =') . $institutionStudentId,
                ]);
        }
        // for Assessments Tab
        // for Absenses Tab
        if ($StudentType == 'Absence') {
            $query
                ->select([
                    'absense_date' => $institutionStudentAbsenses->aliasField('date'),
                    'absense' => $absensesTypes->aliasField('name'),
                ])
                ->leftJoin([$institutionStudentAbsenses->getAlias() => $institutionStudentAbsenses->getTable()], [
                    $this->aliasField('id = ') . $institutionStudentAbsenses->aliasField('student_id')
                ])
                ->leftJoin([$absensesTypes->getAlias() => $absensesTypes->getTable()], [
                    $institutionStudentAbsenses->aliasField('absence_type_id = ') . $absensesTypes->aliasField('id')
                ])
                ->where([
                    $institutionStudentAbsenses->aliasField('student_id =') . $institutionStudentId,
                ]);
        }
        // for Absenses Tab

        // dump($query);die;

    }

    // POCOR-6130 adding tabs in sheet
    //PCOOR-8388 starts
    // public function getAcademicTabElements($options = [])
    // {
    //     $id = (array_key_exists('id', $options)) ? $options['id'] : 0;
    //     $studentID = $this->getStudentID();
        
    //     $institutionID = $this->getInstitutionID();
    //     $type = (array_key_exists('type', $options)) ? $options['type'] : null;
    //     $tabElements = [];
    //     $studentTabElements = [
    //         'Programmes' => ['text' => __('Programmes')],
    //         'Classes' => ['text' => __('Classes')],
    //         'Subjects' => ['text' => __('Subjects')],
    //         'Absences' => ['text' => __('Absences')],
    //         'Behaviours' => ['text' => __('Behaviours')],
    //         'Outcomes' => ['text' => __('Outcomes')],
    //         'Competencies' => ['text' => __('Competencies')],
    //         //POCOR-7474-HINDOL TYPO FIX
    //         'Assessments' => ['text' => __('Assessments')], //POCOR-5786
    //         'ExaminationResults' => ['text' => __('Examinations')],
    //         'ReportCards' => ['text' => __('Report Cards')],
    //         'Awards' => ['text' => __('Awards')],
    //         //'Extracurriculars' => ['text' => __('Extracurriculars')],//POCOR-7648
    //         'Textbooks' => ['text' => __('Textbooks')],
    //         'Risks' => ['text' => __('Risks')],
    //         'Associations' => ['text' => __('Houses')], //POCOR-7938
    //         'Curriculars' => ['text' => __('Curriculars')] //POCOR-6673
    //     ];

    //     $tabElements = array_merge($tabElements, $studentTabElements);
    //     $params = ['id' => $studentID,
    //         'student_id' => $studentID,
    //         'user_id' => $studentID,
    //         'institution_id' => $institutionID,
    //         'type' => $type];
    //     $queryString = $this->paramsEncode($params);
    //     // Programme & Textbooks will use institution controller, other will be still using student controller
    //     $institutionControllerAction = [
    //         'Programmes',
    //         'Textbooks',
    //         'Associations',
    //         'Curriculars',
    //         'Risks'];
    //     foreach ($studentTabElements as $key => $tab) {
    //         if (in_array($key, $institutionControllerAction)) {
    //             $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
    //         } else {
    //             $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
    //         }
    //         $urlParams = [
    //             'action' => $key,
    //             '0' => 'index',
    //             '1' => $queryString
    //         ];
    //         $tabElements[$key]['url'] = array_merge($studentUrl, $urlParams);
    //     }

    //     if (Configure::read('schoolMode')) {
    //         if (isset($tabElements['ExaminationResults'])) {
    //             unset($tabElements['ExaminationResults']);
    //         }
    //         if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
    //             if (isset($tabElements['Risks'])) {
    //                 unset($tabElements['Risks']);
    //             }
    //         }
    //     }

    //     return $tabElements;
    // }
    //PCOOR-8388 ends
    // POCOR-6130

    public
    function findStudents(Query $query, array $options = [])
    {
        $query->where([$this->aliasField('super_admin') . ' <> ' => 1]);

        $limit = (array_key_exists('limit', $options)) ? $options['limit'] : null;
        $page = (array_key_exists('page', $options)) ? $options['page'] : null;

        // conditions
        $firstName = (array_key_exists('first_name', $options)) ? $options['first_name'] : null;
        $lastName = (array_key_exists('last_name', $options)) ? $options['last_name'] : null;
        $openemisNo = (array_key_exists('openemis_no', $options)) ? $options['openemis_no'] : null;
        $identityNumber = (array_key_exists('identity_number', $options)) ? $options['identity_number'] : null;
        $dateOfBirth = (array_key_exists('date_of_birth', $options)) ? $options['date_of_birth'] : null;

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

    public
    function findEnrolledInstitutionStudents(Query $query, array $options = [])
    {
        $query->contain([
            'InstitutionStudents' => function ($q) {
                return $q->where(['InstitutionStudents.student_status_id' => 1]);
            },
            'InstitutionStudents.Institutions.Areas',
            'InstitutionStudents.AcademicPeriods',
            'InstitutionStudents.EducationGrades'
        ]);
        return $query;
    }

    private
    function addTransferButton(Entity $entity, ArrayObject $extra)
    {
        if ($this->AccessControl->check([$this->controller->getName(), 'StudentTransferOut', 'add'])) {
            $toolbarButtons = $extra['toolbarButtons'];

            $queryString = $this->getQueryString();
            $InstitutionId = $this->getQueryString('institution_id');
            $id = $this->getQueryString('id');
            $encodedQueryString = $this->paramsEncode($queryString);

            $StudentsTable = TableRegistry::get('Institution.Students');

            //$institutionStudentId = $extra['institutionStudentId'];
            $institutionStudentId = $this->getQueryString('institution_student_id');
            if($institutionStudentId == null){
                $institutionStudentId = $this->getQueryString('user_id');
            }
            $institutionStudentId = !empty($institutionStudentId) ? $institutionStudentId : $extra['institutionStudentId'];
            $studentEntity = $StudentsTable->get($institutionStudentId);
            $institutionId = $studentEntity->institution_id;

            $params = ['student_id' => $institutionStudentId, 'user_id' => $entity->id, 'institution_id' => $InstitutionId, 'id'=> $id];
            $action = $this->setQueryString(['controller' => $this->controller->getName(), 'action' => 'StudentTransferOut', 'add'], $params);

            $checkIfCanTransfer = $StudentsTable->checkIfCanTransfer($studentEntity, $institutionId);

            if ($checkIfCanTransfer && !Configure::read('schoolMode')) {
                $transferButton = $toolbarButtons['back'];
                $transferButton['type'] = 'button';
                $transferButton['label'] = '<i class="fa kd-transfer"></i>';
                $transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $transferButton['attr']['title'] = __('Transfer');
                $transferButton['url'] = $action;
                //$transferButton['url'][1] = $encodedQueryString;
                $toolbarButtons['transfer'] = $transferButton;
            }
        }
    }

    // needs to migrate

    private
    function addPromoteButton(Entity $entity, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if ($this->AccessControl->check([$this->controller->getName(), 'Promotion', 'add'])) {
            $toolbarButtons = $extra['toolbarButtons'];
            //$institutionStudentId = $extra['institutionStudentId'];
            $institutionStudentId = $this->getQueryString('institution_student_id');
            $params = ['student_id' => $institutionStudentId, 'user_id' => $entity->id];
            $action = $this->setUrlParams(['controller' => $this->controller->getName(), 'action' => 'IndividualPromotion', 'add'], $params);
            // Show Promote button only if the Student Status is Current and academic period is editable
            // Promote button
            $promoteButton = $toolbarButtons['back'];
            $promoteButton['type'] = 'button';
            $promoteButton['label'] = '<i class="fa kd-graduate"></i>';
            $promoteButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
            $promoteButton['attr']['title'] = __('Promotion / Repeat');
            $promoteButton['url'] = $action;
            $promoteButton['url'][1] = $encodedQueryString;

            $toolbarButtons['promote'] = $promoteButton;
            //End
        }
    }

    // needs to migrate

    private
    function addWithdrawButton(Entity $entity, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
       // print_r($encodedQueryString);die;
        if ($this->AccessControl->check([$this->controller->getName(), 'WithdrawRequests', 'add'])) {
            $session = $this->Session;
            $toolbarButtons = $extra['toolbarButtons'];

            $StudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
            $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');

            //$institutionStudentId = $extra['institutionStudentId'];
            $institutionStudentId = $this->getQueryString('institution_student_id');
            $institutionStudentId = !empty($institutionStudentId) ? $institutionStudentId : $extra['institutionStudentId'];
            $studentEntity = $StudentsTable->get($institutionStudentId);

            // Check if the student is enrolled
            $StudentStatusUpdates = TableRegistry::getTableLocator()->get('Institution.StudentStatusUpdates');
            $WithdrawRequests = TableRegistry::getTableLocator()->get('Institution.WithdrawRequests');
            $session->write($WithdrawRequests->getRegistryAlias() . '.id', $institutionStudentId);
            $WorkflowModels = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
            $approvedStatus = $WorkflowModels->getWorkflowStatusSteps('Institution.StudentWithdraw', 'APPROVED');

            $rejectedStatus = $WorkflowModels->getWorkflowStatusSteps('Institution.StudentWithdraw', 'REJECTED');
            $status = $rejectedStatus + $approvedStatus;

            try {
                // check if there is an existing withdraw request
                $withdrawRequest = $WithdrawRequests->find()
                    ->select(['institution_student_withdraw_id' => 'id'])
                    ->where([
                        $WithdrawRequests->aliasField('student_id') => $studentEntity->student_id,
                        $WithdrawRequests->aliasField('institution_id') => $studentEntity->institution_id,
                        $WithdrawRequests->aliasField('education_grade_id') => $studentEntity->education_grade_id,
                        $WithdrawRequests->aliasField('status_id') . ' NOT IN' => $status
                    ])
                    ->first();
                $studentStatusUpdates = $StudentStatusUpdates->find()
                    ->where([
                        $StudentStatusUpdates->aliasField('security_user_id') => $studentEntity->student_id,
                        $StudentStatusUpdates->aliasField('institution_id') => $studentEntity->institution_id,
                        $StudentStatusUpdates->aliasField('education_grade_id') => $studentEntity->education_grade_id,
                        $StudentStatusUpdates->aliasField('academic_period_id') => $studentEntity->academic_period_id,
                        $StudentStatusUpdates->aliasField('execution_status') => 1
                    ])
                    ->first();

            } catch (DatabaseException $e) {
                $withdrawRequest = false;
                $this->Alert->error('WithdrawRequests.configureWorkflowStatus');
            }

            $withdrawButton = $toolbarButtons['back'];
            $withdrawButton['type'] = 'button';
            $withdrawButton['label'] = '<i class="fa kd-dropout"></i>';
            $withdrawButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
            $withdrawButton['attr']['title'] = __('Withdraw');

            $withdrawButton['url'] = $this->url('add', 'QUERY');
            if (!empty($withdrawRequest)) {
                // $queryString = $this->getQueryString();
                $queryString['id'] = $withdrawRequest->institution_student_withdraw_id;
                $encodedQueryStringForWithdraw = $this->paramsEncode($queryString);
                $withdrawButton['url']['action'] = 'StudentWithdraw';
                $withdrawButton['url'][0] = 'view';
                $withdrawButton['url'][1] = $encodedQueryStringForWithdraw;
//                $withdrawButton['url'][2] = $this->paramsEncode(['id' => $withdrawRequest->institution_student_withdraw_id]);
                $toolbarButtons['withdraw'] = $withdrawButton;

            } elseif (!empty($studentStatusUpdates)) {
                $queryString['id'] = $studentStatusUpdates->id;
                $encodedQueryStringForStatus = $this->paramsEncode($queryString);
                $withdrawButton['url']['action'] = 'StudentStatusUpdates';
                $withdrawButton['url'][0] = 'view';
                $withdrawButton['url'][1] = $encodedQueryStringForStatus;
//                $withdrawButton['url'][2] = $this->paramsEncode(['id' => $studentStatusUpdates->id]);
                $toolbarButtons['withdraw'] = $withdrawButton;
            } else {
                $withdrawButton['url']['action'] = 'WithdrawRequests';
                $withdrawButton['url'][0] = 'add';
                $withdrawButton['url'][1] = $encodedQueryString;
                $toolbarButtons['withdraw'] = $withdrawButton;
            }
        }

    }

    /**
     * POCOR-8003
     * @param ArrayObject $extra
     * @return bool
     */
    private function checkStatusCanBeChanged(ArrayObject $extra)
    {
        $StudentsTable = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $editableAcademicPeriods = $AcademicPeriods->getYearList(['isEditable' => true]);

        $Enrolled = $StudentStatuses->getIdByCode('CURRENT');
        //$institutionStudentId = $extra['institutionStudentId'];
        $institutionStudentId = $this->getQueryString('institution_student_id');
        if($institutionStudentId == null){
            $institutionStudentId = $this->getQueryString('user_id');
        }
        $institutionStudentId = !empty($institutionStudentId) ? $institutionStudentId : $extra['institutionStudentId'];
        $studentEntity = $StudentsTable->get($institutionStudentId);
        $academicPeriodId = $studentEntity->academic_period_id;

        // Show Promote button only if the Student Status is Current and academic period is editable
        if ($studentEntity->student_status_id == $Enrolled && array_key_exists($academicPeriodId, $editableAcademicPeriods)) {
            $status_can_be_changed = true;
        }
        return $status_can_be_changed;
    }
}
