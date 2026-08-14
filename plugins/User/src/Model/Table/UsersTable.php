<?php

namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\UserTrait;
use Cake\I18n\Time;
use Cake\Http\Session;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use Cake\Log\Log;


class UsersTable extends AppTable
{
    use OptionsTrait;
    use UserTrait;

    // private $defaultStudentProfile = "Student.default_student_profile.jpg";
    // private $defaultStaffProfile = "Staff.default_staff_profile.jpg";

    private $defaultStudentProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-students'></i></div></div>";
    private $defaultStaffProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
    private $defaultGuardianProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";
    private $defaultUserProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";

    private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";
    private $defaultStaffProfileView = "<div class='profile-image'><i class='kd-staff'></i></div>";
    private $defaultGuardianProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";
    private $defaultUserProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";


    private $defaultImgIndexClass = "profile-image-thumbnail";
    private $defaultImgViewClass = "profile-image";
    private $photoMessage = 'Advisable photo dimension %width by %height';
    private $formatSupport = 'Format Supported: %s';
    private $defaultImgMsg = "<p>* %s <br>* %s</p>";

    public $fieldOrder1;
    public $fieldOrder2;

    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);

        self::handleAssociations($this);

        $this->fieldOrder1 = new ArrayObject(['photo_content', 'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'address', 'postal_code']);
        $this->fieldOrder2 = new ArrayObject(['status', 'modified_user_id', 'modified', 'created_user_id', 'created']);

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'photo_name',
            'content' => 'photo_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'image',
            'useDefaultName' => true
        ]);

        //$this->addBehavior('Area.Areapicker');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StaffRoom' => ['index', 'edit'],
            'ClassStudents' => ['index'],
            'OpenEMIS_Classroom' => ['view', 'edit']
        ]);
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'security_user_create',
                'entity_delete' => 'security_user_delete',
                'entity_update' => 'security_user_update',
                'table_alias' => 'User.Users',
                'contain' => ''
            ]
        ); // for webhook
        $this->getDisplayField('first_name');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Auth.createAuthorisedUser' => 'createAuthorisedUser',
            'Model.Users.afterLogin' => 'afterLogin',
            'Model.Users.updateLoginLanguage' => 'updateLoginLanguage',
            'Model.UserNationalities.onChange' => 'onChangeUserNationalities',
            'Model.UserIdentities.onChange' => 'onChangeUserIdentities',
            'Model.Nationalities.onChange' => 'onChangeNationalities',
            'Model.UserContacts.onChange' => 'onChangeUserContacts'
        ];

        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function updateLoginLanguage(EventInterface $event, $user, $language)
    {
        if ($user['preferred_language'] != $language) {
            $user = $this->get($user['id']);
            $user->preferred_language = $language;
            $this->save($user);
        }
    }

    public function afterLogin(EventInterface $event, $user)
    {
        $lastLogin = new Time();
        $controller = $event->getSubject();
        $SSO = $controller->SSO;
        $Cookie = $controller->Localization->getCookie();
        $session = $controller->getRequest()->getSession();
        if ($session->read('System.language_menu') && $SSO->getAuthenticationType() != 'Local') {
            $preferredLanguage = !empty($user['preferred_language']) ? $user['preferred_language'] : 'en';
            $Cookie->write('System.language', $preferredLanguage);
        } else {
            $preferredLanguage = $session->read('System.language');
        }
        $this->updateAll([
            'last_login' => $lastLogin,
            'preferred_language' => $preferredLanguage
        ], ['id' => $user['id']]);
        $session->write('System.baseCoreUrl', Router::url('/', true));
    }

    public function createAuthorisedUser(EventInterface $event, $userName, array $userInfo)
    {
        $openemisNo = $this->getUniqueOpenemisId();

        $GenderTable = TableRegistry::getTableLocator()->get('User.Genders');
        $genderList = $GenderTable->find('list')->toArray();

        // Just in case the gender is others
        if (!isset($userInfo['gender'])) {
            $userInfo['gender'] = null;
        }
        $gender = array_search($userInfo['gender'], $genderList);
        if ($gender === false) {
            $gender = key($genderList);
        }

        if (isset($userInfo['dateOfBirth'])) {
            try {
                $dateOfBirth = Time::createFromFormat('Y-m-d', $userInfo['dateOfBirth']);
            } catch (\Exception $e) {
                $dateOfBirth = Time::createFromFormat('Y-m-d', '1970-01-01');
            }
        } else {
            $dateOfBirth = Time::createFromFormat('Y-m-d', '1970-01-01');
        }


        $date = Time::now();
        $data = [
            'username' => $userName,
            'openemis_no' => $openemisNo,
            'first_name' => $userInfo['firstName'],
            'last_name' => $userInfo['lastName'],
            'email' => $userInfo['email'],
            'gender_id' => $gender,
            'date_of_birth' => $dateOfBirth,
            'super_admin' => 0,
            'status' => 1,
            'created_user_id' => 1,
            'created' => $date,
        ];
        $userEntity = $this->newEntity($data, ['validate' => false]);
        if ($this->save($userEntity)) {
            return $userName;
        } else {
            return false;
        }
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
        $model->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Attachments', ['className' => 'User.Attachments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('BankAccounts', ['className' => 'User.BankAccounts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Comments', ['className' => 'User.Comments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Languages', ['className' => 'User.UserLanguages', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Awards', ['className' => 'User.Awards', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'foreignKey' => 'security_user_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);
        $model->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function beforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('username', ['visible' => false]);
        $this->ControllerAction->field('super_admin', ['visible' => false]);
        $this->ControllerAction->field('photo_name', ['visible' => false]);
        $this->ControllerAction->field('date_of_death', ['visible' => false]);
        $this->ControllerAction->field('status', ['options' => $this->getSelectOptions('general.active'), 'visible' => false]);
        $this->ControllerAction->field('photo_content', ['type' => 'image']);
        $this->ControllerAction->field('last_login', ['visible' => false]);
        $this->ControllerAction->field('address_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
        $this->ControllerAction->field('birthplace_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);

        $this->ControllerAction->field(
            'date_of_birth',
            [
                'date_options' => [
                    'endDate' => date('d-m-Y', strtotime("-2 year"))
                ],
                'default_date' => false,
            ]
        );

        if ($this->action == 'add') {
            $this->ControllerAction->field('username', ['visible' => false]);
            $this->ControllerAction->field('password', ['visible' => false, 'type' => 'password']);
        }
    }

    public function afterAction(EventInterface $event)
    {
        // POCOR-8683 start
        $action = $this->action;
        if (isset($action) && in_array($action, ['view', 'edit'])) {
            $this->setTabElements();
        }

        if (isset($action) && strtolower($action) != 'index') {
            $this->Navigation->addCrumb($this->getHeader($action));
        }
        // POCOR-8683 end
    }

    //POCOR-6454[START]
    // public function getCorrectEducationGrade($institutionClassId){
    //     $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
    //     $gradeId = $InstitutionClassGrades
    //     ->find()
    //     ->where([$InstitutionClassGrades->aliasField('institution_class_id') =>$institutionClassId])
    //     ->extract('education_grade_id')
    //     ->first();
    //     return $gradeId;
    // }
    //POCOR-6454[END]

    public function findInstitutionStudentsNotInClass(Query $query, array $options)
    {
        $educationGradeIds = null;
        $academicPeriodId = null;
        $institutionClassId = null;
        $institutionId = null;
        $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;

        if (isset($options['institution_class_id'])) {
            $institutionClassId = $options['institution_class_id'];
            $institutionClassRecord = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses')->get($institutionClassId, ['contain' => ['EducationGrades']])->toArray();
            $academicPeriodId = $institutionClassRecord['academic_period_id'];
            $institutionId = $institutionClassRecord['institution_id'];
            $educationGradeIds = array_column($institutionClassRecord['education_grades'], 'id');
            if (empty($educationGradeIds)) {
                return $query->where(['1 = 0']);
            }
        }

        return $query
            ->innerJoinWith('InstitutionStudents')
            ->leftJoinWith('InstitutionClassStudents', function ($q) use ($academicPeriodId, $institutionId, $enrolledStatus) {
                return $q->where([
                    'InstitutionClassStudents.academic_period_id' => $academicPeriodId,
                    'InstitutionClassStudents.institution_id' => $institutionId,
                    'InstitutionClassStudents.student_status_id' => $enrolledStatus //POCOR-5365
                ]);
            })
            ->innerJoinWith('InstitutionStudents.StudentStatuses')
            ->innerJoinWith('InstitutionStudents.AcademicPeriods')
            ->innerJoinWith('InstitutionStudents.EducationGrades')
            ->innerJoinWith('Genders')
            ->where([
                'InstitutionStudents.institution_id' => $institutionId,
                'InstitutionStudents.education_grade_id IN ' => $educationGradeIds,
                'InstitutionStudents.student_status_id' => $enrolledStatus,
                'InstitutionStudents.academic_period_id' => $academicPeriodId,
                'InstitutionClassStudents.id IS NULL'
            ])
            ->select([
                'academic_period_id' => 'InstitutionStudents.academic_period_id',
                'student_status_id' => 'InstitutionStudents.student_status_id',
                'student_status_name' => 'StudentStatuses.name',
                'gender_id' => 'Genders.id',
                'gender_name' => 'Genders.name',
                'education_grade_id' => 'InstitutionStudents.education_grade_id',
                'education_grade_name' => 'EducationGrades.name',
                $this->aliasField('id'),
                $this->aliasField('openemis_no'),
                $this->aliasField('first_name'),
                $this->aliasField('middle_name'),
                $this->aliasField('third_name'),
                $this->aliasField('last_name'),
                $this->aliasField('preferred_name')
            ])
            ->contain('SpecialNeeds')
            ->group([$this->aliasField('id')])
            ->order([$this->aliasField('first_name', 'last_name')]) // POCOR-2547 sort list of staff and student by name
            ->formatResults(function ($results) use ($institutionClassId, $institutionId) {
                $arrReturn = [];
                foreach ($results as $result) {
                    $arrReturn[] = [
                        'name' => $result->name,
                        'openemis_no' => $result->openemis_no,
                        'id' => $result->id,
                        'education_grade_id' => $result->education_grade_id,
                        'education_grade_name' => __($result->education_grade_name),
                        'student_status_id' => $result->student_status_id,
                        'student_status_name' => __($result->student_status_name),
                        'academic_period_id' => $result->academic_period_id,
                        'gender_id' => $result->gender_id,
                        'gender_name' => __($result->gender_name),
                        'institution_id' => $institutionId,
                        'institution_class_id' => $institutionClassId,
                        'has_special_needs' => $result->has_special_needs
                    ];
                }
                return $arrReturn;
            });
    }

    public function findInstitutionStudentsNotInAssociation(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $institutionId = $options['institution_id'];
        //$institutionId = $_SESSION['Institution']['StudentUser']['primaryKey']['institution_id'];
        $associationId = ($options['institution_association_id']) ? $options['institution_association_id'] : 0;
        $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;
        // POCOR-7994 start
        $association_students = TableRegistry::getTableLocator()->get('Student.InstitutionAssociationStudent');
        $the_students = $association_students
            ->find('all')
            ->select('security_user_id')
            ->distinct('security_user_id')
            ->where(['institution_association_id' => $associationId])->toArray();
        $student_ids = array_column($the_students, 'security_user_id');
        if (empty($student_ids)) {
            $student_ids = [0];
        }
        // POCOR-7994 end
        return $query
            ->innerJoinWith('InstitutionStudents')
            ->innerJoinWith('InstitutionStudents.StudentStatuses')
            ->innerJoinWith('InstitutionStudents.AcademicPeriods')
            ->innerJoinWith('InstitutionStudents.EducationGrades')
            ->innerJoinWith('Genders')
            ->where([
                'InstitutionStudents.institution_id' => $institutionId,
                'InstitutionStudents.student_status_id' => $enrolledStatus,
                'InstitutionStudents.academic_period_id' => $academicPeriodId,
                $this->aliasField('id NOT IN') => $student_ids, // POCOR-7994 start
            ])
            ->select([
                'academic_period_id' => 'InstitutionStudents.academic_period_id',
                'student_status_id' => 'InstitutionStudents.student_status_id',
                'student_status_name' => 'StudentStatuses.name',
                'gender_id' => 'Genders.id',
                'gender_name' => 'Genders.name',
                'education_grade_id' => 'InstitutionStudents.education_grade_id',
                'education_grade_name' => 'EducationGrades.name',
                $this->aliasField('id'),
                $this->aliasField('openemis_no'),
                $this->aliasField('first_name'),
                $this->aliasField('middle_name'),
                $this->aliasField('third_name'),
                $this->aliasField('last_name'),
                $this->aliasField('preferred_name')
            ])
            ->group([$this->aliasField('id')])
            ->order([$this->aliasField('first_name', 'last_name')]) // POCOR-2547 sort list of staff and student by name
            ->formatResults(function ($results) use ($institutionId) {
                $arrReturn = [];
                foreach ($results as $result) {
                    $arrReturn[] = [
                        'name' => $result->name,
                        'openemis_no' => $result->openemis_no,
                        'security_user_id' => $result->id,
                        'education_grade_id' => $result->education_grade_id,
                        'education_grade_name' => __($result->education_grade_name),
                        'student_status_id' => $result->student_status_id,
                        'student_status_name' => __($result->student_status_name),
                        'academic_period_id' => $result->academic_period_id,
                        'gender_id' => $result->gender_id,
                        'gender_name' => __($result->gender_name),
                        'institution_id' => $institutionId,
                    ];
                }
                return $arrReturn;
            });
    }

    public function setTabElements()
    {
        if ($this->getAlias() != 'Users') {
            return;
        }

        $plugin = $this->controller->getPlugin();
        $name = $this->controller->getName();

        // $id = $this->ControllerAction->buttons['view']['url'][0];
        $action = $this->ControllerAction->url('view');
        $id = $action[0];

        if ($id == 'view' || $id == 'edit') {
            if (isset($this->ControllerAction->buttons['view']['url'][1])) {
                $id = $this->ControllerAction->buttons['view']['url'][1];
            }
        }

        $tabElements = [
            $this->getAlias() => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $this->paramsEncode(['id' => $id])],
                'text' => __('Details')
            ],
            'Accounts' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $this->paramsEncode(['id' => $id])],
                'text' => __('Account')
            ]
        ];

        if (!in_array($this->controller->getName(), ['Students', 'Staff', 'Guardians'])) {
            $tabElements[$this->getAlias()] = [
                'url' => ['plugin' => Inflector::singularize($this->controller->getName()), 'controller' => $this->controller->getName(), 'action' => $this->alias(), 'view', $this->paramsEncode(['id' => $id])],
                'text' => __('Details')
            ];
        }
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('selectedAction', $this->alias);
        $this->controller->set('tabElements', $tabElements);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $settings)
    {
        $this->ControllerAction->field('first_name', ['visible' => false]);
        $this->ControllerAction->field('middle_name', ['visible' => false]);
        $this->ControllerAction->field('third_name', ['visible' => false]);
        $this->ControllerAction->field('last_name', ['visible' => false]);
        $this->ControllerAction->field('preferred_name', ['visible' => false]);
        $this->ControllerAction->field('address', ['visible' => false]);
        $this->ControllerAction->field('postal_code', ['visible' => false]);
        $this->ControllerAction->field('address_area_id', ['visible' => false]);
        $this->ControllerAction->field('gender_id', ['visible' => false]);
        $this->ControllerAction->field('date_of_birth', ['visible' => false]);
        $this->ControllerAction->field('username', ['visible' => false]);
        $this->ControllerAction->field('birthplace_area_id', ['visible' => false]);
        $this->ControllerAction->field('status', ['visible' => false]);
        $this->ControllerAction->field('photo_content', ['visible' => true]);

        $this->ControllerAction->field('address', ['visible' => false]);
        $this->ControllerAction->field('postal_code', ['visible' => false]);
        $this->ControllerAction->field('address_area_id', ['visible' => false]);
        $this->ControllerAction->field('birthplace_area_id', ['visible' => false]);

        $this->ControllerAction->field('staff_institution_name', ['visible' => false]);
        $this->ControllerAction->field('student_institution_name', ['visible' => false]);

        $this->fields['name']['sort'] = true;
        if ($this->controller->getName() != 'Securities') {
            $this->fields['default_identity_type']['sort'] = true;
        }
    }

    public function indexBeforePaginate(EventInterface $event, ServerRequest $request, Query $query, ArrayObject $options)
    {
        $queryParams = $request->getQuery();

        if (isset($queryParams['sort']) && $queryParams['sort'] == 'name') {
            $query->find('withName', ['direction' => $queryParams['direction']]);
            $query->order([$this->aliasField('name') => $queryParams['direction']]);
        }

        if (isset($queryParams['sort']) && $queryParams['sort'] == 'default_identity_type') {
            $query->find('withDefaultIdentityType', ['direction' => $queryParams['direction']]);
            $query->order([$this->aliasField('default_identity_type') => $queryParams['direction']]);
            $request = $request->withQueryParams(['sort' => 'Users.default_identity_type']);
        }
    }

    public function findWithName(Query $query, array $options)
    {
        $name = '';
        $separator = ", ";
        $keys = $this->getNameKeys();
        foreach ($keys as $k => $v) {
            if (!is_null($this->aliasField($k)) && $v) {
                if ($k != 'last_name') {
                    if ($k == 'preferred_name') {
                        $name .= $separator . '(' . $this->aliasField($k) . ')';
                    } else {
                        $name .= $this->aliasField($k) . $separator;
                    }
                } else {
                    $name .= $this->aliasField($k);
                }
            }
        }
        $name = trim(sprintf('%s', $name));
        $name = str_replace($this->alias, "inner_users", $name);

        return $query
            ->join([
                'table' => 'security_users',
                'alias' => 'inner_users',
                'type' => 'left',
                'select' => 'CONCAT(' . $name . ') AS inner_name',
                'conditions' => ['inner_users.id' => $this->aliasField('id')],
                'order' => ['inner_users.inner_name' => $options['direction']]
            ])
            ->order([$this->aliasField('first_name') => $options['direction']]);

        // return $query
        //      ->order([$this->aliasField('first_name') => $options['direction'],
        //              $this->aliasField('middle_name') => $options['direction'],
        //              $this->aliasField('third_name') => $options['direction'],
        //              $this->aliasField('last_name') => $options['direction']
        //          ]);
    }

    public function findWithDefaultIdentityType(Query $query, array $options)
    {
        return $query
            ->join([
                [
                    'table' => 'user_identities',
                    'alias' => 'Identities',
                    'select' => ['*'],
                    'type' => 'left',
                    'group by' => ['Identities.number'],
                    'conditions' => [
                        'Identities.security_user_id' => $this->aliasField('id')
                    ]
                ]
            ])
            ->contain([
                'Identities' => function ($q) {
                    return $q
                        ->select(['IdentityTypes.id'])
                        ->contain(['IdentityTypes'])
                        ->where(['IdentityTypes.default' => 1, 'Identities.identity_type_id' => 'IdentityTypes.id'])
                        ->order(['IdentityTypes.default DESC']);
                }
            ])
            ->group(['Identities.number'])
            ->order(['Identities.number' => $options['direction']]);
    }

    public function viewBeforeAction(EventInterface $event)
    {
        if ($this->alias() == 'Users') {
            // means that this originates from a controller
            $roleName = $this->controller->getName();
            if (array_key_exists('pass', $this->request->getParam())) {
                $id = reset($this->request->getParam('pass'));
            }
        } else {
            // originates from a model
            $roleName = $this->controller->getName() . '.' . $this->getAlias();
            if (array_key_exists('pass', $this->request->getParam())) {
                $id = $this->request->getAttribute('params')['pass'][1];
            }
        }

        if (isset($id)) {
            $this->Session->write($roleName . '.security_user_id', $id);
        } else {
            $id = $this->Session->read($roleName . '.security_user_id');
        }

        $fieldOrder = array_merge($this->fieldOrder1->getArrayCopy(), $this->fieldOrder2->getArrayCopy());
        $this->ControllerAction->setFieldOrder($fieldOrder);
    }

    public function addEditBeforeAction(EventInterface $event)
    {
        $this->fields['openemis_no']['attr']['readonly'] = true;
        $this->fields['photo_content']['type'] = 'image';
        $this->fields['super_admin']['type'] = 'hidden';
        $this->fields['super_admin']['value'] = 0;
        $this->fields['gender_id']['type'] = 'select';
        $this->fields['gender_id']['options'] = $this->Genders->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();
        $this->fields['address']['type'] = 'text';

        $fieldOrder = array_merge($this->fieldOrder1->getArrayCopy(), $this->fieldOrder2->getArrayCopy());
        $this->ControllerAction->setFieldOrder($fieldOrder);
    }

    public function findUniqueOpenemisId(Query $query, array $options)
    {
        $openemisNo = $this->getUniqueOpenemisId();
        echo json_encode(['openemisNo' => $openemisNo]);
        exit;
    }

    /**
     * POCOR-9364
     * Ensure username is unique; if taken, append suffixes until unique.
     * Keeps your current formatting rules intact (alnum/underscore OR email-ish).
     */
    public function ensureUniqueUsername(string $desired = ''): string
    {
        $base = $desired;
        $suffix = 0;
        while ($this->exists(['username' => $desired])) {
            $suffix++;
            $desired = $base . '_' . $suffix;
        }
        return $desired;
    }

    /**
     * POCOR-9364
     * Generate a unique openemis_no (moved from import class).
     * Uses existing getUniqueOpenemisId() and the OpenemisTemps logic.
     */
    public function nextOpenEmisNo(): string
    {
        // == this is your current getNewOpenEmisNo(), moved in and renamed ==
        $notUnique = true;
        $val = $this->getUniqueOpenemisId();
        while ($notUnique) {
            $user = $this->find()
                ->select(['id'])
                ->where([$this->aliasField('openemis_no') => $val])
                ->first();

            if ($user) {
                $val = $this->getUniqueOpenemisId();
            } else {
                $notUnique = false;
            }
        }
        return $val;
    }

    //POCOR-9540[START]
    // public function getUniqueOpenemisId($options = [])
    // {
    //     // POCOR-9364 to review
    //     $prefix = '';

    //     $prefix = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('openemis_id_prefix');
    //     $prefix = explode(",", $prefix);
    //     $prefix = ($prefix[1] > 0) ? $prefix[0] : '';

    //     $latest = $this->find()
    //         ->order($this->aliasField('id') . ' DESC')
    //         ->first();
    //     if (is_array($latest)) {
    //         $latestOpenemisNo = $latest['SecurityUser']['openemis_no'];
    //     } else {
    //         $latestOpenemisNo = $latest->openemis_no;
    //     }
    //     if (empty($prefix)) {
    //         $latestDbStamp = $latestOpenemisNo;
    //     } else {
    //         $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
    //     }

    //     $latestOpenemisNoLastValue = substr($latestOpenemisNo, -1);

    //     $currentStamp = time();
    //     if ($latestDbStamp <= $currentStamp && is_numeric($latestOpenemisNoLastValue)) {
    //         $newStamp = $latestDbStamp + 1;
    //     } else {
    //         $newStamp = $currentStamp;
    //     }
    //     $newOpenemisNo = $prefix . $newStamp;
    //     $openemisTemps = TableRegistry::getTableLocator()->get('User.OpenemisTemps');
    //     $SecurityUser = TableRegistry::getTableLocator()->get('Security.Users');
    //     $resultOpenemisTemp = $openemisTemps->find('all')
    //         ->order(['id' => 'DESC'])
    //         ->first();
    //     //POCOR-6980[START]
    //     if (strlen($resultOpenemisTemp->openemis_no) < 5) {
    //         $resultOpenemisTemp = $SecurityUser->find('all')
    //             ->order(['id' => 'DESC'])
    //             ->first();
    //     }
    //     //POCOR-6980[END]

    //     $resultOpenemisNoTemp = substr($resultOpenemisTemp->openemis_no, strlen($prefix));
    //     $numericPart = (int) preg_replace('/\D+/', '', $resultOpenemisNoTemp);

    //     $numericPart++;
    //     $newOpenemisNo = $prefix . str_pad($numericPart, 5, '0', STR_PAD_LEFT);

    //     $resultOpenemisTemps = $openemisTemps->find('all')
    //         ->where(['openemis_no' => $newOpenemisNo])
    //         ->first();

    //     if (empty($resultOpenemisTemps->openemis_no)) {
    //         $openemisTemp = $openemisTemps->newEntity([]);
    //         $openemisTemp->openemis_no = $newOpenemisNo;
    //         $openemisTemp->ip_address = $_SERVER['REMOTE_ADDR'];
    //         $openemisTemps->save($openemisTemp);
    //     }
    //     return $newOpenemisNo;
    // }

    public function getUniqueOpenemisId($options = [])
    {
        $prefix = $this->getOpenemisIdPrefix();
        $connection = $this->getConnection();
        $openemisTemps = TableRegistry::getTableLocator()->get('User.OpenemisTemps');
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $usersTableSql = $connection->quoteIdentifier($this->getTable());
        $tempTableSql = $connection->quoteIdentifier('security_users_openemis_no');
        $openemisNoSql = $connection->quoteIdentifier('openemis_no');
        $pattern = $this->getNumericOpenemisRegexp($prefix);
        $maxExpr = $this->getNumericOpenemisCastExpression($usersTableSql, $openemisNoSql, $prefix);

        $selectSql = 'SELECT COALESCE(' . $maxExpr . ', 0) + 1 AS next_openemis_no'
            . ' FROM ' . $usersTableSql
            . ' WHERE ' . $usersTableSql . '.' . $openemisNoSql . ' IS NOT NULL'
            . ' AND ' . $usersTableSql . '.' . $openemisNoSql . ' REGEXP :pattern';

        $row = $connection->execute($selectSql, ['pattern' => $pattern])->fetch('assoc');
        $nextSuffix = isset($row['next_openemis_no']) ? (string)$row['next_openemis_no'] : '1';

        $attempts = 0;
        while ($attempts < 100) {
            $newOpenemisNo = $prefix . $nextSuffix;

            if (!$this->exists([$this->aliasField('openemis_no') => $newOpenemisNo])
                && !$openemisTemps->exists(['openemis_no' => $newOpenemisNo])) {
                if ($attempts === 0) {
                    $insertSql = 'INSERT INTO ' . $tempTableSql . ' (' . $openemisNoSql . ', ip_address, created)'
                        . ' SELECT CAST(COALESCE(' . $maxExpr . ', 0) + 1 AS CHAR), :ip_address, NOW()'
                        . ' FROM ' . $usersTableSql
                        . ' WHERE ' . $usersTableSql . '.' . $openemisNoSql . ' IS NOT NULL'
                        . ' AND ' . $usersTableSql . '.' . $openemisNoSql . ' REGEXP :pattern';

                    try {
                        $connection->execute($insertSql, [
                            'ip_address' => $ipAddress,
                            'pattern' => $pattern,
                        ]);
                    } catch (\Exception $exception) {
                        $openemisTemp = $openemisTemps->newEntity([
                            'openemis_no' => $newOpenemisNo,
                            'ip_address' => $ipAddress,
                        ]);
                        $openemisTemps->save($openemisTemp);
                    }
                } else {
                    $connection->execute(
                        'INSERT INTO ' . $tempTableSql . ' (' . $openemisNoSql . ', ip_address, created)'
                        . ' VALUES (:openemis_no, :ip_address, NOW())',
                        ['openemis_no' => $newOpenemisNo, 'ip_address' => $ipAddress]
                    );
                }

                return $newOpenemisNo;
            }

            $nextSuffix = $this->incrementNumericString($nextSuffix);
            $attempts++;
        }

        return $prefix . $nextSuffix;
    }

    /**
     * Configured OpenEMIS ID prefix (empty string when disabled).
     */
    protected function getOpenemisIdPrefix(): string
    {
        $config = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('openemis_id_prefix');
        $parts = explode(',', (string)$config);

        return (isset($parts[1]) && $parts[1] > 0) ? (string)$parts[0] : '';
    }

    /**
     * MAX(CAST(openemis_no AS UNSIGNED)) — matches security_users numeric openemis_no rule.
     */
    protected function getNumericOpenemisCastExpression(
        string $tableSql,
        string $openemisNoSql,
        string $prefix
    ): string {
        if ($prefix === '') {
            return 'MAX(CAST(' . $tableSql . '.' . $openemisNoSql . ' AS UNSIGNED))';
        }

        $prefixLength = strlen($prefix);

        return 'MAX(CAST(SUBSTRING(' . $tableSql . '.' . $openemisNoSql . ', ' . ($prefixLength + 1) . ') AS UNSIGNED))';
    }

    protected function getNumericOpenemisRegexp(string $prefix): string
    {
        if ($prefix === '') {
            return '^[0-9]+$';
        }

        return '^' . preg_quote($prefix, '/') . '[0-9]+$';
    }

    protected function incrementNumericString(string $value): string
    {
        $value = trim($value);
        if ($value === '' || !ctype_digit($value)) {
            $value = '0';
        }

        if (function_exists('bcadd')) {
            return bcadd($value, '1');
        }

        return (string)((int)$value + 1);
    }
    //POCOR-9540[END]

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('first_name', [
                'ruleCheckIfStringGotNoNumber' => [
                    'rule' => 'checkIfStringGotNoNumber',
                ],
                'ruleNotBlank' => [
                    'rule' => 'notBlank',
                ]
            ])
            ->allowEmpty('middle_name')
            ->allowEmpty('third_name')
            ->add('last_name', [
                'ruleCheckIfStringGotNoNumber' => [
                    'rule' => 'checkIfStringGotNoNumber',
                ]
            ])
            ->allowEmpty('preferred_name')
            ->add('openemis_no', [
                'ruleUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                ]
            ])
            ->requirePresence('username', 'create')
            ->add('username', [
                'ruleMinLength' => [
                    'rule' => ['minLength', 6]
                ],
                'ruleUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                ],
                'ruleCheckUsername' => [
                    'rule' => 'checkUsername',
                    'provider' => 'table',
                ]
            ])
            ->allowEmpty('username', 'update')
            // password validation now in behavior
            ->allowEmpty('photo_content')
            ->allowEmpty('identity_number', function ($context) {
                if (!empty($context['data']['identity_type_id']) && empty($context['data']['identity_number'])) {
                    return false;
                }
                return true;
            })
            ->add('account_type', 'custom', [
                'rule' => function ($value, $context) {

                    $accountTypes = ['is_student', 'is_staff', 'is_guardian', 'others'];
                    return in_array($value, $accountTypes);
                },
                'message' => $this->getMessage('Import.value_not_in_list'),
                'on' => function ($context) {
                    if (array_key_exists('action_type', $context['data']) && $context['data']['action_type'] == 'imported') {
                        return true;
                    }
                    return false;
                }
            ]);

        return $validator;
    }

    /**
     * Generates a random password base on the requirements.
     * Credit to https://www.dougv.com/2010/03/a-strong-password-generator-written-in-php/
     *
     * @param integer $l Number of character for password.
     * @param integer $c Number of uppercase character for password.
     * @param integer $n Number of numerical character for password.
     * @param integer $s Number of special character for password.
     * @return string Random password
     */
    public function generatePassword($l = 6, $c = 0, $n = 0, $s = 0)
    {
        $out = '';
        // get count of all required minimum special chars
        $count = $c + $n + $s;

        // sanitize inputs; should be self-explanatory
        if (!is_int($l) || !is_int($c) || !is_int($n) || !is_int($s)) {
            trigger_error('Argument(s) not an integer', E_USER_WARNING);
            return false;
        } elseif ($l < 0 || $c < 0 || $n < 0 || $s < 0) {
            trigger_error('Argument(s) out of range', E_USER_WARNING);
            return false;
        } elseif ($c > $l) {
            trigger_error('Number of password capitals required exceeds password length', E_USER_WARNING);
            return false;
        } elseif ($n > $l) {
            trigger_error('Number of password numerals exceeds password length', E_USER_WARNING);
            return false;
        } elseif ($s > $l) {
            trigger_error('Number of password capitals exceeds password length', E_USER_WARNING);
            return false;
        } elseif ($count > $l) {
            trigger_error('Number of password special characters exceeds specified password length', E_USER_WARNING);
            return false;
        }
        // all inputs clean, proceed to build password

        // change these strings if you want to include or exclude possible password characters
        $chars = "abcdefghijklmnopqrstuvwxyz";
        $caps = strtoupper($chars);
        $nums = "0123456789";
        $syms = "!@#$%^&*()-+?";

        // build the base password of all lower-case letters
        for ($i = 0; $i < $l; $i++) {
            $out .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        // create arrays if special character(s) required
        if ($count) {
            // split base password to array; create special chars array
            $tmp1 = str_split($out);
            $tmp2 = array();

            // Do not change implementation to using mt_rand to rand unless in PHP 7 as rand will have predicable pattern
            // add required special character(s) to second array
            for ($i = 0; $i < $c; $i++) {
                array_push($tmp2, substr($caps, mt_rand(0, strlen($caps) - 1), 1));
            }
            for ($i = 0; $i < $n; $i++) {
                array_push($tmp2, substr($nums, mt_rand(0, strlen($nums) - 1), 1));
            }
            for ($i = 0; $i < $s; $i++) {
                array_push($tmp2, substr($syms, mt_rand(0, strlen($syms) - 1), 1));
            }
            // hack off a chunk of the base password array that's as big as the special chars array
            $tmp1 = array_slice($tmp1, 0, $l - $count);
            // merge special character(s) array with base password array
            $tmp1 = array_merge($tmp1, $tmp2);
            // mix the characters up
            shuffle($tmp1);
            // convert to string for output
            $out = implode('', $tmp1);
        }
        return $out;
    }

    // this is the method to call for user validation - currently in use by Student Staff..
    public function setUserValidation(Validator $validator, $thisModel = null)
    {
        $validator->setProvider('custom', $this); //POCOR-8080
        $validator
            ->add('first_name', [
                'ruleCheckIfStringGotNoNumber' => [
                    'rule' => 'checkIfStringGotNoNumber',
                ],
                'ruleNotBlank' => [
                    'rule' => 'notBlank',
                ]
            ])
            ->add('last_name', [
                'ruleCheckIfStringGotNoNumber' => [
                    'rule' => 'checkIfStringGotNoNumber',
                ]
            ])
            ->add('openemis_no', [
                'ruleUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                ]
            ])
            ->add('username', [
                'ruleMinLength' => [
                    'rule' => ['minLength', 6]
                ],
                'ruleUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                ],
                'ruleCheckUsername' => [
                    'rule' => 'checkUsername',
                    'provider' => 'table',
                ]
            ])
            ->requirePresence('username', 'create')
            ->allowEmpty('username', 'update')
            // password validation now in behavior
            ->allowEmpty('photo_content');

        $thisModel = ($thisModel == null) ? $this : $thisModel;
        $thisModel->setValidationCode('first_name.ruleCheckIfStringGotNoNumber', 'User.Users');
        $thisModel->setValidationCode('first_name.ruleNotBlank', 'User.Users');
        $thisModel->setValidationCode('last_name.ruleCheckIfStringGotNoNumber', 'User.Users');
        $thisModel->setValidationCode('openemis_no.ruleUnique', 'User.Users');
        $thisModel->setValidationCode('username.ruleMinLength', 'User.Users');
        $thisModel->setValidationCode('username.ruleUnique', 'User.Users');
        $thisModel->setValidationCode('username.ruleAlphanumeric', 'User.Users');
        $thisModel->setValidationCode('username.ruleCheckUsername', 'User.Users');
        $thisModel->setValidationCode('password.ruleNoSpaces', 'User.Users');
        $thisModel->setValidationCode('password.ruleMinLength', 'User.Users');
        $thisModel->setValidationCode('date_of_birth.ruleValidDate', 'User.Users');
        return $validator;
    }

    public function onGetPhotoContent(EventInterface $event, Entity $entity)
    {
        $fileContent = $entity->photo_content;
        $value = "";
        if (empty($fileContent) && is_null($fileContent)) {
            if (($this->hasBehavior('Student')) && ($this->action == "index")) {
                $value = $this->defaultStudentProfileIndex;
            } elseif (($this->hasBehavior('Staff')) && ($this->action == "index")) {
                $value = $this->defaultStaffProfileIndex;
            } elseif (($this->hasBehavior('Guardian')) && ($this->action == "index")) {
                $value = $this->defaultGuardianProfileIndex;
            } elseif (($this->hasBehavior('User')) && ($this->action == "index")) {
                $value = $this->defaultUserProfileIndex;
            } elseif (($this->hasBehavior('Student')) && ($this->action == "view")) {
                $value = $this->defaultStudentProfileView;
            } elseif (($this->hasBehavior('Staff')) && ($this->action == "view")) {
                $value = $this->defaultStaffProfileView;
            } elseif (($this->hasBehavior('Guardian')) && ($this->action == "view")) {
                $value = $this->defaultGuardianProfileView;
            } elseif (($this->hasBehavior('User')) && ($this->action == "view")) {
                $value = $this->defaultUserProfileView;
            }
        } else {
            $value = base64_encode(stream_get_contents($fileContent)); //$fileContent;
        }

        return $value;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'default_identity_type') {
            $IdentityType = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
            $defaultIdentity = $IdentityType->getDefaultEntity();
            if ($defaultIdentity) {
                $value = $defaultIdentity->name;
            }

            return (!empty($value)) ? $value : parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        } elseif ($field == 'student_status' || $field == 'staff_status') {
            return 'Status';
        } elseif ($field == 'programme_class') {
            return 'Programme<span class="divider"></span>Class';
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function getDefaultImgMsg()
    {
        $width = 90;
        $height = 115;
        $photoMsg = __($this->photoMessage);
        $photoMsg = str_replace('%width', $width, $photoMsg);
        $photoMsg = str_replace('%height', $height, $photoMsg);
        $formatSupported = '.jpg, .jpeg, .png, .gif';
        $formatMsg = sprintf(__($this->formatSupport), $formatSupported);
        return sprintf($this->defaultImgMsg, $photoMsg, $formatMsg);
    }

    public function getDefaultImgIndexClass()
    {
        return $this->defaultImgIndexClass;
    }

    public function getDefaultImgViewClass()
    {
        return $this->defaultImgViewClass;
    }

    public function getDefaultImgView()
    {
        $value = "";
        $controllerName = $this->controller->getName();

        if ($this->hasBehavior('Student')) {
            $value = $this->defaultStudentProfileView;
        } elseif ($this->hasBehavior('Staff')) {
            $value = $this->defaultStaffProfileView;
        } elseif ($this->hasBehavior('Guardian')) {
            $value = $this->defaultGuardianProfileView;
        } elseif ($this->hasBehavior('User')) {
            $value = $this->defaultUserProfileView;
        }
        return $value;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($this->controller->getName() != 'Securities') {
            $actions = ['view', 'edit'];
            foreach ($actions as $action) {
                if (array_key_exists($action, $buttons)) {
                    $buttons[$action]['url'][1] = $this->paramsEncode(['id' => $entity->security_user_id]);
                }
            }
            if (isset($buttons['remove'])) {
                $buttons['remove']['attr']['field-value'] = $entity->security_user_id;
            }
        }

        return $buttons;
    }

    // autocomplete used for TrainingSessions
    // the same function is found in Security.Users
    public function autocomplete($search, $extra = [])
    {
        $data = [];
        if (!empty($search)) {
            $query = $this->find();

            // POCOR-3556 add the user type to finder
            if (isset($extra['finder'])) {
                $finders = $extra['finder'];

                foreach ($finders as $finder) {
                    $query->find($finder);
                }
            }

            $query = $query
                ->leftJoinWith('Identities')
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
                ->group([$this->aliasField('id')])
                ->limit(100);

            // function from AdvancedNameSearchBehavior
            if (isset($extra['OR'])) {
                $query = $this->addSearchConditions($query, ['searchTerm' => $search, 'OR' => $extra['OR']]);
            } else {
                $query = $this->addSearchConditions($query, ['searchTerm' => $search]);
            }

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

    public function findAuth(Query $query, array $options)
    {
        $query
            ->select([
                'id',
                'username',
                'password',
                'openemis_no',
                'first_name',
                'middle_name',
                'third_name',
                'last_name',
                'preferred_name',
                'super_admin',
                'status',
                'last_login',
                'preferred_language',
                'is_student',
                'is_staff',
                'is_guardian'
            ])
            ->where([
                'status' => 1
            ]);

        return $query;
    }

    public function findStaff(Query $query, array $options)
    {
        // is_staff == 1
        return $query->where([$this->aliasField('is_staff') => 1]);
    }

    public function findOthers(Query $query, array $options)
    {
        // is_guardian == 1 or (is_staff == 0, is_student == 0, is_guardian == 0)
        return $query->where([
            'OR' => [
                [$this->aliasField('is_guardian') => 1],
                [
                    $this->aliasField('is_staff') => 0,
                    $this->aliasField('is_student') => 0,
                    $this->aliasField('is_guardian') => 0,
                ]
            ]
        ]);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //Stop import if contact/contact type has validation error
        if ($entity->has('contact_error')) {
            return false;
        }
        // POCOR-9101
//        Log::debug(__FUNCTION__);
//        Log::debug(print_r(['errors' => $entity->getErrors(),
//            'options' => $options,
//            'event' => $event],true));
        return true;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {

        $this->handleImportedUserData($entity);
    }




    private function handleImportedUserData(Entity $entity): void
    {
        if (!($entity->has('action_type') && $entity->action_type === 'imported')) {
            return;
        }

        $this->linkImportedContacts($entity);
        $this->dispatchImportListeners($entity);
    }

    private function linkImportedContacts(Entity $entity): void
    {
        if (!$entity->has('contact_entity')) {
            return;
        }

        $securityUserId = $entity->id;
        $contactEntities = $entity->contact_entity;

        if (!is_array($contactEntities)) {
            $contactEntities = [$contactEntities];
        }

        $ContactsTable = TableRegistry::getTableLocator()->get('User.Contacts');

        foreach ($contactEntities as $contactEntity) {
            if (!$contactEntity->has('security_user_id')) {
                $contactEntity->security_user_id = $securityUserId;
                $contactEntity->preferred = 1;
            }

            $ContactsTable->save($contactEntity);
        }
    }
    private function dispatchImportListeners(Entity $entity): void
    {
        $nationalityId = $entity->nationality_id ?? null;
        $identityTypeId = $entity->identity_type_id ?? null;

        if (!$nationalityId) {
            return;
        }

        $listeners = [
            TableRegistry::getTableLocator()->get('User.UserNationalities')
        ];

        if ($identityTypeId) {
            $listeners[] = TableRegistry::getTableLocator()->get('User.Identities');
        }

        $this->dispatchEventToModels('Model.Users.afterSave', [$entity], $this, $listeners);
    }


    public function onChangeUserNationalities(EventInterface $event, Entity $entity)
    {
        $nationalityId = $entity->nationality_id;
        $Nationalities = TableRegistry::getTableLocator()->get('FieldOption.Nationalities');

        // to find out the default identity type linked to this nationality
        $nationality = $Nationalities
            ->find()
            ->where([
                $Nationalities->aliasField($Nationalities->getPrimaryKey()) => $nationalityId
            ])
            ->first();
        // to get the identity record for the user based on the default identity type linked to this nationality
        $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
        $latestIdentity = $UserIdentities->find()
            ->where([
                $UserIdentities->aliasField('security_user_id') => $entity->security_user_id,
                $UserIdentities->aliasField('identity_type_id IS') => $nationality->identity_type_id,
                //$UserIdentities->aliasField('nationality_id') => $nationality->id,
            ])
            ->order([$UserIdentities->aliasField('created') => 'desc'])
            ->first();

        // if there is an existing user identity record
        $identityNumber = null;
        if (!empty($latestIdentity)) {
            $identityNumber = $latestIdentity->number;
        }

        // POCOR 3804
        $UserIdentities->updateAll(
            [
                'nationality_id' => $nationalityId
            ],
            ['security_user_id' => $entity->security_user_id]
        );

        $this->updateAll(
            [
                'nationality_id' => $nationalityId,
                'identity_type_id' => $nationality->identity_type_id,
                'identity_number' => $identityNumber
            ],
            ['id' => $entity->security_user_id]
        );
    }

    public function onChangeUserIdentities(EventInterface $event, Entity $entity)
    {
        $UserNationalityTable = TableRegistry::getTableLocator()->get('User.UserNationalities');
        //POCOR-8664 start
        $UserNationalityTable->updateAll(
            [
                'preferred' => 1,
            ],
            [
                'security_user_id' => $entity->security_user_id,
                'nationality_id' => $entity->nationality_id
            ]
        );
        $UserNationalityTable->updateAll(
            [
                'preferred' => 0,
            ],
            [
                'security_user_id' => $entity->security_user_id,
                'nationality_id <>' => $entity->nationality_id
            ]
        );
        //POCOR-8664 end
        //check whether identity number / type is tied to preferred nationality.
        $preferredNationality = $UserNationalityTable
            ->find()
            ->matching('NationalitiesLookUp')
            ->select(['nationality_id', 'identityTypeId' => 'NationalitiesLookUp.identity_type_id'])
            ->where([
                'NationalitiesLookUp.identity_type_id' => $entity->identity_type_id,
                $UserNationalityTable->aliasField('security_user_id') => $entity->security_user_id,
                $UserNationalityTable->aliasField('preferred') => 1
            ])
            ->first();
        if (!empty($preferredNationality)) {
            // to get the identity record for the user based on the default identity type linked to this nationality
            $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
            $latestIdentity = $UserIdentities->find()
                ->where([
                    $UserIdentities->aliasField('security_user_id') => $entity->security_user_id,
                    $UserIdentities->aliasField('identity_type_id') => $preferredNationality->identityTypeId,
                    $UserIdentities->aliasField('nationality_id') => $preferredNationality->nationality_id,
                ])
                ->order([$UserIdentities->aliasField('created') => 'desc'])
                ->first();

            // if there is an existing user identity record
            $identityNumber = null;
            if (!empty($latestIdentity)) {
                $identityNumber = $latestIdentity->number;
            }

            $this->updateAll(
                [
                    'nationality_id' => $preferredNationality->nationality_id,
                    'identity_type_id' => $preferredNationality->identityTypeId,
                    'identity_number' => $identityNumber
                ],
                ['id' => $entity->security_user_id]
            );
        } else {
            /* if its update, check if any user identities type ids matches the preferred nationality identityTypeId.
             if none found update the identity_number to null */
            if (!$entity->isNew()) {
                $userPreferredNationality = $UserNationalityTable
                    ->find()
                    ->matching('NationalitiesLookUp')
                    ->select(['identityTypeId' => 'NationalitiesLookUp.identity_type_id'])
                    ->where([
                        $UserNationalityTable->aliasField('security_user_id') => $entity->security_user_id,
                        $UserNationalityTable->aliasField('preferred') => 1
                    ])
                    ->first();
                if (!empty($userPreferredNationality)) {
                    $preferredIdentityTypeId = $userPreferredNationality->identityTypeId;
                    $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
                    if ($preferredIdentityTypeId) {
                        $latestIdentity = $UserIdentities->find()
                            ->where([
                                $UserIdentities->aliasField('security_user_id') => $entity->security_user_id,
                                $UserIdentities->aliasField('identity_type_id') => $preferredIdentityTypeId,
                            ])
                            ->order([$UserIdentities->aliasField('created') => 'desc'])
                            ->first();

                        if (empty($latestIdentity)) {
                            $this->updateAll(
                                [
                                    'identity_number' => null
                                ],
                                ['id' => $entity->security_user_id]
                            );
                        }
                    }
                }
            }
        }
    }

    public function onChangeNationalities(EventInterface $event, Entity $entity)
    {
        $nationalityId = $entity->id;
        $identityTypeId = $entity->identity_type_id;

        $connection = ConnectionManager::get('default');
        $connection->execute(
            'UPDATE `security_users` `SU`
            INNER JOIN `nationalities` `N`
                ON `N`.`id` = `SU`.`nationality_id`
                AND `N`.`id` = ?
            LEFT JOIN (
                SELECT `security_user_id`, `identity_type_id`, `number`
                FROM `user_identities` `U1`
                WHERE `created` = (
                    SELECT MAX(`created`)
                    FROM `user_identities`
                    WHERE  `security_user_id` = `U1`.`security_user_id`
                    AND `identity_type_id` = ?
                )
            )AS UI
                ON (
                    `UI`.`identity_type_id` = `N`.`identity_type_id`
                    AND `UI`.`security_user_id` = `SU`.`id`
                )
            SET
                `SU`.`identity_type_id` = ?,
                `SU`.`identity_number` = `UI`.`number`',
            [$nationalityId, $identityTypeId, $identityTypeId],
            ['integer', 'integer', 'integer']
        );
    }

    public function onChangeUserContacts(EventInterface $event, Entity $entity)
    {
        $securityUserId = $entity->security_user_id;
        //POCOR-8660 start
        $contactOptionCode = $entity->contact_option_code;
        if ($contactOptionCode == 'MOB' || $contactOptionCode == 'PHO') {
            $phone = $entity->value;
            // update the user mobile_number with preferred mobile_number
            $this->updateAll(['mobile_number' => $phone], ['id' => $securityUserId]);
        }
        //POCOR-8660 end
        else {
            $email = $entity->value;
            // update the user email with preferred email
            $this->updateAll(['email' => $email], ['id' => $securityUserId]);
        }
    }

    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options)
    {

        if (!empty($_REQUEST['_device']) && $_REQUEST['_device'] == true) {
            $query->formatResults(function ($results) {
                return $results->map(function ($row) {
                    $row->user_avatar = null;

                    if (!empty($row->photo_name)) {
                        $row->user_avatar = base64_encode(stream_get_contents($row->photo_content));
                    }
                    return $row;
                });
            });
        }
    }

    public function findUserAvatar(Query $query, array $options)
    {
        $userAvatar = '';
        $staffId = $options['staff_id'];
        $userDetail = $query->where(['id' => $staffId])->first();
        if (!empty($userDetail->photo_content)) {
            $fileContent = $userDetail->photo_content;
            $userAvatar = base64_encode(stream_get_contents($fileContent));
            echo json_encode(['status' => 200, 'user_avatar' => $userAvatar]);
        } else {
            echo json_encode(['status' => 404, 'user_avatar' => null]);
        }

        die;
    }

    public function findStudents($institutionId = 0)
    {

        $query = TableRegistry::getTableLocator()->get('Institution.Students');
        $studentQuery = $query->find()
            ->contain(['Users'])
            ->where(['institution_id' => $institutionId]);
        $student = $studentQuery->select([
            'id' => 'Users.openemis_no',
            'openemis_no' => 'Users.openemis_no',
            'first_name' => "Users.first_name",
            'middle_name' => "Users.middle_name",
            'third_name' => "Users.third_name",
            'last_name' => "Users.last_name"
        ]);

        $students = $student->formatResults(function ($results) {
            return $results->map(function ($row) {
                $row->name = preg_replace('/\s+/', ' ', $row->first_name . ' ' . $row->middle_name . ' ' . $row->third_name . ' ' . $row->last_name);
                return $row;
            });
        })->toArray();

        return $students;
    }
}
