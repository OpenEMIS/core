<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;


use App\Model\Traits\OptionsTrait;


class InstitutionSpecialNeedsStudentsTable extends AppTable  {
    private $_specialNeeds = [];

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
        $SpecialNeedTypes = TableRegistry::get('SpecialNeeds.SpecialNeedsTypes');

        $SpecialNeedsStudents = $SpecialNeedsAssessments
            ->find()
            ->select([
                $SpecialNeedsAssessments->aliasField('security_user_id'),
                'special_need_name' => $SpecialNeedTypes->aliasField('name')
            ])
            ->contain([
                'Users',
                $SpecialNeedTypes->alias()
            ])
            ->where([
                'Users.is_student' => 1
            ])
            ->hydrate(false)
            ->toArray();

        $studentIdList = Hash::extract($SpecialNeedsStudents, '{n}.security_user_id');
        $specialNeedsNames=Hash::combine($SpecialNeedsStudents, '{n}.special_need_name', '{n}.special_need_name', '{n}.security_user_id');

        $settings['student_id_list'] = $studentIdList;
        $this->_specialNeeds = $specialNeedsNames;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $specialNeedsNames = $settings['special_needs_name'];
        $academicPeriodId = $requestData->academic_period_id;

        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');

        if ($academicPeriodId !=0 ) {
            $query->where([$this->aliasField('academic_period_id') => $academicPeriodId]);
        }

        $studentIdList = $settings['student_id_list'];

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
            ])
            ->where([
                'Users.id IN' => $studentIdList
            ])
            ->group([
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $requestData = json_decode($settings['process']['params']);
        $statusId = $requestData->status;

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
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID'),
            'formatting' => 'string'
        ];

        $newFields[] = [
            'key' => 'InstitutionSpecialNeedsStudents.student_id',
            'field' => 'student_id',
            'type' => 'integer',
            'label' => __('Student'),
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
            'key' => 'Users.identity_number',
            'field' => 'number',
            'type' => 'string',
            'label' => __($identity->name),
            'formatting' => 'string'
        ];

        $newFields[] = [
            'key' => 'NationalitiesLookUp.name',
            'field' => 'all_nationalities',
            'type' => 'string',
            'label' => __('Nationalities')
        ];

        $newFields[] = [
            'key' => 'SpecialNeedsAssessments.special_need_type',
            'field' => 'special_need_type',
            'type' => 'string',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }
}
