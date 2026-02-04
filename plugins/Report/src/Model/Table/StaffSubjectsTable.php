<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StaffSubjectsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_subject_staff');

        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        //$this->belongsTo('Institution.EducationGrades', ['className' => 'Institution.EducationGrades']);

        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => ['index'],
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $indSubjectId = $requestData->education_subject_id;
        $education_grade_id = $requestData->education_grade_id;
        $regionId = null;
        $countryId = null;
        $selectedArea = $requestData->area_education_id;
        $academicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $institutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
        $staff = TableRegistry::getTableLocator()->get('Security.Users');
        $staffStatuses = TableRegistry::getTableLocator()->get('staff_statuses');
        $staffQualifications = TableRegistry::getTableLocator()->get('staff_qualifications');
        $qualificationTitles = TableRegistry::getTableLocator()->get('qualification_titles');
        $qualificationLevels = TableRegistry::getTableLocator()->get('qualification_levels');
        $staffQualificationsSpecialisations = TableRegistry::getTableLocator()->get('staff_qualifications_specialisations');
        $qualificationSpecialisations = TableRegistry::getTableLocator()->get('qualification_specialisations');
        $institutionPositions = TableRegistry::getTableLocator()->get('Institution.institution_positions');
        $staffPositionTitles = TableRegistry::getTableLocator()->get('staff_position_titles');
        $genders = TableRegistry::getTableLocator()->get('User.Genders');
        $mainNationalities = TableRegistry::getTableLocator()->get('FieldOption.Nationalities');
        $institutionSub = TableRegistry::getTableLocator()->get('institution_subjects');
        $educationSubjects = TableRegistry::getTableLocator()->get('education_subjects'); //POCOR-7095
        $userIdentities = TableRegistry::getTableLocator()->get('user_identities');
        $identityTypes = TableRegistry::getTableLocator()->get('identity_types');
        $userNationalities = TableRegistry::getTableLocator()->get('user_nationalities');
        $nationalities = TableRegistry::getTableLocator()->get('nationalities');
        $securityUsers = TableRegistry::getTableLocator()->get('security_users');
        $institutionClassSubjects = TableRegistry::getTableLocator()->get('institution_class_subjects');
        $institutionClasses = TableRegistry::getTableLocator()->get('institution_classes');
        $educationGrades = TableRegistry::getTableLocator()->get('education_grades');
        $institutions = TableRegistry::getTableLocator()->get('institutions');
        $areas = TableRegistry::getTableLocator()->get('areas');

        //POCOR-9124 start
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions['academic_periods.id'] = $academicPeriodId;
        }
        //POCOR-9124 end

        // get all institution staff id
        $institutionStaffTbl = $institutionStaff->find()
            ->select(['staff_id' => $institutionStaff->aliasField('staff_id')])
            ->innerJoin(['academic_periods' => $academicPeriods->getTable()], [
                '(((' . $institutionStaff->aliasField('end_date') . ' IS NOT NULL AND ' . $institutionStaff->aliasField('start_date') . ' <= academic_periods.start_date AND ' . $institutionStaff->aliasField('end_date') . ' >= academic_periods.start_date) OR (' . $institutionStaff->aliasField('end_date') . ' IS NOT NULL AND ' . $institutionStaff->aliasField('start_date') . ' <= academic_periods.end_date AND ' . $institutionStaff->aliasField('end_date') . ' >= academic_periods.end_date) OR (' . $institutionStaff->aliasField('end_date') . ' IS NOT NULL AND ' . $institutionStaff->aliasField('start_date') . ' >= academic_periods.start_date AND ' . $institutionStaff->aliasField('end_date') . ' <= academic_periods.end_date)) OR (' . $institutionStaff->aliasField('end_date') . ' IS NULL AND ' . $institutionStaff->aliasField('start_date') . ' <= academic_periods.end_date))'
            ])
            ->innerJoin(['staff_statuses' => $staffStatuses->getTable()], [
                $staffStatuses->aliasField('id') . ' = ' . $institutionStaff->aliasField('staff_status_id')
            ])
            ->innerJoin(['institution_positions' => $institutionPositions->getTable()], [
                $institutionPositions->aliasField('id') . ' = ' . $institutionStaff->aliasField('institution_position_id')
            ])
            ->innerJoin(['staff_position_titles' => $staffPositionTitles->getTable()], [
                $staffPositionTitles->aliasField('id') . ' = ' . $institutionPositions->aliasField('staff_position_title_id')
            ])
            ->where([
                $institutionStaff->aliasField('staff_status_id') => 1,
                $staffPositionTitles->aliasField('type') => 1,
            ])
            ->where($conditions) //POCOR-9124
            ->group([$institutionStaff->aliasField('staff_id')]);

        // get all staff qualifications staff id - staff_qualification_titles
        $staffQualificationsTbl = $staffQualifications->find()
            ->select([
                'staff_id'  => $staffQualifications->aliasField('staff_id'),
                'staff_qualification_combined'  => 'GROUP_CONCAT(DISTINCT(qualification_levels.name))',
                'staff_specialisation_combined'  => 'GROUP_CONCAT(DISTINCT(IFNULL(qualification_specialisations.name, "")))',
            ])
            ->innerJoin(['qualification_titles' => $qualificationTitles->getTable()], [
                'qualification_titles.id = staff_qualifications.qualification_title_id'
            ])
            ->innerJoin(['qualification_levels' => $qualificationLevels->getTable()], [
                'qualification_levels.id = qualification_titles.qualification_level_id'
            ])
            ->innerJoin(['staff_qualifications_specialisations' => $staffQualificationsSpecialisations->getTable()], [
                'staff_qualifications.id = staff_qualifications_specialisations.staff_qualification_id'
            ])
            ->innerJoin(['qualification_specialisations' => $qualificationSpecialisations->getTable()], [
                'qualification_specialisations.id = staff_qualifications_specialisations.qualification_specialisation_id'
            ])
            ->group(['staff_qualifications.staff_id']);

        // get user identities - default_staff_identities
        $userIdentitiesTbl = $userIdentities->find()
            ->select([
                'security_user_id'  => $userIdentities->aliasField('security_user_id'),
                'staff_default_identity_id' => 'GROUP_CONCAT(' . $userIdentities->aliasField('id') . ')',
                'staff_default_identity_number' => 'GROUP_CONCAT(' . $userIdentities->aliasField('number') . ')',
                'staff_default_identity_type' => 'GROUP_CONCAT(identity_types.name)',
            ])
            ->innerJoin(['identity_types' => $identityTypes->getTable()], [
                'identity_types.id = ' . $userIdentities->aliasField('identity_type_id')
            ])
            ->where(['identity_types.default' => 1])
            ->group([$userIdentities->aliasField('security_user_id')]);

        // get user identities security - other_staff_identities
        $userIdentitiesSecurityTbl = $userIdentities->find()
            ->select([
                'security_user_id'  => $userIdentities->aliasField('security_user_id'),
                'staff_other_identity_numbers' => 'GROUP_CONCAT(CONCAT(identity_types.name, ": ", user_identities.number))',
            ])
            ->innerJoin(['identity_types' => $identityTypes->getTable()], [
                'identity_types.id = ' . $userIdentities->aliasField('identity_type_id')
            ])
            ->where(['identity_types.default !=' => 1])
            ->group([$userIdentities->aliasField('security_user_id')]);

        // get user nationalities security - staff_nationalities
        $userNationalitiesTbl = $userNationalities->find()
            ->select([
                'security_user_id'  => $userNationalities->aliasField('security_user_id'),
                'nationality_name' => 'GROUP_CONCAT(nationalities.name)',
            ])
            ->innerJoin(['nationalities' => $nationalities->getTable()], [
                'nationalities.id = ' . $userNationalities->aliasField('nationality_id')
            ])
            ->where(['user_nationalities.preferred' => 1])
            ->group([$userNationalities->aliasField('security_user_id')]);


        if (!empty($areaId) && $areaId != '-1') {
            //POCOR-7095 start
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[] = $selectedArea;
            if (!empty($allgetArea)) {
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            } else {
                $allselectedAreas = $selectedArea1;
            }
            $conditions['institutions.area_id IN'] = $allselectedAreas;
            //POCOR-7095 end
        }

        if (!empty($institutionId) && $institutionId != -1) {
            $conditions['institutions.id'] = $institutionId;
        }
        if (!empty($education_grade_id) && $education_grade_id != -1) {
            $conditions['education_grades.id'] = $education_grade_id;
        }
        if (!empty($indSubjectId) && $indSubjectId != -1) {
            $conditions['education_subjects.id'] = $indSubjectId;
        }

        // main sql query to generate report
        $datas =   $query->select([
            'area_education'    => 'areas.name',
            'institution_code'  => 'institutions.code',
            'institution'  => 'institutions.name',
            'openEMIS_no'  => 'security_users.openemis_no',
            'default_identity'  => 'IFNULL(`default_staff_identities`.`staff_default_identity_number`, "")',
            'other_identities'  => 'IFNULL(`other_staff_identities`.`staff_other_identity_numbers`, "")',
            'first_name'  => 'security_users.first_name',
            'middle_name'  => 'IFNULL(`security_users`.`middle_name`, "")',
            'third_name'  => 'IFNULL(`security_users`.`third_name`, "")',
            'last_name'  => 'security_users.last_name',
            'gender'  => 'genders.name',
            'nationality'  => 'IFNULL(`staff_nationalities`.`nationality_name`, "")',
            'staff_status'  => 'IF(`staff_status`.`staff_id` IS NULL, "Not Assigned", "Assigned")',
            'qualification_title'  => 'IFNULL(`staff_qualification_titles`.`staff_qualification_combined`, "")',
            'qualification_specializations'  => 'IFNULL(`staff_qualification_titles`.`staff_specialisation_combined`, "")',
            'subject'  => 'education_subjects.name', //POCOR-9534
            'grade'  => 'education_grades.name',
            'class'  => 'institution_classes.name',
        ])
            ->innerJoin(['security_users' => $securityUsers->getTable()], [
                'security_users.id = ' . $this->aliasField('staff_id')
            ])
            ->leftJoin(['staff_status' => $institutionStaffTbl], [
                'staff_status.staff_id = security_users.id'
            ])
            ->innerJoin(['genders' => $genders->getTable()], [
                'genders.id = security_users.gender_id'
            ])
            ->innerJoin(['institution_subjects' => $institutionSub->getTable()], [
                'institution_subjects.id = ' . $this->aliasField('institution_subject_id')
            ])
            ->innerJoin(['institution_class_subjects' => $institutionClassSubjects->getTable()], [
                'institution_class_subjects.institution_subject_id = institution_subjects.id'
            ])
            ->innerJoin(['institution_classes' => $institutionClasses->getTable()], [
                'institution_classes.id = institution_class_subjects.institution_class_id'
            ])
            ->innerJoin(['academic_periods' => $academicPeriods->getTable()], [
                'academic_periods.id = institution_classes.academic_period_id',
                'institution_subjects.academic_period_id = academic_periods.id',
            ])
            ->innerJoin(['education_grades' => $educationGrades->getTable()], [
                'education_grades.id = institution_subjects.education_grade_id',
            ])

            ->innerJoin(['education_subjects' => $educationSubjects->getTable()], [
                'education_subjects.id = institution_subjects.education_subject_id',
            ])
            ->innerJoin(['institutions' => $institutions->getTable()], [
                'institutions.id = ' . $this->aliasField('institution_id'),
            ])
            ->leftJoin(['areas' => $areas->getTable()], [
                'areas.id = institutions.area_id',
            ])
            /*->leftJoin(['regions' => $areas->getTable()], [
            'regions.id = areas.parent_id',
        ])
        ->leftJoin(['country' => $areas->getTable()], [
            'country.id = regions.parent_id',
        ])*/
            ->leftJoin(['staff_qualification_titles' => $staffQualificationsTbl], [
                'staff_qualification_titles.staff_id = ' . $this->aliasField('staff_id')
            ])
            ->leftJoin(['default_staff_identities' => $userIdentitiesTbl], [
                'default_staff_identities.security_user_id = security_users.id'
            ])
            ->leftJoin(['other_staff_identities' => $userIdentitiesSecurityTbl], [
                'other_staff_identities.security_user_id = security_users.id'
            ])
            ->leftJoin(['staff_nationalities' => $userNationalitiesTbl], [
                'staff_nationalities.security_user_id = security_users.id'
            ])
            ->where($conditions)
            ->order([
                'institutions.code' => 'ASC',
                'education_grades.name' => 'ASC',
                'institution_classes.name' => 'ASC',
                'security_users.first_name' => 'ASC',
            ]);

        return $datas;
    }


    //POCOR-7095
    public function getChildren($id, $idArray)
    {
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

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $IdentityType = TableRegistry::getTableLocator()->get('identity_types');
        $userIdTypes = $IdentityType->find()->all();
        $defaultIdType = $IdentityType->find()
            ->where([$IdentityType->aliasField('default') => 1])
            ->first();;


        $newFields = [];
        //Start:POCOR-6779
        $newFields[] = [
            'key' => '',
            'field' => 'area_education',
            'type' => 'string',
            'label' => __('Area Education')
        ];
        //End:POCOR-6779
        $newFields[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'institution',
            'type' => 'string',
            'label' => __('Institution')
        ];
        //Start:POCOR-6779
        $newFields[] = [
            'key' => '',
            'field' => 'openEMIS_no',
            'type' => 'string',
            'label' => __('OpenEMIS No.')
        ];

        //POCOR-7307 add if condition
        if (!empty($defaultIdType)) {
            $newFields[] = [
                'key' => '',
                'field' => str_replace(' ', '_', $defaultIdType->name),
                'type' => 'string',
                'label' => __($defaultIdType->name) //Default Identity
            ];
        }
        //POCOR-7307 end
        $newFields[] = [
            'key' => '',
            'field' => 'other_identities',
            'type' => 'string',
            'label' => __('Other Identities')
        ];
        //End:POCOR-6779

        $newFields[] = [
            'key' => '',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];
        $newFields[] = [
            'key' => 'middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => __('Middle Name'),
        ];
        $newFields[] = [
            'key' => 'third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => __('Third Name'),
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'nationality',
            'type' => 'string',
            'label' => __('Nationality')
        ];
        //Start:POCOR-6779
        $newFields[] = [
            'key' => '',
            'field' => 'staff_status',
            'type' => 'string',
            'label' => __('Staff Status')
        ];
        //End:POCOR-6779
        $newFields[] = [
            'key' => '',
            'field' => 'qualification_title',
            'type' => 'string',
            'label' => __('Qualification Title')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'qualification_specializations',
            'type' => 'string',
            'label' => __('Qualification Specializations')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'subject',
            'type' => 'string',
            'label' => __('Subject')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'grade',
            'type' => 'string',
            'label' => __('Grade')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'class',
            'type' => 'string',
            'label' => __('Class')
        ];

        $fields->exchangeArray($newFields);
    }
}
