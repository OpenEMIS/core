<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use User\Model\Entity\User;
use Cake\I18n\I18n;
use Cake\Network\Session;
use Cake\Routing\Router;

class UserBehavior extends Behavior
{
    private $defaultStudentProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-students'></i></div></div>";
    private $defaultStaffProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
    private $defaultGuardianProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-guardian'></i></div></div>";
    private $defaultUserProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";

    private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";
    private $defaultStaffProfileView = "<div class='profile-image'><i class='kd-staff'></i></div>";
    private $defaultGuardianProfileView = "<div class='profile-image'><i class='kd-guardian'></i></div>";
    private $defaultUserProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";

    private $defaultImgIndexClass = "profile-image-thumbnail";
    private $defaultImgViewClass= "profile-image";
    private $photoMessage = 'Advisable photo dimension %width by %height';
    private $formatSupport = 'Format Supported: %s';
    private $defaultImgMsg = "<p>* %s <br>* %s</p>";

    public function initialize(array $config)
    {
        if ($this->_table->table() == 'security_users') {
            $this->_table->addBehavior('ControllerAction.FileUpload', [
                'name' => 'photo_name',
                'content' => 'photo_content',
                'size' => '2MB',
                'contentEditable' => true,
                'allowable_file_types' => 'image'
            ]);

            $this->_table->addBehavior('Security.Password', [
                'field' => 'password'
            ]);
            $this->_table->addBehavior('Area.Areapicker');
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 0];
        $events['ControllerAction.Model.index.beforePaginate'] = ['callable' => 'indexBeforePaginate', 'priority' => 0];
        $events['ControllerAction.Model.index.afterAction'] = ['callable' => 'indexAfterAction', 'priority' => 50];
        $events['ControllerAction.Model.add.beforeAction'] = ['callable' => 'addBeforeAction', 'priority' => 0];
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 0];
        $events['ControllerAction.Model.addEdit.beforePatch'] = ['callable' => 'addEditBeforePatch', 'priority' => 50];
        $events['ControllerAction.Model.onGetFieldLabel'] = ['callable' => 'onGetFieldLabel', 'priority' => 50];
        $events['Model.excel.onExcelGetStatus'] = 'onExcelGetStatus';

