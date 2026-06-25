<?php

namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use User\Model\Entity\User;
use Cake\I18n\I18n;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\ORM\Locator\TableLocator;
use Cake\Chronos\Chronos;
use Cake\Log\Log;
use Cake\Http\Client;


class UserBehavior extends Behavior
{
    //POCOR-9590: General-tab fields that, when edited, signal drift from the external registry
    const GENERAL_SYNC_FIELDS = ['first_name', 'middle_name', 'third_name', 'last_name', 'gender_id', 'date_of_birth'];

    //POCOR-9590: sync_status values — single source of truth for CakePHP layer
    const SYNC_STATUS_LOCAL   = 0; //POCOR-9590: never been synced with an external registry
    const SYNC_STATUS_SYNCED  = 1; //POCOR-9590: confirmed match with external registry
    const SYNC_STATUS_DRIFTED = 2; //POCOR-9590: was synced; General fields have changed since

    private $defaultStudentProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-students'></i></div></div>";
    private $defaultStaffProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
    private $defaultGuardianProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-guardian'></i></div></div>";
    private $defaultUserProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";

    private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";
    private $defaultStaffProfileView = "<div class='profile-image'><i class='kd-staff'></i></div>";
    private $defaultGuardianProfileView = "<div class='profile-image'><i class='kd-guardian'></i></div>";
    private $defaultUserProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";

    private $defaultImgIndexClass = "profile-image-thumbnail";
    private $defaultImgViewClass = "profile-image";
    private $photoMessage = 'Advisable photo dimension %width by %height';
    private $formatSupport = 'Format Supported: %s';
    private $defaultImgMsg = "<p>* %s <br>* %s</p>";

