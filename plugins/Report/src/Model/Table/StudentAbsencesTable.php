<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StudentAbsencesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absences');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'institution_class_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' =>'institution_student_absence_day_id']);
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Genders = TableRegistry::get('User.Genders');
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $Users = TableRegistry::get('User.Users');
        $StudentGuardians = TableRegistry::get('Student.StudentGuardians');
        $Guardians = TableRegistry::get('Security.Users');
        $UserContacts = TableRegistry::get('UserContacts');
        $GuardianUser = TableRegistry::get('Security.Users');

        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodEntity = $AcademicPeriods->get($academicPeriodId);

            $startDate = $periodEntity->start_date->format('Y-m-d');
            $endDate = $periodEntity->end_date->format('Y-m-d');
        }

        $query
            ->select([
                'openemis_no' => 'Users.openemis_no',
                'student_first_name' => 'Users.first_name',
                'student_middle_name' => 'Users.middle_name',
                'student_third_name' => 'Users.third_name',
                'student_last_name' => 'Users.last_name',
                'student_preferred_name' => 'Users.preferred_name',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'area_level_name' => 'AreaLevels.name',
                'absence_type' => 'AbsenceTypes.name',
                'date' => 'StudentAbsences.date',
                'institution_class' => 'InstitutionClasses.name',
                'education_grade' => $EducationGrades->aliasField('name'),
                'gender' => $Genders->aliasField('name'),
                'identity_type' => $IdentityTypes->aliasField('name'),
                'identity_number' => 'Users.identity_number',
                'address' => 'Users.address',
                'contact' => 'UserContacts.value',
                'guardian_name' => $GuardianUser->find()->func()->concat([
                    'GuardianUser.first_name' => 'literal',
                    " - ",
                    'GuardianUser.last_name' => 'literal'
                ])
            ]) 
            ->contain([                
                'Institutions.Areas.AreaLevels',
                'Institutions.AreaAdministratives',
                'InstitutionClasses'
            ])     
            ->leftJoin(
                [$Users->alias() => $Users->table()],
                    [
                        $Users->aliasField('id = ') . $this->aliasField('student_id')
                    ]
            )      
            ->leftJoin(
                    [$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],
                    [
                        $InstitutionClassGrades->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
                    ]
                )
            ->leftJoin(
                    [$EducationGrades->alias() => $EducationGrades->table()],
                    [
                        $EducationGrades->aliasField('id = ') . $InstitutionClassGrades->aliasField('education_grade_id')
                    ]
                )
            ->leftJoin(
                    [$Genders->alias() => $Genders->table()],
                    [
                        $Genders->aliasField('id = ') . $Users->aliasField('gender_id')
                    ]
                )
            ->leftJoin(
                    [$IdentityTypes->alias() => $IdentityTypes->table()],
                    [
                        $IdentityTypes->aliasField('id = ') . $Users->aliasField('identity_type_id')
                    ]
                )
            ->leftJoin(
                [$UserContacts->alias() => $UserContacts->table()],
                    [
                        $UserContacts->aliasField('security_user_id = ') . $Users->aliasField('id')
                    ]
            )
            ->leftJoin(
                [$StudentGuardians->alias() => $StudentGuardians->table()],
                    [
                        $StudentGuardians->aliasField('student_id = ') . $this->aliasField('student_id')
                    ]
            )
            ->leftJoin(['GuardianUser' => 'security_users'],
                                [
                                    'GuardianUser.id = '.$StudentGuardians->aliasField('guardian_id')
                                ]
            )        
            ->where([
                $this->aliasField('date >= ') => $startDate,
                $this->aliasField('date <= ') => $endDate,
                $this->aliasField('institution_id = ') => $institution_id
            ])
            ->order([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('date')
            ]);
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newArray = [];
        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Users.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student')
        ];
        $newArray[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];

        $newArray[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];

        $newArray[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newArray[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];
        $newArray[] = [
            'key' => 'AreaLevels.name',
            'field' => 'area_level_name',
            'type' => 'string',
            'label' => __('Area Level')
        ];
        $newArray[] = [
            'key' => 'StudentAbsences.date',
            'field' => 'date',
            'type' => 'string',
            'label' => __('Date')
        ];
        $newArray[] = [
            'key' => 'StudentAbsences.absence_type_id',
            'field' => 'absence_type_id',
            'type' => 'integer',
            'label' => __('Absence Type'),
        ];
        $newArray[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'institution_class',
            'type' => 'string',
            'label' => __('Institution Class'),
        ];
        $newArray[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade'),
        ];
        $newArray[] = [
            'key' => 'Genders.name',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender'),
        ];
        $newArray[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type'),
        ];
        $newArray[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => __('Identity Number'),
        ];
        
        $newArray[] = [
            'key' => 'Users.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address'),
        ];

        $newArray[] = [
            'key' => 'UserContacts.value',
            'field' => 'contact',
            'type' => 'string',
            'label' => __('Contact'),
        ];

        $newArray[] = [
            'key' => '',
            'field' => 'guardian_name',
            'type' => 'string',
            'label' => __('Parent Name'),
        ];

        $fields->exchangeArray($newArray);
    }

    public function onExcelGetDate(Event $event, Entity $entity)
    {
        return $this->formatDate($entity->date);
    }    

    public function onExcelGetAbsenceTypeId(Event $event, Entity $entity)
    {
        return $entity->absence_type;
    }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {

        return $entity->institution_id;
    }

    public function onExcelGetStudentName(Event $event, Entity $entity)
    {
        //cant use $this->Users->get() since it will load big data and cause memory allocation problem

        $studentName = [];
        ($entity->student_first_name) ? $studentName[] = $entity->student_first_name : '';
        ($entity->student_middle_name) ? $studentName[] = $entity->student_middle_name : '';
        ($entity->student_third_name) ? $studentName[] = $entity->student_third_name : '';
        ($entity->student_last_name) ? $studentName[] = $entity->student_last_name : '';

        return implode(' ', $studentName);
    }
}
