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
    {//echo "<pre>";print_r($query);die();
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
        $UserIdentities = TableRegistry::get('User.Identities');
        $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        if ( $institution_id > 0) {
            $where = [$this->aliasField('institution_id = ') => $institution_id];
        } else {
            $where = [];
        }

        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
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
                'identity_number' => $UserIdentities->aliasField('number'),
                'address' => 'Users.address',
                'academicPeriodId' => 'AcademicPeriods.id',
                'guardian_name' => $GuardianUser->find()->func()->concat([
                    'GuardianUser.first_name' => 'literal',
                    " ",
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
                    [$UserIdentities->alias() => $UserIdentities->table()],
                    [
                        $UserIdentities->aliasField('security_user_id = ') . $Users->aliasField('id')
                    ]
                )
            ->leftJoin(
                    [$IdentityTypes->alias() => $IdentityTypes->table()],
                    [
                        $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
                    ]
                )
            //pocor-5934
            /*->leftJoin(
                [$UserContacts->alias() => $UserContacts->table()],
                    [
                        $UserContacts->aliasField('security_user_id = ') . $Users->aliasField('id')
                    ]
            )*/
            //pocor-5934
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
            ->leftJoin(
                [$AcademicPeriods->alias() => $AcademicPeriods->table()],
                    [
                        $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id')
                    ]
            )
            ->where([
                $this->aliasField('date >= ') => $startDate,
                $this->aliasField('date <= ') => $endDate,
                $where
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
            'key' => 'Institutions.institution',
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
        //POCOR-5934
        /*$newArray[] = [
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
        ];*/
        //POCOR-5934
        $newArray[] = [
            'key' => 'StudentAbsences.date',
            'field' => 'date',
            'type' => 'string',
            'label' => __('Date')
        ];
        $newArray[] = [
            'key' => 'attendance_per_day',
            'field' => 'attendance_per_day',
            'type' => 'string',
            'label' => __('Attendance Per Day')
        ];
        $newArray[] = [
            'key' => 'subjects',
            'field' => 'subjects',
            'type' => 'string',
            'label' => __('Subjects'),
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
            'key' => 'User.Contact',
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

    public function onExcelGetContact(Event $event, Entity $entity)
    {   
        $openemisNo = $entity->openemis_no;
        $Users = TableRegistry::get('User.Users');
        $userId = $Users->find()->where([$Users->aliasField('openemis_no') => $openemisNo])->first()->id;
        $UserContacts = TableRegistry::get('UserContacts');
        $detail = $UserContacts->find()->where([$UserContacts->aliasField('security_user_id') => $userId])->toArray();
        $contactArray = [];
        if (!empty($detail)) {
            foreach ($detail as $key => $value) {
                $contactArray[] = $value->value;;
            }
        }
        
        return implode(',', $contactArray);
    }

    public function onExcelGetAttendancePerDay(Event $event, Entity $entity)
    {   
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $academicPeriodsId = $entity->academicPeriodId;
        $openemisNo = $entity->openemis_no;
        $Users = TableRegistry::get('User.Users');
        $userId = $Users->find()->where([$Users->aliasField('openemis_no') => $openemisNo])->first()->id;
        
        $record = $this->find()
                    ->select([
                        $StudentAttendanceTypes->aliasField('code'),
                        $StudentAttendanceMarkTypes->aliasField('id'),
                        $StudentAttendancePerDayPeriods->aliasField('name')
                    ])
                    ->innerJoin([$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()], [
                        $StudentMarkTypeStatusGrades->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id')
                    ])
                    ->innerJoin([$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()], [
                        $StudentMarkTypeStatuses->aliasField('id = ') . $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id')
                    ])
                    ->innerJoin([$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()], [
                        $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                    ])
                    ->innerJoin([$StudentAttendanceTypes->alias() => $StudentAttendanceTypes->table()], [
                        $StudentAttendanceTypes->aliasField('id = ') . $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id')
                    ])
                    ->innerJoin([$StudentAttendancePerDayPeriods->alias() => $StudentAttendancePerDayPeriods->table()], [
                        $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id = ') . $StudentAttendanceMarkTypes->aliasField('id')
                    ])  
                    ->where([$this->aliasField('student_id') => $userId])
                    ->toArray();
        $array = [];
        if (!empty($record)) {
           foreach ($record as $key => $value) {
               if ($value->StudentAttendanceTypes['code'] == 'DAY') {
                   $array[] = isset($value->StudentAttendancePerDayPeriods['name']) ? $value->StudentAttendancePerDayPeriods['name'] : '';
               } 
               return implode(',', $array);
           }
        }
    }

    public function onExcelGetSubjects(Event $event, Entity $entity)
    {   
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $academicPeriodsId = $entity->academicPeriodId;
        $openemisNo = $entity->openemis_no;
        $Users = TableRegistry::get('User.Users');
        $userId = $Users->find()->where([$Users->aliasField('openemis_no') => $openemisNo])->first()->id;
        
        $record = $this->find()
                    ->select([
                        $StudentAttendanceTypes->aliasField('code'),
                        $StudentAttendanceMarkTypes->aliasField('id')
                    ])
                    ->innerJoin([$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()], [
                        $StudentMarkTypeStatusGrades->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id')
                    ])
                    ->innerJoin([$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()], [
                        $StudentMarkTypeStatuses->aliasField('id = ') . $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id')
                    ])
                    ->innerJoin([$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()], [
                        $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                    ])
                    ->innerJoin([$StudentAttendanceTypes->alias() => $StudentAttendanceTypes->table()], [
                        $StudentAttendanceTypes->aliasField('id = ') . $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id')
                    ])  
                    ->where([$this->aliasField('student_id') => $userId])
                    ->toArray();
        /*$array = [];
        if (!empty($record)) {
            if ($record->StudentAttendanceTypes['code'] == 'SUBJECT') {
                $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
                    $subjectDetails = $InstitutionSubjectStudents->find()
                                        ->contain('InstitutionSubjects')
                                        ->where([
                                            $InstitutionSubjectStudents->aliasField('student_id') => $userId,
                                            $InstitutionSubjectStudents->aliasField('academic_period_id') => $academicPeriodsId,
                                        ])->toArray();
                
                    if (!empty($subjectDetails)) {
                        foreach ($subjectDetails as $key => $value) {
                                $array[] = $value->institution_subject->name;
                        }
                }
            }

            return implode(',', $array);
        }*/
        $array = [];
        if (!empty($record)) {
           foreach ($record as $key => $value) {
               if ($value->StudentAttendanceTypes['code'] == 'SUBJECT') {
                  $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
                    $subjectDetails = $InstitutionSubjectStudents->find()
                                        ->contain('InstitutionSubjects')
                                        ->where([
                                            $InstitutionSubjectStudents->aliasField('student_id') => $userId,
                                            $InstitutionSubjectStudents->aliasField('academic_period_id') => $academicPeriodsId,
                                        ])->toArray();
                
                    if (!empty($subjectDetails)) {
                        foreach ($subjectDetails as $key => $value) {
                                $array[] = $value->institution_subject->name;
                    }
               } 
               return implode(',', $array);
           }
        }
        }
    }   
}
