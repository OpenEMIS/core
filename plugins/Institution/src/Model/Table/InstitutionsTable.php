<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\I18n\I18n;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use Cake\Http\Session;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Institution\Model\Behavior\LatLongBehavior as LatLongOptions;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use App\Model\Table\AppTable;
use Laminas\Diactoros\UploadedFile;

class InstitutionsTable extends ControllerActionTable
{
    use OptionsTrait;
    const logoMaxWidth = 200; //POCOR-7935
    const logoMaxHeight = 200; //POCOR-7935
    private $_dynamicFieldName = 'custom_field_data';
    private $dashboardQuery = null;
    private $studentsTabsData = [
        0 => "Overview",
        1 => "Map",
        2 => "Shifts",
        3 => "Contact Institution",
        4 => "Contact People"
    ];
    public $shiftTypes = [];
    public $shiftOwnership = [];

    private $classificationOptions = [];

    const SINGLE_OWNER = 1;
    const SINGLE_OCCUPIER = 2;
    const MULTIPLE_OWNER = 3;
    const MULTIPLE_OCCUPIER = 4;

    // For Academic / Non-Academic Institution type
    const ACADEMIC = 1;
    const NON_ACADEMIC = 2;

    const Owner = 1;
    const Occupier = 2;

    private $defaultLogoView = "<div class='profile-image'><i class='fa kd-institutions'></i></div>";
    private $defaultImgIndexClass = "logo-thumbnail";
    private $defaultImgViewClass = "logo-image";
    private $photoMessage = 'Advisable logo dimension %width by %height';
    private $formatSupport = 'Format Supported: %s';
    private $defaultImgMsg = "<p>* %s <br>* %s</p>";

    public function initialize(array $config): void
    {
        $this->setTable('institutions');
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
        $this->belongsTo('InstitutionTypes', ['className' => 'FieldOption.InstitutionTypes', 'foreignKey' => 'institution_type_id']);
        /**
         * end fieldOption tables
         */

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);


