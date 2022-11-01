<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use App\Model\Traits\MessagesTrait;

class InstitutionStudentsOutOfSchoolTable extends AppTable  {
    use MessagesTrait;

    public function initialize(array $config) {
        $this->table('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);
        $this->hasMany('Identities',        ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['super_admin', 'is_student', 'is_staff', 'is_guardian', 'photo_name', 'date_of_death', 'last_login', 'status', 'username'], 
            'pages' => false
        ]);
    }

    public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        $query->join([
            'InstitutionStudent' => [
                'type' => 'left',
                'table' => 'institution_students',
                'conditions' => [
                    $this->aliasField($this->primaryKey()) . ' = InstitutionStudent.student_id'
                ],
            ],            
            'InstitutionStudentFilter' => [
                'type' => 'left',
                'table' => 'institution_students',
                'conditions' => [
                    $this->aliasField($this->primaryKey()) . ' = InstitutionStudentFilter.student_id',
                    'InstitutionStudentFilter.student_status_id = '.$enrolled
                ],
            ],
            'StudentStatus' => [
                'type' => 'left',
                'table' => 'student_statuses',
                'conditions' => [
                    'StudentStatus.id = InstitutionStudent.student_status_id'
                ]
            ],
            'Institution' => [
                'type' => 'left',
                'table' => 'institutions',
                'conditions' => [
                    'Institution.id = InstitutionStudent.institution_id'
                ]
            ],
            'AcademicPeriod' => [
                'type' => 'left',
                'table' => 'academic_periods',
                'conditions' => [
                    'AcademicPeriod.id = InstitutionStudent.academic_period_id'
                ]
            ],
            'EducationGrade' => [
                'type' => 'left',
                'table' => 'education_grades',
                'conditions' => [
                    'EducationGrade.id = InstitutionStudent.education_grade_id'
                ]
            ]
        ]);
        

        $query->contain(['MainNationalities', 'MainIdentityTypes']);

        $query->select([
                    'EndDate' => 'InstitutionStudent.end_date', 'StudentStatus' => 'StudentStatus.name', 'AcademicPeriod' => 'AcademicPeriod.name', 'EducationGrade' => 'EducationGrade.name',
                    'nationality_id' => 'MainNationalities.name', 'identity_type_id' => 'MainIdentityTypes.name',
                    'institution_code' => 'Institution.code','institution_name' => 'Institution.name'
                ]);
        $query->autoFields('true');

        $query->where([$this->aliasField('is_student') => 1,'InstitutionStudent.academic_period_id' => $academicPeriodId]);

        // omit all records who are 'enrolled'
        $query->where([
            'OR' => [
                    'InstitutionStudent.student_status_id != ' => $enrolled,
                    'InstitutionStudent.student_status_id IS NULL'
                ]
            ]);

        // omit all students in current records who has 'enrolled'
        $query->where([
            'InstitutionStudentFilter.student_status_id IS NULL',
            // 'InstitutionStudent.academic_period_id' => $academicPeriodId,
        ]);

