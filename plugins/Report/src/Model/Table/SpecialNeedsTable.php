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
                'identity_type' => 'IdentityTypes.name',
                'identity_number' => 'Users.identity_number',
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
            ])
            ->leftJoin(
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
            ->leftJoin(
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
            ->contain([
                'Institutions',
                'AcademicPeriods',
                'EducationGrades',
                'InstitutionClasses',
                'Users',
                'Users.Genders',
                'Users.IdentityTypes'
            ])
            ->where([
                    $this->aliasField('academic_period_id') => $academic_period_id,
                    $where
                ])
            ->order([
                'EducationGrades.name'
            ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => 'Institution Code',
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => 'Institution Name',
        ];

        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => 'Academic Period',
        ];

        $newFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => 'Education Grade',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEmisId',
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'student_name',
            'type' => 'string',
            'label' => 'Name',
        ];
        
        $newFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender',
            'type' => 'string',
            'label' => 'Gender',
        ];
        $newFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => 'Identity Type',
        ];
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => 'Identity Number',
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsTypes.name',
            'field' => 'special_need_type',
            'type' => 'string',
            'label' => 'Special Needs Type',
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsDifficulties.name',
            'field' => 'special_need_difficulty_type',
            'type' => 'string',
            'label' => 'Special Needs Difficulty Type',
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsServiceTypes.name',
            'field' => 'special_need_service_type',
            'type' => 'string',
            'label' => 'Special Needs Service Type',
        ];
        $newFields[] = [
            'key' => 'SpecialNeedsServices.organization',
            'field' => 'organization',
            'type' => 'string',
            'label' => 'Organization',
        ];
        $newFields[] = [
            'key' => 'GuardianRelations.name',
            'field' => 'guardian_relation',
            'type' => 'string',
            'label' => 'Guardian Relation',
        ];
        $newFields[] = [
            'key' => 'GuardianRelations.openemis_no',
            'field' => 'guardian_openemis_no',
            'type' => 'string',
            'label' => 'Guardian OpenEmisId',
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'guardian_name',
            'type' => 'string',
            'label' => 'Guardian Name',
        ];

        $fields->exchangeArray($newFields);
    }
}
