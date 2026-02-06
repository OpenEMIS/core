<?php
namespace GuardianNav\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Http\Session;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use Cake\Database\Exception as DatabaseException;

class StudentUserTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        $this->setEntityClass('User.User');
        parent::initialize($config);

        // Associations
        self::handleAssociations($this);

        // Behaviors
        $this->addBehavior('User.User');
        if (!in_array('Custom Fields', (array) Configure::read('School.excludedPlugins'))) {
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
                //'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],//cakephp4 comment
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

        //$this->addBehavior('Configuration.Pull'); //cakephp4 comment

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Student.Students.id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add', 'edit']
        ]);
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'security_user_create',
                'entity_delete' => 'security_user_delete',
                'entity_update' => 'security_user_update',
                'table_alias' => 'User.Users'
            ]
        ); // for webhook
        $this->toggle('index', false);
        $this->toggle('remove', false);
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

    public static function handleAssociations($model)
    {
        $model->belongsTo('Genders', ['className' => 'User.Genders']);
        $model->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $model->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $model->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $model->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $model->hasMany('Identities', ['className' => 'User.Identities',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Nationalities', ['className' => 'User.UserNationalities',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Contacts', ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Attachments', ['className' => 'User.Attachments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('BankAccounts', ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Comments', ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Languages', ['className' => 'User.UserLanguages',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Awards', ['className' => 'User.Awards',            'foreignKey' => 'security_user_id', 'dependent' => true]);

        $model->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments',    'foreignKey' => 'security_user_id', 'dependent' => true]);

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

        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students',    'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('InstitutionStaff', ['className' => 'Institution.Staff',    'foreignKey' => 'staff_id', 'dependent' => true]);
        $model->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentBehaviours', ['className' => 'Institution.StudentBehaviours',    'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults',    'foreignKey' => 'student_id', 'dependent' => true]);
        $model->belongsToMany('Guardians', [
            'className' => 'Student.Guardians',
            'foreignKey' => 'student_id',
            'targetForeignKey' => 'guardian_id',
            'through' => 'Student.StudentGuardians',
            'dependent' => true
        ]);
        $model->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission',    'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('StudentCustomFieldValues', ['className' => 'CustomField.StudentCustomFieldValues',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentCustomTableCells', ['className' => 'CustomField.StudentCustomTableCells',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract',    'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('Extracurriculars', ['className' => 'Student.Extracurriculars',    'foreignKey' => 'security_user_id', 'dependent' => true]);
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
        $BaseUsers = self::getDynamicTableInstance('User.Users');
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
            ])
            ;
        return $validator;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('username', ['visible' => false]);
        $toolbarButtons = $extra['toolbarButtons'];

        // Back button does not contain the pass
        if ($this->action == 'edit' && !empty($this->paramsPass(0))) {
            $toolbarButtons['back']['url'][1] = $this->paramsPass(0)    ;
        }

        // this value comes from the list page from StudentsTable->onUpdateActionButtons
        $institutionStudentId = $this->getQueryString('institution_student_id');

        $institutionId = !empty($this->getQueryString('institution_id')) ? $this->getQueryString('institution_id') : $this->request->getSession()->read('Institution.Institutions.id');
        $extra['institutionId'] = $institutionId;
        // this is required if the student link is clicked from the Institution Classes or Subjects
        if (empty($institutionStudentId)) {
            $params = [];
            if ($this->paramsPass(0)) {
                $params = $this->paramsDecode($this->paramsPass(0));
            }

            $studentId = isset($params['id']) ? $params['id'] : $this->Session->read('Institution.StudentUser.primaryKey.id');

            // get the id of the latest student record in the current institution
            $InstitutionStudentsTable = self::getDynamicTableInstance('Institution.Students');
            if($institutionId != null){
                $institutionStudentId = $InstitutionStudentsTable->find()
                    ->where([
                        $InstitutionStudentsTable->aliasField('student_id') => $studentId,
                        $InstitutionStudentsTable->aliasField('institution_id') => $institutionId,
                    ])
                    ->order([$InstitutionStudentsTable->aliasField('created') => 'DESC'])
                    ->extract('id')
                    ->first();
            }else{
                $institutionStudentId = $InstitutionStudentsTable->find()
                    ->where([
                        $InstitutionStudentsTable->aliasField('student_id') => $studentId,
                    ])
                    ->order([$InstitutionStudentsTable->aliasField('created') => 'DESC'])
                    ->extract('id')
                    ->first();
            }
        }
        $this->Session->write('Institution.Students.id', $institutionStudentId);
        if (empty($institutionStudentId)) { // if value is empty, redirect back to the list page
            $event->stopPropagation();
            return $this->controller->redirect(['action' => 'Students', 'index']);
        } else {
            // Get the existing query parameters
            $queryParams = $this->request->getQuery();
            $queryParams['id'] = $institutionStudentId;
            $this->request = $this->request->withQueryParams($queryParams);
            $extra['institutionStudentId'] = $institutionStudentId;

        }
    }

    // POCOR-5684
    public function onGetIdentityNumber(EventInterface $event, Entity $entity){
        $users_ids = self::getDynamicTableInstance('User.Identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])->all();

        $users_ids = self::getDynamicTableInstance('User.Identities');
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
                $users_ids = self::getDynamicTableInstance('User.Identities');
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
    public function onGetIdentityTypeID(EventInterface $event, Entity $entity)
    {
        $users_ids = self::getDynamicTableInstance('User.Identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
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

        if(count($user_identities) == 1){
            // Case 1
            $users_id_type = self::getDynamicTableInstance('FieldOption.IdentityTypes');
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
                $users_ids = self::getDynamicTableInstance('user_identities');
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
            // echo '<pre>'; print_r($nationality_based_ids); die;
            if(count($nationality_based_ids) > 0){
                // Case 2 - returning value
                $users_id_type = self::getDynamicTableInstance('identity_types');
                $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                ])
                ->first();
                return $entity->identity_type_id = $user_id_name->name;
            }else{
                // Case 3 - returning value, return again from Case 1
                $users_id_type = self::getDynamicTableInstance('identity_types');
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
        if (!is_null($entity)) {
            $StudentTable = self::getDynamicTableInstance('Institution.Students');
            $userId = $this->Auth->user('id');
            // $studentId = $this->getStudentID();
            // $institutionID = $this->getInstitutionID();

            $studentEntity = $StudentTable->get($extra['institutionStudentId']);
            $studentId = $studentEntity->student_id;
            $institutionID = $studentEntity->institution_id;
            $isStudentEnrolled = $StudentTable->checkEnrolledInInstitution($studentId, $institutionID);
            $isAllowedByClass = $this->checkClassPermission($studentId, $userId); // POCOR-3010
            if (isset($extra['toolbarButtons']['edit']['url'])) {
                $extra['toolbarButtons']['edit']['url'][1] = $this->paramsEncode(['id' => $studentId]);
            }
            if (!$isStudentEnrolled || !$isAllowedByClass) {
                $this->toggle('edit', false);
            }
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities', 'MainIdentityTypes', 'Genders'
        ]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();
            $this->Session->write('AccessControl.Institutions.ids', $institutionIds);
        }
        $this->Session->write('Student.Students.id', $entity->id);
        $this->Session->write('Student.Students.name', $entity->name);
        $this->setupTabElements($entity);
        $this->setupToolbarButtons($entity, $extra);
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

        $this->fields['nationality_id']['type'] = 'readonly';
        $this->fields['nationality_id']['attr']['value'] = $entity->has('main_nationality') ? $entity->main_nationality->name : '';

        $this->fields['identity_type_id']['type'] = 'readonly';
        $this->fields['identity_type_id']['attr']['value'] = $entity->has('main_identity_type') ? $entity->main_identity_type->name : '';

        $this->field('institution_id', ['type' => 'hidden']);
        $this->fields['institution_id']['value'] = $extra['institutionId'];
    }

    private function setupToolbarButtons(Entity $entity, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['back']['url']['action'] = 'Students';
        //overwrite back button POCOR-6267
        $btnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];

        $extraButtons = [
            'back' => [
                'GuardianNavs' => ['GuardianNavs', 'GuardianNavs', 'index'],
                'action' => 'GuardianNavs',
                'icon' => '<i class="fa kd-back"></i>',
                'title' => __('Back')
            ]
        ];

        foreach ($extraButtons as $key => $attr) {
            $button = [
                'type' => 'button',
                'attr' => $btnAttr,
                'url' => [0 => 'index']
            ];
            $button['url']['action'] = $attr['action'];
            $button['attr']['title'] = $attr['title'];
            $button['label'] = $attr['icon'];
            $extra['toolbarButtons'][$key] = $button;
        }
        // back button
        // Export execute permission.
        if (!$this->AccessControl->check(['Institutions', 'StudentUser', 'excel'])) {
            if (isset($toolbarButtons['export'])) {
                unset($toolbarButtons['export']);
            }
        }

        $this->addPromoteButton($entity, $extra);
        $this->addTransferButton($entity, $extra);
        $this->addWithdrawButton($entity, $extra);
    }

    private function setupTabElements($entity)
    {
        $id = !is_null($this->getQueryString('institution_student_id')) ? $this->getQueryString('institution_student_id') : 0;

        $options = [
            'userRole' => 'Student',
            'action' => $this->action,
            'id' => $id,
            'userId' => $entity->id
        ];

        $tabElements = $this->controller->getUserTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
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

            $params = ['student_id' => $institutionStudentId, 'user_id' => $entity->id];
            $encodedParams = $this->paramsEncode($params);
            $url = $this->url(['controller' => $this->controller->getName(),
                'action' => 'StudentTransferOut',
                '0' => 'add',
                '1' => $encodedParams,
                ]);

            $checkIfCanTransfer = $StudentsTable->checkIfCanTransfer($studentEntity, $institutionId);

            if ($checkIfCanTransfer && !Configure::read('schoolMode')) {
                $transferButton = $toolbarButtons['back'];
                $transferButton['type'] = 'button';
                $transferButton['label'] = '<i class="fa kd-transfer"></i>';
                $transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $transferButton['attr']['title'] = __('Transfer');
                $transferButton['url'] = $url;
                $toolbarButtons['transfer'] = $transferButton;
            }
        }
    }

    private function addPromoteButton(Entity $entity, ArrayObject $extra)
    {
        if ($this->AccessControl->check([$this->controller->getName(), 'Promotion', 'add'])) {
            $toolbarButtons = $extra['toolbarButtons'];

            $StudentsTable = self::getDynamicTableInstance('Institution.Students');
            $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
            $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
            $editableAcademicPeriods = $AcademicPeriods->getYearList(['isEditable' => true]);

            $Enrolled = $StudentStatuses->getIdByCode('CURRENT');
            $institutionStudentId = $extra['institutionStudentId'];
            $studentEntity = $StudentsTable->get($institutionStudentId);
            $academicPeriodId = $studentEntity->academic_period_id;

            $params = ['student_id' => $institutionStudentId, 'user_id' => $entity->id];
            $action = $this->setUrlParams(['controller' => $this->controller->getName(), 'action' => 'IndividualPromotion', 'add'], $params);

            // Show Promote button only if the Student Status is Current and academic period is editable
            if ($studentEntity->student_status_id == $Enrolled && array_key_exists($academicPeriodId, $editableAcademicPeriods)) {
                // Promote button
                $promoteButton = $toolbarButtons['back'];
                $promoteButton['type'] = 'button';
                $promoteButton['label'] = '<i class="fa kd-graduate"></i>';
                $promoteButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
                $promoteButton['attr']['title'] = __('Promotion / Repeat');
                $promoteButton['url'] = $action;

                $toolbarButtons['promote'] = $promoteButton;
                //End
            }
        }
    }

    private function addWithdrawButton(Entity $entity, ArrayObject $extra)
    {
        if ($this->AccessControl->check([$this->controller->getName(), 'WithdrawRequests', 'add'])) {
            $session = $this->Session;
            $toolbarButtons = $extra['toolbarButtons'];

            $InstitutionStudentsTable = self::getDynamicTableInstance('Institution.Students');
            $StudentsTable = self::getDynamicTableInstance('Institution.Students');
            $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');

            $institutionStudentId = $extra['institutionStudentId'];
            $studentEntity = $StudentsTable->get($institutionStudentId);
            $enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

            // Check if the student is enrolled
            if ($studentEntity->student_status_id == $enrolledStatus) {
                $StudentStatusUpdates = TableRegistry::getTableLocator()->get('Institution.StudentStatusUpdates');
                $WithdrawRequests = TableRegistry::getTableLocator()->get('Institution.WithdrawRequests');
                $session->write($WithdrawRequests->getRegistryAlias().'.id', $institutionStudentId);
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
                            $WithdrawRequests->aliasField('status_id').' NOT IN' => $status
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
                    $withdrawButton['url']['action'] = 'StudentWithdraw';
                    $withdrawButton['url'][0] = 'view';
                    $withdrawButton['url'][1] = $this->paramsEncode(['id' => $withdrawRequest->institution_student_withdraw_id]);
                    $toolbarButtons['withdraw'] = $withdrawButton;
                } elseif (!empty($studentStatusUpdates)) {
                    $withdrawButton['url']['action'] = 'StudentStatusUpdates';
                    $withdrawButton['url'][0] = 'view';
                    $withdrawButton['url'][1] = $this->paramsEncode(['id' => $studentStatusUpdates->id]);
                    $toolbarButtons['withdraw'] = $withdrawButton;
                } else {
                    $withdrawButton['url']['action'] = 'WithdrawRequests';
                    $toolbarButtons['withdraw'] = $withdrawButton;
                }
            }
        }
    }

    //to handle identity_number field that is automatically created by mandatory behaviour.
    public function onUpdateFieldIdentityNumber(EventInterface $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['fieldName'] = $this->getAlias().'.identities.0.number';
            $attr['attr']['label'] = __('Identity Number');
        }
        return $attr;
    }

    public function studentsAfterSave(EventInterface $event, $student)
    {
        if ($student->isNew()) {
            $this->updateAll(['is_student' => 1], ['id' => $student->student_id]);
        }
    }

    public function pullBeforePatch(EventInterface $event, Entity $entity, ArrayObject $queryString, ArrayObject $patchOption, ArrayObject $extra)
    {
        if (!isset($queryString['institution_id'])) {
            $session = $this->request->session();
            $queryString['institution_id'] = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
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

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = self::getDynamicTableInstance('FieldOption.IdentityTypes');
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

    public function getAcademicTabElements($options = [])
    {
        $id = (isset($options['id']))? $options['id']: 0;

        $tabElements = [];
        $studentTabElements = [
            'Programmes' => ['text' => __('Programmes')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Absences' => ['text' => __('Absences')],
            'Behaviours' => ['text' => __('Behaviours')],
            'Outcomes' => ['text' => __('Outcomes')],
            'Competencies' => ['text' => __('Competencies')],
            'Results' => ['text' => __('Assessments')],
            'ExaminationResults' => ['text' => __('Examinations')],
            'ReportCards' => ['text' => __('Report Cards')],
            'Awards' => ['text' => __('Awards')],
            //'Extracurriculars' => ['text' => __('Extracurriculars')],//POCOR-7648
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Houses')], //POCOR-7938
            'Curriculars' => ['text' => __('Curriculars')] //POCOR-6673
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        // Programme & Textbooks will use institution controller, other will be still using student controller
        foreach ($studentTabElements as $key => $tab) {
            if ($key == 'Programmes' || $key == 'Textbooks' || $key == 'Associations') {
                $type = (isset($options['type']))? $options['type']: null;
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            } elseif ($key == 'Risks') {
                $type = (isset($options['type']))? $options['type']: null;
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            }elseif ($key == 'Curriculars') {
                $type = (isset($options['type']))? $options['type']: null;
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            } else {
                $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
            }
        }

        if (Configure::read('schoolMode')) {
            if (isset($tabElements['ExaminationResults'])) {
                unset($tabElements['ExaminationResults']);
            }

            if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
                if (isset($tabElements['Risks'])) {
                    unset($tabElements['Risks']);
                }
            }
        }

        return $tabElements;
    }

    // needs to migrate
    public function findStudents(Query $query, array $options = [])
    {
        $query->where([$this->aliasField('super_admin').' <> ' => 1]);

        $limit = (isset($options['limit']))? $options['limit']: null;
        $page = (isset($options['page']))? $options['page']: null;

        // conditions
        $firstName = (isset($options['first_name']))? $options['first_name']: null;
        $lastName = (isset($options['last_name']))? $options['last_name']: null;
        $openemisNo = (isset($options['openemis_no']))? $options['openemis_no']: null;
        $identityNumber = (isset($options['identity_number']))? $options['identity_number']: null;
        $dateOfBirth = (isset($options['date_of_birth']))? $options['date_of_birth']: null;

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
            ;
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

    // needs to migrate
    public function findEnrolledInstitutionStudents(Query $query, array $options = [])
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'first_name':
                return __('First Name');
            case 'middle_name':
                return __('Middle Name');
            case 'third_name':
                return __('Third Name');
            case 'last_name':
                return __('Last Name');
            case 'preferred_name':
                return __('Preferred Name');
            case 'gender_id':
                return __('Gender');
            case 'date_of_birth':
                return __('Date Of Birth');
            case 'details':
                return __('Details');
            case 'address_area_id':
                return __('Address');
            case 'address':
                return __('Address');
            case 'birthplace_area_id':
                return __('Address');
            case 'photo_content':
                return __('Photo Content');
            case 'email':
                return __('Email');
            case 'email':
                return __('Email');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
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
            self::debug([$tableFullAlias, $tableAlias]);
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