        $this->hasMany('InstitutionActivities', ['className' => 'Institution.InstitutionActivities', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAttachments', ['className' => 'Institution.InstitutionAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'location_institution_id']);
        /*$this->hasMany('institutionContactPersons', ['className' => 'Institution.institutionContactPersons', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'institution_id']);*/
        $this->hasOne('ShiftOptions', ['className' => 'Institution.ShiftOptions', 'foreignKey' => 'shift_option_id']);
        $this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'id']);
        $this->hasMany('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'dependent' => true, 'cascadeCallbacks' => true]);

        //$this->hasMany('InstitutionCustomFieldValues', ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'institution_id']); //not same as cakephp 3 code
        //$this->hasMany('InstitutionCustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'id']); //not same as cakephp 3 code
        //$this->hasMany('InstitutionCustomFieldValues', ['className' => 'Institution.InstitutionCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'institution_id']);

        $this->hasMany('InstitutionCustomFieldValues', ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'institution_id']);
        $this->hasMany('CustomFieldValues', ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'id']);
        $this->hasMany('InstitutionCustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'id']);

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
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffSurveys', ['className' => 'Staff.StaffSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);


        $this->belongsToMany('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'joinTable' => 'examination_centres_examinations_institutions',
            'foreignKey' => 'institution_id',
            'targetForeignKey' => ['examination_centre_id', 'examination_id'],
            'through' => 'Examination.ExaminationCentresExaminationsInstitutions',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        // $this->belongsToMany('SecurityGroups', [
        //     'className' => 'Security.SystemGroups',
        //     'joinTable' => 'security_group_institutions',
        //     'foreignKey' => 'institution_id',
        //     'targetForeignKey' => 'security_group_id',
        //     'through' => 'Security.SecurityGroupInstitutions',
        //     'dependent' => true
        // ]);
        $this->belongsToMany('UserGroups', [
            'className' => 'Security.UserGroups',
            'joinTable' => 'security_group_institutions',
            'foreignKey' => 'institution_id',
            'targetForeignKey' => 'security_group_id',
            'through' => 'Security.SecurityGroupInstitutions',
            'dependent' => true
        ]);
        //POCOR-6520 starts: add isset condition only

        $request = Router::getRequest();
        if ($request !== null && isset($request->getParam('pass')[0])
            && $request->getParam('pass')[0] != 'excel'
            && $request->getParam('action')=="Institutions"){//POCOR-8538
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
        }
        $this->addBehavior('Year', ['date_opened' => 'year_opened', 'date_closed' => 'year_closed']);
        $this->addBehavior('TrackActivity', ['target' => 'Institution.InstitutionActivities', 'key' => 'institution_id', 'session' => 'Institution.Institutions.id']);

        // specify order of advanced search fields
        // POCOR-8219 added
        $advancedSearchFieldOrder = [
            'code', 'name', 'classification', 'area_id', 'area_administrative_id', 'institution_locality_id', 'institution_type_id',
            'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_provider_id', 'institution_gender_id',
            'education_systems', 'education_levels', 'education_programmes', 'alternative_name', 'shift_type'
        ];

        $this->addBehavior('AdvanceSearch', [
            'display_country' => false,
            'include' => [
                'code', 'name', 'alternative_name'
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

        $this->getSelectOptions('Shifts.types');//get from options trait
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index', 'view'],
            'Map' => ['index']
        ]);

        $this->addBehavior('ControllerAction.Image');
        /*POCOR-6764 starts*/
        /*POCOR-6346 starts*/
        // $this->shiftTypes = [
        //     self::SINGLE_OWNER => __('Single Owner'),
        //     self::SINGLE_OCCUPIER => __('Single Occupier'),
        //     self::MULTIPLE_OWNER => __('Multiple Owner'),
        //     self::MULTIPLE_OCCUPIER => __('Multiple Occupier')
        // ];
        /*POCOR-6346 ends*/

        $this->shiftTypes = $this->getShiftTypesOptions();

        $this->shiftOwnership = [
            self::Owner => __('Owner'),
            self::Occupier => __('Occupier')
        ];
        /*POCOR-6764 End*/

        $this->classificationOptions = [
            self::ACADEMIC => __('Academic Institution'),
            self::NON_ACADEMIC => __('Non-Academic Institution')
        ];

        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Institution.LatLong');
        $this->addBehavior('Institution.InstitutionTab');
        $this->SecurityGroups = TableRegistry::getTableLocator()->get('Security.SystemGroups');


    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        if(isset($this->request)) { // POCOR-8683
            if (isset($this->request->getParam('pass')[0]) && $this->request->getParam('pass')[0] != 'edit') { //POCOR-8585
                $validator = $this->LatLongValidation(); //POCOR-6625 incomment <vikas.rathore@mail.valocoders.com>
            }
        }
        $validator
            ->setProvider('custom', $this)
            // POCOR-7935:start
            ->add('logo_content', 'hople',
                ['rule' => ['imageSize',
                    ['width' => ['<=', self::logoMaxWidth], 'height' => ['<=', self::logoMaxHeight]]],
                    'message' => 'The image dimensions should not exceed ' . self::logoMaxWidth . 'x' . self::logoMaxHeight . ' pixels.'
                ])
            // POCOR-7935:end
            ->allowEmptyFile('logo_content')
            ->requirePresence('name')->notEmptyString('name')
            ->add('code', 'ruleCustomCode', [
                'rule' => ['validateCustomPattern', 'institution_code'],
                'provider' => 'table',
                'last' => true
            ])
            ->add('code', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                // 'message' => 'Code has to be unique'
            ])
            ->add('classification', [
                'validClassification' => [
                    'rule' => ['range', 1, 2],
                ]
            ])
            ->requirePresence('institution_sector_id')->notEmptyString('institution_sector_id')
            ->requirePresence('institution_type_id')->notEmptyString('institution_type_id')
            ->requirePresence('institution_ownership_id')->notEmptyString('institution_ownership_id')
            ->requirePresence('institution_gender_id')->notEmptyString('institution_gender_id')
            ->add('date_opened', [
                'ruleCompare' => [
                    // 'rule' => ['comparison', 'notequal', '0000-00-00'],
                    'rule' => function ($value, $context) {
                        try{
                            $mydate = strtotime($value);
                            return true;
                        }catch(\Exception $e){
                            Log::write('debug', 'Validation Context 1: ' . print_r([
                                    'context' => $context,
                                    'error' => $e->getMessage()
                                ], true));
                            return false;
                        }
                    },
                    'message' => __('You have entered an invalid date')
                ]
            ])
            ->add('date_opened', 'ruleLessThanToday', [
                'rule' => ['lessThanToday', true],
                'message' => __('Date should not be later than today'),
                'on' => function ($context) {
                    Log::write('debug', 'Validation Context 2: ' . print_r($context->data, true));
                    return true;
                }
            ])
            ->allowEmptyDate('date_closed')
            ->add('date_closed', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_opened', true],
                'message' => __('Date Closed should not be earlier than Date Opened'),
                'on' => function ($context) {
                    Log::write('debug', 'Validation Context 3: ' . print_r($context, true));
                    return true;
                }
            ])
            ->add('date_closed', 'ruleCheckPendingWorkbench', [
                'rule' => 'checkPendingWorkbench',
                'last' => true
            ])
            ->allowEmptyString('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ->allowEmptyString('email')
            ->add('email', 'validEmailCustom', [
                'rule' => ['checkEmailValidation'],
                'message' => 'Please enter a valid email',
                'on' => function ($context) {
                    return !empty($context['data']['email']);
                }
            ])
            ->notEmptyString('institution_locality_id') //POCOR-9407
            ->add('email', [
                'ruleValidEmail' => [
                    'rule' => 'checkEmailFormat',
                    'message' => 'Invalid email address'
                ]
            ])
            ->allowEmptyString('telephone')
            ->add('telephone', 'ruleCustomTelephone', [
                'rule' => ['validateCustomPattern', 'institution_telephone'],
                'provider' => 'table',
                'last' => true
            ])

            ->add('institution_provider_id', 'ruleLinkedSector', [
                'rule' => 'checkLinkedSector',
                'provider' => 'table'
            ])

            ->notEmptyString('address')
            ->add('address', 'ruleMaximum255', [
                'rule' => ['maxLength', 255],
                'message' => 'Maximum allowable character is 255',
                'last' => true
            ])
            ->add('address', 'rulecheckIfStringGotNoSpecialChar', [
                'rule' => [$this, 'checkIfStringGotNoSpecialChar'], // POCOR-8597
                'message' => 'The institution address cannot contain special characters.'
            ]);
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

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';
        $events['Model.AreaAdministrative.afterDelete'] = 'areaAdminstrativeAfterDelete';
       // $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    // public function isAuthorized(EventInterface $event, $scope, $action, $extra)
    // {
    //     if ($action == 'index' || $action == 'view' || $action == 'add') {
    //         // check for the user permission to view here
    //         $event->stopPropagation();
    //         return true;
    //     }
    // }

    public function areaAdminstrativeAfterDelete(EventInterface $event, $areaAdministrative)
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
                ['area_administrative_id' => -1],
                ['id' => $row->id]
            );
        }
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $studentsTabsData = $this->studentsTabsData;
        foreach ($studentsTabsData as $key => $val) {
            $tabsName = $val;
            $sheets[] = [
                'sheetData' => [
                    'institute_tabs_type' => $val
                ],
                'name' => $tabsName,
                'table' => $this,
                'query' => $this
                    ->find()
                /* ->leftJoin([$InstitutionStudents->alias() => $InstitutionStudents->table()],[
                    $this->aliasField('id = ').$InstitutionStudents->aliasField('student_id')
                ])
                ->where([
                    $InstitutionStudents->aliasField('student_id = ').$institutionStudentId,
                ]) */,
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $instituteType = $sheetData['institute_tabs_type'];
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        // echo "<pre>"; print_r($cloneFields); exit;
        foreach ($cloneFields as $key => $value) {
            if ($instituteType == 'Map') {
                if ($value['field'] == 'longitude') {
                    $newFields[] = [
                        'key' => 'Institutions.longitude',
                        'field' => 'longitude',
                        'type' => 'string',
                        'label' => 'Longitude'
                    ];
                    $newFields[] = [
                        'key' => 'Institutions.latitude',
                        'field' => 'latitude',
                        'type' => 'string',
                        'label' => 'Latitude'
                    ];
                }
            }
            if ($instituteType == 'Overview') {
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
            if ($instituteType == 'Shifts') {
                if ($value['field'] == 'shift_type') {
                    $newFields[] = [
                        'key' => 'AcademicPeriods.name',
                        'field' => 'academic_period',
                        'type' => 'string',
                        'label' => 'Academic Period'
                    ];
                    $newFields[] = [
                        'key' => 'ShiftOptions.name',
                        'field' => 'shift_name',
                        'type' => 'string',
                        'label' => 'Shift Name'
                    ];

                    $newFields[] = [
                        'key' => 'InstitutionShifts.start_time',
                        'field' => 'shift_start_time',
                        'type' => 'string',
                        'label' => 'Start Time'
                    ];

                    $newFields[] = [
                        'key' => 'InstitutionShifts.end_time',
                        'field' => 'shift_end_time',
                        'type' => 'string',
                        'label' => 'End Time'
                    ];

                    $newFields[] = [
                        'key' => 'Institutions.name',
                        'field' => 'Owner',
                        'type' => 'string',
                        'label' => 'Owner'
                    ];

                    $newFields[] = [
                        'key' => 'Institutions.name',
                        'field' => 'Occupier',
                        'type' => 'string',
                        'label' => 'Occupier'
                    ];
                }
            }
            if ($instituteType == 'Contact Institution') {
                if ($value['field'] == 'telephone') {
                    $newFields[] = [
                        'key' => 'Institutions.telephone',
                        'field' => 'telephone',
                        'type' => 'string',
                        'label' => 'Telephone'
                    ];
                   /* $newFields[] = [
                        'key' => 'Institutions.fax',
                        'field' => 'fax',
                        'type' => 'string',
                        'label' => 'Fax'
                    ];*/
                    $newFields[] = [
                        'key' => 'Institutions.email',
                        'field' => 'email',
                        'type' => 'string',
                        'label' => 'Email'
                    ];
                    $newFields[] = [
                        'key' => 'Institutions.website',
                        'field' => 'website',
                        'type' => 'string',
                        'label' => 'Website'
                    ];
                }
            }
            if ($instituteType == 'Contact People') {

                if ($value['field'] == 'contact_person') {

                    $newFields[] = [
                        'key' => 'institution_contact_persons.contact_person',
                        'field' => 'person',
                        'type' => 'string',
                        'label' => 'Contact Person'
                    ];

                    $newFields[] = [
                        'key' => 'institution_contact_persons.designation',
                        'field' => 'designation',
                        'type' => 'string',
                        'label' => 'Designation'
                    ];

                    $newFields[] = [
                        'key' => 'institution_contact_persons.department',
                        'field' => 'department',
                        'type' => 'string',
                        'label' => 'Department'
                    ];
                    $newFields[] = [
                        'key' => 'telephone',
                        'field' => 'tel',
                        'type' => 'string',
                        'label' => 'Telephone'
                    ];
                    $newFields[] = [
                        'key' => 'institution_contact_persons.mobile_number',
                        'field' => 'mobile_no',
                        'type' => 'string',
                        'label' => 'Mobile Number'
                    ];
                    /*$newFields[] = [
                        'key' => 'fax',
                        'field' => 'faxs',
                        'type' => 'string',
                        'label' => 'Fax'
                    ];*/
                    $newFields[] = [
                        'key' => 'institution_contact_persons.email',
                        'field' => 'contact_email',
                        'type' => 'string',
                        'label' => 'Email'
                    ];
                    $newFields[] = [
                        'key' => 'institution_contact_persons.preferred',
                        'field' => 'preferred',
                        'type' => 'string',
                        'label' => 'preferred'
                    ];
                }
            }
            if ($instituteType == 'Overview') {
                $InfrastructureCustomFields = TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionCustomFields');
                $customFieldData = $InfrastructureCustomFields->find()->select([
                    'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                    'custom_field' => $InfrastructureCustomFields->aliasfield('name')
                ])->group($InfrastructureCustomFields->aliasfield('id'))->toArray();
                if (!empty($customFieldData)) {
                    foreach ($customFieldData as $data) {
                        $custom_field_id = $data->custom_field_id;
                        $custom_field = $data->custom_field;
                        if ($value['field'] == 'institution_gender_id') {
                            $newFields[] = [
                                'key' => '',
                                'field' => $this->_dynamicFieldName . '_' . $custom_field_id,
                                'type' => 'string',
                                'label' => __($custom_field)
                            ];
                        }
                    }
                }
            }
            $fields->exchangeArray($newFields);
        }
    }

    public function onExcelGetDesignation(EventInterface $event, Entity $entity)
    {

    }

    public function onExcelGetShiftType(EventInterface $event, Entity $entity)
    {
        if (isset($this->shiftTypes[$entity->shift_type])) {
            return __($this->shiftTypes[$entity->shift_type]);
        } else {
            return '';
        }
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $instituteType = $sheetData['institute_tabs_type'];
        $academicPeriod = $this->InstitutionShifts->AcademicPeriods->getCurrent();
        $institutionId = $this->getInstitutionID();
//        POCOR-9139 start
        if($institutionId && is_numeric($institutionId)) {
            $query->where([
                $this->aliasField('id') => $institutionId
            ]);
        }
//        POCOR-9139 end
        if ($instituteType != 'Contact People' && $instituteType != 'Shifts' && $instituteType != 'Overview') { //POCOR-6880
            $query
                ->select(['area_code' => 'Areas.code', 'shift_name' => 'ShiftOptions.name', 'Owner' => 'Institutions.name', 'Occupier' => 'Institutions.name', 'shift_start_time' => 'InstitutionShifts.start_time', 'shift_end_time' => 'InstitutionShifts.end_time'])
                ->LeftJoin([$this->Areas->getAlias() => $this->Areas->getTable()], [
                    $this->Areas->aliasField('id') . ' = ' . 'Institutions.area_id'
                ])
                ->innerJoinWith('InstitutionShifts')
                ->LeftJoin(['InstitutionShifts' => 'institution_shifts'], [
                    $this->aliasField('institution_id') . ' = InstitutionShifts.institution_id',
                    $this->aliasField('academic_period_id') . ' = InstitutionShifts.academic_period_id'
                ])
                ->LeftJoin([$this->InstitutionCustomFieldValues->getAlias() => $this->InstitutionCustomFieldValues->getTable()], [
                    $this->aliasField('id') . ' = ' . $this->InstitutionCustomFieldValues->aliasField('institution_id')
                ])
                ->leftJoin([$this->InstitutionCustomFields->getAlias() => $this->InstitutionCustomFields->getTable()], [
                    $this->InstitutionCustomFieldValues->aliasField('institution_custom_field_id') . ' = ' . $this->InstitutionCustomFields->aliasField('id')
                ])
                ->LeftJoin([$this->ShiftOptions->getAlias() => $this->ShiftOptions->getTable()], [
                    $this->ShiftOptions->aliasField('id') . ' = ' . $this->InstitutionShifts->aliasField('shift_option_id')
                ])
                ->where([
                    'OR' => [
                        [$this->InstitutionShifts->aliasField('location_institution_id') => $institutionId],
                        [$this->InstitutionShifts->aliasField('institution_id') => $institutionId]
                    ],
                    $this->InstitutionShifts->aliasField('academic_period_id') => $academicPeriod
                ])
                ->group([
                    $this->aliasField('id'),
                ]);

        }
        //Start:POCOR-6880
        if ($instituteType == 'Overview') {

        }
        //END:POCOR-6880

        if ($instituteType == 'Contact People') {

            $institutionContactPersons = TableRegistry::getTableLocator()->get('Institution.InstitutionContactPersons');
            $res = $query->select([
                'person' => $institutionContactPersons->aliasField('contact_person'),
                'designation' => $institutionContactPersons->aliasField('designation'),
                'department' => $institutionContactPersons->aliasField('department'),
                'tel' => $institutionContactPersons->aliasField('telephone'),
                'mobile_no' => $institutionContactPersons->aliasField('mobile_number'),
              //  'faxs' => $institutionContactPersons->aliasField('fax'),
                'contact_email' => $institutionContactPersons->aliasField('email'),
                'preferred' => $institutionContactPersons->aliasField('preferred'),
            ])
                ->leftJoin([$institutionContactPersons->getAlias() => $institutionContactPersons->getTable()], [
                    $this->aliasField('id = ') . $institutionContactPersons->aliasField('institution_id')
                ])
                ->where([$institutionContactPersons->aliasField('institution_id')  => $institutionId]);

        }
        if ($instituteType == 'Shifts') {
            $institutionContactPersons = TableRegistry::getTableLocator()->get('Institution.InstitutionContactPersons');
            $res = $query->select(['academic_period' => 'AcademicPeriods.name', 'shift_name' => 'ShiftOptions.name', 'shift_start_time' => 'InstitutionShifts.start_time', 'shift_end_time' => 'InstitutionShifts.end_time', 'Owner' => 'Institutions.name', 'Occupier' => 'Institutions.name',])
                ->LeftJoin(['InstitutionShifts' => 'institution_shifts'], [
                    $this->aliasField('id') . ' = InstitutionShifts.institution_id',
                ])
                ->LeftJoin([$this->ShiftOptions->getAlias() => $this->ShiftOptions->getTable()], [
                    $this->ShiftOptions->aliasField('id') . ' = ' . $this->InstitutionShifts->aliasField('shift_option_id')
                ])
                ->LeftJoin([$this->ShiftOptions->getAlias() => $this->ShiftOptions->getTable()], [
                    $this->ShiftOptions->aliasField('id') . ' = ' . $this->InstitutionShifts->aliasField('shift_option_id')
                ])
                ->LeftJoin([$this->AcademicPeriods->getAlias() => $this->AcademicPeriods->getTable()], [
                    $this->AcademicPeriods->aliasField('id') . ' = ' . $this->InstitutionShifts->aliasField('academic_period_id')
                ])
                ->where(['InstitutionShifts.institution_id' => $institutionId, 'InstitutionShifts.academic_period_id' => $academicPeriod]);

        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $Guardians = TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionCustomFieldValues');
                $institutionCustomFieldOptions = TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionCustomFieldOptions');
                $institutionCustomFields = TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionCustomFields');

                $guardianData = $Guardians->find()
                    ->select([
                        'id' => $Guardians->aliasField('id'),
                        'institution_id' => $Guardians->aliasField('institution_id'),
                        'institution_custom_field_id' => $Guardians->aliasField('institution_custom_field_id'),
                        'text_value' => $Guardians->aliasField('text_value'),
                        'number_value' => $Guardians->aliasField('number_value'),
                        'decimal_value' => $Guardians->aliasField('decimal_value'),
                        'textarea_value' => $Guardians->aliasField('textarea_value'),
                        'date_value' => $Guardians->aliasField('date_value'),
                        'time_value' => $Guardians->aliasField('time_value'),
                        'checkbox_value_text' => 'institutionCustomFieldOptions.name',
                        'question_name' => 'institutionCustomField.name',
                        'field_type' => 'institutionCustomField.field_type',
                        'field_description' => 'institutionCustomField.description',
                        'question_field_type' => 'institutionCustomField.field_type',
                    ])->leftJoin(
                        ['institutionCustomField' => 'institution_custom_fields'],
                        [
                            'institutionCustomField.id = ' . $Guardians->aliasField('institution_custom_field_id')
                        ]
                    )->leftJoin(
                        ['institutionCustomFieldOptions' => 'institution_custom_field_options'],
                        [
                            'institutionCustomFieldOptions.id = ' . $Guardians->aliasField('number_value')
                        ]
                    )
                    ->where([
                        $Guardians->aliasField('institution_id') => $row->id,
                    ])->toArray();

                $existingCheckboxValue = '';
                foreach ($guardianData as $guadionRow) {
                    $fieldType = $guadionRow->field_type;
                    if ($fieldType == 'TEXT') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'CHECKBOX') {
                        $existingCheckboxValue = trim($row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id], ',') . ',' . $guadionRow->checkbox_value_text;
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = trim($existingCheckboxValue, ',');
                    } else if ($fieldType == 'NUMBER') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = $guadionRow->number_value;
                    } else if ($fieldType == 'DECIMAL') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = $guadionRow->decimal_value;
                    } else if ($fieldType == 'TEXTAREA') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = $guadionRow->textarea_value;
                    } else if ($fieldType == 'DROPDOWN') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = $guadionRow->checkbox_value_text;
                    } else if ($fieldType == 'DATE') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = date('Y-m-d', strtotime($guadionRow->date_value));
                    } else if ($fieldType == 'TIME') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = date('h:i A', strtotime($guadionRow->time_value));
                    } else if ($fieldType == 'COORDINATES') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'NOTE') {
                        $row[$this->_dynamicFieldName . '_' . $guadionRow->institution_custom_field_id] = $guadionRow->field_description;
                    }
                }
                return $row;
            });
        });

    }

    public function onGetName(EventInterface $event, Entity $entity)
    {
        $name = $entity->name;
        $redirectToOverview = false;

        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $redirectToOverview = $ConfigItem->value('default_school_landing_page');
        if ($this->AccessControl->check([$this->controller->getName(), 'dashboard'])) {
            // Redirect to overview page based on School Landing
            if (!$redirectToOverview) {
                $name = $event->getSubject()->HtmlField->link($entity->name, [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'action' => 'dashboard',
                    '0' => $this->paramsEncode(['id' => $entity->id,
                        'institution_id' => $entity->id])
                ]);
            } else {
                $name = $event->getSubject()->HtmlField->link($entity->name, [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'action' => 'Institutions',
                    '0' => "view",
                    '1' => $this->paramsEncode(['id' => $entity->id,
                        'institution_id' => $entity->id])
                ]);
            }
        }
        return $name;
    }

    public function onGetShiftType(EventInterface $event, Entity $entity)
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

    public function onUpdateDefaultActions(EventInterface $event)
    {
        return ['downloadFile'];
    }

    public function onUpdateFieldDateOpened(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $today = new Date();
        $attr['date_options']['endDate'] = $today->format('d-m-Y');
        return $attr;
    }

    public function onUpdateFieldDateClosed(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldInstitutionStatusId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['visible'] = false;
        }

        return $attr;
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $this->setInstitutionStatusId($data);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->shift_type = 0;
        }

        $hasSecurityGroupId = $this->checkSecurityGroupId($entity);
        if (!$hasSecurityGroupId) {
            $id = $this->createSecurityGroup($entity); //POCOR-7166
            $entity->security_group_id = $id;
//            $this->log('createSecurityGroup', 'debug');
        }

        // adding debug log to monitor when there was a different between date_opened's year and year_opened
        $this->debugMonitorYearOpened($entity, $options);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        $LatLongPermission = $ConfigItems->value("latitude_longitude"); //POCOR-7045
        $LatPermission = $LatLongPermission; //POCOR-9257: legacy key — mirror latitude_longitude so they never diverge
        $LongPermission = $LatLongPermission; //POCOR-9257: legacy key — mirror latitude_longitude so they never diverge
        //POCOR-9257: keep legacy latitude_mandatory/longitude_mandatory in sync with latitude_longitude
        $ConfigItems->updateAll(['value' => $LatLongPermission], ['code IN' => ['latitude_mandatory', 'longitude_mandatory']]);

        $DataManagementConnections = TableRegistry::getTableLocator()->get('Archive.DataManagementConnections');
        $DataManagementConnectionsResult = $DataManagementConnections
            ->find()
            ->select(['conn_status_id'])
            ->first();
        $this->Session->write('is_connection_stablished', $DataManagementConnectionsResult->conn_status_id);
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

        $this->field('shift_section', ['type' => 'section', 'title' => __('Shifts'), 'visible' => ['view' => true]]);
        $this->field('shift_type', ['visible' => ['view' => false]]);

        $institutionId = $this->getInstitutionID();
        if($institutionId){
            $this->field('shift_details', [
                'type' => 'element',
                'element' => 'Institution.Shifts/details',
                'visible' => ['view' => true],
                'data' => $this->getViewShiftDetail($institutionId, $this->InstitutionShifts->AcademicPeriods->getCurrent())
            ]);
        }

        $this->field('location_section', ['type' => 'section', 'title' => __('Location')]);

        $language = I18n::getLocale();
        $field = 'area_id';
        $areaLabel = $this->onGetFieldLabel($event, $this->getAlias(), $field, $language, true);
        $this->field('area_section', ['type' => 'section', 'title' => $areaLabel]);
        $field = 'area_administrative_id';
        $areaAdministrativesLabel = $this->onGetFieldLabel($event, $this->getAlias(), $field, $language, true);
        $this->field('area_administrative_section', ['type' => 'section', 'title' => $areaAdministrativesLabel]);
        $this->field('contact_section', ['type' => 'section', 'title' => __('Contact'), 'after' => $field]);
        $this->field('other_information_section', ['type' => 'section', 'title' => __('Other Information'), 'after' => 'website', 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);
        //pocor-5669
        // $this->field('longitude', ['visible' => ['view' => false]]);
        // $this->field('latitude', ['visible' => ['view' => false]]);
        //pocor-5669

        // POCOR-6625 starts <vikas.rathore@mail.valocoders.com>
        $this->field('longitude', ['visible' => true]);
        $this->field('latitude', ['visible' => true]);
        // POCOR-6625 starts <vikas.rathore@mail.valocoders.com>

        if (strtolower($this->action) != 'index') {
            $this->Navigation->addCrumb($this->getHeader($this->action));
        }

        if ($this->action == 'edit') {
            // Moved to InstitutionContacts
            $this->field('contact_section', ['visible' => false]);
            $this->field('contact_person', ['visible' => false]);
            $this->field('telephone', ['visible' => false]);
           // $this->field('fax', ['visible' => false]);
            $this->field('email', ['visible' => false]);
            $this->field('website', ['visible' => false]);
        }

        $this->field('logo_name', ['visible' => false]);
        if ($this->action != 'index') {
            $this->field('logo_content', ['type' => 'image']);
        }

        $LatLongPermission = $ConfigItems->value("latitude_longitude");

        if ($LatLongPermission == LatLongOptions::EXCLUDED) {
            $this->field('longitude', ['visible' => false]);
            $this->field('latitude', ['visible' => false]);
        } elseif ($LatLongPermission == LatLongOptions::MANDATORY) {
            //POCOR-9257: null=false drives the * required marker in ControllerAction forms
            $this->field('latitude', ['null' => false]);
            $this->field('longitude', ['null' => false]);
        }
    }

    /**
     * @param Entity $entity
     * @return bool
     *
     */
    private function checkSecurityGroupInstitution(Entity $entity)
    {
        $institution_id = $entity->id;
        $security_group_id = $entity->security_group_id;
        if (!$security_group_id) {
            $security_group_id = $this->createSecurityGroup($entity);
        }
        $securityGroupInstitutions = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions');
        $securityGroupInstitution = $securityGroupInstitutions
            ->find('all')
            ->where(['institution_id' => $institution_id,
                'security_group_id' => $security_group_id])
            ->first();
        if ($securityGroupInstitution) {
            return true;
        }
        return false;

    }

    //POCOR-7271 changed survey condition
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if($entity->isNew()){
            $this->triggerWebhookCommand($entity,'institution_create'); //POCOR-7971
            return;
        } else {
            $this->triggerWebhookCommand($entity, 'institution_update');
        }
        //Start POCOR-7697
        $hasSecurityGroupInstitution = $this->checkSecurityGroupInstitution($entity);
        if (!$hasSecurityGroupInstitution) {
            $this->createSecurityGroupInstitution($entity); //POCOR-7166
        }
        $SurveyFormsFilters = TableRegistry::getTableLocator()->get('Survey.SurveyFormsFilters');
        $todayDate = date("Y-m-d");
        $institutionType = $entity->institution_type_id;
        $institutionProvider = $entity->institution_provider_id;
        $areaEducation = $entity->area_education_id;
        //survey condition changed in POCOR-7271

        $SurveyFormsFilterObj = $SurveyFormsFilters->find()
            ->select(['institution_type' => 'SurveyFilterInstitutionTypes.institution_type_id', 'institution_provider' => 'SurveyFilterInstitutionProviders.institution_provider_id', 'area_education' => 'SurveyFilterAreas.area_education_id', 'survey_form_id'])
            ->leftJoin(['SurveyFilterInstitutionTypes' => 'survey_filter_institution_types'], [
                'SurveyFilterInstitutionTypes.survey_filter_id = SurveyFormsFilters.id'
            ])
            ->leftJoin(['SurveyFilterInstitutionProviders' => 'survey_filter_institution_providers'], [
                'SurveyFilterInstitutionProviders.survey_filter_id = SurveyFormsFilters.id'])
            ->leftJoin(['SurveyFilterAreas' => 'survey_filter_areas'], [
                'SurveyFilterAreas.survey_filter_id = SurveyFormsFilters.id'])
            ->where(['SurveyFilterInstitutionTypes.institution_type_id' => $institutionType,
                'SurveyFilterInstitutionProviders.institution_provider_id' => $institutionProvider,
                /*'SurveyFilterAreas.area_education_id' => $areaEducation*/])
            ->group([$SurveyFormsFilters->aliasField('survey_form_id')])
            ->toArray();

        $institutionFormIds = [];
        if (!empty($SurveyFormsFilterObj)) {
            foreach ($SurveyFormsFilterObj as $value) {
                $institutionFormIds[] = $value->survey_form_id;
            }
        } else {
            // -1 for all area , all provider , all institution type
            $institutionType = -1;
            $institutionProvider = -1;
            $areaEducation = -1;
            $institutionFormIds = [];
            $SurveyFormsFilterObj = $SurveyFormsFilters->find()
                ->select(['institution_type' => 'SurveyFilterInstitutionTypes.institution_type_id', 'institution_provider' => 'SurveyFilterInstitutionProviders.institution_provider_id', 'area_education' => 'SurveyFilterAreas.area_education_id', 'survey_form_id'])
                ->leftJoin(['SurveyFilterInstitutionTypes' => 'survey_filter_institution_types'], [
                    'SurveyFilterInstitutionTypes.survey_filter_id = SurveyFormsFilters.id'
                ])
                ->leftJoin(['SurveyFilterInstitutionProviders' => 'survey_filter_institution_providers'], [
                    'SurveyFilterInstitutionProviders.survey_filter_id = SurveyFormsFilters.id'])
                ->leftJoin(['SurveyFilterAreas' => 'survey_filter_areas'], [
                    'SurveyFilterAreas.survey_filter_id = SurveyFormsFilters.id'])
                ->where(['SurveyFilterInstitutionTypes.institution_type_id' => $institutionType,
                    'SurveyFilterInstitutionProviders.institution_provider_id' => $institutionProvider,
                    /*'SurveyFilterAreas.area_education_id' => $areaEducation*/])
                ->group([$SurveyFormsFilters->aliasField('survey_form_id')])->toArray();
            if (!empty($SurveyFormsFilterObj)) {
                foreach ($SurveyFormsFilterObj as $value) {
                    $institutionFormIds[] = $value->survey_form_id;
                }

            }
        }

        if ($institutionFormIds[0] != 0) //POCOR-6976
        {
            $SurveyStatusesFilters = TableRegistry::getTableLocator()->get('Survey.SurveyStatuses');
            $SurveyStatusPeriodsFilters = TableRegistry::getTableLocator()->get('Survey.SurveyStatusPeriods');
            $SurveyStatusesFiltersObj = $SurveyStatusesFilters->find()
                ->where([
                    $SurveyStatusesFilters->aliasField('date_enabled <=') => $todayDate,
                    $SurveyStatusesFilters->aliasField('date_disabled >=') => $todayDate,
                    $SurveyStatusesFilters->aliasField('survey_form_id IN') => $institutionFormIds
                ])
                ->group([$SurveyStatusesFilters->aliasField('survey_form_id')])->toArray();
            $SurveyStatusesIds = [];
            $SurveyFormIds = [];
            $multipleFormIds = [];
            if (!empty($SurveyStatusesFiltersObj)) {
                // $SurveyStatusTable = $this->SurveyForms->surveyStatuses;
                $SurveyFormsFilters = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
                foreach ($SurveyStatusesFiltersObj as $statusID => $value) {
                    $surveyFormCount = $SurveyFormsFilters->find()
                        ->select([
                            'id' => $SurveyFormsFilters->aliasField('id'),
                            // 'SurveyForms.id',
                            'SurveyStatusPeriods.academic_period_id',
                        ])
                        ->LeftJoin(['SurveyStatuses' => 'survey_statuses'], [
                            $SurveyFormsFilters->aliasField('id') . ' = SurveyStatuses.survey_form_id',
                        ])
                        ->leftJoin(['SurveyStatusPeriods' => 'survey_status_periods'], [
                            'SurveyStatusPeriods.survey_status_id = SurveyStatuses.id'
                        ])
                        ->where([
                            $SurveyFormsFilters->aliasField('id = ') . $institutionFormIds[$statusID],
                            'SurveyStatuses.id' => $value->id
                        ])
                        ->toArray();

                    if (!empty($surveyFormCount)) {
                        foreach ($surveyFormCount as $mlp => $multipleForm) {
                            $SurveyStatusesIds[] = $multipleForm->SurveyStatusPeriods['academic_period_id'] . ',' . $multipleForm->id;
                            $SurveyFormIds[] = $multipleForm->id;
                            $multipleFormIds[] = $multipleForm->SurveyStatusPeriods['academic_period_id'];
                        }
                    }

                }
                $InstitutionSurveys = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveys');
                //POCOR-7189[START]
                if (!empty($SurveyFormIds)) {
                    $institutionSurveysDelete = $InstitutionSurveys->find()
                        ->where([
                            $InstitutionSurveys->aliasField('institution_id = ') . $entity->id,
                            $InstitutionSurveys->aliasField('survey_form_id IN') => $SurveyFormIds,
                            $InstitutionSurveys->aliasField('academic_period_id IN') => $multipleFormIds,
                        ])
                        ->toArray();
                }
                //POCOR-7189[END]
                if (empty($institutionSurveysDelete)) {
                    foreach ($SurveyStatusesIds as $key => $periodObj) {
                        $InstitutionSurveys = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveys');
                        $value = explode(",", $periodObj);
                        $surveyData = [
                            'status_id' => 1,
                            'academic_period_id' => $value[0],
                            'survey_form_id' => $value[1],
                            'institution_id' => $entity->id,
                            'assignee_id' => 0,
                            'created_user_id' => $key,
                            'created' => new Time('NOW')
                        ];
                        $surveyEntity = $InstitutionSurveys->newEntity($surveyData);
                        $InstitutionSurveys->save($surveyEntity);

                    }
                }
            }
        }
        //End POCOR-7029
    }


    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $securityGroupId = $entity->security_group_id;
        $SecurityGroup = TableRegistry::getTableLocator()->get('Security.SystemGroups');

        $groupEntity = $SecurityGroup->find()->where(['id' =>$securityGroupId])->first();
        if(!empty($groupEntity)) {
            $SecurityGroup->delete($groupEntity);
        }
        // Skip webhook if no authenticated user
        if (!empty($options['skip_callbacks'])) {
            return;
        }

        $this->triggerWebhookCommand($entity, 'institution_delete');
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
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

            //comment in upgrade
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

            $selectString = $modelName . '.name';
            $institutionTypesCount = $institutionRecords
                ->contain([$modelName])
                ->select([
                    //'modelId' => $modelId,
                    'count' => $institutionRecords->func()->count($modelId),
                    'name' => $selectString
                ])
                ->group($modelId);

            //Comment in cakephp4
            $this->advancedSearchQuery($this->request, $institutionTypesCount);

            // Creating the data set
            $dataSet = [];
            foreach ($institutionTypesCount->toArray() as $key => $value) {
                // Compile the dataset
                $dataSet[] = [0 => $value['name'], 1 => $value['count']];
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
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        $this->Session->delete('Institution.Institutions.id');
        $plugin = $this->controller->getPlugin();
        $name = $this->controller->getName();
        // POCOR-8219 no pics if localhost
        // Get the base URL from configuration
        $baseUrl = Configure::read('App.fullBaseUrl');
        // Check if the base URL contains 'localhost' or '127.0.0.1'
        $isLocalhost = strpos($baseUrl, 'localhost') !== false || strpos($baseUrl, '127.0.0.1') !== false;
        $imageUrl =  ['plugin' => $plugin, 'controller' => $name, 'action' => $this->getAlias(),
            'image'];
        $imageDefault = 'fa kd-institutions';
        if (!$isLocalhost){
            $this->field('logo_content', ['type' => 'image', 'ajaxLoad' => true, 'imageUrl' => $imageUrl, 'imageDefault' => '"' . $imageDefault . '"', 'order' => 0]);
        }
        $this->field('area_id', ['sort' => ['field' => 'Areas.name']]); //POCOR-6849
        $this->field('institution_type_id', ['sort' => ['field' => 'Types.name']]); //POCOR-6849
        $this->setFieldOrder([
            'logo_content',
            'code',
            'name',
            'area_id',
            'institution_type_id',
            'institution_status_id'
        ]);

        $this->setFieldVisible(['index'], [
            'logo_content',
            'code',
            'name',
            'area_id',
            'institution_type_id',
            'institution_status_id'
        ]);
        $this->controller->set('ngController', 'AdvancedSearchCtrl');
    }

    public function onGetAreaId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $areaName = $entity->Areas['name'];
            // Getting the system value for the area
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
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

    public function onGetAreaAdministrativeId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->area_administrative_id;
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'area_id' && $this->action == 'index') {
            // Getting the system value for the area
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            $AreaTable = TableRegistry::getTableLocator()->get('Area.AreaLevels');
            $value = $AreaTable->find()
                ->where([$AreaTable->aliasField('level') => $areaLevel])
                ->first();

            if (is_object($value)) {
                return $value->name;
            } else {
                return $areaLevel;
            }
        }elseif($field == 'name'){
             return __('Name');
        }elseif($field == 'code'){
             return __('Code');
        }elseif($field == 'alternative_name'){
             return __('Alternative Name');
        }elseif($field == 'date_opened'){
             return __('Date Open');
        }elseif($field == 'date_closed'){
             return __('Date Close');
        }elseif($field == 'classification'){
             return __('Classification');
        }elseif($field == 'address'){
             return __('Address');
        }elseif($field == 'latitude'){
             return __('Latitude');
        }elseif($field == 'longitude'){
             return __('Longitude');
        }elseif($field == 'telephone'){
             return __('Telephone');
        }elseif($field == 'email'){
             return __('Email');
        }elseif($field == 'website'){
             return __('Website');
        }elseif($field == 'institution_type_id'){
             return __('Institution Type');
        }elseif($field == 'institution_status_id'){
             return __('Status');
        }elseif($field == ''){
             return __('Status');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //the query options are setup so that Security.InstitutionBehavior can reuse it
        $extra['query'] = [
            'contain' => ['Types', 'Areas', 'Statuses'],
            'select' => [
                $this->aliasField('id'),
                $this->aliasField('code'),
                $this->aliasField('name'),
                $this->aliasField('area_id'),
                $this->aliasField('institution_status_id'),
                $this->aliasField('logo_name'), //POCOR-8154
                $this->aliasField('logo_content'), //POCOR-8154
                'Areas.name',
                'Types.name',
                'Statuses.name'
            ],
        ];
        $extra['auto_contain'] = false;
        $query->contain($extra['query']['contain']);
        $query->select($extra['query']['select']);

        // Start:POCOR-6849
        $sortList = ['Areas.name', 'name', 'code', 'Types.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        // End:POCOR-6849

        // POCOR-3983 if no sort, active status will be followed by inactive status
        if (!isset($this->request->getQuery['sort'])) {
            $query->order([
                $this->aliasField('institution_status_id') => 'ASC',
                $this->aliasField('name') => 'ASC'
            ]);
        }
        // end POCOR-3983
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->dashboardQuery = clone $query;
        $search = $this->getSearchKey();
        if (empty($search) && !$this->isAdvancedSearchEnabled()) {
            // redirect to school dashboard if it is only one record and no add access

            //POCOR-6866[START]
            $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
            $securityFunctionsData = $securityFunctions
                ->find()
                ->select([
                    'SecurityFunctions.id'
                ])
                ->where([
                    'SecurityFunctions.name' => 'Institution',
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityFunctions.module' => 'Institutions',
                    'SecurityFunctions.category' => 'General'
                ])
                ->first();
            $permission_id = $_SESSION['Permissions']['Institutions']['Institutions']['view'][0];
            if(!empty($permission_id)){
                $securityRoleFunctions =  TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');

                $securityRoleFunctionsData = $securityRoleFunctions
                ->find()
                ->select([
                    'SecurityRoleFunctions._add'
                ])
                ->where([
                    'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
                    'SecurityRoleFunctions.security_role_id' => $permission_id,
                ])
                ->first();
            }
            //POCOR-7191::Start
            $session = $this->Session;
            $superAdmin = $session->read('Auth.User.super_admin');
            if ($superAdmin == 1 && empty($permission_id)) {
                $addAccess = $this->AccessControl->check(['Institutions', 'add']);
            } else {
                $addAccess = $securityRoleFunctionsData->_add;
            }
            //POCOR-7191::End
            //POCOR-6866[END]
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');//POCOR-8751
            $mode = $ConfigItems->value("edition"); //POCOR-8751
            if ($data->count() == 1 && (!$addAccess || Configure::read('schoolMode') || $mode == 'School')) {//POCOR-8751
                $entity = $data->first();
                $event->stopPropagation();
                $action = ['plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'action' => 'dashboard',
                    $this->paramsEncode(['id' => $entity->id,
                        'institution_id' => $entity->id])];
                return $this->controller->redirect($action);
            } elseif ($data->count() == 0 && Configure::read('schoolMode') && $mode == 'School') { //POCOR-8751
                $event->stopPropagation();
                $this->Alert->info('Institutions.noInstitution', ['reset' => true]);
                $action = ['plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'action' => 'Institutions',
                    'add'];
                return $this->controller->redirect($action);
            } elseif(empty(!$search) && empty($this->isAdvancedSearchEnabled())){
                $query->find('SearchInstitution', ['search' => $search]);
            }
        }

        // to display message after redirect
        $sessionKey = 'HideButton.warning';
        if ($this->Session->check($sessionKey)) {
            $this->Alert->warning('security.noAccess');
            $this->Session->delete($sessionKey);
        }
    }

    public function deleteAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
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
            'contact_person', 'telephone', 'email', 'website',

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

        $queryString = $this->request->getParam('pass')[1];

        $extraButtons = [
            'close' => [
                'Institution' => ['Institutions', 'edit', $queryString],
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
                    'url' => [0 => 'edit', 1 => $queryString]
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }
        // POCOR-9519 start
        $this->addManualButton($extra);
        // POCOR-9519 end
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $userId = $this->Session->read('Auth.User.id');
        $superAdmin = $this->Session->read('Auth.User.super_admin');

        $data['userId'] = $userId;
        $data['superAdmin'] = $superAdmin;
    }

    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
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
        //Start:POCOR-6660
        $this->field('latitude', ['type' => 'hidden']);
        $this->field('longitude', ['type' => 'hidden']);

        //End:POCOR-6660
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
            'contact_person', 'telephone', 'email', 'website',
        ]);
        $this->addManualButton($extra); // POCOR-9519
    }

    public function onUpdateFieldInstitutionProviderId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $providerOptions = [];
        $selectedSectorId = '';

        if (isset($request->getData($this->getAlias())['institution_sector_id'])) {
            $selectedSectorId = $request->getData($this->getAlias())['institution_sector_id'];
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
        $attr['onChangeReload'] = true;
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

    public function onUpdateFieldInstitutionTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            // list($typeOptions, $selectedType) = array_values($this->getTypeOptions());

            // $attr['options'] = $typeOptions;
            $attr['onChangeReload'] = 'changeType';
        }

        return $attr;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $institutionId = $entity->id;
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $securityRoles = $this->getInstitutionRoles($userId, $institutionId);
            foreach ($buttons as $key => $b) {
                $url = $this->url($key);
                if (!$this->AccessControl->check($url, $securityRoles)) {
                    unset($buttons[$key]);
                }
            }
        }
        $queryString = $this->paramsEncode(['id' => $institutionId, 'institution_id' => $institutionId]);
        foreach ($buttons as &$button) {
            if (isset($button['url'][1])) {
                $button['url'][1] = $queryString;
                $button['url']['institutionId'] = $button['url'][1];
            }
        }
        // POCOR-3125 history button permission to hide and show the link
        if (isset($buttons['view']) && $this->AccessControl->check(['InstitutionHistories', 'index'])) {
            $customUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'History',
                '0' => 'index',
                '1' => $queryString
            ];
            $icon = '<i class="fa fa-history"></i>';
            // $viewbutton = $buttons['view'];
            // unset($viewbutton['url']);
            // $buttons['history'] = $viewbutton;
            // $buttons['history']['label'] = $icon . __('History');
            // $buttons['history']['url'] = $customUrl;

            $buttons['history'] = $buttons['view'];
            $buttons['history']['label'] = $icon . __('History');
            $buttons['history']['url']['plugin'] = 'Institution';
            $buttons['history']['url']['controller'] = 'Institutions';
            $buttons['history']['url']['action'] = 'History';
            $buttons['history']['url'][0] = 'index';
            $buttons['history']['url'][1] = $queryString;
            if(isset( $buttons['history']['url']['institutionId'])) {
                unset($buttons['history']['url']['institutionId']);
            }
            if(isset( $buttons['history']['url']['?'])) {
                unset( $buttons['history']['url']['?']);
            }
            if(isset( $buttons['history']['url']['page'])) {
                unset( $buttons['history']['url']['page']);
            }
        }

        //die('<pre>' . print_r($buttons,true));
        return $buttons;
    }

    public function addEditOnChangeType(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('institution_type_id', $request->getData($this->getAlias()))) {
                    $selectedType = $request->getData($this->getAlias())['institution_type_id'];
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

    public function onUpdateFieldClassification(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function onGetClassification(EventInterface $event, Entity $entity)
    {
        $selectedClassification = $entity->classification;
        return __($this->classificationOptions[$selectedClassification]);
    }

    public function onExcelGetClassification(EventInterface $event, Entity $entity)
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
        $SecurityGroupInstitutions = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions');
        $SecurityGroupAreas = TableRegistry::getTableLocator()->get('Security.SecurityGroupAreas');
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
            ->enableHydration(false)
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
        $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        return $SecurityGroupUsers->getRolesByUserAndGroup($groupIds, $userId);
    }

    public function debugMonitorYearOpened($entity, $options)
    {
        $time = strtotime($entity->date_opened);
        $yearDateOpened = date("Y", $time);
        $yearOpened = $entity->year_opened;

        if ($yearDateOpened != $yearOpened) {
            $debugInfo = $this->getAlias() . ' (Institution Name: ' . $entity->name . ', Date_Opened: ' . $entity->date_opened . ', year_opened: ' . $yearOpened . ')';

            Log::write('debug', $debugInfo);
            Log::write('debug', $options);
            Log::write('debug', 'End of monitoring year opened');
        }
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->SecurityGroups->getAlias(), $this->InstitutionSurveys->getAlias(), $this->StudentSurveys->getAlias(),
            $this->StaffSurveys->getAlias(),
            $this->StaffPositionProfiles->getAlias(), $this->InstitutionActivities->getAlias(), $this->StudentPromotion->getAlias(),
            $this->StudentAdmission->getAlias(), $this->StudentWithdraw->getAlias(), $this->StudentTransferIn->getAlias(), $this->StudentTransferOut->getAlias(),
            $this->CustomFieldValues->getAlias(), $this->CustomTableCells->getAlias()
        ];
    }

    public function getCustomFilter(EventInterface $event)
    {
        $filters = [
            //POCOR-6618 Starts hide shift type filter from advance search
            // POCOR-6748 // again add shift filter
            'shift_type' => [
                'label' => __('Shift Type'),
                'options' => $this->shiftTypes
            ],//POCOR-6618 Ends*/
            'classification' => [
                'label' => __('Classification'),
                'options' => $this->classificationOptions
            ],
            //alternative_name rename to Shift Ownership filter from advance search because Institutions table only column showing in filter advance search
            'alternative_name' => [
                'label' => __('Shift Ownership'),
                'options' => $this->shiftOwnership
            ]
        ];
        return $filters;
    }

    public function findNotExamCentres(Query $query, array $options)
    {
        // POCOR-8919start
            $query
                ->leftJoinWith('ExaminationCentres')
                ->where([
                    'ExaminationCentres.institution_id IS NULL'
                ]);
            return $query;
// POCOR-8919 end
    }

    public function findMap(Query $query, array $options)
    {
        // [POCOR-6379] - Anand Malvi
        $institutionStatus = TableRegistry::getTableLocator()->get('Institution.Statuses');
        $activeInstitutionStatus = $institutionStatus->find()
            ->select(['id' => $institutionStatus->aliasField('id')])
            ->where([$institutionStatus->aliasField('code') => 'ACTIVE'])->first();
        // [POCOR-6379] - Anand Malvi
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

                    $institutionId = $institution->id;
                    $encodedId = $this->paramsEncode(['id' => $institutionId, 'institution_id' => $institutionId]);
                    $url = Router::url([
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'Institutions',
                        '0' => 'view',
                        '1' => $encodedId
                    ], true);
                    $longitude = $institution->has('longitude') ? $institution->longitude : 0;
                    $latitude = $institution->has('latitude') ? $institution->latitude : 0;

                    $obj = [
                        'id' => $encodedId,
                        'lng' => $longitude,
                        'lat' => $latitude,
                        'content' => $institution->name . "<br/>" . $institution->code . "<br/><br/><a href='" . $url . "' target='_blank'>" . __('View Details') . "</a>"
                    ];

                    $formattedResults[$groupId]['data'][] = $obj;
                }

                $colorIndex = 0;
                foreach ($formattedResults as $key => &$obj) {
                    $colors = $this->getMarkerColor();
                    $markerColor = $colors[$colorIndex % sizeof($colors)];

                    $numberOfRecords = sizeof($obj['data']);
                    $title = $institutionTypes[$key] . ' (' . $numberOfRecords . ')';

                    $obj['marker'] = [
                        'icon' => 'university',
                        'markerColor' => $markerColor,
                        'title' => $title,
                        'id' => $key
                    ];

                    $colorIndex++;
                }

                return $formattedResults;
            })
            // [POCOR-6379] - Anand Malvi
            ->where([
                $this->aliasField('institution_status_id') => $activeInstitutionStatus->id
            ]);
        // [POCOR-6379] - Anand Malvi
        return $query;
    }

    private function getMarkerColor()
    {
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
        if (!empty($institutionId)) {
            $institutionEntity = $this->get($institutionId, ['contain' => 'Statuses']);
            if ($institutionEntity->has('status') && $institutionEntity->status->has('code')) {
                if ($institutionEntity->status->code == 'INACTIVE') {
                    $isActive = false;
                }
            }
        }

        return $isActive;
    }

    public function getDefaultImgMsg()
    {
        $width = self::logoMaxWidth; //POCOR-7935
        $height = self::logoMaxHeight; //POCOR-7935
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
        $value = $this->defaultLogoView;

        return $value;
    }

    public function onGetLogoContent(EventInterface $event, Entity $entity)
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
        $search = $options['_controller']->request->getQuery['_searchByCodeOrName'];
        if (!empty($search)) {
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

    /** Get the feature the value of Shift Options
     * @author Rahul Singh <rahul.singh@mail.valuecoder.com>
     *return array
     *POCOR-6764
     */
    public function getShiftTypesOptions()
    {
        $ShiftOptions = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');

        $shiftOptionsOptions = $ShiftOptions
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->find('visible')
            ->find('order')
            ->toArray();
        return $shiftOptionsOptions;
    }

    //POCOR-7191::Start
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->checkInstitutionRecords($entity)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        } else {

            //POCOR -7324 starts
            $securityGroupInstitutions = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions')
                ->find()->where(['institution_id' => $entity->id])->first();
            if(!empty( $securityGroupInstitutions)) {
                $securityGroups = TableRegistry::getTableLocator()->get('Security.SystemGroups')
                ->find()->where(['id' => $securityGroupInstitutions->security_group_id])->first(); //POCOR-7755
            }
            $institutionActivities = TableRegistry::getTableLocator()->get('Institution.InstitutionActivities')
                ->find()->where(['institution_id' => $entity->id])->first();
            if ($securityGroupInstitutions && $securityGroups) {
                TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions')->delete($securityGroupInstitutions);
                TableRegistry::getTableLocator()->get('Security.SecurityGroups')->delete($securityGroups); //POCOR-7755
            }
            if ($institutionActivities) {
                TableRegistry::getTableLocator()->get('Institution.InstitutionActivities')->delete($institutionActivities);
            }
            //POCOR -7324 ends
            $institutionTable = TableRegistry::getTableLocator()->get('Institution.Institutions')
                ->find()->where(['id' => $entity->id])->first();
            if (TableRegistry::getTableLocator()->get('Institution.Institutions')->delete($entity)) {
                $this->Alert->success('general.delete.success', ['reset' => true]);
                return $this->controller->redirect($this->url('index'));
                //return $this->controller->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions']); // POCOR-7253
            }
        }
    }

    public function checkInstitutionRecords($entity)
    {
        $result = false;
        $institutionId = $entity->id ?? 0;

        if ($institutionId) {

            // Start POCOR-7253

            // // count all institution_activities
            // $institutionActivities = TableRegistry::getTableLocator()->get('institution_activities')
            //     ->find()->where(['institution_id' => $institutionId])->count();

            // // count all institution_custom_field_values
            // $institutionCustomFieldValues = TableRegistry::getTableLocator()->get('institution_custom_field_values')
            //     ->find()->where(['institution_id' => $institutionId])->count();

            // // count all institution_surveys
            // $institutionSurveys = TableRegistry::getTableLocator()->get('institution_surveys')
            //     ->find()->where(['institution_id' => $institutionId])->count();

            // // count all security_group_institutions
            // $securityGroupInstitutions = TableRegistry::getTableLocator()->get('security_group_institutions')
            //     ->find()->where(['institution_id' => $institutionId])->count();


            // if($institutionActivities || $institutionCustomFieldValues || $institutionSurveys || $securityGroupInstitutions) {
            //     $result = true;
            // }


            $institution_attachments = TableRegistry::getTableLocator()->get('Institution.InstitutionAttachments')->find()->where(['institution_id' => $institutionId])->count();
            $institution_positions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions')->find()->where(['institution_id' => $institutionId])->count();
            $institution_shifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts')->find()->where(['institution_id' => $institutionId])->count();
            $institution_classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses')->find()->where(['institution_id' => $institutionId])->count();
            $institution_custom_field_values = TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionCustomFieldValues')->find()->where(['institution_id' => $institutionId])->count();
            $institution_subject_students = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents')->find()->where(['institution_id' => $institutionId])->count();
            $institution_subjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects')->find()->where(['institution_id' => $institutionId])->count();
            $institution_staff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff')->find()->where(['institution_id' => $institutionId])->count();
            $staff_behaviours = TableRegistry::getTableLocator()->get('Staff.StaffBehaviours')->find()->where(['institution_id' => $institutionId])->count();
            $institution_students = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents')->find()->where(['institution_id' => $institutionId])->count();
           // $student_behaviours = TableRegistry::getTableLocator()->get('Student.StudentBehaviours')->find()->where(['institution_id' => $institutionId])->count();
            $studentBehavioursTable = TableRegistry::getTableLocator()->get('Student.StudentBehaviours');
            $query = $studentBehavioursTable->find();
            $student_behaviours = $query->applyOptions(['skipBeforeFind' => true])->where(['institution_id' => $institutionId])->count();
            $institution_student_absences = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentAbsences')->find()->where(['institution_id' => $institutionId])->count();
            $institution_bank_accounts = TableRegistry::getTableLocator()->get('Institution.InstitutionBankAccounts')->find()->where(['institution_id' => $institutionId])->count();
            $institution_fees = TableRegistry::getTableLocator()->get('Institution.InstitutionFees')->find()->where(['institution_id' => $institutionId])->count();
            $institution_lands = TableRegistry::getTableLocator()->get('Institution.InstitutionLands')->find()->where(['institution_id' => $institutionId])->count();
            $institution_buildings = TableRegistry::getTableLocator()->get('Institution.InstitutionBuildings')->find()->where(['institution_id' => $institutionId])->count();
            $institution_floors = TableRegistry::getTableLocator()->get('Institution.InstitutionFloors')->find()->where(['institution_id' => $institutionId])->count();
            $institution_rooms = TableRegistry::getTableLocator()->get('Institution.InstitutionRooms')->find()->where(['institution_id' => $institutionId])->count();
            $institution_grades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades')->find()->where(['institution_id' => $institutionId])->count();
            $assessment_item_results = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults')->find()->where(['institution_id' => $institutionId])->count();
            $institution_quality_rubrics = TableRegistry::getTableLocator()->get('Institution.InstitutionRubrics')->find()->where(['institution_id' => $institutionId])->count();
            $institution_quality_visits = TableRegistry::getTableLocator()->get('Quality.InstitutionQualityVisits')->find()->where(['institution_id' => $institutionId])->count();
            $examination_centres = TableRegistry::getTableLocator()->get('Examination.ExaminationCentres')->find()->where(['institution_id' => $institutionId])->count();
            $examination_student_subject_results = TableRegistry::getTableLocator()->get('Examination.ExaminationStudentSubjectResults')->find()->where(['institution_id' => $institutionId])->count();
            $institution_committees = TableRegistry::getTableLocator()->get('Institution.InstitutionCommittees')->find()->where(['institution_id' => $institutionId])->count();
            $examination_centres_examinations_institutions = TableRegistry::getTableLocator()->get('Examination.ExaminationCentresExaminationsInstitutions')->find()->where(['institution_id' => $institutionId])->count();
            $institution_staff_transfers = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffTransfers')->find()->where(['new_institution_id' => $institutionId])->count();
            $institution_staff_transfers_1 = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffTransfers')->find()->where(['previous_institution_id' => $institutionId])->count();


            if ($institution_attachments || $institution_positions || $institution_shifts || $institution_classes || $institution_custom_field_values || $institution_subject_students || $institution_subjects || $institution_staff || $staff_behaviours || $institution_students || $student_behaviours || $institution_student_absences || $institution_bank_accounts || $institution_fees || $institution_lands || $institution_buildings || $institution_floors || $institution_rooms || $institution_grades || $assessment_item_results || $institution_quality_rubrics || $institution_quality_visits || $examination_centres || $examination_student_subject_results || $institution_committees || $examination_centres_examinations_institutions || $institution_staff_transfers || $institution_staff_transfers_1) {
                $result = true;
            }
            // End POCOR-7253


        }
        return $result;
    }

    /**
     * @param Entity $entity
     * @return mixed|null
     *
     */
    private function createSecurityGroup(Entity $entity)
    {
        $userId = $_SESSION['Auth']['User']['id']; //POCOR-7166
        //POCOR-7116 :Start
        $insName = $entity->code . " - " . $entity->name;
        $SecurityGroupsTable = TableRegistry::getTableLocator()->get('Security.SystemGroups');
        $SecurityGroupsEntity = [
            'name' => $insName,
            'modified_user_id' => $userId, //POCOR-7166
            'modified' => NULL,
            'created_user_id' => $userId, //POCOR-7166
            'created' => date('Y-m-d h:i:s')
        ];
        $SecurityGroups = $SecurityGroupsTable->newEntity($SecurityGroupsEntity);
        $id = null;
        if ($SecurityGroupResult = $SecurityGroupsTable->save($SecurityGroups)) {
            $id = $SecurityGroupResult->id;
        }
        return $id;
    }

    /**
     * @param Entity $entity
     * @return bool|\Cake\Datasource\EntityInterface|mixed|null
     *
     */
    private function createSecurityGroupInstitution(Entity $entity)
    {
        $userId = $_SESSION['Auth']['User']['id']; //POCOR-7166
        $institution = $entity->id;
        $security_group_id = $entity->security_group_id;
        $SecurityGroupsInstitutionsTable = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions');
        $SecurityGroupsInstitutionsEntity = [
            'institution_id' => $institution,
            'security_group_id' => $security_group_id,
            'modified' => NULL,
            'created_user_id' => $userId, //POCOR-7166
            'created' => date('Y-m-d h:i:s')
        ];
        $SecurityGroups = $SecurityGroupsInstitutionsTable->newEntity($SecurityGroupsInstitutionsEntity);
        $SecurityGroupInstitutionsResult = null;
        if ($SecurityGroupInstitutionsResult = $SecurityGroupsInstitutionsTable->save($SecurityGroups)) {
//            $this->log('security_group_institution created', 'debug');
            return $SecurityGroupInstitutionsResult;
        }
        return $SecurityGroupInstitutionsResult;
    }

    /**
     * @param Entity $entity
     * @return bool
     *
     */
    private function checkSecurityGroupId(Entity $entity)
    {
        $security_group_id = $entity->security_group_id;
        if (!$security_group_id) {
            return false;
        }
        $SecurityGroupsTable = TableRegistry::getTableLocator()->get('Security.SystemGroups');
        $securityGroup = $SecurityGroupsTable
            ->find('all')
            ->where(['id' => $security_group_id])
            ->first();
        if ($securityGroup) {
            return true;
        }
        return false;
    }
    //POCOR-7191::end

    //POCOR-7971 Start

    public function triggerWebhookCommand(Entity $entity, string $eventKey): void
    {
        // Only run when triggered from Institution controller
        $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');

        $user = $Webhooks->resolveCurrentUser();
        $contain = ['Sectors', 'Types', 'Areas', 'AreaAdministratives', 'Localities', 'Genders'];
        $tableAlias = 'Institution.Institutions';
        $body = $Webhooks->prepareWebhookBody($tableAlias, $entity, $contain);
        $body['classification_name']       = ($entity->classification == 1)
            ? __('Academic Institution')
            : __('Non-academic Institution');


        // --- 2. Append custom fields ---
        $customFieldValues = TableRegistry::getTableLocator()
            ->get('InstitutionCustomField.InstitutionCustomFieldValues');
        $customFields      = TableRegistry::getTableLocator()
            ->get('InstitutionCustomField.InstitutionCustomFields');
        $optionsTable      = TableRegistry::getTableLocator()
            ->get('InstitutionCustomField.InstitutionCustomFieldOptions');

        $cfData = $customFieldValues->find()
            ->select([
                'id'                => $customFields->aliasField('id'),
                'name'              => $customFields->aliasField('name'),
                'field_type'        => $customFields->aliasField('field_type'),
                'text_value'        => $customFieldValues->aliasField('text_value'),
                'number_value'      => $customFieldValues->aliasField('number_value'),
                'decimal_value'     => $customFieldValues->aliasField('decimal_value'),
                'textarea_value'    => $customFieldValues->aliasField('textarea_value'),
                'date_value'        => $customFieldValues->aliasField('date_value'),
                'time_value'        => $customFieldValues->aliasField('time_value'),
                'checkbox_value_text' => 'institutionCustomFieldOptions.name',
            ])
            ->leftJoin(
                [$customFields->getAlias() => $customFields->getTable()],
                [$customFields->aliasField('id =') .
                    $customFieldValues->aliasField('institution_custom_field_id')]
            )
            ->leftJoin(
                ['institutionCustomFieldOptions' => $optionsTable->getTable()],
                ['institutionCustomFieldOptions.institution_custom_field_id = ' .
                    $customFieldValues->aliasField('institution_custom_field_id')]
            )
            ->where([$customFieldValues->aliasField('institution_id') => $entity->id])
            ->group([$customFields->aliasField('id')])
            ->enableHydration(false)
            ->toArray();

        $customFieldsArr = ['custom_field' => []];
        foreach ($cfData as $i => $val) {
            $fieldType = $val['field_type'] ?? '';
            $entry = [
                'id'   => $val['id'] ?? '',
                'name' => $val['name'] ?? '',
            ];

            switch ($fieldType) {
                case 'TEXT':
                    $entry['text_value'] = $val['text_value'] ?? '';
                    break;
                case 'DECIMAL':
                    $entry['decimal_value'] = $val['decimal_value'] ?? '';
                    break;
                case 'TEXTAREA':
                    $entry['textarea_value'] = $val['textarea_value'] ?? '';
                    break;
                case 'DATE':
                    $entry['date_value'] = $val['date_value'] ?? '';
                    break;
                case 'TIME':
                    $entry['time_value'] = !empty($val['time_value'])
                        ? date('h:i A', strtotime($val['time_value']))
                        : '';
                    break;
                case 'CHECKBOX':
                    $entry['checkbox_value'] = $val['checkbox_value_text'] ?? '';
                    break;
                case 'DROPDOWN':
                    $entry['dropdown_value'] = $val['checkbox_value_text'] ?? '';
                    break;
                case 'COORDINATES':
                    $entry['cordinate_value'] = $val['text_value'] ?? '';
                    break;
            }
            $customFieldsArr['custom_field'][] = $entry;
        }

        $body = array_merge($body, $customFieldsArr);
        if ($eventKey === 'institution_delete') {
            $body['deleted_at'] = date('Y-m-d H:i:s');
            $body['deleted_by'] = $user['openemis_no']
                ?? $user['username']
                ?? 'system';
        }

        // --- 3. Queue webhook for async processing (POCOR-9257) ---
        try {
            $WebhookQueue = TableRegistry::getTableLocator()->get('Alert.WebhookQueue'); //POCOR-9257: moved to Alert plugin
            $result = $WebhookQueue->queueWebhook($eventKey, $body, $user);
            if ($result) {
                // Log::debug("[InstitutionsTable] ✓ Queued webhook for event: {$eventKey}, institution ID: {$entity->id}");
            } else {
                Log::warning("[InstitutionsTable] Failed to queue webhook for event: {$eventKey}, institution ID: {$entity->id}");
            }
        } catch (\Throwable $e) {
            // POCOR-9257: Graceful degradation - queueing failures don't break parent process
            Log::error("[InstitutionsTable] Exception while queueing webhook: " . $e->getMessage());
        }

    }

    //POCOR-7971 ::end

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //echo "<pre>"; print_r($query->toArray); die;
      //  return $query;
    }

    /**
     * @param ArrayObject|array $extra
     * @return void
     */
    private function addManualButton(ArrayObject|array $extra): void
    {
        $is_manual_exist = $this->getManualUrl('Institutions', 'Institution', 'General');
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
    }
}
