<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;

/**
 * Get the Staff  details in excel file 
 * POCOR-6581
 */
class InstitutionStaffPositionProfileTable extends AppTable
{

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles', 'foreignKey' => 'institution_staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');

    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
        $reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData           = json_decode($settings['process']['params']);
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $subject = TableRegistry::get('Institution.InstitutionSubjects');
        $academic_period = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $getyear = $academic_period->find('all')
                   ->select(['name'=>$academic_period->aliasField('start_year')])
                   ->where(['id'=>$academicPeriodId])
                   ->limit(1);
        foreach($getyear->toArray() as $val) {
            $year  = $val['start_year'];
        }
        $institution = TableRegistry::get('Institutions');
        $staffStatus = TableRegistry::get('Staff.StaffStatuses');
        $positions = TableRegistry::get('Institution.InstitutionPositions');
        $grade = TableRegistry::get('Institution.StaffPositionGrades');
        $title = TableRegistry::get('Institution.StaffPositionTitles');
        $SecurityUser = TableRegistry::get('User.Users');
        $OR = [
                [$this->aliasField('end_year IS NULL')]
            ];

            $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('FTE'),
                'start_year'=>$this->aliasField('start_year'),
                $this->aliasField('staff_id'),  
                $this->aliasField('staff_type_id'),
                $this->aliasField('staff_status_id'),
                $this->aliasField('institution_id'),
                'position_id'=> $this->aliasField('institution_position_id'),
                'subject_name' => 'InstitutionSubjects.name',
                'academic_period' => 'AcademicPeriods.name',
                'academic_id' => 'AcademicPeriods.id',
               // 'absences_day' => $this->find()->func()->sum('InstitutionStaffLeave.number_of_days'),
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'Institutions_name' => 'Institutions.name',
                        'Institutions_code' => 'Institutions.code',//POCOR-6886 selecting Institutions code
                    ]
                ],
                'Users' => [
                    'fields' => [
                        'Users.id', // this field is required for Identities and IdentityTypes to appear
                        'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                        'Identities_number' => 'Users.username',
                    ]
                ],
                
                'StaffTypes' => [
                    'fields' => [
                        'StaffTypes.name'
                    ]
                ],
                'StaffStatuses' => [
                    'fields' => [
                        'employment_status'=>'StaffStatuses.name'
                    ]
                ],
                'Positions' => [
                    'fields' => [
                        'assignee_id' => 'assignee_id',
                        'position_no' => 'Positions.position_no',
                        'is_home' => 'Positions.is_homeroom',
                    ]
                ],
                'Positions.Assignees' => [
                    'fields' => [
                        'assigneefName' => 'Assignees.first_name',
                        'assigneelName' => 'Assignees.last_name',
                    ]
                ],

                'Positions.Statuses' => [
                    'fields' => [
                        'staffStatus'=>'Statuses.name'
                    ]
                ],
                'Positions.StaffPositionTitles' => [
                    'fields' => [
                        'position_title' => 'StaffPositionTitles.name',
                        'positionsType' => 'StaffPositionTitles.type',
                    ]
                ],
                'Positions.StaffPositionGrades' => [
                    'fields' => [
                        'grade_name' => 'StaffPositionGrades.name',
                        
                    ]
                ],
                
            ])
            ->leftJoin(
                [$academic_period->alias() => $academic_period->table()],
                [$academic_period->aliasField('id = ') . $academicPeriodId]
            )
            ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            $this->aliasfield('staff_id') . ' = '.'InstitutionSubjectStaff.staff_id',
                        ])
            
            ->leftJoin(
                [$subject->alias() => $subject->table()],
                [$subject->aliasField('id = ') . 'InstitutionSubjectStaff.institution_subject_id']
            )
            ->group([$this->aliasfield('staff_id')])
        ->where([
                    'OR' => [
                        'OR' => $OR,
                        $this->aliasfield('start_year') => $year,
                    ]
                ])
        ->andWhere([$this->aliasfield('institution_id') => $institutionId]);
        

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        {
            return $results->map(function ($row)
            {
                    $row['referrer_full_name'] = $row['first_name'] .' '. $row['last_name'];
                    if($row['is_home']==1){
                        $row['referrer_is_home'] = 'Yes';
                    }else{
                        $row['referrer_is_home'] = 'No';
                    }
                    if($row['positionsType']==1){
                        $row['referrer_is_type'] = 'Teaching';
                    }else{
                        $row['referrer_is_type'] = 'Non-Teaching';
                    }
                    $row['referrer_position_type'] = $row['title'] .'-'. $row['referrer_is_type'];

                    $row['assignee_user_full_name'] = $row['assigneefName'] .' '. $row['assigneelName'];
                return $row;
            });
        });
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'Positions.position_no',
            'field' => 'position_no',
            'type' => 'string',
            'label' => __('Number')
        ];
        $newFields[] = [
            'key'   => 'referrer_position_type',
            'field' => 'referrer_position_type',
            'type'  => 'string',
            'label' => __('Title'),
        ];
        $newFields[] = [
            'key'   => 'Positions.StaffPositionGrades',
            'field' => 'grade_name',
            'type'  => 'string',
            'label' => __('Grade'),
        ];
        /**POCOR-6886 starts - added Institutions code colunm to report*/ 
        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'Institutions_code',
            'type' => 'string',
            'label' =>__('Institution Code'),
        ];
        /**POCOR-6886 ends*/ 
        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'Institutions_name',
            'type' => 'string',
            'label' =>__('Institution'),
        ];
        $newFields[] = [
            'key'   => 'assignee_user_full_name',
            'field' => 'assignee_user_full_name',
            'type'  => 'string',
            'label' => __('Assignee'),
        ];
        $newFields[] = [
            'key'   => 'referrer_is_home',
            'field' => 'referrer_is_home',
            'type'  => 'string',
            'label' => __('Homeroom Teacher'),
        ];
        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Staff'),
        ];
        $newFields[] = [
            'key' => 'InstitutionStaff.FTE',
            'field' => 'FTE',
            'type' => 'integer',
            'label' => 'FTE',
        ];
        $newFields[] = [
            'key'   => 'StaffStatuses',
            'field' => 'employment_status',
            'type'  => 'string',
            'label' => __('Status'),
        ];
        $newFields[] = [
            'key'   => 'identity_type',
            'field' => 'identity_type',
            'type'  => 'string',
            'label' => __('Identity Type'),
        ];
        
        $newFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        
        $newFields[] = [
            'key'   => 'academic_period',
            'field' => 'academic_period',
            'type'  => 'integer',
            'label' => __('Academic Period'),
        ];
        $newFields[] = [
            'key'   => 'institution_classes',
            'field' => 'institution_classes',
            'type'  => 'string',
            'label' => __('Classes'),
        ];
        $newFields[] = [
            'key'   => 'subject_name',
            'field' => 'subject_name',
            'type'  => 'string',
            'label' => __('Subject'),
        ];
        $newFields[] = [
            'key'   => 'staff_absence_day',
            'field' => 'staff_absence_day',
            'type'  => 'integer',
            'label' => __('Absences'),
        ];

        $fields->exchangeArray($newFields);
    }

    /**
     * Get staff absences days
     */
    public function onExcelGetStaffAbsenceDay(Event $event, Entity $entity)
    {
        $userid =  $entity->staff_id;
        $Institutionstaff = TableRegistry::get('Institution.InstitutionStaff');
        $staffleave = TableRegistry::get('Institution.InstitutionStaffLeave');
        $absenceDay = $staffleave->find()
            ->leftJoin(['InstitutionStaff' => 'institution_staff'], ['InstitutionStaff.staff_id = '. $staffleave->aliasField('staff_id')])
            ->select([
                'days' => "SUM(".$staffleave->aliasField('number_of_days').")"

            ])
           // ->group(['InstitutionStaffLeave.staff_id'])
            ->where([$staffleave->aliasField('staff_id') => $userid]);
            if($absenceDay!=null){
                $data = $absenceDay->toArray();
                $entity->staff_absence_day = '';
                foreach($data as $key=>$val){
                    $entity->staff_absence_day = $val['days'];
                }
                 return $entity->staff_absence_day;
            }
            return '';
    }

    public function onExcelGetInstitutionClasses(Event $event, Entity $entity)
    {
        $classname = [];
        $staff = TableRegistry::get('Institution.InstitutionStaff');
        $positions = TableRegistry::get('Institution.InstitutionPositions');
        $homeRoomteacher = $staff->find()
                            ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
                            $this->aliasfield('institution_position_id') . ' = '.'InstitutionPositions.id',
                        ])
                        ->where(['InstitutionPositions.is_homeroom'=>1,
                            'institution_id'=>$entity->institution_id
                    ]);
        if(!empty($homeRoomteacher)){                
            if ($entity->staff_id) 
            {
                $class = TableRegistry::get('Institution.InstitutionClasses');
                $getclass = $class->find()
                            ->select(['name'])
                            ->where(['staff_id'=>$entity->staff_id,
                                'academic_period_id'=>$entity->academic_id
                        ]);
                    if($getclass!=null){
                        $instituteclass = $getclass->toArray();
                        foreach ($instituteclass as $key => $value) {
                            $classname[] = $value->name;
                        }
                }

                return implode(', ', $classname);
            }
        }
    }

    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $userIdentities = TableRegistry::get('user_identities');
        $userIdentitiesResult = $userIdentities->find()
            ->leftJoin(['IdentityTypes' => 'identity_types'], ['IdentityTypes.id = '. $userIdentities->aliasField('identity_type_id')])
            ->select([
                'identity_number' => $userIdentities->aliasField('number'),
                'identity_type_name' => 'IdentityTypes.name',
                'default_name' => 'IdentityTypes.default',
            ])
            ->where([$userIdentities->aliasField('security_user_id') => $entity->staff_id])
            ->order([$userIdentities->aliasField('id DESC')])
            ->hydrate(false)->toArray();
            $userIdentitiesdata = $userIdentities->find()
            ->where([$userIdentities->aliasField('security_user_id') => $entity->staff_id])
            ->order([$userIdentities->aliasField('id DESC')])
            ->hydrate(false)->toArray();
            
            $entity->custom_identity_number = '';
            $other_identity_array = [];
            if (!empty($userIdentitiesResult)) {
                foreach ($userIdentitiesResult as $user_identities_data ) {
                    if ($user_identities_data['default_name']==1 && count($userIdentitiesdata)>1) {
                        $entity->custom_identity_number = $user_identities_data['identity_number'];
                        $entity->custom_identity_name   = $user_identities_data['identity_type_name'];
                    }elseif($user_identities_data['default_name']!=1 && count($userIdentitiesdata)>1){
                        $entity->custom_identity_name = '';
                        $entity->custom_identity_number='';
                    }elseif($user_identities_data['identity_type_name']=='Passport') {
                        $entity->custom_identity_name = '';
                        $entity->custom_identity_number='';
                    }elseif($user_identities_data['identity_type_name']=='Birth Certificate') {
                        $entity->custom_identity_number = $user_identities_data['identity_number'];
                        $entity->custom_identity_name   = $user_identities_data['identity_type_name'];
                    }
            }
        $entity->custom_identity_other_data = implode(',', $other_identity_array);
        return $entity->custom_identity_name;
    }
}
    public function onExcelGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->custom_identity_number;
    }
}