        return $events;
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $dataArray = $data->getArrayCopy();
        if (array_key_exists($this->_table->alias(), $dataArray)) {
            if (array_key_exists('username', $dataArray[$this->_table->alias()])) {
                $data[$this->_table->alias()]['username'] = trim($dataArray[$this->_table->alias()]['username']);
            }
        }
    }

    public function onExcelGetStatus(Event $event, Entity $entity)
    {
        if ($entity->status == 1) {
            return __('Active');
        } else {
            return __('Inactive');
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $this->trimFields($data);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->preferred_language = 'en';
        }
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function beforeAction(Event $event)
    {
        switch ($this->_table->table()) {
            case 'institution_students':
            case 'institution_staff':
            case 'student_guardians':
                break;
            default:
                $this->_table->fields['username']['visible'] = false;
                $this->_table->fields['last_login']['visible'] = false;
                break;
        }
        if ($this->_table->table() == 'security_users') {
            $this->_table->addBehavior('OpenEmis.Section');
            $this->_table->fields['is_student']['type'] = 'hidden';
            $this->_table->fields['is_staff']['type'] = 'hidden';
            $this->_table->fields['is_guardian']['type'] = 'hidden';
            $this->_table->fields['photo_name']['visible'] = false;
            $this->_table->fields['super_admin']['visible'] = false;
            $this->_table->fields['date_of_death']['visible'] = false;
            $this->_table->fields['external_reference']['visible'] = false;
            $this->_table->fields['status']['visible'] = false;
            $this->_table->fields['preferred_language']['visible'] = false;
            $this->_table->fields['address_area_id']['type'] = 'areapicker';
            $this->_table->fields['address_area_id']['source_model'] = 'Area.AreaAdministratives';
            $this->_table->fields['birthplace_area_id']['type'] = 'areapicker';
            $this->_table->fields['birthplace_area_id']['source_model'] = 'Area.AreaAdministratives';
            $this->_table->fields['gender_id']['type'] = 'select';
            $this->_table->fields['nationality_id']['visible'] = ['index' => false, 'view' => true, 'edit' => true, 'add' => false];
            $this->_table->fields['identity_type_id']['visible'] = ['index' => false, 'view' => true, 'edit' => true, 'add' => false];

            $i = 10;
            $this->_table->fields['first_name']['order'] = $i++;
            $this->_table->fields['middle_name']['order'] = $i++;
            $this->_table->fields['third_name']['order'] = $i++;
            $this->_table->fields['last_name']['order'] = $i++;
            $this->_table->fields['preferred_name']['order'] = $i++;
            $this->_table->fields['gender_id']['order'] = $i++;
            if ($this->isCAv4()) {
                $this->_table->field('date_of_birth', [
                    'date_options' => [
                        'endDate' => date('d-m-Y')
                    ],
                    'default_date' => false,
                ]);
            } else {
                $this->_table->ControllerAction->field('date_of_birth', [
                        'date_options' => [
                            'endDate' => date('d-m-Y')
                        ],
                        'default_date' => false,
                    ]
                );
            }

            $this->_table->fields['date_of_birth']['order'] = $i++;
            //POCOR-5668 remove nationality, identity type, identity number
            //$this->_table->fields['nationality_id']['order'] = $i++;
            //$this->_table->fields['identity_type_id']['order'] = $i++;
            //$this->_table->fields['identity_number']['order'] = $i++;
            $this->_table->fields['email']['order'] = $i++;

            $this->_table->fields['address']['order'] = $i++;
            $this->_table->fields['postal_code']['order'] = $i++;
            $this->_table->fields['address_area_id']['order'] = $i++;
            $this->_table->fields['birthplace_area_id']['order'] = $i++;

            if ($this->_table->action != 'index') {
                if ($this->isCAv4()) {
                    $this->_table->field('photo_content', ['type' => 'image', 'order' => 0]);
                    $this->_table->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
                } else {
                    $this->_table->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
                    $this->_table->ControllerAction->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
                }
            }

            // edit page, email = readonly
            if ($this->_table->action == 'edit') {
                if ($this->isCAv4()) {
                    $this->_table->field('email', ['type' => 'readonly', 'after' => 'identity_number']);
                } else {
                    $this->_table->ControllerAction->field('email', ['type' => 'readonly', 'after' => 'identity_number']);
                }
            }

            // add page, email = hidden
            if ($this->_table->action == 'add') {
                if ($this->isCAv4()) {
                    $this->_table->field('email', ['type' => 'hidden']);
                } else {
                    $this->_table->ControllerAction->field('email', ['type' => 'hidden']);
                }
            }
  
            if ($this->_table->registryAlias() != 'Security.Users') {
                $language = I18n::locale();
                if ($this->isCAv4()) {
                    $this->_table->field('information_section', ['type' => 'section', 'title' => __('Information'), 'before' => 'photo_content', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    //POCOR-5668 add identity section starts
                    $this->_table->field('identity_section', ['type' => 'section', 'title' => __('Identities / Nationalities'), 'after' => 'email', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $security_users_id = '';
                    $model = $this->_table;
                    if($this->_table->controller->request->params['pass'][0] == 'view'){
                        $security_users_id = $model->paramsDecode($this->_table->controller->request->params['pass']['1'])['id'];
                    }
                    if($security_users_id > 0){
                        $this->_table->field('details', [
                            'type' => 'element',
                            'after' => 'identity_section',
                            'element' => 'User.UserIdentities/details',
                            'visible' => ['view'=>true],
                            'data' => $this->getViewUserIdentities($security_users_id)
                        ]);
                    }
                    //POCOR-5668 add identity section ends
                    $this->_table->field('location_section', ['type' => 'section', 'title' => __('Location'), 'before' => 'address', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $field = 'address_area_id';
                    $userTableLabelAlias = 'Users';
                    $areaLabel = $this->onGetFieldLabel($event, $userTableLabelAlias, $field, $language, true);
                    $this->_table->field('address_area_section', ['type' => 'section', 'title' => $areaLabel, 'before' => $field, 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $field = 'birthplace_area_id';
                    $areaLabel = $this->onGetFieldLabel($event, $userTableLabelAlias, $field, $language, true);
                    $this->_table->field('birthplace_area_section', ['type' => 'section', 'title' => $areaLabel, 'before' => $field, 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $this->_table->field('other_information_section', ['type' => 'section', 'title' => __('Other Information'), 'after' => $field, 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                } else {
                    $this->_table->ControllerAction->field('information_section', ['type' => 'section', 'title' => __('Information'), 'before' => 'photo_content', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    //POCOR-5668 add identity section starts
                    $this->_table->field('identity_section', ['type' => 'section', 'title' => __('Identities / Nationalities'), 'after' => 'email', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $security_users_id = '';
                    $model = $this->_table;
                    if($this->_table->controller->request->params['pass'][0] == 'view'){
                        $security_users_id = $model->paramsDecode($this->_table->controller->request->params['pass']['1'])['id'];
                    }
                    if($security_users_id > 0){
                        $this->_table->field('details', [
                            'type' => 'element',
                            'after' => 'identity_section',
                            'element' => 'User.UserIdentities/details',
                            'visible' => ['view'=>true],
                            'data' => $this->getViewUserIdentities($security_users_id)
                        ]);
                    }
                    //POCOR-5668 add identity section ends
                    $this->_table->ControllerAction->field('location_section', ['type' => 'section', 'title' => __('Location'), 'before' => 'address', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $field = 'address_area_id';
                    $userTableLabelAlias = 'Users';
                    $areaLabel = $this->onGetFieldLabel($event, $userTableLabelAlias, $field, $language, true);
                    $this->_table->ControllerAction->field('address_area_section', ['type' => 'section', 'title' => $areaLabel, 'before' => $field, 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $field = 'birthplace_area_id';
                    $areaLabel = $this->onGetFieldLabel($event, $userTableLabelAlias, $field, $language, true);
                    $this->_table->ControllerAction->field('birthplace_area_section', ['type' => 'section', 'title' => $areaLabel, 'before' => $field, 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    $this->_table->ControllerAction->field('other_information_section', ['type' => 'section', 'title' => __('Other Information'), 'after' => $field, 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                }
            }
        }
    }

    //POCOR-5668 add identity section starts
    public function getViewUserIdentities($security_users_id)
    {
        $UserIdentities = TableRegistry::get('user_identities');
        $IdentityTypes = TableRegistry::get('identity_types');
        $UserNationalities = TableRegistry::get('user_nationalities');
        $Nationalities = TableRegistry::get('nationalities');
       
        $data = $UserIdentities->find()
                ->select([
                    $UserIdentities->aliasField('id'),
                    $UserIdentities->aliasField('identity_type_id'),
                    $IdentityTypes->aliasField('name'),
                    $UserIdentities->aliasField('number'),
                    $UserIdentities->aliasField('nationality_id'),
                    $Nationalities->aliasField('name'),
                    $UserNationalities->aliasField('preferred')
                ])
                ->leftJoin(
                    [$IdentityTypes->alias() => $IdentityTypes->table()], [
                        $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
                    ]
                )
                ->leftJoin(
                    [$UserNationalities->alias() => $UserNationalities->table()], [
                        $UserNationalities->aliasField('security_user_id = ') . $UserIdentities->aliasField('security_user_id'),
                        $UserNationalities->aliasField('nationality_id = ') . $UserIdentities->aliasField('nationality_id')
                    ]
                )
                ->leftJoin(
                    [$Nationalities->alias() => $Nationalities->table()], [
                        $Nationalities->aliasField('id = ') . $UserIdentities->aliasField('nationality_id')
                    ]
                )
                ->where([
                    $UserIdentities->aliasField('security_user_id') => $security_users_id,
                ])
                ->toArray();
        return $data;
    }
    //POCOR-5668 add identity section ends

    public function addBeforeAction(Event $event)
    {
        if ($this->_table->table() == 'security_users') {
            $this->_table->fields['is_student']['value'] = 0;
            $this->_table->fields['is_staff']['value'] = 0;
            $this->_table->fields['is_guardian']['value'] = 0;
        }
    }

    public function indexAfterAction(Event $event)
    {
        $plugin = $this->_table->controller->plugin;
        $name = $this->_table->controller->name;

        switch ($this->_table->alias()) {
            case 'Students':
                $imageDefault = 'kd-students';
                break;
            case 'Staff':
                $imageDefault = 'kd-staff';
                break;
            case 'Guardians':
                $imageDefault = 'kd-guardian';
                break;
            case 'Directories':
                $tableClass = get_class($this->_table);
                $userType = $tableClass::OTHER;
                $session = $this->_table->request->session();
                if ($session->check('Directories.advanceSearch.belongsTo.user_type')) {
                    $userType = $session->read('Directories.advanceSearch.belongsTo.user_type');
                }
                if ($userType == $tableClass::STUDENT) {
                    $imageDefault = 'kd-students';
                } else if ($userType == $tableClass::STAFF) {
                    $imageDefault = 'kd-staff';
                } else if ($userType == $tableClass::GUARDIAN) {
                    $imageDefault = 'kd-guardian';
                } else {
                    $imageDefault = 'fa fa-user';
                }
                break;
            default:
                $imageDefault = 'fa fa-user';
                break;
        }

        if ($this->isCAv4()) {
            switch ($this->_table->alias()) {
                case 'Guardians':
                    $imageUrl =  ['plugin' => 'Student', 'controller' => 'Students', 'action' => $this->_table->alias(), 'image'];

                    if ($name == 'Profiles') { // POCOR-1983 for profile guardian
                        $imageUrl =  ['plugin' => 'Profile', 'controller' => $name, 'action' => 'ProfileGuardians', 'image'];
                    }
                    break;
                case 'Students':
                    $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->_table->alias(), 'image'];

                    if ($name == 'Profiles') {
                        $imageUrl =  ['plugin' => 'Profile', 'controller' => $name, 'action' => 'ProfileStudents', 'image'];
                    } elseif ($name == 'Directories') {
                        $imageUrl =  ['plugin' => 'Directory', 'controller' => $name, 'action' => 'GuardianStudents', 'image'];
                    }
                    break;
                default:
                    $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->_table->alias(), 'image'];
                    break;
            }
        } else if ($this->_table->ControllerAction->getTriggerFrom() == 'Controller') {
            // for controlleraction->model
            $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => 'getImage'];
        } else {
            // for controlleraction->modelS
            $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->_table->alias(), 'getImage'];
        }

        if ($this->isCAv4()) {
            $this->_table->field('photo_content', ['type' => 'image', 'ajaxLoad' => true, 'imageUrl' => $imageUrl, 'imageDefault' => '"'.$imageDefault.'"', 'order' => 0]);

            // check if the openemis_no isset, if its set, the field got the sort field no need to sort = true
            $openemisNoAttr = ['type' => 'readonly', 'order' => 1];
            if (!isset($this->_table->fields['openemis_no']['sort'])) {
                $openemisNoAttr['sort'] = true;
            }

            $this->_table->field('openemis_no', $openemisNoAttr);
        } else {
            $this->_table->ControllerAction->field('photo_content', ['type' => 'image', 'ajaxLoad' => true, 'imageUrl' => $imageUrl, 'imageDefault' => '"'.$imageDefault.'"', 'order' => 0]);
            $this->_table->ControllerAction->field('openemis_no', [
                'type' => 'readonly',
                'order' => 1,
                'sort' => true
            ]);
        }

        if ($this->_table->table() == 'security_users') {
            if ($this->isCAv4()) {
                $this->_table->field('name', ['order' => 3, 'sort' => ['field' => 'first_name']]);
            } else {
                $this->_table->ControllerAction->field('name', [
                    'order' => 3,
                    'sort' => ['field' => 'first_name']
                ]);
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_search'] = false;
        // $extra['auto_contain'] = false;

        $table = $query->repository()->table();
        if ($table != 'security_users') {
            $query->matching('Users');

            $this->_table->fields['openemis_no']['sort'] = ['field' => 'Users.openemis_no'];
            $sortList = ['Users.openemis_no', 'Users.first_name'];
            if (array_key_exists('sortWhitelist', $extra['options'])) {
                $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
            }
            $extra['options']['sortWhitelist'] = $sortList;
        }
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $this->indexBeforeQuery($event, $query, $options);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity instanceof User) {
            $value = $entity->openemis_no;
        } else if ($entity->has('_matchingData')) {
            $value = $entity->_matchingData['Users']->openemis_no;
        } else if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        return $value;
    }

    public function onGetIdentity(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity instanceof User) {
            $value = $entity->default_identity_type;
        } else if ($entity->has('_matchingData')) {
            $value = $entity->_matchingData['Users']->default_identity_type;
        } else if ($entity->has('user')) {
            $value = $entity->user->default_identity_type;
        }
        return $value;
    }

    public function onGetName(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity instanceof User) {
            $value = $entity->name;
        } else if ($entity->has('_matchingData')) {
            $value = $entity->_matchingData['Users']->name;
        } else if ($entity->has('user')) {
            $value = $entity->user->name;
        }
        return $value;
    }

    public function onGetGenderId(Event $event, Entity $entity)
    {
        if ($entity->has('gender') && $entity->gender->name) {
            return __($entity->gender->name);
        }
    }

    public function onGetPhotoContent(Event $event, Entity $entity)
    {
        // check file name instead of file content
        $fileContent = null;
        $userEntity = null;
        if ($entity instanceof User) {
            $fileContent = $entity->photo_content;
            $userEntity = $entity;
        } else if ($entity->has('_matchingData')) {
            $fileContent = $entity->_matchingData['Users']->photo_content;
            $userEntity = $entity->_matchingData['Users'];
        } else if ($entity->has('user')) {
            $fileContent = $entity->user->photo_content;
            $userEntity = $entity->user;
        }

        $value = "";
        $alias = $this->_table->alias();
        if (empty($fileContent) && is_null($fileContent)) {
            if ($alias == 'Students' || $alias == 'StudentUser' || (($userEntity) && $userEntity->is_student)) {
                $value = $this->defaultStudentProfileIndex;
            } else if ($alias == 'Staff' || $alias == 'StaffUser' || (($userEntity) && $userEntity->is_staff)) {
                $value = $this->defaultStaffProfileIndex;
            } else if ($alias == 'Guardians' || $alias == 'GuardianUser' || (($userEntity) && $userEntity->is_guardian)) {
                $value = $this->defaultGuardianProfileIndex;
            } else {
                $value = $this->defaultUserProfileIndex;
            }
        } else {
            $value = base64_encode(stream_get_contents($fileContent));
        }
        return $value;
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
        // const STUDENT = 1;
        // const STAFF = 2;
        // const GUARDIAN = 3;
        // const OTHER = 4;
        $userType = 0;
        if (isset($this->_table->request->data[$this->_table->alias()]['user_type'])) {
            $userType = $this->_table->request->data[$this->_table->alias()]['user_type'];
        }
        $tableClass = get_class($this->_table);
        $value = '';
        $alias = $this->_table->alias();
        if ($alias == 'Students' || $alias == 'StudentUser' || ($alias == 'Directories' && $userType == $tableClass::STUDENT)) {
            $value = $this->defaultStudentProfileView;
        } else if ($alias == 'Staff' || $alias == 'StaffUser' || ($alias == 'Directories' && $userType == $tableClass::STAFF)) {
            $value = $this->defaultStaffProfileView;
        } else if ($alias == 'Guardians' || $alias == 'GuardianUser' || ($alias == 'Directories' && $userType == $tableClass::GUARDIAN)) {
            $value = $this->defaultGuardianProfileView;
        } else {
            $value = $this->defaultUserProfileView;
        }
        return $value;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        return $this->_table->onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function getUniqueOpenemisId($options = [])
    {
        $prefix = TableRegistry::get('Configuration.ConfigItems')->value('openemis_id_prefix');
        $prefix = explode(",", $prefix);
        $prefix = ($prefix[1] > 0)? $prefix[0]: '';
        
          $latest = $this->_table->find()
            ->order($this->_table->aliasField('id').' DESC')
            ->first();


        $latestOpenemisNo = $latest->openemis_no;
        $latestOpenemisNo = 0;
        if (empty($prefix)) {
            $latestDbStamp = $latestOpenemisNo;
        } else {
            $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
        }
        
         $currentStamp = time();

        if ($latestDbStamp >= $currentStamp) {
            $newStamp = $latestDbStamp + 1;
        } else {
            list($microSecond, $second) = explode(' ', microtime());
            $random = $second + $microSecond * 1000000;
            $newStamp = time() + str_pad(mt_rand(0, $random), 9, '0', STR_PAD_LEFT);
        }
        
        return $prefix.$newStamp;
    }

    public function getImage($id)
    {
        $base64Format = (array_key_exists('base64', $this->_table->controller->request->query))? $this->_table->controller->request->query['base64']: false;

        $this->_table->controller->autoRender = false;
        $this->_table->controller->ControllerAction->autoRender = false;

        $currModel = $this->_table;
        $photoData = $currModel->find()
            ->contain('Users')
            ->select(['Users.photo_content'])
            ->where([$currModel->aliasField($currModel->primaryKey()) => $id])
            ->first()
            ;

        if (!empty($photoData) && $photoData->has('Users') && $photoData->Users->has('photo_content')) {
            $phpResourceFile = $photoData->Users->photo_content;

            if ($base64Format) {
                echo base64_encode(stream_get_contents($phpResourceFile));
            } else {
                $this->_table->controller->response->type('jpg');
                $this->_table->controller->response->body(stream_get_contents($phpResourceFile));
            }
        }
    }

    private function trimFields($data)
    {
        $list = ['first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name'];
        foreach ($list as $value) {
            if (array_key_exists($value, $data) && strlen($data[$value]) > 0) {
                $data[$value] = trim($data[$value]);
            }
        }
    }
}
