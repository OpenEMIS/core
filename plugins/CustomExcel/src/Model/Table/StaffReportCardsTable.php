<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;

class StaffReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';
    //private $fileType = 'pdf';

    public function initialize(array $config)
    {
        $this->table('institution_staff');
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
            ]
        ]);
    }

    public function implementedEvents()
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
		return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $StaffReportCards = TableRegistry::get('Institution.StaffReportCards');
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

    public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $StaffReportCards = TableRegistry::get('Institution.StaffReportCards');
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

    public function afterRenderExcelTemplate(Event $event, ArrayObject $extra, $controller)
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
    
	public function onExcelTemplateInitialiseProfiles(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('staff_profile_template_id', $params)) {
            $StaffTemplates = TableRegistry::get('ProfileTemplate.StaffTemplates');
            $entity = $StaffTemplates->get($params['staff_profile_template_id'], ['contain' => ['AcademicPeriods']]);
			
            $extra['report_card_start_date'] = $entity->start_date;
            $extra['report_card_end_date'] = $entity->end_date;

            return $entity->toArray();
        }
    }
	
	public function onExcelTemplateInitialiseInstitutions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Institutions = TableRegistry::get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], ['contain' => ['AreaAdministratives', 'Types']]);
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffUsers(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');

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
					'staff_position_title' => 'Positions.StaffPositionTitles.staff_position_title',
					'gender' => 'Genders.name',
                ])
                ->contain([
                    'Users' => [
                        'fields' => [
                            'identity_number',
                            'first_name',
                            'last_name',
                            'photo_content',
                            'email',
                            'address',
                            'date_of_birth',
                        ]
                    ],
					'Positions.StaffPositionTitles'=>[
						'fields' => [
							'staff_position_title' => 'StaffPositionTitles.name',
						]
					]
                ])
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
	
	public function onExcelTemplateInitialiseStaffDemographics(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');

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
	
	public function onExcelTemplateInitialiseStaffContacts(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $UserContacts = TableRegistry::get('user_contacts');

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
	
	public function onExcelTemplateInitialiseStaffNationalities(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $UserNationalities = TableRegistry::get('user_nationalities');

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
	
	public function onExcelTemplateInitialiseStaffSalaries(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $StaffSalaries = TableRegistry::get('staff_salaries');

            $entity = $StaffSalaries
                ->find()
                ->select([
					'gross_salary' => $StaffSalaries->aliasField('gross_salary'),
                ])
                ->where([
                    $StaffSalaries->aliasField('staff_id') => $params['staff_id'],
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffAreas(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $SecurityUsers = TableRegistry::get('security_users');

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
	
	public function onExcelTemplateInitialiseStaffClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $InstitutionClasses = TableRegistry::get('institution_classes');
            $InstitutionClassesSecondaryStaff = TableRegistry::get('institution_classes_secondary_staff');
			
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
					[$InstitutionClasses->alias() => $InstitutionClasses->table()],
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
	
	public function onExcelTemplateInitialiseStaffSubjects(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $InstitutionSubjectStaff = TableRegistry::get('institution_subject_staff');
			
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
	
	public function onExcelTemplateInitialiseStaffQualifications(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $StaffQualifications = TableRegistry::get('staff_qualifications');

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
	
	public function onExcelTemplateInitialiseStaffAwards(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('staff_id', $params)) {
            $UserAwards = TableRegistry::get('user_awards');

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
	
}
