<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\I18n\I18n;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use Cake\Network\Session;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Institution\Model\Behavior\LatLongBehavior as LatLongOptions;

class InstitutionStatusTable extends ControllerActionTable
{ 
    use OptionsTrait;

    private $withdrawStudents = [
        1 => ['id' => 1, 'name' => 'Yes'],
        2 => ['id' => 0, 'name' => 'No'],
    ];

    private $defaultLogoView = "<div class='profile-image'><i class='fa kd-institutions'></i></div>";
    private $defaultImgIndexClass = "logo-thumbnail";
    private $defaultImgViewClass= "logo-image";
    private $photoMessage = 'Advisable logo dimension %width by %height';
    private $formatSupport = 'Format Supported: %s';
    private $defaultImgMsg = "<p>* %s <br>* %s</p>";

    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);
        /**
         * fieldOption tables
         */
        $this->belongsTo('Localities', ['className' => 'Institution.Localities', 'foreignKey' => 'institution_locality_id']);
        $this->belongsTo('Types', ['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
        $this->belongsTo('Ownerships', ['className' => 'Institution.Ownerships', 'foreignKey' => 'institution_ownership_id']);
        $this->belongsTo('Statuses', ['className' => 'Institution.Statuses', 'foreignKey' => 'institution_status_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);
        $this->belongsTo('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Genders', ['className' => 'Institution.Genders', 'foreignKey' => 'institution_gender_id']);
        /**
         * end fieldOption tables
         */

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

        $this->hasMany('InstitutionActivities', ['className' => 'Institution.InstitutionActivities', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAttachments', ['className' => 'Institution.InstitutionAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'location_institution_id']);
        $this->hasMany('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        // Note: InstitutionClasses already cascade deletes 'InstitutionSubjectStudents' - dependent and cascade not neccessary
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('Staff', ['className' => 'Institution.Staff', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffTransferIn', ['className' => 'Institution.StaffTransferIn', 'foreignKey' => 'new_institution_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffTransferOut', ['className' => 'Institution.StaffTransferOut', 'foreignKey' => 'previous_institution_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffReleaseIn', ['className' => 'Institution.StaffTransferIn', 'foreignKey' => 'new_institution_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffRelease', ['className' => 'Institution.StaffRelease', 'foreignKey' => 'previous_institution_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('Students', ['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentBehaviours', ['className' => 'Institution.StudentBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('SecurityGroups', [
            'className' => 'Security.SystemGroups',
            'joinTable' => 'security_group_institutions',
            'foreignKey' => 'institution_id',
            'targetForeignKey' => 'security_group_id',
            'through' => 'Security.SecurityGroupInstitutions',
            'dependent' => true
        ]);

        $this->addBehavior('CustomField.Record', [
            'fieldKey' => 'institution_custom_field_id',
            'tableColumnKey' => 'institution_custom_table_column_id',
            'tableRowKey' => 'institution_custom_table_row_id',
            'fieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFields'],
            'formKey' => 'institution_custom_form_id',
            'filterKey' => 'institution_custom_filter_id',
            'formFieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFields'],
            'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
            'recordKey' => 'institution_id',
            'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);

        $this->addBehavior('Year', ['date_opened' => 'year_opened', 'date_closed' => 'year_closed']);
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'logo_name',
            'content' => 'logo_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'image',
            'useDefaultName' => true
        ]);

    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        
        $validator
        ->add('date_opened', [
            'ruleCompare' => [
                'rule' => ['comparison', 'notequal', '0000-00-00'],
            ]
        ])
        ->allowEmpty('date_closed')
        ->add('date_opened', 'ruleLessThanToday', [
            'rule' => ['lessThanToday', true]
        ])
        ->add('date_closed', 'ruleCompareDateReverse', [
            'rule' => ['compareDateReverse', 'date_opened', true]
        ])
        ->allowEmpty('area_id')
        ->allowEmpty('institution_locality_id')
        ->allowEmpty('institution_type_id')
        ->allowEmpty('institution_ownership_id')
        ->allowEmpty('institution_sector_id')
        ->allowEmpty('institution_provider_id')
        ->allowEmpty('institution_gender_id');
        
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        // set action for webhook
        $this->webhookAction = $this->action;
        
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Institutions', 'index'];
        $this->field('security_group_id', ['visible' => false]);
        // $this->field('institution_site_area_id', ['visible' => false]);
        $this->field('date_opened');
        $this->field('date_closed');
        $this->field('modified', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);

        $this->field('institution_locality_id', ['type' => 'select']);
        $this->field('institution_ownership_id', ['type' => 'select']);
        $this->field('institution_status_id');
        $this->field('institution_sector_id', ['type' => 'select', 'onChangeReload' => true]);
        if ($this->action == 'index' || $this->action == 'view') {
            $this->field('contact_person', ['visible' => false]);
            $this->field('institution_provider_id', ['type' => 'select']);
        }
        $this->field('institution_type_id');
        $this->field('institution_gender_id', ['type' => 'select']);
        $this->field('area_administrative_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives', 'displayCountry' => false]);
        $this->field('area_id', ['type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => false]);

        $this->field('information_section', ['type' => 'section', 'title' => __('Information')]);

        $this->field('shift_section', ['type' => 'section', 'title' => __('Shifts'), 'visible' => ['view'=>true]]);
        $this->field('shift_type', ['visible' => ['view' => true]]);

        $this->field('shift_details', [
            'type' => 'element',
            'element' => 'Institution.Shifts/details',
            'visible' => ['view'=>true],
            'data' => $this->getViewShiftDetail($this->Session->read('Institution.Institutions.id'), $this->InstitutionShifts->AcademicPeriods->getCurrent())
        ]);

        $this->field('location_section', ['type' => 'section', 'title' => __('Location')]);

        $language = I18n::locale();
        $field = 'area_id';
        $areaLabel = $this->onGetFieldLabel($event, $this->alias(), $field, $language, true);
        $this->field('area_section', ['type' => 'section', 'title' => $areaLabel]);
        $field = 'area_administrative_id';
        $areaAdministrativesLabel = $this->onGetFieldLabel($event, $this->alias(), $field, $language, true);
        $this->field('area_administrative_section', ['type' => 'section', 'title' => $areaAdministrativesLabel]);
        $this->field('contact_section', ['type' => 'section', 'title' => __('Contact'), 'after' => $field]);
        $this->field('other_information_section', ['type' => 'section', 'title' => __('Other Information'), 'after' => 'website', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
        //pocor-5669
        $this->field('longitude', ['visible' => ['view' => false]]);
        $this->field('latitude', ['visible' => ['view' => false]]);
        //pocor-5669
        if (strtolower($this->action) != 'index') {
            $this->Navigation->addCrumb($this->getHeader($this->action));
        }

        if ($this->action == 'edit') {
            // Moved to InstitutionContacts
            $this->field('contact_section', ['visible' => false]);
            $this->field('contact_person', ['visible' => false]);
            $this->field('telephone', ['visible' => false]);
            $this->field('fax', ['visible' => false]);
            $this->field('email', ['visible' => false]);
            $this->field('website', ['visible' => false]);
        }

        $this->field('logo_name', ['visible' => false]);
        if ($this->action != 'index') {
            $this->field('logo_content', ['type' => 'image']);
        }

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $LatLongPermission = $ConfigItems->value("latitude_longitude");

        if ($LatLongPermission == LatLongOptions::EXCLUDED) {
            $this->field('longitude', ['visible' => false]);
            $this->field('latitude', ['visible' => false]);
        }

        if ($this->controllerAction == 'InstitutionStatus' && $this->webhookAction == 'edit') {
            $this->field('modified', ['visible' => false]);
            $this->field('modified_user_id', ['visible' => false]);
            $this->field('created', ['visible' => false]);
            $this->field('created_user_id', ['visible' => false]);
            $this->field('security_group_id', ['visible' => false]);
            $this->field('institution_locality_id', ['type' => 'hidden']);
            $this->field('institution_ownership_id', ['type' => 'hidden']);
            $this->field('institution_provider_id', ['type' => 'hidden']);
            $this->field('institution_sector_id', ['type' => 'hidden']);
            $this->field('institution_type_id', ['type' => 'hidden']);
            $this->field('institution_gender_id', ['type' => 'hidden']);
            $this->field('shift_type', ['visible' => false]);
            $this->field('area_administrative_id', ['visible' => false]);
            $this->field('area_id', ['type' => 'hidden']);
            $this->field('contact_section', ['visible' => false]);
            $this->field('contact_person', ['visible' => false]);
            $this->field('telephone', ['visible' => false]);
            $this->field('fax', ['visible' => false]);
            $this->field('email', ['visible' => false]);
            $this->field('website', ['visible' => false]);
            $this->field('longitude', ['visible' => false]);
            $this->field('latitude', ['visible' => false]);
            $this->field('postal_code', ['visible' => false]);
            $this->field('alternative_name', ['visible' => false]);
            $this->field('year_opened', ['visible' => false]);
            $this->field('year_closed', ['visible' => false]);
            $this->field('address', ['visible' => false]);
            $this->field('logo_name', ['visible' => false]);
            $this->field('logo_content', ['visible' => false]);
            $this->field('classification', ['visible' => false]);

            $this->field('information_section',['visible' => false]);
            $this->field('location_section', ['visible' => false]);
            $this->field('area_section', ['visible' => false]);
            $this->field('area_administrative_section', ['visible' => false]);
            $this->field('contact_section', ['visible' => false]);
            $this->field('other_information_section', ['visible' => false]);
        }

    }

    public function getSelectOptions()
    {
    //Return all required options and their key
        $withdrawStudentsOptions = [];
        foreach ($this->withdrawStudents as $key => $databaseType) {
            $withdrawStudentsOptions[$databaseType['id']] = __($databaseType['name']);
        }
        $selectedDatabaseType = key($withdrawStudentsOptions);

        return compact('withdrawStudentsOptions', 'selectedDatabaseType');
    }

    public function getViewShiftDetail($institutionId, $academicPeriod)
    {
        $data = $this->InstitutionShifts->find()
        ->innerJoinWith('Institutions')
        ->innerJoinWith('LocationInstitutions')
        ->innerJoinWith('ShiftOptions')
        ->select([
            'Owner' => 'Institutions.name',
            'OwnerId' => 'Institutions.id',
            'Occupier' => 'LocationInstitutions.name',
            'OccupierId' => 'LocationInstitutions.id',
            'Shift' => 'ShiftOptions.name',
            'ShiftId' => 'ShiftOptions.id',
            'StartTime' => 'InstitutionShifts.start_time',
            'EndTime' => 'InstitutionShifts.end_time'
        ])
        ->where([
            'OR' => [
                [$this->InstitutionShifts->aliasField('location_institution_id') => $institutionId],
                [$this->InstitutionShifts->aliasField('institution_id') => $institutionId]
            ],
            $this->InstitutionShifts->aliasField('academic_period_id') => $academicPeriod
        ])
        ->toArray();

        return $data;
    }

    public function getDefaultImgMsg()
    {
        $width = 200;
        $height = 200;
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

        $value = $this->defaultLogoView;

        return $value;
    }

    public function onGetLogoContent(Event $event, Entity $entity)
    {
        $fileContent = $entity->logo_content;
        $value = "";
        if (empty($fileContent) && is_null($fileContent)) {
            $value = $this->defaultLogoView;
        } else {
            $value = base64_encode(stream_get_contents($fileContent));//$fileContent;
        }

        return $value;
    }

    /******************************************************************************************************************
    **
    ** view action methods
    **
    ******************************************************************************************************************/
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'information_section',
            'logo_content',
            'name', 'alternative_name', 'code', 'classification', 'institution_sector_id', 'institution_provider_id', 'institution_type_id',
            'institution_ownership_id', 'institution_gender_id', 'date_opened', 'date_closed', 'institution_status_id',

            'shift_section',
            'shift_type', 'shift_details',

            'location_section',
            'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

            'area_section',
            'area_id',

            'area_administrative_section',
            'area_administrative_id',

            'contact_section',
            'contact_person', 'telephone', 'fax', 'email', 'website',

            'map_section',
            'map',
        ]);

        // update button
        $btnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        
        $session = $this->request->session();
        $institutionId = $this->request->pass[1];
        
        $extraButtons = [
            'close' => [
                'Institution' => ['Institutions', 'edit', $institutionId],
                'action' => 'InstitutionStatus',
                'icon' => '<i class="fa kd-key"></i>',
                'title' => __('Status Update')
            ]
        ];
        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'button',
                    'attr' => $btnAttr,
                    'url' => [0 => 'edit', 1 => $institutionId] 
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }
        // update button

        // overwrite back button
        $btnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        
        $extraButtons = [
            'back' => [
                'Institution' => ['Institutions', 'Institutions', 'index'],
                'action' => 'Institutions',
                'icon' => '<i class="fa kd-back"></i>',
                'title' => __('Back')
            ]
        ];
        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
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
        }
        // back button
        
    }

    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->Session->delete('Institutions.id');

        $plugin = $this->controller->plugin;
        $name = $this->controller->name;
        $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->alias(), 'image'];
        $imageDefault = 'fa kd-institutions';
        $this->field('logo_content', ['type' => 'image', 'ajaxLoad' => true, 'imageUrl' => $imageUrl, 'imageDefault' => '"'.$imageDefault.'"', 'order' => 0]);

        $this->setFieldOrder([
            'logo_content', 'code', 'name', 'area_id', 'institution_type_id', 'institution_status_id'
        ]);

        $this->setFieldVisible(['index'], [
            'logo_content', 'code', 'name', 'area_id', 'institution_type_id', 'institution_status_id'
        ]);
        $this->controller->set('ngController', 'AdvancedSearchCtrl');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //the query options are setup so that Security.InstitutionBehavior can reuse it
        $extra['query'] = [
            'contain' => ['Types', 'Areas','Statuses'],
            'select' => [
                $this->aliasField('id'),
                $this->aliasField('code'),
                $this->aliasField('name'),
                $this->aliasField('area_id'),
                $this->aliasField('institution_status_id'),
                'Areas.name',
                'Types.name',
                'Statuses.name'
            ],
        ];
        $extra['auto_contain'] = false;
        $query->contain($extra['query']['contain']);
        $query->select($extra['query']['select']);

        // POCOR-3983 if no sort, active status will be followed by inactive status
        if (!isset($this->request->query['sort'])) {
            $query->order([
                $this->aliasField('institution_status_id') => 'ASC',
                $this->aliasField('name') => 'ASC'
            ]);
        }
        // end POCOR-3983
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->dashboardQuery = clone $query;
        $search = $this->getSearchKey();
        if (empty($search) && !$this->isAdvancedSearchEnabled()) {
            // redirect to school dashboard if it is only one record and no add access
            $addAccess = $this->AccessControl->check(['Institutions', 'add']);
            if ($data->count() == 1 && (!$addAccess || Configure::read('schoolMode'))) {
                $entity = $data->first();
                $event->stopPropagation();
                $action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'dashboard', $this->paramsEncode(['id' => $entity->id])];
                return $this->controller->redirect($action);
            } elseif ($data->count() == 0 && Configure::read('schoolMode')) {
                $event->stopPropagation();
                $this->Alert->info('Institutions.noInstitution', ['reset' => true]);
                $action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Institutions', 'add'];
                return $this->controller->redirect($action);
            }
        }

        // to display message after redirect
        $sessionKey = 'HideButton.warning';
        if ($this->Session->check($sessionKey)) {
            $this->Alert->warning('security.noAccess');
            $this->Session->delete($sessionKey);
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index') {
            $institutionCount = $this->find();
            $conditions = [];

            $institutionCount = clone $this->dashboardQuery;
            $cloneClass = clone $this->dashboardQuery;

            $models = [
                ['Types', $this->aliasField('institution_type_id'), 'Type', 'query' => $this->dashboardQuery],
                ['Sectors', $this->aliasField('institution_sector_id'), 'Sector', 'query' => $this->dashboardQuery],
                ['Localities', $this->aliasField('institution_locality_id'), 'Locality', 'query' => $this->dashboardQuery],
            ];

            foreach ($models as $key => $model) {
                $institutionArray[$key] = $this->getDonutChart('institutions', $model);
            }

            $indexDashboard = 'dashboard';
            $count = $institutionCount->count();
            unset($institutionCount);

            if (!$this->isAdvancedSearchEnabled()) { //function to determine whether dashboard should be shown or not
                $extra['elements']['mini_dashboard'] = [
                    'name' => $indexDashboard,
                    'data' => [
                        'model' => 'institutions',
                        'modelCount' => $count,
                        'modelArray' => $institutionArray,
                    ],
                    'options' => [],
                    'order' => 1
                ];
            }
        }
        $extra['formButtons'] = false;
    }


    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->Alert->info('general.status_update');
        $data = $this->find()->where(['id' => $entity->id])->first();
        if (!empty($data)) {
            $status = $data->institution_status_id;
            if ($status == 1) {
               $statusName = 'Active';
               $newStatus = 'Inactive';
               $dateClosed = date('d-m-Y');
               $dateOpen = $data->date_opened->format('d-m-Y');
               $this->field('name', ['attr' => ['readonly' => 'readonly']]);
               $this->field('code', ['attr' => ['readonly' => 'readonly']]);
               $this->field('date_opened', ['attr' => ['value' => $dateOpen]]);
               $this->field('date_closed', ['attr' => ['value' => $dateClosed]]);
               $this->field('current_status', ['attr' => ['readonly' => 'readonly', 'value' => $statusName]]);
               //Setup fields
               list($withdrawStudentsOptions) = array_values($this->getSelectOptions());
               $this->field('institution_status_id', ['type' => 'readonly','attr' => ['label' => __('New Status'), 'value' => $newStatus]]);
               $this->field('withdraw_students', ['type' => 'select', 'options' => $withdrawStudentsOptions]);
               $this->field('end_staff_positions', ['type' => 'select', 'options' => $withdrawStudentsOptions]);
               $this->field('end_infrastructure_usage', ['type' => 'select', 'options' => $withdrawStudentsOptions]);
           } elseif ($status == 2) {
            $dateOpened = date('d-m-Y');
            $statusName = 'Inactive';
            $newStatus = 'Active';
            $this->field('name', ['attr' => ['readonly' => 'readonly']]);
            $this->field('code', ['attr' => ['readonly' => 'readonly']]);
            $this->field('date_opened', ['attr' => ['value' => $dateOpened]]);
            $this->field('date_closed', ['attr' => ['value' => '']]);
            $this->field('current_status', ['attr' => ['readonly' => 'readonly', 'value' => $statusName]]);
            $this->field('institution_status_id', ['type' => 'readonly', 'attr' => ['label' => __('New Status'), 'value' => $newStatus]]);
        }
    }

    // hide list button
    $btnAttr = [
        'class' => 'btn btn-xs btn-default',
        'data-toggle' => 'tooltip',
        'data-placement' => 'bottom',
        'escape' => false
    ];
    
    $extraButtons = [
        'list' => [
            'Institution' => ['Institutions', 'Institutions', 'index'],
            'action' => 'Institutions',
            'icon' => '<i class="fa kd-lists"></i>',
            'title' => __('List')
        ]
    ];
    foreach ($extraButtons as $key => $attr) {
        if ($this->AccessControl->check($attr['permission'])) {
            $button = [
                'type' => 'hidden',
                'attr' => $btnAttr,
                'url' => [0 => 'index'] 
            ];
            $button['url']['action'] = $attr['action'];
            $button['attr']['title'] = $attr['title'];
            $button['label'] = $attr['icon'];

            $extra['toolbarButtons'][$key] = $button;
        }
    }
        // back button
}

