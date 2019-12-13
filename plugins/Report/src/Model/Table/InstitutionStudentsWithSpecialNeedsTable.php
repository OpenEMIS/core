<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use App\Model\Traits\OptionsTrait;

class InstitutionStudentsWithSpecialNeedsTable extends AppTable  {
    private $_specialNeeds = [];
    private $_specialNeedDifficultyName = [];
    private $_comment = [];
    private $_dateOfAssessment = [];
    
    public function initialize(array $config) {
        $this->table('institution_students');
        parent::initialize($config);

        $this->belongsTo('Users',           ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions',    ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['start_year', 'end_year', 'previous_institution_student_id'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');

    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $SpecialNeedsAssessments = TableRegistry::get('SpecialNeeds.SpecialNeedsAssessments');
        $SpecialNeedsTypes = TableRegistry::get('SpecialNeeds.SpecialNeedsTypes');
        $SpecialNeedDifficulties = TableRegistry::get('SpecialNeeds.SpecialNeedDifficulties');

        $SpecialNeedsStudents = $SpecialNeedsAssessments
            ->find()
            ->select([
                $SpecialNeedsAssessments->aliasField('security_user_id'),
                'special_need_name' => $SpecialNeedsTypes->aliasField('name'),
                $SpecialNeedsAssessments->aliasField('comment'),
                'date_of_assessment' => $SpecialNeedsAssessments->aliasField('date'),
                'special_need_difficulty_id' => $SpecialNeedsAssessments->aliasField('special_need_difficulty_id'),
                'special_need_difficulty_name' => $SpecialNeedDifficulties->aliasField('name'),
            ])
            ->leftJoin(
                    [$SpecialNeedDifficulties->alias()=>$SpecialNeedDifficulties->table()],
                    [$SpecialNeedsAssessments->aliasField('special_need_difficulty_id')=>$SpecialNeedDifficulties->aliasField('id')])
            ->contain([
                'Users',                
                $SpecialNeedsTypes->alias(),
                $SpecialNeedDifficulties->alias()
            ])
            
            ->where([
                'Users.is_student' => 1
            ])
            ->hydrate(false)
            ->toArray();

        $studentIdList = Hash::extract($SpecialNeedsStudents, '{n}.security_user_id');
        $specialNeedsNames=Hash::combine($SpecialNeedsStudents, '{n}.special_need_name', '{n}.special_need_name', '{n}.security_user_id');
        $comment=Hash::combine($SpecialNeedsStudents, '{n}.comment', '{n}.comment', '{n}.security_user_id');
        $dateOfAssessment=Hash::combine($SpecialNeedsStudents, '{n}.date_of_assessment', '{n}.date_of_assessment', '{n}.security_user_id');
        $SpecialNeedsDifficultyName=Hash::combine($SpecialNeedsStudents, '{n}.special_need_difficulty_name', '{n}.special_need_difficulty_name', '{n}.security_user_id');

        $settings['student_id_list'] = $studentIdList;
        $this->_specialNeeds = $specialNeedsNames;
        $this->_comment = $comment;
        $this->_dateOfAssessment = $dateOfAssessment;
        $this->_specialNeedDifficultyName = $SpecialNeedsDifficultyName;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $userId = $requestData->user_id;
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $userAreaCodes = $SecurityGroupUsers->getAreaCodesByUser($userId);
        
        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
       
        if ($academicPeriodId !=0 ) {
            $query->where([$this->aliasField('academic_period_id') => $academicPeriodId]);
        }
        
        $studentIdList = 0;
        
        if(!empty($settings['student_id_list'])){
            $studentIdList = $settings['student_id_list'];
        }
        
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('student_id'),
                $this->aliasField('student_status_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('start_date'),
                $this->aliasField('end_date'),
                'class_name' => $Class->aliasField('name'),
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'Users.id', // this field is needed for Nationalities and NationalitiesLookUp to appear
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'Users.preferred_name',
                        'date_of_birth' => 'Users.date_of_birth',
                        'username' => 'Users.username',
                        'address_area_id' => 'Users.address_area_id',
                        'number' => 'Users.identity_number'
                    ]
                ],
                'Users.Genders' => [
                    'fields' => [
                        'gender_name' => 'Genders.name'
                    ]
                ],
                'Users.MainNationalities' => [
                    'fields' => [
                        'preferred_nationality' => 'MainNationalities.name'
                    ]
                ],
                'Users.Nationalities' => [
                    'fields' => [
                        'Nationalities.security_user_id'
                    ],
                ],
                'Users.Nationalities.NationalitiesLookUp' => [
                    'fields' => [
                        'NationalitiesLookUp.name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'code' => 'Institutions.code',
                        'Institutions.name'
                    ]
                ],
                'Institutions.Types' => [
                    'fields' => [
                        'institution_type' => 'Types.name'
                    ]
                ],
                'Institutions.Providers' => [
                    'fields' => [
                        'institution_provider' => 'Providers.name',
                    ]
                ],
                'Institutions.Areas' => [
                    'fields' => [
                        'area_code' => 'Areas.code',
                        'area_name' => 'Areas.name'
                    ]
                ],
                'Institutions.AreaAdministratives' => [
                    'fields' => [
                        'area_administrative_code' => 'AreaAdministratives.code',
                        'area_administrative_name' => 'AreaAdministratives.name'
                    ]
                ],
                'StudentStatuses' => [
                    'fields' => [
                        'StudentStatuses.name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'EducationGrades.code',
                        'EducationGrades.name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.code',
                        'AcademicPeriods.name'
                    ]
                ]
            ])
            ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                $ClassStudents->aliasField('student_status_id = ') . $this->aliasField('student_status_id'),
                $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
            ])
            ->leftJoin([$Class->alias() => $Class->table()], [
                $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
            ]);
        
            if(!empty($userAreaCodes)){
                $query->where([
                    'Users.id IN' => $studentIdList,
                    'AreaAdministratives.code IN ' => $userAreaCodes
                ]);
            }else{
                $query->where([
                    'Users.id IN' => $studentIdList
                ]);
            }
                 
            $query->group([
                'Users.id'
            ])
            ->order([
                'Institutions.name',
                'EducationGrades.name',
                'StudentStatuses.name',
                $this->aliasField('start_date') => 'DESC'
            ]);
    }

    public function onExcelRenderAge(Event $event, Entity $entity, $attr)
    {
        $age = '';
        if ($entity->has('date_of_birth') && !empty($entity->date_of_birth)) {
            $dateOfBirth = $entity->date_of_birth->format('Y-m-d');
            $today = date('Y-m-d');
            $age = date_diff(date_create($dateOfBirth), date_create($today))->y;
        }
        return $age;
    }
    
    public function onExcelGetDateOfAssessment(Event $event, Entity $entity)
    {
        $studentId = $entity->student_id;

        if (array_key_exists($studentId, $this->_dateOfAssessment)) {
            $dateOfAssessment = array_filter($this->_dateOfAssessment[$studentId]);
            $newDateOffAssessment = [];
            foreach($dateOfAssessment as $date){
                $newDateOffAssessment[] = date('F d, Y', strtotime($date));
            }
            
            return implode(', ', $newDateOffAssessment);
        }

        return '';
    }
    
    public function onExcelGetAllNationalities(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('nationalities')) {
                if (!empty($entity->user->nationalities)) {
                    foreach ($entity->user->nationalities as $userNationality) {
                        if ($userNationality->has('nationalities_look_up')) {
                            $return[] = $userNationality->nationalities_look_up->name;
                        }
                    }
                }
            }
        }

        return implode(', ', array_values($return));
    }

    public function onExcelGetSpecialNeedType(Event $event, Entity $entity)
    {
        $studentId = $entity->student_id;

        if (array_key_exists($studentId, $this->_specialNeeds)) {
            $studentSpecialNeeds = $this->_specialNeeds[$studentId];
            return implode(', ', $studentSpecialNeeds);
        }

        return '';
    }
    
    
    public function onExcelGetSpecialNeedDifficulty(Event $event, Entity $entity)
    {
        $studentId = $entity->student_id;

        if (array_key_exists($studentId, $this->_specialNeedDifficultyName)) {
            $studentSpecialNeedDifficulty = $this->_specialNeedDifficultyName[$studentId];
            return implode(', ', $studentSpecialNeedDifficulty);
        }

        return '';
    }
    
    public function onExcelGetComment(Event $event, Entity $entity)
    {
        $studentId = $entity->student_id;

        if (array_key_exists($studentId, $this->_comment)) {
            $comment = array_filter($this->_comment[$studentId]);
            return implode(', ', $comment);
        }

        return '';
    }
    
    public function onExcelGetDate(Event $event, Entity $entity)
    {
        $studentId = $entity->student_id;

        if (array_key_exists($studentId, $this->_date)) {
            $date = array_filter($this->_date[$studentId]);
            return implode(', ', $date);
        }

        return '';
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $requestData = json_decode($settings['process']['params']);
        
        $userId = $requestData->user_id;
        $userSuperAdmin = $requestData->super_admin;
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $userAreaCodes = $SecurityGroupUsers->getAreaCodesByUser($userId);
        
        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $principalRoleId = $SecurityRoles->getPrincipalRoleId();
        $deputyPrincipalRoleId = $SecurityRoles->getDeputyPrincipalRoleId();
        
        $showStatus = true;
        
        if($userSuperAdmin){
            $showStatus = true;
        }elseif($principalRoleId){
            $showStatus = false;
        }elseif($deputyPrincipalRoleId){
            $showStatus = false;
        }elseif(!empty ($userAreaCodes)){
            $showStatus = false;
        }
                
        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Students.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_type_id',
            'field' => 'institution_type',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_provider_id',
            'field' => 'institution_provider',
            'type' => 'integer',
            'label' => '',
        ];
        
        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID'),
            'formatting' => 'string'
        ];
        
        $newFields[] = [
            'key' => 'Users.username',
            'field' => 'username',
            'type' => 'string',
            'label' => __('Username'),
            'formatting' => 'string'
        ];        
                
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'number',
            'type' => 'string',
            'label' => __($identity->name),
            'formatting' => 'string'
        ];

        $newFields[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => ''
        ];
        
        $newFields[] = [
            'key' => 'Institutions.area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];
        
        $newFields[] = [
            'key' => 'Institutions.area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];
        
        $newFields[] = [
            'key' => 'Institutions.area_administrative_code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];
        
        $newFields[] = [
            'key' => 'Institutions.area_administrative_name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];
        
        $newFields[] = [
            'key' => 'Age',
            'field' => 'age',
            'type' => 'age',
            'label' => __('Age'),
        ];

        $newFields[] = [
            'key' => 'User.student_status',
            'field' => 'student_status_id',
            'type' => 'string',
            'label' => __('Student Status')
        ];
        
        $newFields[] = [
            'key' => 'InstitutionSpecialNeedsStudents.student_id',
            'field' => 'student_id',
            'type' => 'integer',
            'label' => __('Student'),
            'formatting' => 'string'
        ];

        $newFields[] = [
            'key' => 'EducationGrade.education_grade_id',
            'field' => 'education_grade_id',
            'type' => 'string',
            'label' => __('Education Grades')
        ];

        $newFields[] = [
            'key' => 'InstitutionSpecialNeedsStudents.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'InstitutionSpecialNeedsStudents.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => __('Start Date')
        ];

        $newFields[] = [
            'key' => 'InstitutionSpecialNeedsStudents.end_date',
            'field' => 'end_date',
            'type' => 'date',
            'label' => __('End Date')
        ];
        
        $newFields[] = [
            'key' => 'InstitutionSpecialNeedsStudents.class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Class Name')
        ];
        
        $newFields[] = [
            'key' => 'SpecialNeedsAssessments.date_of_assessment',
            'field' => 'date_of_assessment',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'SpecialNeedsAssessments.special_need_type',
            'field' => 'special_need_type',
            'type' => 'string',
            'label' => ''
        ];
        
        if($showStatus){
            $newFields[] = [
                'key' => 'SpecialNeedsAssessments.special_need_difficulty',
                'field' => 'special_need_difficulty',
                'type' => 'string',
                'label' => ''
            ];

            $newFields[] = [
                'key' => 'SpecialNeedsAssessments.comment',
                'field' => 'comment',
                'type' => 'string',
                'label' => ''
            ];
        }

        $fields->exchangeArray($newFields);
    }
}
