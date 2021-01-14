<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class SpecialNeedsTable extends AppTable
{
	use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'joinType' => 'LEFT']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'joinType' => 'LEFT']);
       
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'joinType' => 'LEFT']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'joinType' => 'LEFT']);
        
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['start_year', 'end_year', 'security_group_user_id'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
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
        // Setting request data and modifying fetch condition
        $requestData = json_decode($settings['process']['params']);
        $academic_period_id = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $Users = TableRegistry::get('User.Users');
        $Genders = TableRegistry::get('User.Genders');
        $SpecialNeedsAssessments = TableRegistry::get('SpecialNeeds.SpecialNeedsAssessments');
        $SpecialNeedsServices = TableRegistry::get('SpecialNeeds.SpecialNeedsServices');
        $SpecialNeedsTypes = TableRegistry::get('SpecialNeeds.SpecialNeedsTypes');
        $SpecialNeedsDifficulties = TableRegistry::get('SpecialNeeds.SpecialNeedsDifficulties');
        $SpecialNeedsServiceTypes = TableRegistry::get('SpecialNeeds.SpecialNeedsServiceTypes');
        $StudentGuardians = TableRegistry::get('Student.StudentGuardians');
        $InstitutionStudentRisks = TableRegistry::get('Institution.InstitutionStudentRisks');
        $GuardianRelations = TableRegistry::get('Student.GuardianRelations');
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        $UserContact = TableRegistry::get('user_contacts');
        $UserSpecialNeedsReferrals = TableRegistry::get('user_special_needs_referrals');
        if ($institution_id != 0) {
            $where = [$this->aliasField('institution_id') => $institution_id];
        } else {
            $where = [];
        }
        $query
            ->select([
                'code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'academic_period' => 'AcademicPeriods.name',
                'education_grade' => 'EducationGrades.name',
                'institution_class' => 'InstitutionClasses.name',
                'openemis_no' => 'Users.openemis_no',
                'student_name' => $Users->find()->func()->concat([
                    'Users.first_name' => 'literal',
                    " - ",
                    'Users.last_name' => 'literal']),
                'gender' => 'Genders.name',
                'date_of_birth' => 'Users.date_of_birth',
                'start_year' => 'AcademicPeriods.start_year',
                'identity_type' => $IdentityTypes->aliasField('name'),
                'identity_number' => $UserIdentities->aliasField('number'),
                'special_need_type' => 'SpecialNeedsTypes.name',
                'special_need_difficulty_type' => 'SpecialNeedsDifficulties.name',
                'special_need_service_type' => 'SpecialNeedsServiceTypes.name',
                'organization' => 'SpecialNeedsServices.organization',
                'guardian_relation' => 'GuardianRelations.name',
                'guardian_openemis_no' => 'GuardianUser.openemis_no',
                'guardian_name' => $Users->find()->func()->concat([
                    'GuardianUser.first_name' => 'literal',
                    " - ",
                    'GuardianUser.last_name' => 'literal']),
                'guardian_contact_number' => $UserContact->aliasField('value'),
                'referred_user_id' => $UserSpecialNeedsReferrals->aliasField('security_user_id'),
                'referred_staff_id' => $UserSpecialNeedsReferrals->aliasField('referrer_id'),
            ])
            ->leftJoin(
                    [$Users->alias() => $Users->table()],
                    [
                        $Users->aliasField('id = ') . $this->aliasField('student_id')
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
            ->innerJoin(
                    [$SpecialNeedsAssessments->alias() => $SpecialNeedsAssessments->table()],
                    [
                        $SpecialNeedsAssessments->aliasField('security_user_id = ') . $this->aliasField('student_id')
                    ]
                )
            ->leftJoin(
                    [$SpecialNeedsTypes->alias() => $SpecialNeedsTypes->table()],
                    [
                        $SpecialNeedsTypes->aliasField('id = ') . $SpecialNeedsAssessments->aliasField('special_need_type_id')
                    ]
                )
            ->leftJoin(
                    [$SpecialNeedsDifficulties->alias() => $SpecialNeedsDifficulties->table()],
                    [
                        $SpecialNeedsDifficulties->aliasField('id = ') . $SpecialNeedsAssessments->aliasField('special_need_difficulty_id')
                    ]
                )
            ->innerJoin(
                    [$SpecialNeedsServices->alias() => $SpecialNeedsServices->table()],
                    [
                        $SpecialNeedsServices->aliasField('security_user_id = ') . $this->aliasField('student_id')
                    ]
                )
            ->leftJoin(
                    [$SpecialNeedsServiceTypes->alias() => $SpecialNeedsServiceTypes->table()],
                    [
                        $SpecialNeedsServiceTypes->aliasField('id = ') . $SpecialNeedsServices->aliasField('special_needs_service_type_id')
                    ]
                )
            ->leftJoin(
                    [$StudentGuardians->alias() => $StudentGuardians->table()],
                    [
                        $StudentGuardians->aliasField('student_id = ') . $this->aliasField('student_id')
                    ]
                )
            ->leftJoin(
                    [$GuardianRelations->alias() => $GuardianRelations->table()],
                    [
                        $GuardianRelations->aliasField('id = ') . $StudentGuardians->aliasField('guardian_relation_id')
                    ]
                )
            ->leftJoin(
                    [$InstitutionStudentRisks->alias() => $InstitutionStudentRisks->table()],
                    [
                        $InstitutionStudentRisks->aliasField('student_id = ') . $this->aliasField('student_id')
                    ]
                )
            ->leftJoin(['GuardianUser' => 'security_users'], [
                        'GuardianUser.id = '.$StudentGuardians->aliasField('guardian_id')
                    ])
            ->leftJoin([$UserContact->alias() => $UserContact->table()], [
                $UserContact->aliasField('security_user_id = ') . 'GuardianUser.id'
            ])
            ->leftJoin([$UserSpecialNeedsReferrals->alias() => $UserSpecialNeedsReferrals->table()], [
                $UserSpecialNeedsReferrals->aliasField('security_user_id = ') . $this->aliasField('student_id')
            ])
            ->contain([
                'Institutions',
                'AcademicPeriods',
                'EducationGrades',
                'InstitutionClasses',
                'Users.Genders'
            ])
            ->group([
                'Users.id'
            ])
            ->where([
                    $this->aliasField('academic_period_id') => $academic_period_id,
                    $where
                ])
            ->order([
                'EducationGrades.name'
            ]);

            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    
                    $UserSpecialNeedsReferrals = TableRegistry::get('user_special_needs_referrals');
                    $staff_user_data = $UserSpecialNeedsReferrals
                                ->find()
                                ->where([$UserSpecialNeedsReferrals->alias('security_user_id')=>$row->referred_user_id])
                                ->toArray();
                    $security_users = TableRegistry::get('security_users');
                    foreach($staff_user_data AS $staff_user){
                        $val = $security_users
                                    ->find()
                                    ->select([
                                        $security_users->aliasField('first_name'),
                                        $security_users->aliasField('middle_name'),
                                        $security_users->aliasField('last_name'),
                                        ])  
                                    ->where([
                                        $security_users->aliasField('id') => $staff_user->referrer_id
                                    ])->first();
                        $name[] = $val->first_name." ".$val->middle_name." ".$val->last_name;
                    }
                    $name = array_unique($name);
                    $implodedArr = implode(",",$name);
                    $row['staff_name'] = $implodedArr;


                    $UserContact = TableRegistry::get('user_contacts');

                    foreach($staff_user_data AS $staff_user){
                        $val = $UserContact
                                    ->find()
                                    ->select([
                                        $UserContact->aliasField('value'),
                                        ])  
                                    ->where([
                                        $UserContact->aliasField('security_user_id') => $staff_user->referrer_id
                                    ])->first();
                        if(empty($val->value)){
                        }
                        else{
                            $contact[] = $val->value;
                        }
                    }
                    $contact = array_unique($contact);
                    $implodedContactArr = implode(",",$contact);
                    $row['staff_contact'] = $implodedContactArr;
                              
                    
                    return $row;
                });
            });
    }

    public function onExcelGetAge(Event $event, Entity $entity)
    {
        $age = '';
        if (!empty($entity->start_year) && !empty($entity->date_of_birth)) {
            $startYear = $entity->start_year;
            $dob = $entity->date_of_birth->format('Y');
            $age = $startYear - $dob;
        }

        return $age;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Name')
        ];
        $newFields[] = [
            'key' => 'Users.age',
            'field' => 'age',
            'type' => 'string',
            'label' => __('Age')
        ];
        $newFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        $newFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsTypes.name',
            'field' => 'special_need_type',
            'type' => 'string',
            'label' => __('Disability Type')
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsDifficulties.name',
            'field' => 'special_need_difficulty_type',
            'type' => 'string',
            'label' => __('Difficulty Type')
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsServiceTypes.name',
            'field' => 'special_need_service_type',
            'type' => 'string',
            'label' => __('Program Assigned')
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsServices.organization',
            'field' => 'organization',
            'type' => 'string',
            'label' => __('Organization')
        ];
        $newFields[] = [
            'key' => 'GuardianRelations.name',
            'field' => 'guardian_relation',
            'type' => 'string',
            'label' => __('Guardian Relations')
        ];
        $newFields[] = [
            'key' => 'GuardianRelations.openemis_no',
            'field' => 'guardian_openemis_no',
            'type' => 'string',
            'label' => __('Guardian OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'guardian_name',
            'type' => 'string',
            'label' => __('Guardian Name')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'guardian_contact_number',
            'type' => 'string',
            'label' => __('Guardian Contact Number')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Referrer Staff Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'staff_contact',
            'type' => 'string',
            'label' => __('Referrer Staff Contact number')
        ];

        $fields->exchangeArray($newFields);
    }
}