public function editAfterSave(Event $event, Entity $entity, ArrayObject $options)
{
    if (!$entity->isNew()) {
        $this->validator()->remove('area_id', 'required');
        $this->validator()->remove('institution_locality_id', 'required');
        $this->validator()->remove('institution_type_id', 'required');
        $this->validator()->remove('institution_ownership_id', 'required');
        $this->validator()->remove('institution_sector_id', 'required');
        $this->validator()->remove('institution_provider_id', 'required');
        $this->validator()->remove('institution_gender_id', 'required');

        if ($options['InstitutionStatus']['current_status'] == 'Active') {
            if(!empty($options['InstitutionStatus']['withdraw_students']) && $options['InstitutionStatus']['withdraw_students'] == 1) {
                $institutionStudents = TableRegistry::get('institution_students');
                $query = $institutionStudents->query();
                $query->update()
                ->set(['end_date' => date('Y-m-d'), 'student_status_id' => 4])
                ->where(['institution_id' => $entity->id])
                ->execute();
            } 
            if(!empty($options['InstitutionStatus']['end_staff_positions']) && $options['InstitutionStatus']['end_staff_positions'] == 1) {
                $institutionStaff = TableRegistry::get('institution_staff');
                $query = $institutionStaff->query();
                $query->update()
                ->set(['end_date' => date('Y-m-d'), 'staff_status_id' => 2]) 
                ->where(['institution_id' => $entity->id])
                ->execute();
            }
            if(!empty($options['InstitutionStatus']['end_infrastructure_usage']) && $options['InstitutionStatus']['end_infrastructure_usage'] == 1) {
                $institutionRoom = TableRegistry::get('institution_rooms');
                $query = $institutionRoom->query();
                $query->update()
                ->set(['end_date' => date('Y-m-d'), 'room_status_id' => 2])
                ->where(['institution_id' => $entity->id])
                ->execute();
            }

            $query = $this->query();
            $query->update()
            ->set(['date_closed' => date('Y-m-d'), 'institution_status_id' => 2])
            ->where(['id' => $entity->id])
            ->execute();
        }
    //when status is inactive
        elseif ($options['InstitutionStatus']['current_status'] == 'Inactive') {
            $query = $this->query();
            $query->update()
            ->set(['date_opened' => date('Y-m-d'), 'date_closed' => NULL, 'institution_status_id' => 1])
            ->where(['id' => $entity->id])
            ->execute();
        }  
    }
}