        $query->group([$this->aliasField($this->primaryKey())]);
        $query->order(['InstitutionStudent.end_date desc']);
    }

    public function onExcelRenderAge(Event $event, Entity $entity, $attr) {
        $age = '';
        if ($entity->has('date_of_birth')) {
            if (!empty($entity->date_of_birth)) {
                $dateOfBirth = $entity->date_of_birth->format('Y-m-d');
                $today = date('Y-m-d');
                $age = date_diff(date_create($dateOfBirth), date_create($today))->y;
            }
        }
    
        return $age;
    }

    public function onExcelGetStudentStatus(Event $event, Entity $entity) {
        return (!$entity->has('StudentStatus') || empty($entity->StudentStatus))? $this->getMessage('Institution.InstitutionStudents.notInSchool'): $entity->StudentStatus;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $extraField[] = [
            'key' => 'AcademicPeriod.name',
            'field' => 'AcademicPeriod',
            'type' => 'string',
            'label' => 'Academic Period',
        ];

        $extraField[] = [
            'key' => 'StudentStatus.name',
            'field' => 'StudentStatus',
            'type' => 'string',
            'label' => 'Student Status',
        ];
        
        $extraField[] = [
            'key' => 'Institution.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => 'Institution Code',
        ];

        $extraField[] = [
            'key' => 'Institution.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => 'Institution Name',
        ];
    
        $extraField[] = [
            'key' => 'EducationGrade.name',
            'field' => 'EducationGrade',
            'type' => 'string',
            'label' => 'Education Grade',
        ];

        $extraField[] = [
            'key' => 'InstitutionStudent.end_date',
            'field' => 'EndDate',
            'type' => 'string',
            'label' => 'End Date',
        ];

        $extraField[] = [
            'key' => 'Age',
            'field' => 'Age',
            'type' => 'Age',
            'label' => 'Age at End Date',
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Openemis No')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Full Name')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.preferred_name',
            'field' => 'preferred_name',
            'type' => 'string',
            'label' => __('Preferred Name')
        ];
        // START: POCOR-6511
        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.student_email',
            'field' => 'student_email',
            'type' => 'string',
            'label' => __('Student Email')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.contact_id',
            'field' => 'student_contact_id',
            'type' => 'string',
            'label' => __('Student Contact')
        ];
        // END: POCOR-6511
        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.postal_code',
            'field' => 'postal_code',
            'type' => 'string',
            'label' => __('Postal Code')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.address_area_id',
            'field' => 'address_area_id',
            'type' => 'string',
            'label' => __('Address Area')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.birthplace_area_id',
            'field' => 'birthplace_area_id',
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.gender_id',
            'field' => 'gender_id',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'string',
            'label' => __('Date Of Birth')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.nationality_id',
            'field' => 'nationality_id',
            'type' => 'string',
            'label' => __('Nationality')
        ];
        // START: POCOR-6511
        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.guardian_email',
            'field' => 'guardian_email',
            'type' => 'string',
            'label' => __('Guardian Email')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.guardian_contact_id',
            'field' => 'guardian_contact_id',
            'type' => 'string',
            'label' => __('Guardian Contact')
        ];
        // START: POCOR-6511
        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.identity_type_id',
            'field' => 'identity_type_id',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.external_reference',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => __('External Reference')
        ];

        

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.reason_id',
            'field' => 'reason_id',
            'type' => 'string',
            'label' => __('Reason')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];


        // $newFields = array_merge($extraField, $fields->getArrayCopy());
        $fields->exchangeArray($extraField);
    }

    public function onExcelGetComment(Event $event, Entity $entity)
    {
        $academicPeriods = $this->getIdByAcademicPeriods($entity->AcademicPeriod);
        $userId = $entity->id;

        $studentWithdraw = TableRegistry::get('institution_student_withdraw');
        $comment = $studentWithdraw
        ->find()
        ->where([
            $studentWithdraw->aliasField('student_id') => $userId,
            $studentWithdraw->aliasField('academic_period_id') => $academicPeriods,
        ])
        ->first();


        return !empty($comment->comment) ? $comment->comment : '';
    }

    public function onExcelGetReasonId(Event $event, Entity $entity)
    {
        $academicPeriods = $this->getIdByAcademicPeriods($entity->AcademicPeriod);
        $userId = $entity->id;

        $StudentWithdrawReasons = TableRegistry::get('Student.StudentWithdrawReasons');
        $Statuses = TableRegistry::get('Student.StudentStatuses');
        $InstitutionStudents = TableRegistry::get('institution_students');
        $studentWithdraw = TableRegistry::get('institution_student_withdraw');
        $reason = $studentWithdraw
        ->find()
        ->select([
                'student_withdraw_reason' => $StudentWithdrawReasons->aliasField('name')
            ])
        ->leftJoin(
            [$StudentWithdrawReasons->alias() => $StudentWithdrawReasons->table()],
            [
                $StudentWithdrawReasons->aliasField('id = ') . $studentWithdraw->aliasField('student_withdraw_reason_id')
            ]
        )
        ->where([
            $studentWithdraw->aliasField('student_id') => $userId,
            $studentWithdraw->aliasField('academic_period_id') => $academicPeriods,
        ])
        ->first();


        return !empty($reason->student_withdraw_reason) ? $reason->student_withdraw_reason : '';
    }

    public function getIdByAcademicPeriods($code)
    {
        $academicPeriods = TableRegistry::get('academic_periods');
        $entity = $academicPeriods->find()
            ->where([$academicPeriods->aliasField('name') => $code])
            ->first();

        return $entity->id;
    }


    public function onExcelGetStudentContactId(Event $event, Entity $entity)
    {
        $userId = $entity->id;

        $contact = [];
        $guardianContact = [];
        $UserContacts = TableRegistry::get('User.Contacts');
        //START: POCOR-6511
        $conditionForStudent[] = $UserContacts->aliasField("contact_type_id  IN (1,2,15) ");
        //END: POCOR-6511
        $userContactResults = $UserContacts
        ->find()
        ->contain(['ContactTypes.ContactOptions'])
        ->select(['value'])                     
        ->where([
            $UserContacts->aliasField('security_user_id') => $userId,
            'OR' => $conditionForStudent
        ])
        ->all();
        if (!$userContactResults->isEmpty()) {
             foreach ($userContactResults as $key => $code) {
                $contact[] = $code->value;
            }
        }
        return implode(',', $contact);
    }

    /*
    * function to get guardian contact number
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return array
    * @ticket POCOR-6511
    */

    public function onExcelGetGuardianContactId(Event $event, Entity $entity)
    {
        $userId = $entity->id;
        $guardianContact = [];
        $StudentGuardiansContact = TableRegistry::get('Student.StudentGuardians');
        $StudentGuardiansContactResult = $StudentGuardiansContact
        ->find()
        ->select($StudentGuardiansContact->aliasField('guardian_id'))                     
        ->where([
            $StudentGuardiansContact->aliasField('student_id') => $userId
        ])
        ->first();

        
        $UserContacts = TableRegistry::get('User.Contacts');
        $conditionForGuardian[] = $UserContacts->aliasField("contact_type_id IN (1,2,15) ");
        $userContactResults = $UserContacts
        ->find()
        ->contain(['ContactTypes.ContactOptions'])
        ->select(['value'])                     
        ->where([
            $UserContacts->aliasField('security_user_id') => $StudentGuardiansContactResult->guardian_id,
            'OR' => $conditionForGuardian
        ])
        ->all();
        if (!$userContactResults->isEmpty()) {
             foreach ($userContactResults as $key => $code) {
                $guardianContact[] = $code->value;
            }
        }
        return implode(',', $guardianContact);
    }

    /*
    * function to get student email address
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return array
    * @ticket POCOR-6511
    */

    public function onExcelGetStudentEmail(Event $event, Entity $entity)
    {
        $userId = $entity->id;

        $contact = [];
        $guardianContact = [];
        $UserContacts = TableRegistry::get('User.Contacts');
        $conditionForStudent[] = $UserContacts->aliasField("contact_type_id = 8 ");
        $userContactResults = $UserContacts
        ->find()
        ->contain(['ContactTypes.ContactOptions'])
        ->select(['value'])                     
        ->where([
            $UserContacts->aliasField('security_user_id') => $userId,
            'OR' => $conditionForStudent
        ])
        ->all();
        if (!$userContactResults->isEmpty()) {
             foreach ($userContactResults as $key => $code) {
                $contact[] = $code->value;
            }
        }
        return implode(',', $contact);
    }

    /*
    * function to get guardian email address
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return array
    * @ticket POCOR-6511
    */

    public function onExcelGetGuardianEmail(Event $event, Entity $entity)
    {
        $userId = $entity->id;
        $guardianContact = [];
        $StudentGuardiansContact = TableRegistry::get('Student.StudentGuardians');
        $StudentGuardiansContactResult = $StudentGuardiansContact
        ->find()
        ->select($StudentGuardiansContact->aliasField('guardian_id'))                     
        ->where([
            $StudentGuardiansContact->aliasField('student_id') => $userId
        ])
        ->first();

        
        $UserContacts = TableRegistry::get('User.Contacts');
        $conditionForGuardian[] = $UserContacts->aliasField("contact_type_id = 8 ");
        $userContactResults = $UserContacts
        ->find()
        ->contain(['ContactTypes.ContactOptions'])
        ->select(['value'])                     
        ->where([
            $UserContacts->aliasField('security_user_id') => $StudentGuardiansContactResult->guardian_id,
            'OR' => $conditionForGuardian
        ])
        ->all();
        if (!$userContactResults->isEmpty()) {
             foreach ($userContactResults as $key => $code) {
                $guardianContact[] = $code->value;
            }
        }
        return implode(',', $guardianContact);
    }

    public function onExcelGetStudentName(Event $event, Entity $entity)
    {
        $studentName = [];
        ($entity->first_name) ? $studentName[] = $entity->first_name : '';
        ($entity->middle_name) ? $studentName[] = $entity->middle_name : '';
        ($entity->third_name) ? $studentName[] = $entity->third_name : '';
        ($entity->last_name) ? $studentName[] = $entity->last_name : '';

        return implode(' ', $studentName);
    }

    public function onExcelGetDateOfBirth(Event $event, Entity $entity)
    {
        $dateOfBirth = '';
        if ($entity->has('date_of_birth')) {
            if (!empty($entity->date_of_birth)) {
                $dateOfBirth = $entity->date_of_birth->format('F d,Y');
            }
        }
    
        return $dateOfBirth;
    }

   
}