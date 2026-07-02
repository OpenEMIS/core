<?php

namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Exception as DatabaseException;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;

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
        $request = Router::getRequest();
        if ($request !== null && !in_array('Custom Fields', (array)Configure::read('School.excludedPlugins')) && (($request->getParam('action') != 'StudentAdmission') && ($request->getParam('action') != 'StudentEnrolment'))) {
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
               // 'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
               'recordKey' => 'student_id',
                'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
               'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
           ]);
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
        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Student.Students.id']);
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'security_user_create',
                'entity_delete' => 'security_user_delete',
                'entity_update' => 'security_user_update',
                'table_alias' => 'User.Users'
            ]
        ); // for webhook
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

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
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
        $events['Model.Students.afterSaveCustom'] = 'studentsAfterSave';
        $events['ControllerAction.Model.pull.beforePatch'] = 'pullBeforePatch';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $BaseUsers = TableRegistry::getTableLocator()->get('User.Users');
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
            
            //POCOR-9607
            // ->add('date_of_birth', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
            //     'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
            //     'on' => 'create'
            // ])
             ->add('date_of_birth', [
                'ruleCheckAdmissionAge' => [
                    'rule' => 'checkAdmissionAge',
                ]
             ])
             //POCOR-9607

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
            ])
            ->allowEmptyString('email')
            ->add('email', 'validEmailCustom', [
                'rule' => ['checkEmailValidation'],
                'message' => 'Please enter a valid email',
                'on' => function ($context) {
                    return !empty($context['data']['email']);
                }
            ])//POCOR-9680
            ->allowEmptyString('mobile_number')
            ->add('mobile_number', 'numeric', [
                'rule' => 'numeric',
                'message' => 'Only numbers are allowed'
            ]); //POCOR-9680
        return $validator;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
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
            $InstitutionStudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
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
    public function onGetIdentityNumber(EventInterface $event, Entity $entity)
    {
        $users_ids = self::getDynamicTableInstance('User.UserIdentities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])->all();

        $users_ids = self::getDynamicTableInstance('User.UserIdentities');
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
            $nationalities = self::getDynamicTableInstance('FieldOption.Nationalities');
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
                $users_ids = self::getDynamicTableInstance('User.UserIdentities');
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
    public function onGetIdentityTypeID(EventInterface $event, Entity $entity)
    {
        $users_ids = self::getDynamicTableInstance('User.Identities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();
        $users_ids = self::getDynamicTableInstance('User.Identities');
        $user_id_data = $users_ids->find()
            ->select(['number', 'identity_type_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            $users_id_type = self::getDynamicTableInstance('User.IdentityTypes');
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
            $nationalities = self::getDynamicTableInstance('FieldOption.Nationalities');
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
                $users_ids = self::getDynamicTableInstance('User.UserIdentities');
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
                $users_id_type = self::getDynamicTableInstance('FieldOption.IdentityTypes');
                $user_id_name = $users_id_type->find()
                    ->select(['name'])
                    ->where([
                        $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                    ])
                    ->first();
                return $entity->identity_type_id = $user_id_name->name;
            } else {
                // Case 3 - returning value, return again from Case 1
                $users_id_type = self::getDynamicTableInstance('FieldOption.IdentityTypes');
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

    public function afterAction(EventInterface $event, ArrayObject $extra)
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
                $Class = self::getDynamicTableInstance('Institution.InstitutionClasses');
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

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities', 'MainIdentityTypes', 'Genders'
        ]);
    }

    //POCOR-7982

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if (!empty($entity->date_of_death)) { //POCOR-8059
            if (isset($entity->dod_range)) {
                $event->stopPropagation();
                $this->Alert->warning('general.dodmsg', ['reset' => true]);
                $url = $this->url('edit');
                return $this->controller->redirect($url);
            }
        }

        //POCOR-9590: drift detection now lives in UserBehavior::beforeSave (1→2 on dirty general field). Removed older reset-to-0 rule which contradicted the 3-state model.
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
        $this->addSyncButton($entity, $extra);
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

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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

    public function onUpdateFieldIdentityNumber(EventInterface $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['fieldName'] = $this->getAlias() . '.identities.0.number';
            $attr['attr']['label'] = __('Identity Number');
        }
        return $attr;
    }

    //POCOR-9393
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {

        $this->dispatchEvent('Model.Students.afterSaveCustom', [
            'entity' => $entity,
            'options' => $options
        ]);

    }


    //POCOR-9393
    public function studentsAfterSave(EventInterface $event)
    {
        $entity = $event->getData('entity');
        if ($entity->isNew()) {
            $this->updateAll(['is_student' => 1], ['id' => $entity->student_id]);
        }
    }

    //to handle identity_number field that is automatically created by mandatory behaviour.

    public function pullBeforePatch(EventInterface $event, Entity $entity, ArrayObject $queryString, ArrayObject $patchOption, ArrayObject $extra)
    {
        if (!isset($queryString['institution_id'])) {
            $session = $this->request->getSession();
            $queryString['institution_id'] = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->getInstitutionID();
        }
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $studentsTabsData = $this->studentsTabsData;
        $InstitutionStudents = self::getDynamicTableInstance('User.InstitutionStudents');
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

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $StudentType = $sheetData['student_tabs_type'];

        $newFields = [];
        if ($StudentType == 'General') {
            $IdentityType = self::getDynamicTableInstance('FieldOption.IdentityTypes');
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
            $InfrastructureCustomFields = self::getDynamicTableInstance('student_custom_fields');
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

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $InstitutionStudents = self::getDynamicTableInstance('User.InstitutionStudents');
        $ClassStudents = self::getDynamicTableInstance('Institution.InstitutionClassStudents');
        $Classes = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $studentAbsenceDays = self::getDynamicTableInstance('InstitutionStudentAbsenceDays');
        $Subjects = self::getDynamicTableInstance('Institution.InstitutionSubjects');
        $SubjectStudents = self::getDynamicTableInstance('Institution.InstitutionSubjectStudents');
        $institutionStudentId = $settings['id'];
        $institutionId = $this->getInstitutionID();
        $periodId = $this->request->getQuery('academic_period_id');
        $currDateTime = date("Y-m-d");

        // for Academic Tab
        $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
        $institutions = self::getDynamicTableInstance('Institution.Institutions');
        $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades');
        $EducationProgrammes = self::getDynamicTableInstance('Education.EducationProgrammes');
        $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
        // for Academic Tab

        // for abesense
        $institutionStudentAbsenses = self::getDynamicTableInstance('Institution.InstitutionStudentAbsences');
        $absensesTypes = self::getDynamicTableInstance('absence_types');
        // for abesense

        // for assessments
        $Assessments = self::getDynamicTableInstance('Assessment.Assessments');
        $AssessmentPeriods = self::getDynamicTableInstance('Assessment.AssessmentPeriods');
        $AssessmentItemResults = self::getDynamicTableInstance('Assessment.AssessmentItemResults');
        $EducationSubjects = self::getDynamicTableInstance('Education.EducationSubjects');
        // for assessments

        $sheetData = $settings['sheet']['sheetData'];
        $StudentType = $sheetData['student_tabs_type'];

        // for Generals Tab
        if ($StudentType == 'General') {
            $query->formatResults(function (CollectionInterface $results) {
                return $results->map(function ($row) {
                    // POCOR-6130 custome fields code
                    $Guardians = self::getDynamicTableInstance('StudentCustomField.StudentCustomFieldValues');
                    $studentCustomFieldOptions = self::getDynamicTableInstance('StudentCustomField.StudentCustomFieldOptions');
                    $studentCustomFields = self::getDynamicTableInstance('StudentCustomField.StudentCustomFields');

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
    //     $id = (isset($options['id'])) ? $options['id'] : 0;
    //     $studentID = $this->getStudentID();

    //     $institutionID = $this->getInstitutionID();
    //     $type = (isset($options['type'])) ? $options['type'] : null;
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

    private function addTransferButton(Entity $entity, ArrayObject $extra)
    {
        if ($this->AccessControl->check([$this->controller->getName(), 'StudentTransferOut', 'add'])) {
            $toolbarButtons = $extra['toolbarButtons'];

            $StudentsTable = self::getDynamicTableInstance('Institution.Students');
            $StudentTransfers = self::getDynamicTableInstance('Institution.InstitutionStudentTransfers');

            $institutionStudentId = $extra['institutionStudentId'];
            $studentEntity = $StudentsTable->get($institutionStudentId);

            $institutionId = $studentEntity->institution_id;
            $studentId = $studentEntity->student_id;

            $params = ['institution_student_id' => $institutionStudentId,
                'user_id' => $entity->id,
                'student_id' => $entity->id,
                'institution_id' => $institutionId];
            $encodedParams = $this->paramsEncode($params);


            $checkIfCanTransfer = $StudentsTable->checkIfCanTransfer($studentEntity, $institutionId);

            if ($checkIfCanTransfer && !Configure::read('schoolMode')) {
                $transferButton = $toolbarButtons['back'];
                $transferButton['type'] = 'button';
                $transferButton['label'] = '<i class="fa kd-transfer"></i>';
                $transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $transferButton['attr']['title'] = __('Transfer');
                $url = $transferButton['url'];
                $url['controller'] = $this->controller->getName();
                $url['action'] = 'StudentTransferOut';
                $url[0] = 'add';
                $url[1] = $encodedParams;
                $transferButton['url'] = $url;
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

    //POCOR-9590: Sync button on the student General view toolbar — visible only when user has a preferred identity matching the active external data source's identity_type_id
    private function addSyncButton(Entity $entity, ArrayObject $extra)
    {
        //POCOR-9590: delegate to controller when it supports the method (StudentsController); fall back for InstitutionsController and others
        $permission = method_exists($this->controller, 'syncUserPermission')
            ? $this->controller->syncUserPermission()
            : ['Institutions', 'Students', 'add'];
        if (!$this->AccessControl->check($permission)) {
            return;
        }
        //POCOR-9590: institution_students.id is a UUID — the security_user_id lives under student_id
        $securityUserId = $entity->student_id ?? $entity->id;
        if (!$this->isSyncEligibleUser($securityUserId)) {
            return; //POCOR-9590: hide button for Local users (no preferred external identity, or no active source)
        }

        $toolbarButtons = $extra['toolbarButtons'];

        //POCOR-9590: encode full context (user + institution + institution_student) so syncUser can redirect back to the same view
        $encodedParams = $this->paramsEncode([
            'user_id'                => $securityUserId,
            'student_id'             => $securityUserId,
            'institution_id'         => $this->getInstitutionID(),
            'institution_student_id' => $entity->id,
        ]);

        $syncButton = $toolbarButtons['back'];
        $syncButton['type']          = 'button';
        $syncButton['label']         = '<i class="fa fa-refresh"></i>';
        $syncButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
        $syncButton['attr']['title'] = __('Sync');
        $syncButton['url'] = [
            'plugin'     => 'Student',
            'controller' => 'Students',
            'action'     => 'SyncUser',
            0            => $encodedParams,
        ];
        $toolbarButtons['sync'] = $syncButton;
    }

    //POCOR-9590: isSyncEligibleUser + getActiveExternalSourceIdentityTypeId moved to User\Model\Behavior\UserBehavior

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
        $StudentsTable = self::getDynamicTableInstance('Institution.Students');
        $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
        $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
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

    private static function debug($something)
    {
        if (is_null($something)) {
            $message = 'NULL';
        } elseif (is_bool($something)) {
            $message = $something ? 'TRUE' : 'FALSE';
        } elseif (is_array($something) || is_object($something)) {
            $message = json_encode($something, JSON_PRETTY_PRINT);
        } else {
            $message = (string)$something;
        }

        \Cake\Log\Log::debug($message);
    }

    /**
     * @param string $tableName
     * @return Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        $locator = TableRegistry::getTableLocator();;
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        // Parse plugin and table names if dot notation is used
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

//     /**
//     * Triggers webhooks for a user. POCOR-9393
//     *
//     * @param int $userRecordId
//     * @param array $requestData
//     */
//    private function triggerWebhooks($userRecordId, $requestData)
//    {
//        $institutionStudents = self::getDynamicTableInstance('Institution.Students');
//        $InstitutionClasses = self::getDynamicTableInstance('Institution.InstitutionClasses');
//        $userNationalities = self::getDynamicTableInstance('User.UserNationalities');
//        $nationalities = self::getDynamicTableInstance('FieldOption.Nationalities');
//        $identities = self::getDynamicTableInstance('User.Identities');
//        $IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
//        $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
//
//        $bodyData = $institutionStudents->find()
//                    ->select([
//                        'id'         => $institutionStudents->aliasField('id'),
//                        'student_id' => $institutionStudents->aliasField('student_id'),
//                        // user info
//                        'username'    => 'Users.username',
//                        'openemis_no' => 'Users.openemis_no',
//                        'first_name'  => 'Users.first_name',
//                        'middle_name' => 'Users.middle_name',
//                        'third_name'  => 'Users.third_name',
//                        'last_name'   => 'Users.last_name',
//                        'date_of_birth' => 'Users.date_of_birth',
//                        'email'       => 'Users.email',
//                        'address'     => 'Users.address',
//                        'postal_code' => 'Users.postal_code',
//
//                        // institution & status
//                        'institution'     => 'Institutions.name',
//                        'student_status'  => 'StudentStatuses.name',
//                        'academic_period' => 'AcademicPeriods.name',
//                        'education_grade' => 'EducationGrades.name',
//                        'institution_class' => $InstitutionClasses->aliasField('name'),
//                        'institution_class_id' => $InstitutionClasses->aliasField('id'),
//
//                        // demographics
//                        'gender'         => 'Genders.name',
//                        'address_area'   => 'AddressAreas.name',
//                        'birthplace_area'=> 'BirthplaceAreas.name',
//                        'mobile_number'        => 'Users.mobile_number',
//                        'nationality'    => $nationalities->aliasField('name'),
//                        'user_identities_number'    => $identities->aliasField('number'),
//                        'identity_types'    => $IdentityTypes->aliasField('name'),
//                    ])
//                    ->contain([
//                        'Institutions',
//                        'EducationGrades',
//                        'AcademicPeriods',
//                        'StudentStatuses',
//                        'Users' => [
//                            'Genders',
//                            'MainNationalities',
//                            'AddressAreas',
//                            'BirthplaceAreas',
//                        ]
//                    ])
//                    ->leftJoin(
//                    [$InstitutionClassStudents->getAlias() => $InstitutionClassStudents->getTable()],
//                    [
//                        $InstitutionClassStudents->aliasField('student_id') . ' = ' . $institutionStudents->aliasField('student_id'),
//                    ])
//                    ->leftJoin(
//                    [$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()],
//                    [
//                        $InstitutionClasses->aliasField('id') . ' = ' . $InstitutionClassStudents->aliasField('institution_class_id'),
//                    ])
//                    ->leftJoin(
//                        [$userNationalities->getAlias() => $userNationalities->getTable()],
//                        [
//                            $userNationalities->aliasField('security_user_id') . ' = ' . $institutionStudents->aliasField('student_id'),
//                        ])
//                    ->leftJoin(
//                        [$nationalities->getAlias() => $nationalities->getTable()],
//                        [
//                            $nationalities->aliasField('id') . ' = ' . $userNationalities->aliasField('nationality_id'),
//                        ])
//                    ->leftJoin(
//                        [$identities->getAlias() => $identities->getTable()],
//                        [
//                            $identities->aliasField('security_user_id') . ' = ' . $institutionStudents->aliasField('student_id'),
//                        ])
//                    ->leftJoin(
//                        [$IdentityTypes->getAlias() => $IdentityTypes->getTable()],
//                        [
//                            $IdentityTypes->aliasField('id') . ' = ' . $identities->aliasField('identity_type_id'),
//                        ])
//                    ->where([
//                        $institutionStudents->aliasField('student_id') => $requestData['id']
//                    ])
//                    ->enableHydration(false)->first();
//
//            //Log::error($bodyData->sql());
//
//
//        //Fetch student custom fields
//        $studentCustomFieldValues = self::getDynamicTableInstance('student_custom_field_values');
//        $studentCustomFieldOptions = self::getDynamicTableInstance('student_custom_field_options');
//
//        $studentCustomData = $studentCustomFieldValues->find()
//            ->select([
//                'id' => $studentCustomFieldValues->aliasField('id'),
//                'custom_id' => 'studentCustomField.id',
//                'student_id' => $studentCustomFieldValues->aliasField('student_id'),
//                'student_custom_field_id' => $studentCustomFieldValues->aliasField('student_custom_field_id'),
//                'text_value' => $studentCustomFieldValues->aliasField('text_value'),
//                'number_value' => $studentCustomFieldValues->aliasField('number_value'),
//                'decimal_value' => $studentCustomFieldValues->aliasField('decimal_value'),
//                'textarea_value' => $studentCustomFieldValues->aliasField('textarea_value'),
//                'date_value' => $studentCustomFieldValues->aliasField('date_value'),
//                'time_value' => $studentCustomFieldValues->aliasField('time_value'),
//                'option_value_text' => $studentCustomFieldOptions->aliasField('name'),
//                'name' => 'studentCustomField.name',
//                'field_type' => 'studentCustomField.field_type',
//            ])
//            ->leftJoin(
//                ['studentCustomField' => 'student_custom_fields'],
//                ['studentCustomField.id = ' . $studentCustomFieldValues->aliasField('student_custom_field_id')]
//            )
//            ->leftJoin(
//                [$studentCustomFieldOptions->getAlias() => $studentCustomFieldOptions->getTable()],
//                [
//                    $studentCustomFieldOptions->aliasField('student_custom_field_id') . ' = ' . $studentCustomFieldValues->aliasField('student_custom_field_id'),
//                    $studentCustomFieldOptions->aliasField('id') . ' = ' . $studentCustomFieldValues->aliasField('number_value')
//                ]
//            )
//            ->where([
//                $studentCustomFieldValues->aliasField('student_id') => $userRecordId,
//            ])
//            ->enableHydration(false)
//            ->toArray();
//
//        if (!empty($bodyData)) {
//            $body = $this->prepareWebhookBody($bodyData, $studentCustomData);
//
//            $webhooks = self::getDynamicTableInstance('Webhook.Webhooks');
//            if (!empty($requestData['id'])) {
//                $webhooks->triggerShell('student_update', ['username' => ''], $body);
//            }
//        }
//    }
//
//    /**
//     * Prepares the webhook body.
//     * POCOR-9393
//     * @param array $student
//     * @return array
//     */
//    private function prepareWebhookBody($student, array $studentCustomData = [])
//    {
//
//        $body = [
//            'student_id'    => $student['student_id'] ?? null,
//            'username'      => $student['username'] ?? null,
//            'openemis_no'   => $student['openemis_no'] ?? null,
//            'first_name'    => $student['first_name'] ?? null,
//            'middle_name'   => $student['middle_name'] ?? null,
//            'third_name'    => $student['third_name'] ?? null,
//            'last_name'     => $student['last_name'] ?? null,
//           'date_of_birth' => !empty($student['date_of_birth'])
//                               ? $student['date_of_birth']->format('Y-m-d') : null,
//            'email'         => $student['email'] ?? null,
//            'address'       => $student['address'] ?? null,
//            'postal_code'   => $student['postal_code'] ?? null,
//            'mobile_number'   => $student['mobile_number'] ?? null,
//            'institution'     => $student['institution'] ?? null,
//            'student_status'          => $student['student_status'] ?? null,
//            'academic_period' => $student['academic_period'] ?? null,
//            'education_grade' => $student['education_grade'] ?? null,
//            'institution_class' => $student['institution_class'] ?? null,
//            'institution_class_id' => $student['institution_class_id'] ?? null,
//            'gender'          => $student['gender'] ?? null,
//            'address_area'    => $student['address_area'] ?? null,
//            'birthplace_area' => $student['birthplace_area'] ?? null,
//            'nationality'     => $student['nationality'] ?? null,
//            'user_identities_number'  => $student['user_identities_number'] ?? null,
//            'identity_types'     => $student['identity_types'] ?? null,
//        ];
//
//        // Attach custom fields if available
//        $customFields = [];
//        if (!empty($studentCustomData)) {
//            $count = 0;
//            foreach ($studentCustomData as $val) {
//                $fieldType = $val['field_type'] ?? '';
//                $entry = [
//                    'id'   => $val['custom_id'] ?? '',
//                    'name' => $val['name'] ?? '',
//                ];
//
//                if ($fieldType === 'TEXT') {
//                    $entry['text_value'] = $val['text_value'] ?? '';
//                } elseif ($fieldType === 'CHECKBOX') {
//                    $entry['checkbox_value'] = $val['option_value_text'] ?? '';
//                } elseif ($fieldType === 'NUMBER') {
//                    $entry['number_value'] = $val['number_value'] ?? '';
//                } elseif ($fieldType === 'DECIMAL') {
//                    $entry['decimal_value'] = $val['decimal_value'] ?? '';
//                } elseif ($fieldType === 'TEXTAREA') {
//                    $entry['textarea_value'] = $val['textarea_value'] ?? '';
//                } elseif ($fieldType === 'DROPDOWN') {
//                    $entry['dropdown_value'] = $val['option_value_text'] ?? '';
//                } elseif ($fieldType === 'DATE') {
//                    $entry['date_value'] = !empty($val['date_value'])
//                        ? date('Y-m-d', strtotime($val['date_value']))
//                        : '';
//                } elseif ($fieldType === 'TIME') {
//                    $entry['time_value'] = !empty($val['time_value'])
//                        ? date('h:i A', strtotime($val['time_value']))
//                        : '';
//                } elseif ($fieldType === 'COORDINATES') {
//                    $entry['coordinate_value'] = $val['text_value'] ?? '';
//                }
//
//                $customFields['custom_field'][$count] = $entry;
//                $count++;
//            }
//        }
//
//        return array_merge($body, $customFields);
//    }

}
