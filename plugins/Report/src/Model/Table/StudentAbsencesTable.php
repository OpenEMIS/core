<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

use DateTime; // POCOR-7479

// report change in POCOR-7267

class StudentAbsencesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_student_absence_details');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'institution_class_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Institution.EducationGrades', 'foreignKey' =>'education_grade_id']);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_year',
                'end_year',
                'full_day',
                'start_date',
                'start_time',
                'end_time',
                'end_date'
            ],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
    }


    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onExcelBeforeStart (EventInterface $event, ArrayObject $settings, ArrayObject $sheets) {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }


    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        $grades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $academicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $securityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $selectedArea = $requestData->area_education_id;
        $conditions = [];

        // POCOR-7479
        $institutionTypeId = $requestData->institution_type_id;
        $educationGradeId = $requestData->education_grade_id;
        $reportStartDate = new DateTime($requestData->report_start_date);
        $reportEndDate = new DateTime($requestData->report_end_date);
        $startDate = $reportStartDate->format('Y-m-d');
        $endDate = $reportEndDate->format('Y-m-d');


        $conditions = [];
        $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                                ])
                        ->where(['institution_type_id' => $institutionTypeId])
                        ->toArray();
        if ($institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }

        if ($educationGradeId != -1) {
            $conditions[$this->aliasField('education_grade_id')] = $educationGradeId;
        }

        if (!empty($startDate)) {
            $conditions[$this->aliasField('date >=')] = $startDate;
        }

        if (!empty($startDate)) {
            $conditions[$this->aliasField('date <=')] = $endDate;
        }

         // END POCOR-7479

        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }

        if (!empty($institutionId) && $institutionId != '-1') {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
                $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }
        $join = []; 
        $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('absence_type_id'),
                $this->aliasField('student_absence_reason_id'),
                'get_date' => $query->func()->DATE_FORMAT([
                    $this->aliasField('date') => 'identifier',
                    "'%Y-%m-%d'" => 'literal'
                ]),//POCOR-8772
                'default_identity_type'=> "(SELECT IFNULL(student_identities.identity_type, ''))",   
                'identity_number'=> "(SELECT IFNULL(student_identities.identity_number, ''))",   
                'address'=> "(SELECT IFNULL(Users.address, ''))",   
                'contacts'=> "(SELECT IFNULL(contact_info.contacts, ''))",
                'period_name' => "(SELECT IF(InstitutionSubjects.name IS NOT NULL, '', IFNULL(period_info.period_name, CONCAT('Period ', " . $this->aliasField('period') . "))))", // POCOR-8902   
                // 'period_name'=> "(SELECT IF(InstitutionSubjects.name IS NOT NULL, '', IFNULL(period_info.period_name, '')))",   
                'subject_name' =>"(SELECT IFNULL(InstitutionSubjects.name, ''))",
                'education_grade_name'=> $grades->aliasField('name'),
                'academic_period' => $academicPeriod->aliasField('name'),
                'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal',
                    ]),  
                ])
            ->contain([
                'Users' => [
                   'fields' => [
                        'Users.id',
                        'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                        'gender_name' => 'Genders.name'
                   ]
             ],
             'Users.Genders' => [
                    'fields' => [
                        'name'
                    ]
                ],
            'Institutions' => [
                    'fields' => [
                       'institution_id'=> 'Institutions.id',
                       'institution_name'=> 'Institutions.name',
                        'institution_code'=>'Institutions.code'
                    ]
                ],
            'Institutions.Areas' => [
                    'fields' => [
                        'area_name' => 'Areas.name',
                        'area_code' => 'Areas.code'
                    ]
                ],
            'InstitutionClasses' => [
                    'fields' => [
                       'institution_class_name'=> 'InstitutionClasses.name'
                    ]
                ],
            'AbsenceTypes' => [
                    'fields' => [
                       'absence_type_name'=> 'AbsenceTypes.name',
                    ]
                ],
            ])
             ->innerJoin([$grades->getAlias() => $grades->getTable()], [
                $grades->aliasField('id = ') . $this->aliasField('education_grade_id')
            ])
            ->innerJoin([$academicPeriod->getAlias() => $academicPeriod->getTable()], [
                $academicPeriod->aliasField('id = ') . $this->aliasField('academic_period_id')
            ])
             ->innerJoin([$securityUsers->getAlias() => $securityUsers->getTable()], [
                $securityUsers->aliasField('id = ') . $this->aliasField('student_id')
            ])
            ->leftJoin([$InstitutionSubjects->getAlias() => $InstitutionSubjects->getTable()], [
                $InstitutionSubjects->aliasField('id = ') . $this->aliasField('subject_id')
            ]);

            $join['period_info'] = [
                'type' => 'left',
                'table' => "(SELECT student_mark_type_status_grades.education_grade_id
                            ,student_mark_type_statuses.academic_period_id
                            ,student_attendance_per_day_periods.name period_name
                            ,student_attendance_per_day_periods.period
                        FROM student_mark_type_status_grades
                        INNER JOIN student_mark_type_statuses
                        ON student_mark_type_statuses.id = student_mark_type_status_grades.student_mark_type_status_id
                        INNER JOIN student_attendance_mark_types
                        ON student_attendance_mark_types.id = student_mark_type_statuses.student_attendance_mark_type_id
                        INNER JOIN student_attendance_per_day_periods
                        ON student_attendance_per_day_periods.student_attendance_mark_type_id = student_attendance_mark_types.id)" ,
                        'conditions' => ['period_info.education_grade_id = EducationGrades.id',
                                        'period_info.academic_period_id = AcademicPeriods.id',
                                        'period_info.period = ' . $this->aliasField('period'),
                                        ],  
            ];

            $join['student_identities'] = [
                'type' => 'left',
                'table' => "(SELECT  user_identities.security_user_id
                            ,GROUP_CONCAT(identity_types.name) identity_type
                            ,GROUP_CONCAT(user_identities.number) identity_number
                            FROM user_identities
                            INNER JOIN identity_types
                            ON identity_types.id = user_identities.identity_type_id
                            WHERE identity_types.default = 1
                            GROUP BY  user_identities.security_user_id)" ,
                            'conditions' => ['student_identities.security_user_id = Users.id']  
            ];

            $join['contact_info'] = [
                'type' => 'left',
                'table' => "(SELECT user_contacts.security_user_id
                            ,GROUP_CONCAT(CONCAT(' ', contact_options.name, ' (', contact_types.name, '): ', user_contacts.value)) contacts
                            FROM user_contacts
                            INNER JOIN contact_types
                            ON contact_types.id = user_contacts.contact_type_id
                            INNER JOIN contact_options
                            ON contact_options.id = contact_types.contact_option_id
                            WHERE user_contacts.preferred = 1
                            GROUP BY user_contacts.security_user_id)" ,
                            'conditions' => ['contact_info.security_user_id = Users.id']  
            ];
            $query->where($conditions)
            ->order(['Institutions.name','EducationGrades.name','InstitutionClasses.name']);

            $query->join($join);     
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];

        $newFields[] = [
            'key' => 'education_grade_name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $newFields[] = [
            'key' => 'institution_class_name',
            'field' => 'institution_class_name',
            'type' => 'string',
            'label' => __('Institution Class')
        ];

        $newFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Openemis No')
        ];

        $newFields[] = [
            'key' => 'student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];

        $newFields[] = [
            'key' => 'gender_name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $newFields[] = [
            'key' => 'default_identity_type',
            'field' => 'default_identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];

        $newFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $newFields[] = [
            'key' => 'address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];
        $newFields[] = [
            'key' => 'contacts',
            'field' => 'contacts',
            'type' => 'string',
            'label' => __('Contacts')
        ];
        $newFields[] = [
            'key' => 'get_date',
            'field' => 'get_date',
            'type' => 'string',  // Changed from 'date' to 'string'POCOR-8772
            'label' => __('Date'),
            'format' => '#'     // Prevents Excel from auto-formatting the date
        ];
        $newFields[] = [
            'key' => 'period_name',
            'field' => 'period_name',
            'type' => 'string',
            'label' => __('Period')
        ];
        $newFields[] = [
            'key' => 'subject_name',
            'field' => 'subject_name',
            'type' => 'string',
            'label' => __('Subject')
        ];

        $newFields[] = [
            'key' => 'absence_type_name',
            'field' => 'absence_type_name',
            'type' => 'string',
            'label' => __('Absence Type')
        ];
        
        $fields->exchangeArray($newFields);
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
  
}