public function onUpdateFieldDateOpened(Event $event, array $attr, $action, Request $request)
{
    $session = $this->request->session();
    $institutionId = $this->request->pass[1];
    $id = $this->controller->paramsDecode($institutionId)['id'];
    $data = $this->find()->where(['id' => $id])->first();
    $dateOpen = $data->date_opened->format('d-m-Y');
    $today = new Date();

    if ($action == 'edit' && $data->institution_status_id == 1) {
        $attr['type'] = 'readonly';
        $attr['value'] = $dateOpen;
    }

    elseif ($action == 'edit' && $data->institution_status_id == 2) {
        $attr['type'] = 'readonly';
        $attr['value'] = $today->format('d-m-Y');
    }


    return $attr;
}

public function onUpdateFieldDateClosed(Event $event, array $attr, $action, Request $request)
{
    $session = $this->request->session();
    $institutionId = $this->request->pass[1];
    $id = $this->controller->paramsDecode($institutionId)['id'];
    $data = $this->find()->where(['id' => $id])->first();
    $today = new Date();
    if ($action == 'edit' && $data->institution_status_id == 1) {
        $attr['type'] = 'readonly';
        $attr['value'] = $today->format('d-m-Y');
    }

    elseif ($action == 'edit' && $data->institution_status_id == 2) {
        $attr['type'] = 'readonly';
        $attr['value'] = '';
    }

    return $attr;
}

}