    public function initialize(array $config): void
    {
        if ($this->_table->getTable() == 'security_users') {
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

    public function implementedEvents(): array
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

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $dataArray = $data->getArrayCopy();
        if (array_key_exists($this->_table->getAlias(), $dataArray)) {
            if (array_key_exists('username', $dataArray[$this->_table->getAlias()])) {
                $data[$this->_table->getAlias()]['username'] = trim($dataArray[$this->_table->getAlias()]['username']);
            }
        }
    }

    public function onExcelGetStatus(EventInterface $event, Entity $entity)
    {
        if ($entity->status == 1) {
            return __('Active');
        } else {
            return __('Inactive');
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $this->trimFields($data);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // POCOR-9101 start
        if(trim($entity->email) == ''){
            $entity->email = null;
        }
        if(trim($entity->mobile_number) == ''){
            $entity->mobile_number = null;
        }
        // POCOR-9101 end
        if ($entity->isNew()) {
            $entity->preferred_language = 'en';
        } else {
            $dob = date('Y-m-d', strtotime($entity->date_of_birth));
            $dod = date('Y-m-d', strtotime($entity->date_of_death));
            if ($dob > $dod) {
                $entity->dod_range = "greater";
            }
        }

        //POCOR-9590: drift detection — Synced (1) → Not Synced (2) when any General field changes. Local (0) and Not Synced (2) stay where they are.
        //POCOR-9590: skip when sync_status itself is dirty — that means the SyncUser action just set it to 1, the field changes ARE the sync, don't immediately flip back to 2
        if ($this->_table->getTable() === 'security_users' && !$entity->isNew() && (int)$entity->sync_status === self::SYNC_STATUS_SYNCED && !$entity->isDirty('sync_status')) {
            foreach (self::GENERAL_SYNC_FIELDS as $f) { //POCOR-9590
                if ($entity->isDirty($f)) {
                    $entity->sync_status = self::SYNC_STATUS_DRIFTED;
                    break;
                }
            }
        }

        //POCOR-9590: inception sync — new user created from external search (external_reference populated) starts as Synced
        if ($this->_table->getTable() === 'security_users' && $entity->isNew() && !empty($entity->external_reference)) {
            $entity->sync_status = self::SYNC_STATUS_SYNCED;
        }
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion == '4.0';
    }

    public function beforeAction(EventInterface $event)
    {
        //POCOR-9590: hide system-managed sync_status from view/edit/add — the visual indicator lives in the Identities-tab badge
        if (isset($this->_table->fields['sync_status'])) {
            $this->_table->fields['sync_status']['visible'] = false;
        }
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $configData = $ConfigItems->find('all', ['conditions' => ['name LIKE' => '%' . 'Date of Death' . '%']])->first();
        $schema = $this->_table->getSchema();
        $columns = $schema->columns();
        switch ($this->_table->getTable()) {
            case 'institution_students':
            case 'institution_staff':
            case 'student_guardians':
                break;
            default:
                $this->_table->fields['username']['visible'] = false;
                $this->_table->fields['last_login']['visible'] = false;
                //POCOR-8660 start
                $this->_table->fields['failed_logins']['visible'] = false;
                $this->_table->fields['email']['visible'] =  true;
                $this->_table->fields['mobile_number']['visible'] =  true;
                //POCOR-8660 end

                break;
        }
        if ($this->_table->getTable() == 'security_users') {
            $this->_table->addBehavior('OpenEmis.Section');
            // $table = new $this->_table;
            // $this->_table->fields = $table->getSchema()->columns();
            // $this->_table->fields = $this->_table->getFields();

            // // Access the table schema
            // $schema = $this->_table->getSchema();

            // // Get the field information
            // $fieldInfo = $schema->getColumn('is_student');

            // // Modify the type to 'hidden'
            // $fieldInfo['type'] = 'hidden';

            // // Update the schema
            // $schema->addColumn('is_student', $fieldInfo);
            // echo "<pre>"; print_r($fieldInfo);
            // die;
            $this->_table->fields['is_student']['type'] = 'hidden';
            $this->_table->fields['is_staff']['type'] = 'hidden';
            $this->_table->fields['is_guardian']['type'] = 'hidden';
            $this->_table->fields['photo_name']['visible'] = false;
            $this->_table->fields['super_admin']['visible'] = false;
            if ($configData->value == 1) {
                $this->_table->fields['date_of_death']['visible'] = ['index' => false, 'view' => true, 'edit' => true, 'add' => false];
            } else {
                $this->_table->fields['date_of_death']['visible'] = ['index' => false, 'view' => false, 'edit' => false, 'add' => false];
            }
            $this->_table->fields['external_reference']['visible'] = false;
            $this->_table->fields['status']['visible'] = false;
            $this->_table->fields['preferred_language']['visible'] = false;
            $this->_table->fields['address_area_id']['type'] = 'areapicker';
            $this->_table->fields['address_area_id']['source_model'] = 'Area.AreaAdministratives';
            $this->_table->fields['birthplace_area_id']['type'] = 'areapicker';
            $this->_table->fields['birthplace_area_id']['source_model'] = 'Area.AreaAdministratives';
            $this->_table->fields['gender_id']['type'] = 'select';
            $this->_table->fields['nationality_id']['visible'] = ['index' => false, 'view' => false, 'edit' => false, 'add' => false];
            $this->_table->fields['identity_type_id']['visible'] = ['index' => false, 'view' => false, 'edit' => false, 'add' => false];
            $this->_table->fields['identity_number']['visible'] = ['index' => false, 'view' => false, 'edit' => false, 'add' => false];

            $i = 10;
            $this->_table->fields['photo_content']['visible'] = true;
            $this->_table->fields['first_name']['order'] = $i++;
            $this->_table->fields['middle_name']['order'] = $i++;
            $this->_table->fields['third_name']['order'] = $i++;
            $this->_table->fields['last_name']['order'] = $i++;
            $this->_table->fields['preferred_name']['order'] = $i++;
            $this->_table->fields['gender_id']['order'] = $i++;
            // POCOR-8286 date format start
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $systemDateFormat = $ConfigItems->value('date_format');
            $phpToDatepickerFormat = [
                'd' => 'dd',
                'j' => 'd',
                'D' => 'D',        // Mon, Tue (short day)
                'l' => 'DD',       // Monday, Tuesday (full day)
                'm' => 'mm',
                'n' => 'm',
                'M' => 'M',        // Jan, Feb (short month)
                'F' => 'MM',       // January, February (full month)
                'y' => 'yy',       // 2-digit year
                'Y' => 'yyyy'      // 4-digit year
            ];

            $datepickerFormat = preg_replace_callback('/[a-zA-Z]/', function ($matches) use ($phpToDatepickerFormat) {
                return $phpToDatepickerFormat[$matches[0]] ?? $matches[0];
            }, $systemDateFormat);
            // POCOR-8286 date format end
            if ($this->isCAv4()) {

                $this->_table->field('date_of_birth', [
                    'date_options' => [
                        'format' => $datepickerFormat, // POCOR-8286
                        'endDate' => date('d-m-Y')
                    ],
                    'default_date' => false,
                ]);
            } else {
                $this->_table->ControllerAction->field(
                    'date_of_birth',
                    [
                        'date_options' => [
                            'format' => $datepickerFormat, // POCOR-8286
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
            $this->_table->fields['mobile_number']['order'] = $i++;

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

            // edit page, email = editable - POCOR-7124
            if ($this->_table->action == 'edit') {
                if ($this->isCAv4()) {
                    $this->_table->field('email', ['type' => 'string', 'after' => 'gender_id']);
                    $this->_table->field('mobile_number', ['type' => 'string', 'after' => 'email']);
                    $this->_table->field('date_of_death', ['type' => 'date', 'after' => 'date_of_birth']); //POCOR-7982
                } else {
                    $this->_table->ControllerAction->field('email', ['type' => 'string', 'after' => 'gender_id']);  //POCOR-6833
                    $this->_table->field('mobile_number', ['type' => 'string', 'after' => 'email']);
                    $this->_table->ControllerAction->field('date_of_death', ['type' => 'date', 'after' => 'date_of_birth']); //POCOR-7982
                }
            }

            //POCOR-7982
            if ($this->_table->action == 'view') {

                if ($this->isCAv4()) {
                    $this->_table->field('date_of_death', ['type' => 'date', 'after' => 'date_of_birth']);
                } else {
                    $this->_table->ControllerAction->field('date_of_death', ['type' => 'date', 'after' => 'date_of_birth']);
                }
            }
            //POCOR-7982
            // add page, email = hidden
            if ($this->_table->action == 'add') {
                if ($this->isCAv4()) {
                    $this->_table->field('email', ['type' => 'string', 'label' => __('Mobile')]);
                    $this->_table->field('mobile_number', ['type' => 'string', 'label' => __('Mobile')]);
                } else {
                    $this->_table->ControllerAction->field('email', ['type' => 'string', 'label' => __('Email')]);
                    $this->_table->ControllerAction->field('mobile_number', ['type' => 'string', 'label' => __('Mobile')]);
                }
            }
                
            if ($this->_table->getRegistryAlias() != 'Security.Users') {
                $language = I18n::getLocale();
                if ($this->isCAv4()) {
                    $this->_table->field('information_section', ['type' => 'section', 'title' => __('Information'), 'before' => 'photo_content', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
                    //POCOR-5668 add identity section starts
                    $this->_table->field('identity_section', ['type' => 'section', 'title' => __('Identities / Nationalities'), 'after' => 'mobile_number', 'visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => true]]);
                    $security_users_id = '';
                    $model = $this->_table;
                    if ($this->_table->controller->getRequest()->getAttribute('params')['pass'][0] == 'view') {
                        $security_users_id = $model->paramsDecode($this->_table->controller->getRequest()->getAttribute('params')['pass'][1]);
                        if (count($security_users_id) >= 1) {
                            $security_users_id = $security_users_id['user_id'];
                            //POCOR-9603[START]
                            if(empty($security_users_id)){
                                $security_users_id = $model->paramsDecode($this->_table->controller->getRequest()->getAttribute('params')['pass'][1]);
                                $security_users_id = $security_users_id['id'];
                            }
                            //POCOR-9603[END]
                        }
                    }
                    if ($security_users_id > 0) {
                        $this->_table->field('details', [
                            'type' => 'element',
                            'after' => 'identity_section',
                            'element' => 'User.UserIdentities/details',
                            'visible' => ['view' => true],
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
                    $this->_table->field('identity_section', ['type' => 'section', 'title' => __('Identities / Nationalities'), 'after' => 'mobile_number', 'visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => true]]);
                    $security_users_id = '';
                    $model = $this->_table;
                    if ($this->_table->controller->getRequest()->getAttribute('params')['pass'][0] == 'view') {
                        $security_users_id = $model->paramsDecode($this->_table->controller->getRequest()->getAttribute('params')['pass'][1]);
                        if (count($security_users_id) >= 1) {
                            $security_users_id = $security_users_id['user_id'];
                        }
                    }
                    if ($security_users_id > 0) {
                        $this->_table->field('details', [
                            'type' => 'element',
                            'after' => 'identity_section',
                            'element' => 'User.UserIdentities/details',
                            'visible' => ['view' => true],
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
        $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
        $IdentityTypes = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes')->setAlias('identity_types');
        $UserNationalities = TableRegistry::getTableLocator()->get('User.UserNationalities')->setAlias('user_nationalities');
        $Nationalities = TableRegistry::getTableLocator()->get('FieldOption.Nationalities')->setAlias('nationalities');

        $data = $UserIdentities->find()
            ->select([
                $UserIdentities->aliasField('id'),
                $UserIdentities->aliasField('identity_type_id'),
                $IdentityTypes->aliasField('name'),
                $UserIdentities->aliasField('number'),
                $UserIdentities->aliasField('nationality_id'),
                $Nationalities->aliasField('name'),
                $UserIdentities->aliasField('preferred')
            ])
            ->leftJoin(
                [$IdentityTypes->getAlias() => $IdentityTypes->getTable()],
                [
                    $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
                ]
            )
            ->leftJoin(
                [$UserNationalities->getAlias() => $UserNationalities->getTable()],
                [
                    $UserNationalities->aliasField('security_user_id = ') . $UserIdentities->aliasField('security_user_id'),
                    $UserNationalities->aliasField('nationality_id = ') . $UserIdentities->aliasField('nationality_id')
                ]
            )
            ->leftJoin(
                [$Nationalities->getAlias() => $Nationalities->getTable()],
                [
                    $Nationalities->aliasField('id = ') . $UserIdentities->aliasField('nationality_id')
                ]
            )
            ->where([
                $UserIdentities->aliasField('security_user_id') => $security_users_id,
            ])
            ->toArray();

        //POCOR-9590: stored sync_status on the user (0=Local, 1=Synced, 2=Not Synced)
        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $userRow = $SecurityUsers->find()->select(['sync_status'])->where(['id' => $security_users_id])->first();
        $syncStatus = $userRow ? (int)$userRow->sync_status : self::SYNC_STATUS_LOCAL;

        //POCOR-9590: identity_type_id of the currently active external data source — used to decide which row in the Identities table is sync-eligible
        $activeIdentityTypeId = $this->getActiveExternalSourceIdentityTypeId();

        return [
            'data' => $data,
            'sync_status' => $syncStatus,
            'active_source_identity_type_id' => $activeIdentityTypeId,
        ];
    }

    //POCOR-9590: returns identity_type_id of the (first) enabled external data source whose
    //identity_type_id attribute is configured, or null if no source is enabled / no identity_type set.
    //
    //OpenEMIS represents enabled external sources as per-source rows in config_items:
    //   type = 'External Data Source - Identity', value = '1' (enabled), name = 'Seychelles Civil Status' / 'OpenEMIS Core' / 'UNHCR' / ...
    //There is no separate single-active-source pointer row to query — earlier revisions of this helper
    //expected one (code='external_data_source_type'), which only exists on instances where the admin
    //has saved the legacy "External Data Source" config form. On a fresh install (and on the dmo-dev
    //remote we tested against) that row is absent, so the badge silently never lit up. The fix is to
    //read the same per-source enable flags the wizards already use.
    //
    //Public so the 3 user-tables (StudentUser / StaffUser / Directories) can call it via behavior __call proxy.
    public function getActiveExternalSourceIdentityTypeId()
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $enabledSourceNames = $ConfigItems->find()
            ->where([
                'type' => 'External Data Source - Identity',
                'value' => '1',
            ])
            ->extract('name')
            ->toArray();
        if (empty($enabledSourceNames)) {
            return null;
        }
        $ExternalAttrs = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        $row = $ExternalAttrs->find()
            ->where([
                'external_data_source_type IN' => $enabledSourceNames,
                'attribute_field' => 'identity_type_id',
                'value IS NOT' => null,
                'value !=' => '',
            ])
            ->first();
        return ($row && !empty($row->value)) ? (int)$row->value : null;
    }

    //POCOR-9590: a user is sync-eligible iff an external source is active AND
    //   (a) they already have an external_reference (synced before, can re-sync), OR
    //   (b) they have at least one preferred user_identities row (Local user — first-time inception sync).
    //   The source-side `identity_type_id` attribute is intentionally NOT consulted here: it is enforced
    //   at sync-execution time inside buildExternalUserDiff(), so a missing config row never silently
    //   hides the button. This keeps the feature usable even on instances whose UI form omits the field.
    public function isSyncEligibleUser($securityUserId): bool
    {
        if (!$this->hasActiveExternalSource()) {
            return false;
        }
        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $user = $SecurityUsers->find()
            ->select(['external_reference'])
            ->where(['id' => $securityUserId])
            ->first();
        if ($user && !empty($user->external_reference)) {
            return true;
        }
        $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
        return $UserIdentities->find()
            ->where(['security_user_id' => $securityUserId, 'preferred' => 1])
            ->count() > 0;
    }

    //POCOR-9590: thin probe used by isSyncEligibleUser — returns true iff at least one
    //External Data Source Identity is enabled. Uses the same per-source enable convention
    //(type='External Data Source - Identity', value='1') as the wizards, so the badge
    //works on fresh installs without an admin needing to first save the legacy form.
    private function hasActiveExternalSource(): bool
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        return $ConfigItems->find()
            ->where([
                'type' => 'External Data Source - Identity',
                'value' => '1',
            ])
            ->count() > 0;
    }
    //POCOR-9590: fetches and diffs a user against the active external identity source.
    //Returns an array on success or a plain-string error message on failure — callers check is_string($result).
    public function buildExternalUserDiff(int $userId): array|string
    {
        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $ExternalAttrs = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        $ConfigItems   = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $Genders       = TableRegistry::getTableLocator()->get('User.Genders');

        $user = $SecurityUsers->get($userId, ['contain' => ['Genders']]);

        //POCOR-9590: pick the enabled External Data Source Identity whose configured
        //identity_type_id matches the user's preferred identity. Per-source enable flag is
        //(type='External Data Source - Identity', value='1', name=source label) — same
        //convention the wizards use.
        $enabledSourceNames = $ConfigItems->find()
            ->where([
                'type' => 'External Data Source - Identity',
                'value' => '1',
            ])
            ->extract('name')
            ->toArray();
        if (empty($enabledSourceNames)) {
            return 'No external identity source is enabled.';
        }

        $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
        $preferredIdentity = $UserIdentities->find()
            ->select(['identity_type_id'])
            ->where(['security_user_id' => $userId, 'preferred' => 1])
            ->first();
        $preferredTypeId = $preferredIdentity ? (int)$preferredIdentity->identity_type_id : null;

        //Match enabled source by its configured identity_type_id attribute; fall back to the first
        //source that has any attribute rows if no semantic match is found.
        $configs = [];
        $sourceName = null;
        if ($preferredTypeId) {
            $matchRow = $ExternalAttrs->find()
                ->where([
                    'external_data_source_type IN' => $enabledSourceNames,
                    'attribute_field' => 'identity_type_id',
                    'value' => (string)$preferredTypeId,
                ])
                ->first();
            if ($matchRow) {
                $sourceName = $matchRow->external_data_source_type;
                $configs = $ExternalAttrs->find()
                    ->where(['external_data_source_type' => $sourceName])
                    ->all()
                    ->combine('attribute_field', 'value')
                    ->toArray();
            }
        }
        if (empty($configs)) {
            foreach ($enabledSourceNames as $candidate) {
                $candidateConfigs = $ExternalAttrs->find()
                    ->where(['external_data_source_type' => $candidate])
                    ->all()
                    ->combine('attribute_field', 'value')
                    ->toArray();
                if (!empty($candidateConfigs)) {
                    $configs = $candidateConfigs;
                    $sourceName = $candidate;
                    break;
                }
            }
        }
        if (empty($configs)) {
            return 'No external identity source has attributes configured.';
        }

        $tokenUrl     = $configs['token_uri'] ?? null;
        //POCOR-9590: fall back to api_url for sources (e.g. Seychelles) that store the endpoint there instead of user_endpoint_uri
        $userEndpoint = $configs['user_endpoint_uri'] ?: ($configs['api_url'] ?? null);
        if ($userEndpoint && strpos($userEndpoint, '{external_reference}') === false) {
            $userEndpoint = rtrim($userEndpoint, '/') . '/{external_reference}';
        }
        $clientId   = $configs['client_id'] ?? null;
        //POCOR-9590: OAuth2 client_credentials grant uses client_secret (Seychelles, plain OAuth).
        //JWT-bearer grants use the long signed-assertion private_key (legacy OpenEMIS Core).
        //Pick by grant_type so the right secret reaches the IdP, regardless of which extra
        //fields happen to be filled on the source row.
        $grantType  = $configs['grant_type'] ?? '';
        $privateKey = ($grantType === 'client_credentials')
            ? ($configs['client_secret'] ?? ($configs['private_key'] ?? null))
            : ($configs['private_key'] ?? ($configs['client_secret'] ?? null));

        if (!$tokenUrl || !$userEndpoint) {
            return 'External identity source is not configured.';
        }

        //POCOR-9590: OAuth2 client_credentials — form-encoded + scope required by Seychelles Civil Status
        $http          = new Client();
        $tokenResponse = $http->post($tokenUrl, [
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $privateKey,
            'scope'         => $configs['scope'] ?? ($configs['scopes'] ?? ''),
        ], ['headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);

        if (!$tokenResponse->isOk()) {
            return 'Failed to authenticate with external identity source.';
        }
        $accessToken = $tokenResponse->getJson()['access_token'] ?? null;
        if (!$accessToken) {
            return 'External identity source did not return a valid token.';
        }

        //POCOR-9590: external_reference is set on users created via External Search; fall back to preferred identity number for users added manually
        $externalRef = $user->external_reference;
        if (empty($externalRef)) {
            $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
            $idRow = $UserIdentities->find()
                ->where(['security_user_id' => $userId, 'identity_type_id' => $configs['identity_type_id'] ?? 0, 'preferred' => 1])
                ->first();
            $externalRef = $idRow ? $idRow->number : null;
        }
        if (empty($externalRef)) {
            return 'No external reference found for this user.';
        }

        $apiUrl      = str_replace('{external_reference}', $externalRef, $userEndpoint);
        $apiResponse = $http->get($apiUrl, [], ['headers' => ['Authorization' => 'Bearer ' . $accessToken]]);
        if (!$apiResponse->isOk()) {
            return 'Failed to retrieve data from external identity source.';
        }

        //POCOR-9590: shared lenient mapper — same code path the add-from-external wizard uses.
        //Seychelles returns name keys with inconsistent casing and the source row often omits the
        //first_name/last_name mappings, so pass the well-known Seychelles defaults (mapper matching
        //is case-insensitive). Without this, sync left first_name/last_name empty / hard-failed.
        $apiData  = $apiResponse->getJson();
        $defaults = ($sourceName === 'Seychelles Civil Status')
            ? \User\Lib\ExternalIdentityMapper::SEYCHELLES_DEFAULT_MAPPINGS
            : [];
        ['mapped' => $externalValues, 'missing' => $missingMappings] = \User\Lib\ExternalIdentityMapper::map($apiData, $configs, $defaults);
        if (!empty($missingMappings)) {
            $missingDetail = implode(', ', array_map(fn($f, $p) => "$f→$p", array_keys($missingMappings), $missingMappings));
            return 'External source response is missing keys for configured mappings: ' . $missingDetail;
        }

        $externalGenderId = null;
        if (!empty($externalValues['gender'])) {
            $genderRow        = $Genders->find()->where(['name' => $externalValues['gender']])->first();
            $externalGenderId = $genderRow ? $genderRow->id : null;
        }

        $userDob = $user->date_of_birth instanceof \DateTimeInterface
            ? $user->date_of_birth->format('Y-m-d')
            : (string)$user->date_of_birth;

        $diff = [];
        foreach (['first_name', 'middle_name', 'third_name', 'last_name'] as $field) {
            if (isset($externalValues[$field]) && (string)$externalValues[$field] !== (string)$user->$field) {
                $diff[$field] = ['current' => $user->$field, 'external' => $externalValues[$field]];
            }
        }
        if (isset($externalValues['date_of_birth']) && $externalValues['date_of_birth'] !== '' && $externalValues['date_of_birth'] !== $userDob) {
            $diff['date_of_birth'] = ['current' => $userDob, 'external' => $externalValues['date_of_birth']];
        }
        if ($externalGenderId && $externalGenderId !== $user->gender_id) {
            $diff['gender'] = [
                'current'  => $user->has('gender') ? $user->gender->name : '',
                'external' => $externalValues['gender'],
            ];
        }

        return compact('user', 'configs', 'externalValues', 'externalGenderId', 'diff');
    }

    //POCOR-9590: applies external values onto $user and marks sync_status=SYNCED; caller is responsible for saving
    public function applySyncToUser(Entity $user, array $externalValues, ?int $externalGenderId): void
    {
        foreach (['first_name', 'middle_name', 'third_name', 'last_name'] as $field) {
            if (isset($externalValues[$field])) {
                $user->$field = $externalValues[$field];
            }
        }
        if (!empty($externalValues['date_of_birth'])) {
            $user->date_of_birth = $externalValues['date_of_birth'];
        }
        if ($externalGenderId) {
            $user->gender_id = $externalGenderId;
        }
        $user->sync_status = self::SYNC_STATUS_SYNCED;
    }


    //POCOR-5668 add identity section ends

    public function addBeforeAction(EventInterface $event)
    {
        if ($this->_table->getTable() == 'security_users') {
            $this->_table->fields['is_student']['value'] = 0;
            $this->_table->fields['is_staff']['value'] = 0;
            $this->_table->fields['is_guardian']['value'] = 0;
        }
    }

    public function indexAfterAction(EventInterface $event)
    {
        $plugin = $this->_table->controller->getPlugin();
        $name = $this->_table->controller->getName();

        switch ($this->_table->getAlias()) {
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
                $session = $this->_table->request->getSession();
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
            switch ($this->_table->getAlias()) {
                case 'Guardians':
                    $imageUrl =  ['plugin' => 'Student', 'controller' => 'Students', 'action' => $this->_table->getAlias(), 'image'];

                    if ($name == 'Profiles') { // POCOR-1983 for profile guardian
                        $imageUrl =  ['plugin' => 'Profile', 'controller' => $name, 'action' => 'ProfileGuardians', 'image'];
                    }
                    break;
                case 'Students':
                    $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->_table->getAlias(), 'image'];

                    if ($name == 'Profiles') {
                        $imageUrl =  ['plugin' => 'Profile', 'controller' => $name, 'action' => 'ProfileStudents', 'image'];
                    } elseif ($name == 'Directories') {
                        $imageUrl =  ['plugin' => 'Directory', 'controller' => $name, 'action' => 'GuardianStudents', 'image'];
                    }
                    break;
                default:
                    $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->_table->getAlias(), 'image'];
                    break;
            }
        } else if ($this->_table->ControllerAction->getTriggerFrom() == 'Controller') {
            // for controlleraction->model
            $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => 'getImage'];
        } else {
            // for controlleraction->modelS
            $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->_table->getAlias(), 'getImage'];
        }

        if ($this->isCAv4()) {
            $this->_table->field('photo_content', ['type' => 'image', 'ajaxLoad' => true, 'imageUrl' => $imageUrl, 'imageDefault' => '"' . $imageDefault . '"', 'order' => 0]);

            // check if the openemis_no isset, if its set, the field got the sort field no need to sort = true
            $openemisNoAttr = ['type' => 'readonly', 'order' => 1];
            if (!isset($this->_table->fields['openemis_no']['sort'])) {
                $openemisNoAttr['sort'] = true;
            }

            $this->_table->field('openemis_no', $openemisNoAttr);
        } else {
            $this->_table->ControllerAction->field('photo_content', ['type' => 'image', 'ajaxLoad' => true, 'imageUrl' => $imageUrl, 'imageDefault' => '"' . $imageDefault . '"', 'order' => 0]);
            $this->_table->ControllerAction->field('openemis_no', [
                'type' => 'readonly',
                'order' => 1,
                'sort' => true
            ]);
        }

        if ($this->_table->getTable() == 'security_users') {
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_search'] = false;
        // $extra['auto_contain'] = false;
        $table = $query->getRepository()->getTable();
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

    public function indexBeforePaginate(EventInterface $event, Request $request, Query $query, ArrayObject $options)
    {
        $this->indexBeforeQuery($event, $query, $options);
    }

    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
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

    public function onGetIdentity(EventInterface $event, Entity $entity)
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

    public function onGetName(EventInterface $event, Entity $entity)
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

    public function onGetGenderId(EventInterface $event, Entity $entity)
    {
        if ($entity->has('gender') && $entity->gender->name) {
            return __($entity->gender->name);
        }
    }

    public function onGetPhotoContent(EventInterface $event, Entity $entity)
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
        $alias = $this->_table->getAlias();
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
        if (isset($this->_table->request->data[$this->_table->getAlias()]['user_type'])) {
            $userType = $this->_table->request->data[$this->_table->getAlias()]['user_type'];
        }
        $tableClass = get_class($this->_table);
        $value = '';
        $alias = $this->_table->getAlias();
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'email') {
            return __('Email');
        } elseif ($field == 'mobile_number') {
            return __('Mobile Number');
        } else {
            return $this->_table->onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function getUniqueOpenemisId($options = [])
    {
        $prefix = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('openemis_id_prefix');
        $prefix = explode(",", $prefix);
        $prefix = ($prefix[1] > 0) ? $prefix[0] : '';

        $latest = $this->_table->find()
            ->order($this->_table->aliasField('id') . ' DESC')
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

        return $prefix . $newStamp;
    }

    public function getImage($id)
    {
        $base64Format = (array_key_exists('base64', $this->_table->controller->request->query)) ? $this->_table->controller->request->query['base64'] : false;

        $this->_table->controller->autoRender = false;
        $this->_table->controller->ControllerAction->autoRender = false;

        $currModel = $this->_table;
        $photoData = $currModel->find()
            ->contain('Users')
            ->select(['Users.photo_content'])
            ->where([$currModel->aliasField($currModel->getPrimaryKey()) => $id])
            ->first();

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
            if (isset($data[$value]) && strlen($data[$value]) > 0) { // POCOR-8446
                $data[$value] = trim($data[$value]);
            }
        }
        // POCOR-8286 start
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $systemDateFormat = $ConfigItems->value('date_format');
        try {
            $dob = $data['date_of_birth'] ?? null;
            if ($dob) {
                $date = Chronos::createFromFormat($systemDateFormat, $dob);
                $data['date_of_birth'] = $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            Log::warning("Invalid date: " . $data['date_of_birth'] . ' with format ' . $systemDateFormat);
        }
        // POCOR-8286 end
    }
}
