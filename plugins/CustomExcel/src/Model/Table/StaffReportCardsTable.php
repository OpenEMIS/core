<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;
use Cake\Datasource\ConnectionManager;//POCOR-9128

class StaffReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';
    //private $fileType = 'pdf';

    public function initialize(array $config): void
    {
        $this->setTable('institution_staff');
        parent::initialize($config);

        $this->addBehavior('CustomExcel.StaffExcelReport', [
            'templateTable' => 'ProfileTemplate.StaffTemplates',
            'templateTableKey' => 'staff_profile_template_id',
            'format' => $this->fileType,
            'download' => false,
            'wrapText' => true,
            'lockSheets' => true,
            'variables' => [
                'Profiles',
                'StaffReportCards',
                'Institutions',
                'StaffUsers',
                'StaffNationalities',
                'StaffDemographics',
                'StaffContacts',
                'StaffSalaries',
                'StaffAreas',
                'StaffClasses',
                'StaffSubjects',
                'StaffQualifications',
                'StaffAwards',
                'StaffLicense',//POCOR-9128
                'InstitutionStaff',//POCOR-9128
                'StaffLeave',//POCOR-9128
                'StaffTraining',//POCOR-9128
                'StaffCareer', //POCOR-9007
                'StaffPosition' //POCOR-9007
            ]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateAfterGenerate'] = 'onExcelTemplateAfterGenerate';
        $events['ExcelTemplates.Model.afterRenderExcelTemplate'] = 'afterRenderExcelTemplate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseProfiles'] = 'onExcelTemplateInitialiseProfiles';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutions'] = 'onExcelTemplateInitialiseInstitutions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffUsers'] = 'onExcelTemplateInitialiseStaffUsers';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffDemographics'] = 'onExcelTemplateInitialiseStaffDemographics';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffContacts'] = 'onExcelTemplateInitialiseStaffContacts';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffSalaries'] = 'onExcelTemplateInitialiseStaffSalaries';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffNationalities'] = 'onExcelTemplateInitialiseStaffNationalities';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffAreas'] = 'onExcelTemplateInitialiseStaffAreas';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffClasses'] = 'onExcelTemplateInitialiseStaffClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffSubjects'] = 'onExcelTemplateInitialiseStaffSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualifications'] = 'onExcelTemplateInitialiseStaffQualifications';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffAwards'] = 'onExcelTemplateInitialiseStaffAwards';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffLicense'] = 'onExcelTemplateInitialiseStaffLicense';//POCOR-9128
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStaff'] = 'onExcelTemplateInitialiseInstitutionStaff';//POCOR-9128
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffLeave'] = 'onExcelTemplateInitialiseStaffLeave';//POCOR-9128
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffTraining'] = 'onExcelTemplateInitialiseStaffTraining';//POCOR-9128
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffCareer'] = 'onExcelTemplateInitialiseStaffCareer'; //POCOR-9007
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffPosition'] = 'onExcelTemplateInitialiseStaffPosition'; //POCOR-9007
        return $events;
    }

    public function onExcelTemplateBeforeGenerate(EventInterface $event, array $params, ArrayObject $extra)
    {
        $StaffReportCards = TableRegistry::getTableLocator()->get('Institution.StaffReportCards');
        if (!$StaffReportCards->exists($params)) {
            // insert staff report card record if it does not exist
            $params['status'] = $StaffReportCards::IN_PROGRESS;
            $params['started_on'] = date('Y-m-d H:i:s');
            $newEntity = $StaffReportCards->newEntity($params);
            $StaffReportCards->save($newEntity);
        } else {
            // update status to in progress if record exists
            $StaffReportCards->updateAll([
                'status' => $StaffReportCards::IN_PROGRESS,
                'started_on' => date('Y-m-d H:i:s')
            ], $params);
        }
    }

    public function onExcelTemplateAfterGenerate(EventInterface $event, array $params, ArrayObject $extra)
    {
        $StaffReportCards = TableRegistry::getTableLocator()->get('Institution.StaffReportCards');
        $StaffReportCardData = $StaffReportCards
            ->find()
            ->select([
                $StaffReportCards->aliasField('academic_period_id'),
                $StaffReportCards->aliasField('staff_id'),
                $StaffReportCards->aliasField('institution_id'),
                $StaffReportCards->aliasField('staff_profile_template_id')
            ])
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'StaffTemplates' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Staffs' => [
                    'fields' => [
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ]
            ])
            ->where([
                $StaffReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                $StaffReportCards->aliasField('institution_id') => $params['institution_id'],
                $StaffReportCards->aliasField('staff_profile_template_id') => $params['staff_profile_template_id'],
                $StaffReportCards->aliasField('staff_id') => $params['staff_id'],
            ])
            ->first();

        // set filename
        $fileName = $StaffReportCardData->institution->code . '_' . $StaffReportCardData->staff_template->code. '_' . $StaffReportCardData->staff->openemis_no . '_' . $StaffReportCardData->staff->name . '.' . $this->fileType;
        //$fileName = $StaffReportCardData->academic_period->name . '_' . $StaffReportCardData->staff_template->code. '_' . $StaffReportCardData->institution->name . '.' . $this->fileType;
        $filepath = $extra['file_path'];
        $fileContent = file_get_contents($filepath);
        $status = $StaffReportCards::GENERATED;

        // save file
        $StaffReportCards->updateAll([
            'status' => $status,
            'completed_on' => date('Y-m-d H:i:s'),
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);

        // delete staff report card process
        $StaffReportCardProcesses = TableRegistry::Get('ReportCard.StaffReportCardProcesses');
        $StaffReportCardProcesses->deleteAll([
            'staff_profile_template_id' => $params['staff_profile_template_id'],
            'institution_id' => $params['institution_id'],
            'staff_id' => $params['staff_id']
        ]);
    }

    public function afterRenderExcelTemplate(EventInterface $event, ArrayObject $extra, $controller)
    {
        $params = $extra['params'];
        $url = [
            'plugin' => 'ProfileTemplate',
            'controller' => 'ProfileTemplates',
            'action' => 'StaffProfiles',
            'index',
            'institution_id' => $params['institution_id'],
            'staff_profile_template_id' => $params['staff_profile_template_id'],
            'academic_period_id' => $params['academic_period_id']
        ];

        $event->stopPropagation();
        return $controller->redirect($url);
    }

    public function onExcelTemplateInitialiseProfiles(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['staff_profile_template_id'])) {
            $StaffTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.StaffTemplates');
            $entity = $StaffTemplates->get($params['staff_profile_template_id'], ['contain' => ['AcademicPeriods']]);

            $extra['report_card_start_date'] = $entity->start_date;
            $extra['report_card_end_date'] = $entity->end_date;

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutions(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id'])) {
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], ['contain' => ['AreaAdministratives', 'Types']]);
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStaffUsers(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');

            $entity = $Staff
                ->find()
                ->select([
                    'first_name' => 'Users.first_name',
                    'last_name' => 'Users.last_name',
                    'email' => 'Users.email',
                    'photo_content' => 'Users.photo_content',
                    'address' => 'Users.address',
                    'date_of_birth' => 'Users.date_of_birth',
                    'identity_number' => 'Users.identity_number',
                    // 'staff_position_title' => 'Positions.StaffPositionTitles.staff_position_title',
                    'gender' => 'Genders.name',
                ])
                ->join([
                    'Users' => [
                        'table' => 'security_users',
                        'type' => 'INNER',
                        'conditions' => 'Users.id = Staff.staff_id'
                    ],
                    'Genders' => [
                        'table' => 'genders',
                        'type' => 'INNER',
                        'conditions' => 'Genders.id = Users.gender_id'
                    ]
                ])
                // ->contain([
                //     'Users' => [
                //         'fields' => [
                //             'identity_number',
                //             'first_name',
                //             'last_name',
                //             'photo_content',
                //             'email',
                //             'address',
                //             'date_of_birth',
                //         ]
                //     ],
                //  'Positions.StaffPositionTitles'=>[
                //      'fields' => [
                //          'staff_position_title' => 'StaffPositionTitles.name',
                //      ]
                //  ]
                // ])
                ->matching('Users.Genders')
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    $Staff->aliasField('staff_id') => $params['staff_id'],
                ])
                ->first();
                //echo '<pre>';print_r($entity);die;
                $result = [];
                $result = [
                    'name' => $entity->first_name.' '.$entity->last_name,
                    'identity_number' => $entity->identity_number,
                    'photo_content' => $entity->photo_content,
                    'email' => $entity->email,
                    'address' => $entity->address,
                    'date_of_birth' => $entity->date_of_birth,
                    'staff_position_title' => $entity->staff_position_title,
                    'gender' => $entity->gender,
                ];
            return $result;
        }
    }

    public function onExcelTemplateInitialiseStaffDemographics(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');

            $entity = $Staff
                ->find()
                ->select([
                    'demographic_type_name' => 'DemographicTypes.name',
                ])
                ->innerJoin(
                ['UserDemographics' => 'user_demographics'],
                [
                    'UserDemographics.security_user_id ='. $Staff->aliasField('staff_id')
                ]
                )
                ->leftJoin(
                ['DemographicTypes' => 'demographic_types'],
                [
                    'DemographicTypes.id = UserDemographics.demographic_types_id'
                ]
                )
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    $Staff->aliasField('staff_id') => $params['staff_id'],
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStaffContacts(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $UserContacts = TableRegistry::getTableLocator()->get('user_contacts');

            $entity = $UserContacts
                ->find()
                ->select([
                    'contact' => $UserContacts->aliasField('value'),
                ])
                ->where([
                    $UserContacts->aliasField('security_user_id') => $params['staff_id'],
                    $UserContacts->aliasField('preferred') => 1,
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStaffNationalities(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $UserNationalities = TableRegistry::getTableLocator()->get('user_nationalities');

            $entity = $UserNationalities
                ->find()
                ->select([
                    'name' => 'Nationalities.name',
                ])
                ->innerJoin(
                ['Nationalities' => 'nationalities'],
                [
                    'Nationalities.id ='. $UserNationalities->aliasField('nationality_id')
                ]
                )
                ->where([
                    $UserNationalities->aliasField('security_user_id') => $params['staff_id'],
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStaffSalaries(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $StaffSalaries = TableRegistry::getTableLocator()->get('staff_salaries');

            $staffSalaryData = $StaffSalaries
                ->find()
                ->select([
                    'salary_date'  => $StaffSalaries->aliasField('salary_date'),
                    'gross_salary' => $StaffSalaries->aliasField('gross_salary'),
                    'net_salary'   => $StaffSalaries->aliasField('net_salary'),
                ])
                ->where([
                    $StaffSalaries->aliasField('staff_id') => $params['staff_id'],
                ])
                ->order([$StaffSalaries->aliasField('salary_date') => 'DESC'])
                ->toArray();

            $entity = $result = [];
            if (!empty($staffSalaryData)) {
                foreach ($staffSalaryData as $key => $data) {
                    $result = [
                        'id'           => $key,
                        'salary_date'  => !empty($data['salary_date']) 
                            ? $data['salary_date']->format('d-m-Y') 
                            : '',
                        'gross_salary' => !empty($data['gross_salary']) ? $data['gross_salary'] : '',
                        'net_salary'   => !empty($data['net_salary']) ? $data['net_salary'] : '',
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStaffAreas(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $SecurityUsers = TableRegistry::getTableLocator()->get('security_users');

            $entity = $SecurityUsers
                ->find()
                ->select([
                    'area_administrative_name' => 'AreaAdministratives.name',
                    'area_administrative_level' => 'AreaAdministrativeLevels.name',
                ])
                ->innerJoin(
                ['AreaAdministratives' => 'area_administratives'],
                [
                    'AreaAdministratives.id ='. $SecurityUsers->aliasField('address_area_id')
                ]
                )
                ->innerJoin(
                ['AreaAdministrativeLevels' => 'area_administrative_levels'],
                [
                    'AreaAdministrativeLevels.id = AreaAdministratives.area_administrative_level_id'
                ]
                )
                ->where([
                    $SecurityUsers->aliasField('id') => $params['staff_id'],
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStaffClasses(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $InstitutionClasses = TableRegistry::getTableLocator()->get('institution_classes');
            $InstitutionClassesSecondaryStaff = TableRegistry::getTableLocator()->get('institution_classes_secondary_staff');

            $InstitutionClassesData = [];
            $InstitutionClassesData = $InstitutionClasses
                ->find()
                ->select([
                    'id' => $InstitutionClasses->aliasField('id'),
                    'class_name' => $InstitutionClasses->aliasField('name'),
                    'total_male_students' => $InstitutionClasses->aliasField('total_male_students'),
                    'total_female_students' => $InstitutionClasses->aliasField('total_female_students'),
                    'education_grade' => 'EducationGrades.name',
                ])
                ->leftJoin(
                ['InstitutionClassGrades' => 'institution_class_grades'],
                [
                    'InstitutionClassGrades.institution_class_id ='. $InstitutionClasses->aliasField('id')
                ]
                )
                ->leftJoin(
                ['EducationGrades' => 'education_grades'],
                [
                    'EducationGrades.id = InstitutionClassGrades.education_grade_id'
                ]
                )
                ->where([
                    $InstitutionClasses->aliasField('staff_id') => $params['staff_id'],
                    $InstitutionClasses->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionClasses->aliasField('institution_id') => $params['institution_id'],
                ])
                ->toArray();

            $SecondaryStaffData = [];
            $SecondaryStaffData = $InstitutionClassesSecondaryStaff
                ->find()
                ->select([
                    'id' => $InstitutionClasses->aliasField('id'),
                    'class_name' => $InstitutionClasses->aliasField('name'),
                    'total_male_students' => $InstitutionClasses->aliasField('total_male_students'),
                    'total_female_students' => $InstitutionClasses->aliasField('total_female_students'),
                    'education_grade' => 'EducationGrades.name',
                ])
                ->innerJoin(
                    [$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()],
                    [
                        $InstitutionClasses->aliasField('id = ') .  $InstitutionClassesSecondaryStaff->aliasField('institution_class_id'),
                    ]
                )
                ->innerJoin(
                    ['InstitutionClassGrades' => 'institution_class_grades'],
                    [
                        'InstitutionClassGrades.institution_class_id ='. $InstitutionClasses->aliasField('id')
                    ]
                )
                ->leftJoin(
                    ['EducationGrades' => 'education_grades'],
                    [
                        'EducationGrades.id = InstitutionClassGrades.education_grade_id'
                    ]
                )
                ->where([
                    $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $params['staff_id'],
                    $InstitutionClasses->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionClasses->aliasField('institution_id') => $params['institution_id'],
                ])
                ->toArray();

            $entity = array_merge($InstitutionClassesData,$SecondaryStaffData);
            $result = [];
            $total_students = 0;
            foreach ($entity as $value) {
                $total_students = $value->total_male_students + $value->total_female_students;
                $result[] = [
                    'id' => $value->id,
                    'class_name' => $value->class_name,
                    'education_grade' => $value->education_grade,
                    'total_students' => $total_students,
                ];
            }
            return $result;
        }
    }

    public function onExcelTemplateInitialiseStaffSubjects(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $InstitutionSubjectStaff = TableRegistry::getTableLocator()->get('institution_subject_staff');

            $entity = [];
            $entity = $InstitutionSubjectStaff
                ->find()
                ->select([
                    'id' => 'InstitutionSubjects.id',
                    'subject_name' => 'InstitutionSubjects.name',
                    'total_male_students' => 'InstitutionSubjects.total_male_students',
                    'total_female_students' => 'InstitutionSubjects.total_female_students',
                    'education_grade' => 'EducationGrades.name',
                ])
                ->innerJoin(
                ['InstitutionSubjects' => 'institution_subjects'],
                [
                    'InstitutionSubjects.id ='. $InstitutionSubjectStaff->aliasField('institution_subject_id'),
                    'InstitutionSubjects.academic_period_id' => $params['academic_period_id'],
                    'InstitutionSubjects.institution_id' => $params['institution_id'],
                ]
                )
                ->leftJoin(
                ['EducationGrades' => 'education_grades'],
                [
                    'EducationGrades.id = InstitutionSubjects.education_grade_id'
                ]
                )
                ->where([
                    $InstitutionSubjectStaff->aliasField('staff_id') => $params['staff_id'],
                    $InstitutionSubjectStaff->aliasField('institution_id') => $params['institution_id'],
                ])
                ->toArray();

            $result = [];
            $total_students = 0;
            foreach ($entity as $value) {
                $total_students = $value->total_male_students + $value->total_female_students;
                $result[] = [
                    'id' => $value->id,
                    'subject_name' => $value->subject_name,
                    'education_grade' => $value->education_grade,
                    'total_students' => $total_students,
                ];
            }
            return $result;
        }
    }

    public function onExcelTemplateInitialiseStaffQualifications(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $StaffQualifications = TableRegistry::getTableLocator()->get('Staff.Qualifications');

            $entity = $StaffQualifications->find()
                ->select([
                    'id' => 'QualificationTitles.id',
                    'qualification_title' => 'QualificationTitles.name',
                    'qualification_level' => 'QualificationLevels.name',
                ])
                ->innerJoin(
                ['QualificationTitles' => 'qualification_titles'],
                [
                    'QualificationTitles.id ='. $StaffQualifications->aliasField('qualification_title_id'),
                ]
                )
                ->innerJoin(
                ['QualificationLevels' => 'qualification_levels'],
                [
                    'QualificationLevels.id = QualificationTitles.qualification_level_id',
                ]
                )
                ->where([
                    $StaffQualifications->aliasField('staff_id') => $params['staff_id'],
                ])
                ->toArray()
            ;
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStaffAwards(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['staff_id'])) {
            $UserAwards = TableRegistry::getTableLocator()->get('user_awards');

            $entity = $UserAwards->find()
                ->select([
                    'id' => $UserAwards->aliasField('id'),
                    'award' => $UserAwards->aliasField('award'),
                ])
                ->where([
                    $UserAwards->aliasField('security_user_id') => $params['staff_id'],
                ])
                ->toArray()
            ;
            return $entity;
        }
    }
    //POCOR-9128 starts
    public function onExcelTemplateInitialiseStaffLicense(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (empty($params['staff_id'])) {
            return [];
        }
        $staffId = $params['staff_id'];
        $connection = ConnectionManager::get('default');
        $staffLicensesData = $connection->execute("SELECT 
                                        license_types.name AS license_type_name,
                                        IFNULL(license_classification_info.license_classification_names, '') AS license_classification_names,
                                        staff_licenses.license_number,
                                        staff_licenses.issue_date,
                                        staff_licenses.expiry_date
                                    FROM staff_licenses
                                    INNER JOIN license_types 
                                        ON license_types.id = staff_licenses.license_type_id
                                    LEFT JOIN (
                                        SELECT 
                                            staff_licenses_classifications.staff_license_id,
                                            GROUP_CONCAT(DISTINCT license_classifications.name) AS license_classification_names
                                        FROM staff_licenses_classifications
                                        INNER JOIN license_classifications 
                                            ON license_classifications.id = staff_licenses_classifications.license_classification_id
                                        GROUP BY staff_licenses_classifications.staff_license_id
                                    ) AS license_classification_info 
                                        ON license_classification_info.staff_license_id = staff_licenses.id
                                    WHERE staff_licenses.security_user_id = " . $staffId . "
                                    ORDER BY staff_licenses.expiry_date DESC")->fetchAll(\PDO::FETCH_ASSOC);
        
        $entity = $result = [];
        if (!empty($staffLicensesData)) {
            foreach ($staffLicensesData as $key => $data) {
                $result = [
                    'id' => $key,
                    'type' => !empty($data['license_type_name']) ? $data['license_type_name'] : '',
                    'classification' => !empty($data['license_classification_names']) ? $data['license_classification_names'] : '',
                    'number' => !empty($data['license_number']) ? $data['license_number'] : '',
                    'issue_date' => !empty($data['issue_date']) ? $data['issue_date'] : '',
                    'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : ''
                ];
                $entity[] = $result;
            }
        }
        return $entity;
    }
    
    public function onExcelTemplateInitialiseInstitutionStaff(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (empty($params['staff_id'])) {
            return [];
        }
        $staffId = $params['staff_id'];
        $connection = ConnectionManager::get('default');
        $InstitutionStaffData = $connection->execute("SELECT institutions.name institution_name
                                                    ,institution_positions.position_no staff_position
                                                    ,staff_types.name staff_type_name
                                                    ,institution_staff.start_date
                                                    ,institution_staff.end_date
                                                FROM institution_staff
                                                INNER JOIN staff_types
                                                ON staff_types.id = institution_staff.staff_type_id
                                                INNER JOIN institution_positions
                                                ON institution_positions.id = institution_staff.institution_position_id
                                                INNER JOIN institutions
                                                ON institutions.id = institution_staff.institution_id
                                                WHERE institution_staff.staff_id = " . $staffId . " 
                                                ORDER BY institution_staff.start_date DESC")->fetchAll(\PDO::FETCH_ASSOC);
        
        $entity = $result = [];
        if (!empty($InstitutionStaffData)) {
            foreach ($InstitutionStaffData as $key => $data) {
                $result = [
                    'id' => $key,
                    'institution_name' => !empty($data['institution_name']) ? $data['institution_name'] : '',
                    'position' => !empty($data['staff_position']) ? $data['staff_position'] : '',
                    'staff_type' => !empty($data['staff_type_name']) ? $data['staff_type_name'] : '',
                    'start_date' => !empty($data['start_date']) ? $data['start_date'] : '',
                    'end_date' => !empty($data['end_date']) ? $data['end_date'] : ''
                ];
                $entity[] = $result;
            }
        }
        return $entity;
    }

    public function onExcelTemplateInitialiseStaffLeave(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (empty($params['staff_id'])) {
            return [];
        }
        $staffId = $params['staff_id'];
        $connection = ConnectionManager::get('default');
        $staffLeaveData = $connection->execute("SELECT workflow_steps.name status_name
                                                        ,CONCAT(IFNULL(security_users.first_name,''), ' ', IFNULL(security_users.last_name,'')) assignee_name
                                                        ,staff_leave_types.name staff_leave_type
                                                        ,institution_staff_leave.date_from
                                                        ,institution_staff_leave.date_to
                                                        ,institution_staff_leave.start_time
                                                        ,institution_staff_leave.end_time
                                                        ,institution_staff_leave.full_day
                                                        ,(DATEDIFF(institution_staff_leave.date_to, institution_staff_leave.date_from) + 1) number_of_days
                                                        ,institution_staff_leave.comments
                                                        ,academic_periods.name academic_period_name
                                                    FROM institution_staff_leave
                                                    INNER JOIN staff_leave_types
                                                    ON staff_leave_types.id = institution_staff_leave.staff_leave_type_id
                                                    LEFT JOIN workflow_steps
                                                    ON workflow_steps.id = institution_staff_leave.status_id
                                                    LEFT JOIN security_users
                                                    ON security_users.id = institution_staff_leave.assignee_id
                                                    LEFT JOIN academic_periods
                                                    ON academic_periods.id = institution_staff_leave.academic_period_id
                                                    WHERE institution_staff_leave.staff_id = " . $staffId . "
                                                    ORDER BY institution_staff_leave.date_from DESC")->fetchAll(\PDO::FETCH_ASSOC);

        $entity = $result = [];
        if (!empty($staffLeaveData)) {
            foreach ($staffLeaveData as $key => $data) {
                $time = !empty($data['full_day']) ? __('All Day') : (!empty($data['start_time']) ? $data['start_time'] : '') . (!empty($data['end_time']) ? ' - ' . $data['end_time'] : '');
                $result = [
                    'id'             => $key,
                    'status'         => !empty($data['status_name']) ? $data['status_name'] : '',
                    'assignee'       => !empty($data['assignee_name']) ? trim($data['assignee_name']) : '',
                    'type'           => !empty($data['staff_leave_type']) ? $data['staff_leave_type'] : '',
                    'date_from'      => !empty($data['date_from']) ? $data['date_from'] : '',
                    'date_to'        => !empty($data['date_to']) ? $data['date_to'] : '',
                    'time'           => $time,
                    'number_of_days' => !empty($data['number_of_days']) ? $data['number_of_days'] : '',
                    'comments'       => !empty($data['comments']) ? $data['comments'] : '',
                    'academic_period'=> !empty($data['academic_period_name']) ? $data['academic_period_name'] : '',
                ];
                $entity[] = $result;
            }
        }
        return $entity;
    }

    public function onExcelTemplateInitialiseStaffTraining(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (empty($params['staff_id'])) {
            return [];
        }
        $staffId = $params['staff_id'];
        $connection = ConnectionManager::get('default');
        $InstitutionStaffData = $connection->execute("SELECT staff_training_categories.name staff_training_category_name
                                                ,training_field_of_studies.name training_field_of_study_name
                                                ,staff_trainings.name staff_training_name
                                                ,staff_trainings.completed_date
                                                ,staff_trainings.credit_hours
                                            FROM staff_trainings
                                            INNER JOIN staff_training_categories
                                            ON staff_training_categories.id = staff_trainings.staff_training_category_id
                                            INNER JOIN training_field_of_studies
                                            ON training_field_of_studies.id = staff_trainings.training_field_of_study_id
                                            WHERE staff_trainings.staff_id = " . $staffId . "
                                            ORDER BY staff_trainings.completed_date DESC")->fetchAll(\PDO::FETCH_ASSOC);
        
        $entity = $result = [];
        if (!empty($InstitutionStaffData)) {
            foreach ($InstitutionStaffData as $key => $data) {
                $result = [
                    'id' => $key,
                    'category' => !empty($data['staff_training_category_name']) ? $data['staff_training_category_name'] : '',
                    'field_of_study' => !empty($data['training_field_of_study_name']) ? $data['training_field_of_study_name'] : '',
                    'name' => !empty($data['staff_training_name']) ? $data['staff_training_name'] : '',
                    'completion_date' => !empty($data['completed_date']) ? $data['completed_date'] : '',
                    'credit_hours' => !empty($data['credit_hours']) ? $data['credit_hours'] : ''
                ];
                $entity[] = $result;
            }
        }
        return $entity;
    }//POCOR-9128 ends

    //POCOR-9007
    public function onExcelTemplateInitialiseStaffCareer(EventInterface $event, array $params, ArrayObject $extra) 
    {
        if (empty($params['staff_id'])) {
            return [];
        }

        $StaffEmploymentStatuses = TableRegistry::getTableLocator()->get('Staff.EmploymentStatuses');

        $data = $StaffEmploymentStatuses->find()
            ->select([
                'status_type' => 'EmploymentStatusTypes.name',
                'status_date' => $StaffEmploymentStatuses->aliasField('status_date'),
                'comment'     => $StaffEmploymentStatuses->aliasField('comment'),
            ])
            ->leftJoin(
                ['EmploymentStatusTypes' => 'employment_status_types'],
                ['EmploymentStatusTypes.id = ' . $StaffEmploymentStatuses->aliasField('status_type_id')]
            )
            ->where([
                $StaffEmploymentStatuses->aliasField('staff_id') => $params['staff_id'],
            ])
            ->order([
                $StaffEmploymentStatuses->aliasField('status_date') => 'DESC'
            ])
            ->enableHydration(false)
            ->toArray();

        $entity = [];

        if (!empty($data)) {
            foreach ($data as $key => $row) {
                $entity[] = [
                    'id' => $key,
                    'status_type' => !empty($row['status_type']) ? $row['status_type'] : '',
                    'status_date' => !empty($row['status_date']) 
                        ? $row['status_date']->format('Y-m-d') 
                        : '',
                    'comment' => !empty($row['comment']) ? $row['comment'] : ''
                ];
            }
        }

        return $entity;
    }

    //POCOR-9007
    public function onExcelTemplateInitialiseStaffPosition(EventInterface $event, array $params, ArrayObject $extra)
    {
        if (empty($params['staff_id'])) {
            return [];
        }
        $staffId = (int)$params['staff_id'];
        $connection = ConnectionManager::get('default');
        $staffPositionData = $connection->execute(
            "SELECT 
                i.name AS institution_name,
                i.code AS institution_code,
                ip.position_no,
                spt.name AS position_title,
                st.name AS staff_type,
                so.name AS shift_name,
                so.start_time AS shift_start_time,
                so.end_time AS shift_end_time,
                ist.start_date,
                ist.end_date,
                ss.name AS staff_status

            FROM institution_staff ist

            LEFT JOIN institution_positions ip 
                ON ip.id = ist.institution_position_id

            LEFT JOIN institutions i 
                ON i.id = ist.institution_id

            LEFT JOIN staff_position_titles spt 
                ON spt.id = ip.staff_position_title_id

            LEFT JOIN staff_types st 
                ON st.id = ist.staff_type_id

            LEFT JOIN shift_options so
            ON so.id = ip.shift_id

            LEFT JOIN staff_statuses ss
                ON ss.id = ist.staff_status_id

            WHERE ist.staff_id = :staff_id

            ORDER BY ist.start_date DESC",
            ['staff_id' => $staffId]
        )->fetchAll('assoc');
        $entity = [];
        if (!empty($staffPositionData)) {
            foreach ($staffPositionData as $key => $data) {

                $entity[] = [
                    'id'               => $key + 1,
                    'institution' => ($data['institution_code'] ?? '') . ' - ' . ($data['institution_name'] ?? ''),
                    'position_no'      => $data['position_no'] ?? '',
                    'position_title'   => $data['position_title'] ?? '',
                    'staff_type'       => $data['staff_type'] ?? '',
                    'shift'            => $data['shift_name'] ?? '',
                    'shift_time'       => (!empty($data['shift_start_time']) && !empty($data['shift_end_time']))
                                            ? $data['shift_start_time'] . ' - ' . $data['shift_end_time']
                                            : '',
                    'start_date'       => !empty($data['start_date']) ? date('d-m-Y', strtotime($data['start_date'])) : '',
                    'end_date'         => !empty($data['end_date']) ? date('d-m-Y', strtotime($data['end_date'])) : '',
                    'staff_status'     => $data['staff_status'] ?? '',
                ];
            }
        }

        return $entity;
    }

    
}
