<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\UserTrait;
use Cake\I18n\Time;
use Cake\Network\Session;
use Cake\Datasource\ConnectionManager;

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
    private $defaultImgViewClass= "profile-image";
    private $photoMessage = 'Advisable photo dimension %width by %height';
    private $formatSupport = 'Format Supported: %s';
    private $defaultImgMsg = "<p>* %s <br>* %s</p>";

    public $fieldOrder1;
    public $fieldOrder2;

    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        self::handleAssociations($this);

        $this->fieldOrder1 = new ArrayObject(['photo_content', 'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'address', 'postal_code']);
        $this->fieldOrder2 = new ArrayObject(['status','modified_user_id','modified','created_user_id','created']);

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'photo_name',
            'content' => 'photo_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'image',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StaffRoom' => ['index', 'add'],
            'ClassStudents' => ['index'],
            'OpenEMIS_Classroom' => ['view', 'edit']
        ]);

        $this->displayField('first_name');
    }

    public function implementedEvents()
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

    public function updateLoginLanguage(Event $event, $user, $language)
    {
        if ($user['preferred_language'] != $language) {
            $user = $this->get($user['id']);
            $user->preferred_language = $language;
            $this->save($user);
        }
    }

    public function afterLogin(Event $event, $user)
    {
        $lastLogin = new Time();
        $controller = $event->subject();
        $SSO = $controller->SSO;
        $Cookie = $controller->Localization->getCookie();
        $session = $controller->request->session();
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
    }

    public function createAuthorisedUser(Event $event, $userName, array $userInfo)
    {
        $openemisNo = $this->getUniqueOpenemisId();

        $GenderTable = TableRegistry::get('User.Genders');
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
        $model->hasMany('Identities', ['className' => 'User.Identities',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Nationalities', ['className' => 'User.UserNationalities',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Contacts', ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Attachments', ['className' => 'User.Attachments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('BankAccounts', ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Comments', ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Languages', ['className' => 'User.UserLanguages',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Awards', ['className' => 'User.Awards',            'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('ExaminationItemResults', ['className' => 'Examination.ExaminationItemResults', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students',    'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'foreignKey' => 'security_role_id',
            'targetForeignKey' => 'security_user_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);
        $model->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $model->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function beforeAction(Event $event)
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

    public function afterAction(Event $event)
    {
        if (isset($this->action) && in_array($this->action, ['view', 'edit'])) {
            $this->setTabElements();
        }

        if (isset($this->action) && strtolower($this->action) != 'index') {
            $this->Navigation->addCrumb($this->getHeader($this->action));
        }
    }

    public function findInstitutionStudentsNotInClass(Query $query, array $options)
    {
        $educationGradeIds = null;
        $academicPeriodId = null;
        $institutionClassId = null;
        $institutionId = null;
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;

        if (array_key_exists('institution_class_id', $options)) {
            $institutionClassId = $options['institution_class_id'];
            $institutionClassRecord = TableRegistry::get('Institution.InstitutionClasses')->get($institutionClassId, ['contain' => ['EducationGrades']])->toArray();
            $academicPeriodId = $institutionClassRecord['academic_period_id'];
            $institutionId = $institutionClassRecord['institution_id'];
            $educationGradeIds = array_column($institutionClassRecord['education_grades'], 'id');
            if (empty($educationGradeIds)) {
                return $query->where(['1 = 0']);
            }
        }
        return $query
            ->innerJoinWith('InstitutionStudents')
            ->leftJoinWith('InstitutionClassStudents', function ($q) use ($academicPeriodId, $institutionId) {
                return $q->where([
                    'InstitutionClassStudents.academic_period_id' => $academicPeriodId,
                    'InstitutionClassStudents.institution_id' => $institutionId
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
                        'institution_class_id' => $institutionClassId
                    ];
                }
                return $arrReturn;
            });
    }

    public function setTabElements()
    {
        if ($this->alias() != 'Users') {
            return;
        }

        $plugin = $this->controller->plugin;
        $name = $this->controller->name;

        // $id = $this->ControllerAction->buttons['view']['url'][0];
        $action = $this->ControllerAction->url('view');
        $id = $action[0];

        if ($id=='view' || $id=='edit') {
            if (isset($this->ControllerAction->buttons['view']['url'][1])) {
                $id = $this->ControllerAction->buttons['view']['url'][1];
            }
        }

        $tabElements = [
            $this->alias => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $this->paramsEncode(['id' => $id])],
                'text' => __('Details')
            ],
            'Accounts' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $this->paramsEncode(['id' => $id])],
                'text' => __('Account')
            ]
        ];

        if (!in_array($this->controller->name, ['Students', 'Staff', 'Guardians'])) {
            $tabElements[$this->alias] = [
                'url' => ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => $this->alias(), 'view', $this->paramsEncode(['id' => $id])],
                'text' => __('Details')
            ];
        }
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('selectedAction', $this->alias);
        $this->controller->set('tabElements', $tabElements);
    }

    public function indexBeforeAction(Event $event, ArrayObject $settings)
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
        if ($this->controller->name != 'Securities') {
            $this->fields['default_identity_type']['sort'] = true;
        }
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $queryParams = $request->query;

        if (array_key_exists('sort', $queryParams) && $queryParams['sort'] == 'name') {
            $query->find('withName', ['direction' => $queryParams['direction']]);
            $query->order([$this->aliasField('name') => $queryParams['direction']]);
        }

        if (array_key_exists('sort', $queryParams) && $queryParams['sort'] == 'default_identity_type') {
            $query->find('withDefaultIdentityType', ['direction' => $queryParams['direction']]);
            $query->order([$this->aliasField('default_identity_type') => $queryParams['direction']]);
            $request->query['sort'] = 'Users.default_identity_type';
        }
    }

    public function findWithName(Query $query, array $options)
    {
        $name = '';
        $separator = ", ";
        $keys = $this->getNameKeys();
        foreach ($keys as $k => $v) {
            if (!is_null($this->aliasField($k))&&$v) {
                if ($k!='last_name') {
                    if ($k=='preferred_name') {
                        $name .= $separator . '('. $this->aliasField($k) .')';
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
                    'type'  => 'left',
                    'select' => 'CONCAT('.$name.') AS inner_name',
                    'conditions' => ['inner_users.id' => $this->aliasField('id')],
                    'order' => ['inner_users.inner_name' => $options['direction']]
                ])
            ->order([$this->aliasField('first_name') => $options['direction']]);

        // return $query
        // 		->order([$this->aliasField('first_name') => $options['direction'],
        // 				$this->aliasField('middle_name') => $options['direction'],
        // 				$this->aliasField('third_name') => $options['direction'],
        // 				$this->aliasField('last_name') => $options['direction']
        // 			]);
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

    public function viewBeforeAction(Event $event)
    {
        if ($this->alias() == 'Users') {
            // means that this originates from a controller
            $roleName = $this->controller->name;
            if (array_key_exists('pass', $this->request->params)) {
                $id = reset($this->request->params['pass']);
            }
        } else {
            // originates from a model
            $roleName = $this->controller->name.'.'.$this->alias();
            if (array_key_exists('pass', $this->request->params)) {
                $id = $this->request->params['pass'][1];
            }
        }

        if (isset($id)) {
            $this->Session->write($roleName.'.security_user_id', $id);
        } else {
            $id = $this->Session->read($roleName.'.security_user_id');
        }

        $fieldOrder = array_merge($this->fieldOrder1->getArrayCopy(), $this->fieldOrder2->getArrayCopy());
        $this->ControllerAction->setFieldOrder($fieldOrder);
    }

    public function addEditBeforeAction(Event $event)
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

    public function getUniqueOpenemisId($options = [])
    {
        $prefix = '';

        $prefix = TableRegistry::get('Configuration.ConfigItems')->value('openemis_id_prefix');
        $prefix = explode(",", $prefix);
        $prefix = ($prefix[1] > 0)? $prefix[0]: '';

        $latest = $this->find()
            ->order($this->aliasField('id').' DESC')
            ->first();

        if (is_array($latest)) {
            $latestOpenemisNo = $latest['SecurityUser']['openemis_no'];
        } else {
            $latestOpenemisNo = $latest->openemis_no;
        }
        if (empty($prefix)) {
            $latestDbStamp = $latestOpenemisNo;
        } else {
            $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
        }

        $currentStamp = time();
        if ($latestDbStamp >= $currentStamp) {
            $newStamp = $latestDbStamp + 1;
        } else {
            $newStamp = $currentStamp;
        }

        return $prefix.$newStamp;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

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
            ;
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
            ->allowEmpty('photo_content')
            ;

        $thisModel = ($thisModel == null)? $this: $thisModel;
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

    public function onGetPhotoContent(Event $event, Entity $entity)
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
            $value = base64_encode(stream_get_contents($fileContent));//$fileContent;
        }

        return $value;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'default_identity_type') {
            $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
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
        $controllerName = $this->controller->name;

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

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($this->controller->name != 'Securities') {
            $actions = ['view', 'edit'];
            foreach ($actions as $action) {
                if (array_key_exists($action, $buttons)) {
                    $buttons[$action]['url'][1] = $this->paramsEncode(['id' => $entity->security_user_id]);
                }
            }
            if (array_key_exists('remove', $buttons)) {
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
            if (array_key_exists('finder', $extra)) {
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // This logic is meant for Import
        if ($entity->has('customColumns')) {
            foreach ($entity->customColumns as $column => $value) {
                switch ($column) {
                    case 'Identity':
                        $userIdentitiesTable = TableRegistry::get('User.Identities');

                        $defaultValue = $userIdentitiesTable->IdentityTypes->getDefaultValue();

                        if ($defaultValue) {
                            $userIdentityData = $userIdentitiesTable->newEntity([
                                'identity_type_id' => $defaultValue,
                                'number' => $value,
                                'security_user_id' => $entity->id
                            ]);
                            $userIdentitiesTable->save($userIdentityData);
                        }
                        break;
                }
            }
        }

        // This logic is meant for Import
        if ($entity->has('record_source')) {
            if ($entity->record_source == 'import_user') {
                $listeners = [
                    TableRegistry::get('User.UserNationalities'),
                    TableRegistry::get('User.Identities')
                ];
                $this->dispatchEventToModels('Model.Users.afterSave', [$entity], $this, $listeners);
            }
        }
    }

    public function onChangeUserNationalities(Event $event, Entity $entity)
    {
        $nationalityId = $entity->nationality_id;
        $Nationalities = TableRegistry::get('FieldOption.Nationalities');

        // to find out the default identity type linked to this nationality
        $nationality = $Nationalities
                        ->find()
                        ->where([
                            $Nationalities->aliasField($Nationalities->primaryKey()) => $nationalityId
                        ])
                        ->first();

        // to get the identity record for the user based on the default identity type linked to this nationality
        $UserIdentities = TableRegistry::get('User.Identities');
        $latestIdentity = $UserIdentities->find()
        ->where([
            $UserIdentities->aliasField('security_user_id') => $entity->security_user_id,
            $UserIdentities->aliasField('identity_type_id') => $nationality->identity_type_id,
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
                'nationality_id' => $nationalityId,
                'identity_type_id' => $nationality->identity_type_id,
                'identity_number' => $identityNumber
            ],
            ['id' => $entity->security_user_id]
        );
    }

    public function onChangeUserIdentities(Event $event, Entity $entity)
    {
        $UserNationalityTable = TableRegistry::get('User.UserNationalities');

        //check whether identity number / type is tied to preferred nationality.
        $isPreferredNationality = $UserNationalityTable
                                ->find()
                                ->matching('NationalitiesLookUp')
                                ->select(['nationality_id', 'identityTypeId' => 'NationalitiesLookUp.identity_type_id'])
                                ->where([
                                    'NationalitiesLookUp.identity_type_id' => $entity->identity_type_id,
                                    $UserNationalityTable->aliasField('security_user_id') => $entity->security_user_id,
                                    $UserNationalityTable->aliasField('preferred') => 1
                                ]);

        if ($isPreferredNationality->count()) {
            $preferredNationality = $isPreferredNationality->first();
            // to get the identity record for the user based on the default identity type linked to this nationality
            $UserIdentities = TableRegistry::get('User.Identities');
            $latestIdentity = $UserIdentities->find()
            ->where([
                $UserIdentities->aliasField('security_user_id') => $entity->security_user_id,
                $UserIdentities->aliasField('identity_type_id') => $preferredNationality->identityTypeId,
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
        }
    }

    public function onChangeNationalities(Event $event, Entity $entity)
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
            [$nationalityId,$identityTypeId,$identityTypeId],
            ['integer','integer','integer']
        );
    }

    public function onChangeUserContacts(Event $event, Entity $entity)
    {
        $securityUserId = $entity->security_user_id;
        $email = $entity->value;

        // update the user email with preferred email
        $this->updateAll(['email' => $email], ['id' => $securityUserId]);
    }
}
