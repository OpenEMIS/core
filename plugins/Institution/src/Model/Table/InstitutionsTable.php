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

class InstitutionsTable extends ControllerActionTable
{
    use OptionsTrait;
    private $dashboardQuery = null;

    public $shiftTypes = [];

    private $classificationOptions = [];

    const SINGLE_OWNER = 1;
    const SINGLE_OCCUPIER = 2;
    const MULTIPLE_OWNER = 3;
    const MULTIPLE_OCCUPIER = 4;

    // For Academic / Non-Academic Institution type
    const ACADEMIC = 1;
    const NON_ACADEMIC = 2;

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

        $this->hasMany('InstitutionBankAccounts', ['className' => 'Institution.InstitutionBankAccounts', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionFees', ['className' => 'Institution.InstitutionFees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionGrades', ['className' => 'Institution.InstitutionGrades', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('StudentPromotion', ['className' => 'Institution.StudentPromotion', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferOut', ['className' => 'Institution.StudentTransferOut', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'previous_institution_id']);
        $this->hasMany('StudentTransferIn', ['className' => 'Institution.StudentTransferIn', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'previous_institution_id']);
        $this->hasMany('AssessmentItemResults', ['className' => 'Institution.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRubrics', ['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionQualityVisits', ['className' => 'Quality.InstitutionQualityVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentSurveys', ['className' => 'Student.StudentSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentres', ['className' => 'Examination.ExaminationCentres', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationItemResults', ['className' => 'Examination.ExaminationItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'joinTable' => 'examination_centres_examinations_institutions',
            'foreignKey' => 'institution_id',
            'targetForeignKey' => ['examination_centre_id', 'examination_id'],
            'through' => 'Examination.ExaminationCentresExaminationsInstitutions',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
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
        $this->addBehavior('TrackActivity', ['target' => 'Institution.InstitutionActivities', 'key' => 'institution_id', 'session' => 'Institution.Institutions.id']);

        // specify order of advanced search fields
        $advancedSearchFieldOrder = [
            'shift_type', 'classification', 'area_id', 'area_administrative_id', 'institution_locality_id', 'institution_type_id',
            'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_provider_id', 'institution_gender_id', 'education_programmes',
            'code', 'name',
        ];
        $this->addBehavior('AdvanceSearch', [
            'display_country' => false,
            'include' =>[
                'code', 'name'
            ],
            'order' => $advancedSearchFieldOrder
        ]);
        $this->addBehavior('Excel', ['excludes' => ['security_group_id'], 'pages' => ['view']]);
        $this->addBehavior('Security.Institution');
        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('OpenEmis.Map');
        $this->addBehavior('HighChart', ['institutions' => ['_function' => 'getNumberOfInstitutionsByModel']]);
        $this->addBehavior('Import.ImportLink');

        $this->addBehavior('Institution.AdvancedProgrammeSearch');

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'logo_name',
            'content' => 'logo_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'image',
            'useDefaultName' => true
        ]);

        $this->shiftTypes = $this->getSelectOptions('Shifts.types'); //get from options trait
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index', 'view'],
            'Map' => ['index']
        ]);

        $this->addBehavior('ControllerAction.Image');

        $this->classificationOptions = [
            self::ACADEMIC => __('Academic Institution'),
            self::NON_ACADEMIC => __('Non-Academic Institution')
        ];

        $this->setDeleteStrategy('restrict');

        $this->addBehavior('Institution.LatLong');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        // $validator = $this->LatLongValidation();

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
        ->add('date_closed', 'ruleCheckPendingWorkbench', [
            'rule' => 'checkPendingWorkbench',
            'last' => true
        ])
        ->add('classification', [
            'validClassification' => [
                'rule' => ['range', 1, 2],
            ]
        ])

            // ->add('address', 'ruleMaximum255', [
            //      'rule' => ['maxLength', 255],
            //      'message' => 'Maximum allowable character is 255',
            //      'last' => true
            //  ])

        ->add('code', 'ruleCustomCode', [
            'rule' => ['validateCustomPattern', 'institution_code'],
            'provider' => 'table',
            'last' => true
        ])

        ->allowEmpty('postal_code')
        ->add('postal_code', 'ruleCustomPostalCode', [
            'rule' => ['validateCustomPattern', 'postal_code'],
            'provider' => 'table',
            'last' => true
        ])

        ->add('code', 'ruleUnique', [
            'rule' => 'validateUnique',
            'provider' => 'table',
                    // 'message' => 'Code has to be unique'
        ])

        ->allowEmpty('email')
        ->add('email', [
            'ruleValidEmail' => [
                'rule' => 'email'
            ]
        ])

        ->allowEmpty('telephone')
        ->add('telephone', 'ruleCustomTelephone', [
            'rule' => ['validateCustomPattern', 'institution_telephone'],
            'provider' => 'table',
            'last' => true
        ])

        ->allowEmpty('fax')
        ->add('fax', 'ruleCustomFax', [
            'rule' => ['validateCustomPattern', 'institution_fax'],
            'provider' => 'table',
            'last' => true
        ])

        // ->add('area_id', 'ruleAuthorisedArea', [
        //     'rule' => ['checkAuthorisedArea']
        // ])
        // ->add('area_id', 'ruleConfiguredArea', [
        //     'rule' => ['checkConfiguredArea']
        // ])
        // ->allowEmpty('area_administrative_id')
        // ->add('area_administrative_id', 'ruleConfiguredAreaAdministrative', [
        //     'rule' => ['checkConfiguredArea']
        // ])
        ->add('institution_provider_id', 'ruleLinkedSector', [
            'rule' => 'checkLinkedSector',
            'provider' => 'table'
        ])
        ->allowEmpty('logo_content')
        ;
        return $validator;
    }

    public function getNonAcademicConstant()
    {
        return self::NON_ACADEMIC;
    }

    public function getAcademicConstant()
    {
        return self::ACADEMIC;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';
        $events['Model.AreaAdministrative.afterDelete'] = 'areaAdminstrativeAfterDelete';
        return $events;
    }

    public function areaAdminstrativeAfterDelete(Event $event, $areaAdministrative)
    {
        $subquery = $this->AreaAdministratives
        ->find()
        ->select(1)
        ->where(function ($exp, $q) {
            return $exp->equalFields($this->AreaAdministratives->aliasField('id'), $this->aliasField('area_administrative_id'));
        });

        $query = $this->find()
        ->select('id')
        ->where(function ($exp, $q) use ($subquery) {
            return $exp->notExists($subquery);
        });

        foreach ($query as $row) {
            $this->updateAll(
                ['area_administrative_id' => null],
                ['id' => $row->id]
            );
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if ($value['field'] == 'area_id') {
                $newFields[] = [
                    'key' => 'Areas.code',
                    'field' => 'area_code',
                    'type' => 'string',
                    'label' => ''
                ];
            }
        }
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetShiftType(Event $event, Entity $entity)
    {
        if (isset($this->shiftTypes[$entity->shift_type])) {
            return __($this->shiftTypes[$entity->shift_type]);
        } else {
            return '';
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
        ->contain(['Areas'])
        ->select(['area_code' => 'Areas.code']);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        $name = $entity->name;
        $redirectToOverview = false;

        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $redirectToOverview = $ConfigItem->value('default_school_landing_page');

        if ($this->AccessControl->check([$this->controller->name, 'dashboard'])) {
            // Redirect to overview page based on School Landing
            if (!$redirectToOverview) {
                $name = $event->subject()->HtmlField->link($entity->name, [
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'action' => 'dashboard',
                    'institutionId' => $this->paramsEncode(['id' => $entity->id]),
                    '0' => $this->paramsEncode(['id' => $entity->id])
                ]);
            }
            else {
                $name = $event->subject()->HtmlField->link($entity->name, [
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'action' => 'Institutions',
                    '0' => "view",
                    '1' => $this->paramsEncode(['id' => $entity->id])
                ]);
            }
        }
        
        return $name;
    }

    public function onGetShiftType(Event $event, Entity $entity)
    {
        $type = ' ';
        if (array_key_exists($entity->shift_type, $this->shiftTypes)) {
            $type = $this->shiftTypes[$entity->shift_type];
        }
        return $type;
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

    public function onUpdateDefaultActions(Event $event)
    {
        return ['downloadFile'];
    }

    public function onUpdateFieldDateOpened(Event $event, array $attr, $action, Request $request)
    {
        $today = new Date();
        $attr['date_options']['endDate'] = $today->format('d-m-Y');
        return $attr;
    }

    public function onUpdateFieldDateClosed(Event $event, array $attr, $action, Request $request)
    {
        $attr['default_date'] = false;

        if ($action == 'add') {
            $attr['visible'] = false;
        }
        //POCOR-5683
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        //POCOR-5683
        return $attr;
    }

    public function onUpdateFieldInstitutionStatusId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['visible'] = false;
        }

        return $attr;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $this->setInstitutionStatusId($data);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->shift_type = 0;
        }

        // adding debug log to monitor when there was a different between date_opened's year and year_opened
        $this->debugMonitorYearOpened($entity, $options);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $TransferConnections = TableRegistry::get('TransferConnections.TransferConnections');
        $TransferConnectionsResult = $TransferConnections
            ->find()
            ->select(['conn_status_id'])
            ->first();
        $this->Session->write('is_connection_stablished', $TransferConnectionsResult->conn_status_id);
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
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {   
        $SecurityGroup = TableRegistry::get('Security.SystemGroups');
        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');

        $dispatchTable = [];
        $dispatchTable[] = $SecurityGroup;
        $dispatchTable[] = $this->ExaminationCentres;
        $dispatchTable[] = $SecurityGroupAreas;
        
        if(!empty($this->controllerAction) && ($this->controllerAction == 'Institutions')) {
            // Webhook institution create -- start
            $bodyData =  $this->find('all',
                            [ 'contain' => [
                                'Sectors',
                                'Types',
                                'Areas',
                                'AreaAdministratives',
                                'Localities',
                                'Genders'
                            ],
                    ])->where([
                        $this->aliasField('id') => $entity->id
                    ]);
            foreach ($bodyData as $key => $value) {
                $sectorName = $value->sector->name;
                $typeName = $value->sector->name;
                $genderName = $value->gender->name;
                $localitiesName =  $value->locality->name;
                $areaEducationId = $value->area->id;
                $areaEducationName = $value->area->name;
                $areaAdministrativeId = $value->area_administrative->id;
                $areaAdministrativeName = $value->area_administrative->name;
            }
            
            $classificationId = $entity->classification;
            if($classificationId == 1 ){
                $clss= 'Academic Institution';
            } else {
                $clss = 'Non-academic institution';
            }
            
            $body = array();
            $body = [
                'institution_id' => $entity->id,
                'institution_name' => $entity->name,
                'institution_alternative_name' => $entity->alternative_name,
                'institution_code' => $entity->code,
                'institution_classification' => $clss,
                'institution_sector' => !empty($sectorName) ? $sectorName : NULL,
                'institution_type' =>  !empty($typeName) ? $typeName : NULL,
                'institution_gender' => !empty($genderName) ? $genderName : NULL,
                'institution_date_opened' => date("d-m-Y", strtotime($entity->date_opened)),
                'institution_address' => $entity->address,
                'institution_postal_code' => $entity->postal_code,
                'institution_locality' => !empty($localitiesName) ? $localitiesName : NULL,
                'institution_latitude' => $entity->latitude,
                'institution_longitude' => $entity->longitude,
                'institution_area_education_id' => !empty($areaEducationId) ? $areaEducationId : NULL,
                'institution_area_education' =>  !empty($areaEducationName) ? $areaEducationName : NULL,
                'institution_area_administrative_id' => !empty($areaAdministrativeId) ? $areaAdministrativeId : NULL,
                'institution_area_administrative' => !empty($areaAdministrativeName) ? $areaAdministrativeName : NULL,
                'institution_contact_person' => $entity->contact_person,
                'institution_telephone' => $entity->telephone,
                'institution_mobile' => $entity->fax,
                'institution_email' => $entity->email,
                'institution_website' => $entity->website,
            ];
            if($this->webhookAction == 'add' && empty($event->data['entity']->security_group_id)) {
                $Webhooks = TableRegistry::get('Webhook.Webhooks');
                if ($this->Auth->user()) { 
                    $Webhooks->triggerShell('institutions_create', ['username' => $username], $body);
                }   
            }
        // Webhook institution create -- end

        // Webhook institution update --start
            if($this->webhookAction == 'edit') {
                $Webhooks = TableRegistry::get('Webhook.Webhooks');
                if ($this->Auth->user()) {
                    $Webhooks->triggerShell('institutions_update', ['username' => $username], $body);
                }
            }            
        // webhook institution update --end
        }
        
        foreach ($dispatchTable as $model) {
            $model->dispatchEvent('Model.Institutions.afterSave', [$entity], $this);
        }
    }


    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $securityGroupId = $entity->security_group_id;
        $SecurityGroup = TableRegistry::get('Security.SystemGroups');

        $groupEntity = $SecurityGroup->get($securityGroupId);
        $SecurityGroup->delete($groupEntity);
        $body = array();
        $body = [
            'institution_id' => $entity->id
        ];

        //webhook event
        $Webhooks = TableRegistry::get('Webhook.Webhooks');
        if ($this->Auth->user()) {
         $Webhooks->triggerShell('institutions_delete', ['username' => $username],$body);
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

    public function getNumberOfInstitutionsByModel($params = [])
    {
        if (!empty($params)) {
            $query = $params['query'];

            $modelName = $params[0];
            $modelId = $params[1];
            $key = $params[2];
            $params['key'] = __($key);

            $institutionRecords = clone $query;

            $selectString = $modelName.'.name';
            $institutionTypesCount = $institutionRecords
            ->contain([$modelName])
            ->select([
                    //'modelId' => $modelId,
                'count' => $institutionRecords->func()->count($modelId),
                'name' => $selectString
            ])
            ->group($modelId)
            ;

            $this->advancedSearchQuery($this->request, $institutionTypesCount);

            // Creating the data set
            $dataSet = [];
            foreach ($institutionTypesCount->toArray() as $key => $value) {
                // Compile the dataset
                $dataSet[] = [0 => $value['name'], 1 =>$value['count']];
            }
            
            /*$dataSet = [
                ['Lower Secondary', 7],
                ['Upper  Secondary', 4],
                ['Pre-primary', 6],
                ['Primary', 15]
            ];*/            
            
            $params['dataSet'] = $dataSet;
        }
        unset($institutionRecords);
        return $params;
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

    public function onGetAreaId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $areaName = $entity->Areas['name'];
            // Getting the system value for the area
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            // Getting the current area id
            $areaId = $entity->area_id;
            try {
                if ($areaId > 0) {
                    $path = $this->Areas
                    ->find('path', ['for' => $areaId])
                    ->contain('AreaLevels')
                    ->toArray();

                    foreach ($path as $value) {
                        if ($value['area_level']['level'] == $areaLevel) {
                            $areaName = $value['name'];
                        }
                    }
                }
            } catch (InvalidPrimaryKeyException $ex) {
                $this->log($ex->getMessage(), 'error');
            }
            return $areaName;
        }
        return $entity->area_id;
    }

    public function onGetAreaAdministrativeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->area_administrative_id;
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'area_id' && $this->action == 'index') {
            // Getting the system value for the area
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            $AreaTable = TableRegistry::get('Area.AreaLevels');
            $value = $AreaTable->find()
            ->where([$AreaTable->aliasField('level') => $areaLevel])
            ->first();

            if (is_object($value)) {
                return $value->name;
            } else {
                return $areaLevel;
            }
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
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

    public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $extra['redirect'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Institutions',
            'index'
        ];
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

        // from onUpdateToolbarButtons
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
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('classification', ['type' => 'select', 'options' => [], 'entity' => $entity, 'after' => 'code']);

        // hide shift section if institution is non-academic
        if ($entity->classification == self::NON_ACADEMIC) {
            $this->field('shift_section', ['visible' => false]);
            $this->field('shift_type', ['visible' => false]);
            $this->field('shift_details', ['visible' => false]);
        }

        // POCOR-3983 Add info message to display message inactive status
        if ($entity->has('status') && $entity->status->code == 'INACTIVE') {
            $this->Alert->info('general.inactive_message');
        }
    }

    /******************************************************************************************************************
    **
    ** add / addEdit action methods
    **
    ******************************************************************************************************************/
    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $userId = $this->Session->read('Auth.User.id');
        $superAdmin = $this->Session->read('Auth.User.super_admin');

        $data['userId'] = $userId;
        $data['superAdmin'] = $superAdmin;
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'information_section',
            'logo_content',
            'name', 'alternative_name', 'code', 'classification', 'institution_sector_id', 'institution_provider_id', 'institution_type_id',
            'institution_ownership_id', 'institution_gender_id', 'date_opened', 'date_closed', 'institution_status_id',

            'location_section',
            'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

            'area_section',
            'area_id',

            'area_administrative_section',
            'area_administrative_id',
        ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('institution_type_id', ['type' => 'select']);
        $this->field('institution_provider_id', ['type' => 'select', 'sectorId' => $entity->institution_sector_id]);
        $this->field('classification', ['type' => 'select', 'options' => [], 'entity' => $entity, 'after' => 'code']);

        $this->setFieldOrder([
            'information_section',
            'logo_content',
            'name', 'alternative_name', 'code', 'classification', 'institution_sector_id', 'institution_provider_id', 'institution_type_id',
            'institution_ownership_id', 'institution_gender_id', 'date_opened', 'date_closed', 'institution_status_id',

            'location_section',
            'address', 'postal_code', 'institution_locality_id', 'latitude', 'longitude',

            'area_section',
            'area_id',

            'area_administrative_section',
            'area_administrative_id',

            'contact_section',
            'contact_person', 'telephone', 'fax', 'email', 'website',
        ]);
    }

    public function onUpdateFieldInstitutionProviderId(Event $event, array $attr, $action, Request $request)
    {
        $providerOptions = [];
        $selectedSectorId = '';

        if (isset($request->data[$this->alias()]['institution_sector_id'])) {
            $selectedSectorId = $request->data[$this->alias()]['institution_sector_id'];
        } elseif ($action == 'add') {
            $SectorTable = $this->Sectors;
            $defaultSector = $SectorTable
            ->find()
            ->where([$SectorTable->aliasField('default') => 1])
            ->first();

            if (!empty($defaultSector)) {
                $selectedSectorId = $defaultSector->id;
            }
        } elseif ($action == 'edit') {
            $selectedSectorId = $attr['sectorId'];
        }

        if (!empty($selectedSectorId)) {
            $ProviderTable = $this->Providers;
            $providerOptions = $ProviderTable->find('list')
            ->where([$ProviderTable->aliasField('institution_sector_id') => $selectedSectorId])
            ->toArray();
        }

        $attr['options'] = $providerOptions;
        $attr['empty'] = true;
        return $attr;
    }

    /******************************************************************************************************************
    **
    ** essential methods
    **
    ******************************************************************************************************************/

    // autocomplete used for UserGroups
    public function autocomplete($search, $params = [])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $search = sprintf('%s%%', $search);

        $list = $this
        ->find()
        ->where([
            'OR' => [
                $this->aliasField('name') . ' LIKE' => $search,
                $this->aliasField('code') . ' LIKE' => $search
            ]
        ])
        ->where([$conditions])
        ->order([$this->aliasField('name')])
        ->all();

        $data = array();
        foreach ($list as $obj) {
            $data[] = [
                'label' => sprintf('%s (%s)', $obj->name, $obj->code),
                'value' => $obj->id
            ];
        }
        return $data;
    }

    public function onUpdateFieldInstitutionTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            // list($typeOptions, $selectedType) = array_values($this->getTypeOptions());

            // $attr['options'] = $typeOptions;
            $attr['onChangeReload'] = 'changeType';
        }

        return $attr;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $institutionId = $entity->id;
            $securityRoles = $this->getInstitutionRoles($userId, $institutionId);
            foreach ($buttons as $key => $b) {
                $url = $this->url($key);
                if (!$this->AccessControl->check($url, $securityRoles)) {
                    unset($buttons[$key]);
                }
            }
        }
        foreach ($buttons as &$button) {
            if (isset($button['url'][1])) {
                $button['url']['institutionId'] = $button['url'][1];
            }
        }

        // POCOR-3125 history button permission to hide and show the link
        if (isset($buttons['view']) && $this->AccessControl->check(['InstitutionHistories', 'index'])) {
            $icon = '<i class="fa fa-history"></i>';

            $buttons['history'] = $buttons['view'];
            $buttons['history']['label'] = $icon . __('History');
            $buttons['history']['url']['plugin'] = 'Institution';
            $buttons['history']['url']['controller'] = 'InstitutionHistories';
            $buttons['history']['url']['action'] = 'index';
        }
        // end history button

        return $buttons;
    }

    public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('institution_type_id', $request->data[$this->alias()])) {
                    $selectedType = $request->data[$this->alias()]['institution_type_id'];
                    $entity->institution_type_id = $selectedType;
                }
            }
        }
    }

    public function getTypeOptions()
    {
        $typeOptions = $this->Types->getList()->toArray();
        $selectedType = $this->Types->getDefaultValue();

        // $selectedType = $this->queryString('type', $typeOptions);
        // $this->advancedSelectOptions($typeOptions, $selectedType);
        // , ['default' => $typeDefault]

        return compact('typeOptions', 'selectedType');
    }

    /******************************************************************************************************************
    **
    ** Security Functions
    **
    ******************************************************************************************************************/

    public function onUpdateFieldClassification(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (!Configure::read('schoolMode')) {
                $attr['select'] = false;
                $attr['options'] = $this->classificationOptions;
            } else {
                $attr['type'] = 'hidden';
                $attr['value'] = self::ACADEMIC;
            }
        } elseif ($action == 'edit') {
            $attr['type'] = 'disabled';
            $attr['attr']['value'] = __($this->classificationOptions[$attr['entity']->classification]);
        }
        return $attr;
    }

    public function onGetClassification(Event $event, Entity $entity)
    {
        $selectedClassification = $entity->classification;
        return __($this->classificationOptions[$selectedClassification]);
    }

    public function onExcelGetClassification(Event $event, Entity $entity)
    {
        return __($this->classificationOptions[$entity->classification]);
    }

    /**
     * To get the list of security group id for the particular institution and user
     *
     * @param integer $userId User Id
     * @param integer $institutionId Institution id
     * @return array The list of security group id that the current user for access to the institution
     */
    public function getSecurityGroupId($userId, $institutionId)
    {
        $institutionEntity = $this->get($institutionId);

        // Get parent of the area and the current area
        $areaId = $institutionEntity->area_id;
        $Areas = $this->Areas;
        $institutionArea = $Areas->get($areaId);

        // Getting the security groups
        $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
        $securityGroupIds = $SecurityGroupAreas->find()
        ->innerJoinWith('Areas')
        ->innerJoinWith('SecurityGroups.Users')
        ->where([
            'Areas.lft <= ' => $institutionArea->lft,
            'Areas.rght >= ' => $institutionArea->rght,
            'Users.id' => $userId
        ])
        ->union(
            $SecurityGroupInstitutions->find()
            ->innerJoinWith('SecurityGroups.Users')
            ->where([
                $SecurityGroupInstitutions->aliasField('institution_id') => $institutionId,
                'Users.id' => $userId
            ])
            ->select([$SecurityGroupInstitutions->aliasField('security_group_id')])
            ->distinct([$SecurityGroupInstitutions->aliasField('security_group_id')])
        )
        ->select([$SecurityGroupAreas->aliasField('security_group_id')])
        ->distinct([$SecurityGroupAreas->aliasField('security_group_id')])
        ->hydrate(false)
        ->toArray();
        $securityGroupIds = $this->array_column($securityGroupIds, 'security_group_id');
        return $securityGroupIds;
    }

    /**
     * To list of roles that are authorised for access to a particular institution
     *
     * @param integer $userId User Id
     * @param integer $institutionId Institution id
     * @return array The list of security roles id that the current user for access to the institution
     */
    public function getInstitutionRoles($userId, $institutionId)
    {
        $groupIds = $this->getSecurityGroupId($userId, $institutionId);
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        return $SecurityGroupUsers->getRolesByUserAndGroup($groupIds, $userId);
    }

    public function debugMonitorYearOpened($entity, $options)
    {
        $time = strtotime($entity->date_opened);
        $yearDateOpened = date("Y", $time);
        $yearOpened = $entity->year_opened;

        if ($yearDateOpened != $yearOpened) {
            $debugInfo = $this->alias() . ' (Institution Name: ' . $entity->name . ', Date_Opened: ' . $entity->date_opened . ', year_opened: ' . $yearOpened . ')';

            Log::write('debug', $debugInfo);
            Log::write('debug', $options);
            Log::write('debug', 'End of monitoring year opened');
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->SecurityGroups->alias(), $this->InstitutionSurveys->alias(), $this->StudentSurveys->alias(),
            $this->StaffPositionProfiles->alias(), $this->InstitutionActivities->alias(), $this->StudentPromotion->alias(),
            $this->StudentAdmission->alias(), $this->StudentWithdraw->alias(), $this->StudentTransferIn->alias(), $this->StudentTransferOut->alias(),
            $this->CustomFieldValues->alias(), $this->CustomTableCells->alias()
        ];
    }

    public function getCustomFilter(Event $event)
    {
        $filters = [
            'shift_type' => [
                'label' => __('Shift Type'),
                'options' => $this->shiftTypes
            ],
            'classification' => [
                'label' => __('Classification'),
                'options' => $this->classificationOptions
            ]
        ];
        return $filters;
    }

    public function findNotExamCentres(Query $query, array $options)
    {
        if (isset($options['academic_period_id'])) {
            $query
            ->leftJoinWith('ExaminationCentres', function ($q) use ($options) {
                return $q
                ->where(['ExaminationCentres.academic_period_id' => $options['academic_period_id']]);
            })
            ->where([
                'ExaminationCentres.institution_id IS NULL'
            ])
            ;
            return $query;
        }
    }

    public function findMap(Query $query, array $options)
    {
        $query
        ->select([
            'id',
            'code',
            'name',
            'longitude',
            'latitude'
        ])
        ->contain([
            'Types' => [
                'fields' => [
                    'Types.id',
                    'Types.name',
                    'Types.order'
                ],
                'sort' => ['Types.order' => 'ASC']
            ]
        ])
        ->formatResults(function (ResultSetInterface $results) {
            $formattedResults = [];
            $institutionTypes = [];
            foreach ($results as $institution) {
                $groupId = 'group_' . $institution->type->id;
                $institutionTypes[$groupId] = $institution->type->name;

                if (!array_key_exists($groupId, $formattedResults)) {
                    $formattedResults[$groupId]['data'] = [];
                }

                $encodedId = $this->paramsEncode(['id' => $institution->id]);
                $url = Router::url([
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Institutions',
                    'view',
                    'institutionId' => $encodedId,
                    $encodedId
                ], true);
                $longitude = $institution->has('longitude') ? $institution->longitude : 0;
                $latitude = $institution->has('latitude') ? $institution->latitude : 0;

                $obj = [
                    'id' => $encodedId,
                    'lng' => $longitude,
                    'lat' => $latitude,
                    'content' => $institution->name."<br/>".$institution->code."<br/><br/><a href='".$url."' target='_blank'>".__('View Details')."</a>"
                ];

                $formattedResults[$groupId]['data'][] = $obj;
            }

            $colorIndex = 0;
            foreach ($formattedResults as $key => &$obj) {
                $colors = $this->getMarkerColor();
                $markerColor = $colors[$colorIndex % sizeof($colors)];

                $numberOfRecords = sizeof($obj['data']);
                $title = $institutionTypes[$key] . ' ('.$numberOfRecords.')';

                $obj['marker'] = [
                    'icon' => 'university',
                    'markerColor' => $markerColor,
                    'title' => $title,
                    'id' => $key
                ];

                $colorIndex++;
            }

            return $formattedResults;
        });

        return $query;
    }

    private function getMarkerColor() {
        $colors = [
            'darkred',
            'purple',
            'orange',
            'green',
            'blue',
            'darkgreen',
            'darkblue'
        ];

        return $colors;
    }

    private function setInstitutionStatusId(ArrayObject $data)
    {
        $activeStatus = $this->Statuses->getIdByCode('ACTIVE');
        $inactiveStatus = $this->Statuses->getIdByCode('INACTIVE');

        $data['institution_status_id'] = $activeStatus;
        if ($data->offsetExists('date_closed') && !empty($data['date_closed'])) {
            $todayDate = new Date();
            $dateClosed = new Date($data['date_closed']);

            if ($dateClosed < $todayDate) {
                $data['institution_status_id'] = $inactiveStatus;
            }
        }
    }

    public function isActive($institutionId)
    {
        $isActive = true;

        $institutionEntity = $this->get($institutionId, ['contain' => 'Statuses']);
        if ($institutionEntity->has('status') && $institutionEntity->status->has('code')) {
            if ($institutionEntity->status->code == 'INACTIVE') {
                $isActive = false;
            }
        }

        return $isActive;
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
    
    public function findSearchInstitution(Query $query, array $options)
    {
        $search = $options['_controller']->request->query['_searchByCodeOrName'];
        if(!empty($search)){
            $query->where([
                'OR' => [
                    $this->aliasField('name') . ' LIKE' => "%$search%",
                    $this->aliasField('code') . ' LIKE' => $search
                ]
            ]);
        }
        
        //echo $query; die;
        return $query;
    }
}
