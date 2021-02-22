<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;

class InstitutionReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';
    //private $fileType = 'pdf';

    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);

        $this->addBehavior('CustomExcel.InstitutionExcelReport', [
            'templateTable' => 'ProfileTemplate.ProfileTemplates',
            'templateTableKey' => 'report_card_id',
            'format' => $this->fileType,
            'download' => false,
            'wrapText' => true,
            'lockSheets' => true,
            'variables' => [
                'Profiles',
                'InstitutionReportCards',
				'Institutions',
				'InstitutionShifts',
				'Principal',
                'DeputyPrincipal',
                'InstitutionMaleStudents',
                'InstitutionFemaleStudents',
                'InstitutionTotalStudents',
                'SpecialNeedMaleStudents',
                'SpecialNeedFemaleStudents',
                'SpecialNeedTotalStudents',
                'InstitutionContactPersons',
                'InstitutionBudgets',
                'InstitutionExpenditures',
                'InstitutionLands',
                'InfrastructureuUtilityInternets',
                'InfrastructureWashSanitationStudents',
                'InfrastructureWashSanitationStaffs',
                'StudentToiletRatio',
                'RoomTypes',
                'RoomTypeCount',
                'StaffPositions',
                'EducationGrades',
                'EducationGradeStudents',
                'EducationGradeClasses',
                'InstitutionSubjects',
                'QualificationTitles',
                'StaffQualificationSubjects',
                'StudentTeacherRatio',
                'TotalStaffs',
                'StaffQualificationDuties',
                'StaffQualificationPositions',
                'StaffQualificationStaffType',
                'InstitutionCommittees',
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
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionContactPersons'] = 'onExcelTemplateInitialiseInstitutionContactPersons';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionShifts'] = 'onExcelTemplateInitialiseInstitutionShifts';
        $events['ExcelTemplates.Model.onExcelTemplateInitialisePrincipal'] = 'onExcelTemplateInitialisePrincipal';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseDeputyPrincipal'] = 'onExcelTemplateInitialiseDeputyPrincipal';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionMaleStudents'] = 'onExcelTemplateInitialiseInstitutionMaleStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionFemaleStudents'] = 'onExcelTemplateInitialiseInstitutionFemaleStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionTotalStudents'] = 'onExcelTemplateInitialiseInstitutionTotalStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseSpecialNeedMaleStudents'] = 'onExcelTemplateInitialiseSpecialNeedMaleStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseSpecialNeedFemaleStudents'] = 'onExcelTemplateInitialiseSpecialNeedFemaleStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseSpecialNeedTotalStudents'] = 'onExcelTemplateInitialiseSpecialNeedTotalStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionBudgets'] = 'onExcelTemplateInitialiseInstitutionBudgets';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionExpenditures'] = 'onExcelTemplateInitialiseInstitutionExpenditures';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionLands'] = 'onExcelTemplateInitialiseInstitutionLands';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureuUtilityInternets'] = 'onExcelTemplateInitialiseInfrastructureuUtilityInternets';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureWashSanitationStudents'] = 'onExcelTemplateInitialiseInfrastructureWashSanitationStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureWashSanitationStaffs'] = 'onExcelTemplateInitialiseInfrastructureWashSanitationStaffs';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentToiletRatio'] = 'onExcelTemplateInitialiseStudentToiletRatio';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseRoomTypes'] = 'onExcelTemplateInitialiseRoomTypes';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseRoomTypeCount'] = 'onExcelTemplateInitialiseRoomTypeCount';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffPositions'] = 'onExcelTemplateInitialiseStaffPositions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseEducationGrades'] = 'onExcelTemplateInitialiseEducationGrades';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseEducationGradeStudents'] = 'onExcelTemplateInitialiseEducationGradeStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseEducationGradeClasses'] = 'onExcelTemplateInitialiseEducationGradeClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionSubjects'] = 'onExcelTemplateInitialiseInstitutionSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseQualificationTitles'] = 'onExcelTemplateInitialiseQualificationTitles';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualificationSubjects'] = 'onExcelTemplateInitialiseStaffQualificationSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentTeacherRatio'] = 'onExcelTemplateInitialiseStudentTeacherRatio';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseTotalStaffs'] = 'onExcelTemplateInitialiseTotalStaffs';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualificationDuties'] = 'onExcelTemplateInitialiseStaffQualificationDuties';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualificationPositions'] = 'onExcelTemplateInitialiseStaffQualificationPositions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualificationStaffType'] = 'onExcelTemplateInitialiseStaffQualificationStaffType';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionCommittees'] = 'onExcelTemplateInitialiseInstitutionCommittees';
		return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $InstitutionReportCards = TableRegistry::get('Institution.InstitutionReportCards');
        if (!$InstitutionReportCards->exists($params)) {
            // insert institution report card record if it does not exist
            $params['status'] = $InstitutionReportCards::IN_PROGRESS;
            $params['started_on'] = date('Y-m-d H:i:s');
            $newEntity = $InstitutionReportCards->newEntity($params);
            $InstitutionReportCards->save($newEntity);
        } else {
            // update status to in progress if record exists
            $InstitutionReportCards->updateAll([
                'status' => $InstitutionReportCards::IN_PROGRESS,
                'started_on' => date('Y-m-d H:i:s')
            ], $params);
        }
    }

    public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $InstitutionsReportCards = TableRegistry::get('Institution.InstitutionReportCards');
		$institutionReportCardData = $InstitutionsReportCards
            ->find()
            ->select([
                $InstitutionsReportCards->aliasField('academic_period_id'),
                $InstitutionsReportCards->aliasField('institution_id'),
				$InstitutionsReportCards->aliasField('report_card_id')
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
                'ProfileTemplates' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ]
            ])
            ->where([
                $InstitutionsReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                $InstitutionsReportCards->aliasField('institution_id') => $params['institution_id'],
                $InstitutionsReportCards->aliasField('report_card_id') => $params['report_card_id'],
            ])
            ->first();
			
        // set filename
        $fileName = $institutionReportCardData->academic_period->name . '_' . $institutionReportCardData->profile_template->code. '_' . $institutionReportCardData->institution->name . '.' . $this->fileType;
        $filepath = $extra['file_path'];
        $fileContent = file_get_contents($filepath);
        $status = $InstitutionsReportCards::GENERATED;
		
        // save file
        $InstitutionsReportCards->updateAll([
            'status' => $status,
            'completed_on' => date('Y-m-d H:i:s'),
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);

        // delete institution report card process
        $InstitutionReportCardProcesses = TableRegistry::Get('ReportCard.InstitutionReportCardProcesses');
        $InstitutionReportCardProcesses->deleteAll([
            'report_card_id' => $params['report_card_id'],
            'institution_id' => $params['institution_id']
        ]);
    }

    public function afterRenderExcelTemplate(Event $event, ArrayObject $extra, $controller)
    {
        $params = $extra['params'];
        $url = [
            'plugin' => 'ProfileTemplate',
            'controller' => 'ProfileTemplates',
            'action' => 'InstitutionProfiles',
            'index',
            'report_card_id' => $params['report_card_id'],
            'academic_period_id' => $params['academic_period_id']
        ];

        $event->stopPropagation();
        return $controller->redirect($url);
    }
    
	public function onExcelTemplateInitialiseProfiles(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_id', $params)) {
            $ProfileTemplates = TableRegistry::get('ProfileTemplate.ProfileTemplates');
            $entity = $ProfileTemplates->get($params['report_card_id'], ['contain' => ['AcademicPeriods']]);

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
	
	public function onExcelTemplateInitialiseInstitutionLands(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
            $entity = $InstitutionLands
                ->find()
				->select([
                    $InstitutionLands->aliasField('area')
                ])
                ->where([
                    $InstitutionLands->aliasField('institution_id') => $params['institution_id'],
                    $InstitutionLands->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInfrastructureuUtilityInternets(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InfrastructureUtilityInternets = TableRegistry::get('Institution.InfrastructureUtilityInternets');
            $entity = $InfrastructureUtilityInternets
                ->find()
				->select([
                    $InfrastructureUtilityInternets->UtilityInternetConditions->aliasField('name')
                ])
				->contain('UtilityInternetConditions')
                ->where([
                    $InfrastructureUtilityInternets->aliasField('institution_id') => $params['institution_id'],
                    $InfrastructureUtilityInternets->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInfrastructureWashSanitationStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InfrastructureWashSanitations = TableRegistry::get('Institution.InfrastructureWashSanitations');
            $infrastructure_wash_sanitation_use_id = 2; // student
			$entity = $InfrastructureWashSanitations
                ->find()
				->select([
                    'quantity' => 'SUM(InfrastructureWashSanitationQuantities.value)'
                ])
				->innerJoin(
				['InfrastructureWashSanitationQuantities' => 'infrastructure_wash_sanitation_quantities'],
				[
					'InfrastructureWashSanitationQuantities.infrastructure_wash_sanitation_id = '. $InfrastructureWashSanitations->aliasField('id')
				]
				)
                ->where([
                    $InfrastructureWashSanitations->aliasField('institution_id') => $params['institution_id'],
                    $InfrastructureWashSanitations->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InfrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_use_id') => $infrastructure_wash_sanitation_use_id,
                ])
				->group($InfrastructureWashSanitations->aliasField('institution_id'))
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInfrastructureWashSanitationStaffs(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InfrastructureWashSanitations = TableRegistry::get('Institution.InfrastructureWashSanitations');
            $infrastructure_wash_sanitation_use_id = 1; // staff
			$entity = $InfrastructureWashSanitations
                ->find()
				->select([
                    'quantity' => 'SUM(InfrastructureWashSanitationQuantities.value)'
                ])
				->innerJoin(
				['InfrastructureWashSanitationQuantities' => 'infrastructure_wash_sanitation_quantities'],
				[
					'InfrastructureWashSanitationQuantities.infrastructure_wash_sanitation_id = '. $InfrastructureWashSanitations->aliasField('id')
				]
				)
                ->where([
                    $InfrastructureWashSanitations->aliasField('institution_id') => $params['institution_id'],
                    $InfrastructureWashSanitations->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InfrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_use_id') => $infrastructure_wash_sanitation_use_id,
                ])
				->group($InfrastructureWashSanitations->aliasField('institution_id'))
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentToiletRatio(Event $event, array $params, ArrayObject $extra)
    {
		if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
			$totalStudent = $InstitutionStudents
				->find()
				->contain('Users')
				->matching('StudentStatuses', function ($q) {
					return $q->where(['StudentStatuses.code' => 'CURRENT']);
				})
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
        }
		
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InfrastructureWashSanitations = TableRegistry::get('Institution.InfrastructureWashSanitations');
            $infrastructure_wash_sanitation_use_id = 2; // student
			$totalStudentToilet = $InfrastructureWashSanitations
                ->find()
				->select([
                    'quantity' => 'SUM(InfrastructureWashSanitationQuantities.value)'
                ])
				->innerJoin(
				['InfrastructureWashSanitationQuantities' => 'infrastructure_wash_sanitation_quantities'],
				[
					'InfrastructureWashSanitationQuantities.infrastructure_wash_sanitation_id = '. $InfrastructureWashSanitations->aliasField('id')
				]
				)
                ->where([
                    $InfrastructureWashSanitations->aliasField('institution_id') => $params['institution_id'],
                    $InfrastructureWashSanitations->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InfrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_use_id') => $infrastructure_wash_sanitation_use_id,
                ])
				->group($InfrastructureWashSanitations->aliasField('institution_id'))
                ->first();
				
			$totalStudentToilet = !empty($totalStudentToilet->quantity) ? $totalStudentToilet->quantity : 0;
			if(!empty($totalStudent) && !empty($totalStudentToilet)) {
				$entity = $totalStudent/$totalStudentToilet;
				$entity = number_format((float)$entity, 2, '.', '');
			} else{
				$entity = '0 ';
			}
			return $entity;	
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionContactPersons(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionContactPersons = TableRegistry::get('Institution.InstitutionContactPersons');

            $entity = $InstitutionContactPersons
                ->find()
				->select([
                    $InstitutionContactPersons->aliasField('telephone'),
                    $InstitutionContactPersons->aliasField('email')
                ])
                ->where([
                    $InstitutionContactPersons->aliasField('institution_id') => $params['institution_id'],
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionShifts(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('institution_id', $params)) {
            $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');

            $entity = $InstitutionShifts
                ->find()
                ->where([
                    $InstitutionShifts->aliasField('institution_id') => $params['institution_id'],
                    $InstitutionShifts->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->count();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialisePrincipal(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $principalRoleId = $SecurityRoles->getPrincipalRoleId();

            $entity = $Staff
                ->find()
                ->select([
                    $Staff->aliasField('id'),
                    $Staff->aliasField('FTE'),
                    $Staff->aliasField('start_date'),
                    $Staff->aliasField('start_year'),
                    $Staff->aliasField('end_date'),
                    $Staff->aliasField('end_year'),
                    $Staff->aliasField('staff_id'),
                    $Staff->aliasField('security_group_user_id')
                ])
                ->innerJoinWith('SecurityGroupUsers')
                ->contain([
                    'Users' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ]
                ])
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    'SecurityGroupUsers.security_role_id' => $principalRoleId
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseDeputyPrincipal(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $deputyPrincipalRoleId = $SecurityRoles->getDeputyPrincipalRoleId();

            $entity = $Staff
                ->find()
                ->select([
                    $Staff->aliasField('id'),
                    $Staff->aliasField('FTE'),
                    $Staff->aliasField('start_date'),
                    $Staff->aliasField('start_year'),
                    $Staff->aliasField('end_date'),
                    $Staff->aliasField('end_year'),
                    $Staff->aliasField('staff_id'),
                    $Staff->aliasField('security_group_user_id')
                ])
                ->innerJoinWith('SecurityGroupUsers')
                ->contain([
                    'Users' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ]
                ])
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    'SecurityGroupUsers.security_role_id' => $deputyPrincipalRoleId
                ])
                ->first();

            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionMaleStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $gender_id = 1; // male
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->matching('StudentStatuses', function ($q) {
					return $q->where(['StudentStatuses.code' => 'CURRENT']);
				})
				->where([$InstitutionStudents->Users->aliasField('gender_id') => $gender_id])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionFemaleStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $gender_id = 2; // females
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->matching('StudentStatuses', function ($q) {
					return $q->where(['StudentStatuses.code' => 'CURRENT']);
				})
				->where([$InstitutionStudents->Users->aliasField('gender_id') => $gender_id])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionTotalStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->matching('StudentStatuses', function ($q) {
					return $q->where(['StudentStatuses.code' => 'CURRENT']);
				})
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentTeacherRatio(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $InstitutionStaffs = TableRegistry::get('Institution.Staff');
			$totalStudents = $InstitutionStudents
				->find()
				->contain('Users')
				->matching('StudentStatuses', function ($q) {
					return $q->where(['StudentStatuses.code' => 'CURRENT']);
				})
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
			
			$totalStaffs = $InstitutionStaffs
				->find()
				->contain('Users')
				->where([$InstitutionStaffs->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
			if(!empty($totalStudents) && !empty($totalStaffs)) {
				$entity = $totalStudents/$totalStaffs;
				$entity = number_format((float)$entity, 2, '.', '');
			} else{
				$entity = 0;
			}
			return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseTotalStaffs(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStaffs = TableRegistry::get('Institution.Staff');
			$entity = $InstitutionStaffs
				->find()
				->contain('Users')
				->where([$InstitutionStaffs->aliasField('institution_id') => $params['institution_id']])
				->group($InstitutionStaffs->aliasField('staff_id'))
				->count()
			;
			return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseSpecialNeedMaleStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $SpecialNeedsServices = TableRegistry::get('SpecialNeeds.SpecialNeedsServices');

			$gender_id = 1; // male
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecialNeed' => 'user_special_needs_assessments'],
				[
					'SpecialNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->innerJoin(
                    [$SpecialNeedsServices->alias() => $SpecialNeedsServices->table()],
                    [
                        $SpecialNeedsServices->aliasField('security_user_id = ') . $InstitutionStudents->aliasField('student_id')
                    ]
                )
				->group([
					'Users.id'
				])
				->where([$InstitutionStudents->Users->aliasField('gender_id') => $gender_id])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
				->count()
			;
			if(!empty($entity)) {
				$entity = $entity;
			} else {
				$entity = '0 ';
			}
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseSpecialNeedFemaleStudents(Event $event, array $params, ArrayObject $extra)
    {
		if (array_key_exists('institution_id', $params) && array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $SpecialNeedsServices = TableRegistry::get('SpecialNeeds.SpecialNeedsServices');

			$gender_id = 2; // female
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecialNeed' => 'user_special_needs_assessments'],
				[
					'SpecialNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->innerJoin(
                    [$SpecialNeedsServices->alias() => $SpecialNeedsServices->table()],
                    [
                        $SpecialNeedsServices->aliasField('security_user_id = ') . $InstitutionStudents->aliasField('student_id')
                    ]
                )
				->group([
					'Users.id'
				])
				->where([$InstitutionStudents->Users->aliasField('gender_id') => $gender_id])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
				->count()
			;
			if(!empty($entity)) {
				$entity = $entity;
			} else {
				$entity = '0 ';
			}
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseSpecialNeedTotalStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $SpecialNeedsServices = TableRegistry::get('SpecialNeeds.SpecialNeedsServices');

			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecialNeed' => 'user_special_needs_assessments'],
				[
					'SpecialNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->innerJoin(
                    [$SpecialNeedsServices->alias() => $SpecialNeedsServices->table()],
                    [
                        $SpecialNeedsServices->aliasField('security_user_id = ') . $InstitutionStudents->aliasField('student_id')
                    ]
                )
				->group([
					'Users.id'
				])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
				->count()
			;
			if(!empty($entity)) {
				$entity = $entity;
			} else {
				$entity = '0 ';
			}
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionBudgets(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionBudgets = TableRegistry::get('Institution.InstitutionBudgets');
			$entity = $InstitutionBudgets
				->find()
				->select([
					'amount' => 'SUM(amount)'
				])
				->contain('AcademicPeriods')
				->innerJoin(
				['Institution' => 'institutions'],
				[
					'Institution.id = '. $InstitutionBudgets->aliasField('institution_id')
				]
				)
				->where([$InstitutionBudgets->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionBudgets->aliasField('academic_period_id') => $params['academic_period_id']])
				->first()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionExpenditures(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionExpenditures = TableRegistry::get('Institution.InstitutionExpenditures');
			$entity = $InstitutionExpenditures
				->find()
				->select([
					'amount' => 'SUM(amount)'
				])
				->contain('AcademicPeriods')
				->innerJoin(
				['Institution' => 'institutions'],
				[
					'Institution.id = '. $InstitutionExpenditures->aliasField('institution_id')
				]
				)
				->where([$InstitutionExpenditures->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionExpenditures->aliasField('academic_period_id') => $params['academic_period_id']])
				->first()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseRoomTypes(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $RoomTypes = TableRegistry::get('room_types');
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');

            $entity = $RoomTypes->find()
				->select([
					$RoomTypes->aliasField('id'),
					$RoomTypes->aliasField('name')
				])
				->toArray()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseEducationGrades(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionGrades = TableRegistry::get('institution_grades');

            $entity = $InstitutionGrades->find()
				->select([
					'id' => 'EducationGrades.id',
					'name' => 'EducationGrades.name',
				])
				->innerJoin(
				['EducationGrades' => 'education_grades'],
				[
					'EducationGrades.id = '. $InstitutionGrades->aliasField('education_grade_id')
				]
				)
				->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])	
				->hydrate(false)
				->toArray()
			;
			
			$totalArray = [];
			$totalArray = [
				'id' => count($entity) + 1,
				'name' => 'Total',
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseEducationGradeStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStudents = TableRegistry::get('institution_students');
            $InstitutionGrades = TableRegistry::get('institution_grades');

            $EducationGradesData = $InstitutionGrades->find()
				->select([
					'id' => 'EducationGrades.id'
				])
				->innerJoin(
				['EducationGrades' => 'education_grades'],
				[
					'EducationGrades.id = '. $InstitutionGrades->aliasField('education_grade_id')
				]
				)
				->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])	
				->hydrate(false)
				->toArray()
			;
			$total_count = 0;
			foreach ($EducationGradesData as $value) {
				$InstitutionStudentsData = $InstitutionStudents->find()
					->select([
						'count' => 'count(id)'
					])
					->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
					->where([$InstitutionStudents->aliasField('student_status_id') => 1])
					->hydrate(false)
					->toArray()
				;
				
				$result = [];
				$total_student_count = 0;
				foreach ($InstitutionStudentsData as $data) {
					$total_student_count = $data['count'];
					$result = [
						'education_grade_id' => $value['id'],
						'count' => $data['count'],
					];
				}
				$total_count = $total_count + $total_student_count;
				$entity[] = $result;
			}
			$totalArray = [];
			$totalArray = [
				'education_grade_id' => (!empty($value['id']) ? $value['id'] : 0) + 1,
				'count' => $total_count,
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseEducationGradeClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionClasses = TableRegistry::get('institution_classes');
            $InstitutionGrades = TableRegistry::get('institution_grades');

            $EducationGradesData = $InstitutionGrades->find()
				->select([
					'id' => 'EducationGrades.id'
				])
				->innerJoin(
				['EducationGrades' => 'education_grades'],
				[
					'EducationGrades.id = '. $InstitutionGrades->aliasField('education_grade_id')
				]
				)
				->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])	
				->hydrate(false)
				->toArray()
			;
			$total_count = 0;
			foreach ($EducationGradesData as $value) {
				$InstitutionClassesData = $InstitutionClasses->find()
					->select([
						'count' => 'count(institutionClassGrades.id)'
					])
					->innerJoin(
					['institutionClassGrades' => 'institution_class_grades'],
					[
						'institutionClassGrades.institution_class_id = '. $InstitutionClasses->aliasField('id')
					]
					)
					->where(['institutionClassGrades.education_grade_id' => $value['id']])
					->where([$InstitutionClasses->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionClasses->aliasField('academic_period_id') => $params['academic_period_id']])
					->hydrate(false)
					->toArray()
				;
				
				$result = [];
				$total_student_count = 0;
				foreach ($InstitutionClassesData as $data) {
					$total_student_count = $data['count'];
					$result = [
						'education_grade_id' => $value['id'],
						'count' => $data['count'],
					];
				}
				$total_count = $total_count + $total_student_count;
				$entity[] = $result;
			}
			$totalArray = [];
			$totalArray = [
				'education_grade_id' => (!empty($value['id']) ? $value['id'] : 0) + 1,
				'count' => $total_count,
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionSubjects(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');

            $InstitutionSubjectsData = $InstitutionSubjects->find()
				->select([
					$InstitutionSubjects->aliasField('id'),
					$InstitutionSubjects->aliasField('name'),
					'education_grade_name'=> 'EducationGrades.name',
					'education_grade_id'=> 'EducationGrades.id',
				])
				->innerJoin(
				['EducationGrades' => 'education_grades'],
				[
					'EducationGrades.id = '. $InstitutionSubjects->aliasField('education_grade_id')
				]
				)
				->group([
					'EducationGrades.id',
					$InstitutionSubjects->aliasField('name')
				])
				->order($InstitutionSubjects->aliasField('id'))
				->where([$InstitutionSubjects->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
				->hydrate(false)
				->toArray()
			;
			
			$result = [];
			$total_students = 0;
			foreach ($InstitutionSubjectsData as $data) {
				
				$InstitutionSubjectStudent = $InstitutionSubjects
                ->find()
				->select([
					$InstitutionSubjects->aliasField('id'),
					$InstitutionSubjects->aliasField('name'),
					'education_grade_name'=> 'EducationGrades.name',
					'education_grade_id'=> 'EducationGrades.id',
                    'total_male_students' => 'SUM(total_male_students)',
                    'total_female_students' => 'SUM(total_female_students)',
                ])
				->innerJoin(
					['EducationGrades' => 'education_grades'],
					[
						'EducationGrades.id = '. $InstitutionSubjects->aliasField('education_grade_id')
					]
				)
                ->where([
                    $InstitutionSubjects->aliasField('name') => $data['name'],
                    $InstitutionSubjects->aliasField('education_grade_id') => $data['education_grade_id'],
                    $InstitutionSubjects->aliasField('institution_id') => $params['institution_id'],
                    $InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->first();
				
				$students = $InstitutionSubjectStudent->total_male_students + $InstitutionSubjectStudent->total_female_students;
				$total_students = $total_students + $students;

				$result = [
					'id' => $InstitutionSubjectStudent->id,
					'name' => $InstitutionSubjectStudent->name,
					'education_grade_name' => $InstitutionSubjectStudent->education_grade_name,
					'total_students' => $students,
				];
				$entity[] = $result;
			}
			$totalArray = [];
			$totalArray = [
				'id' => (!empty($data['id']) ? $data['id'] : 0)  + 1,
				'name' => 'Total',
				'education_grade_name' => '',
				'total_students' => $total_students,
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseQualificationTitles(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $QualificationTitles = TableRegistry::get('qualification_titles');

            $entity = $QualificationTitles->find()
				->select([
					$QualificationTitles->aliasField('id'),
					$QualificationTitles->aliasField('name'),
				])
				->hydrate(false)
				->toArray()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffQualificationSubjects(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $QualificationTitles = TableRegistry::get('qualification_titles');
            $InstitutionSubjects = TableRegistry::get('institution_subjects');
            $StaffQualifications = TableRegistry::get('staff_qualifications');

            $QualificationTitlesData = $QualificationTitles->find()
				->select([
					$QualificationTitles->aliasField('id'),
					$QualificationTitles->aliasField('name'),
				])
				->hydrate(false)
				->toArray()
			;
			
			$init = 1;
			$totalStaff = 0;
			foreach ($QualificationTitlesData as $value) {
				
				$NumberOfStaff = $InstitutionSubjects->find()
					->select([
						'number_of_staff' => 'count(InstitutionSubjectStaff.staff_id)'
					])
					->innerJoin(
					['InstitutionSubjectStaff' => 'institution_subject_staff'],
					[
						'InstitutionSubjectStaff.institution_subject_id = '. $InstitutionSubjects->aliasField('id')
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionSubjectStaff.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where([$InstitutionSubjects->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
					->hydrate(false)
					->first()
				;
		
				$InstitutionSubjectsData = $InstitutionSubjects->find()
					->select([
						$InstitutionSubjects->aliasField('name')
					])
					->innerJoin(
					['InstitutionSubjectStaff' => 'institution_subject_staff'],
					[
						'InstitutionSubjectStaff.institution_subject_id = '. $InstitutionSubjects->aliasField('id')
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionSubjectStaff.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where([$InstitutionSubjects->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
					->hydrate(false)
					->toArray()
				;
				$result = [];
				if(!empty($InstitutionSubjectsData)) {
					foreach ($InstitutionSubjectsData as $data) {
						
						$totalStaff = $NumberOfStaff['number_of_staff'] + $totalStaff;

						$result = [
							'id' => $init,
							'qualification_title' => $value['name'],
							'name' => $data['name'],
							'number_of_staff' => $NumberOfStaff['number_of_staff'],
						];
						$entity[] = $result;
						$init++;
					}
				} else {
					$result = [
						'id' => $init,
						'qualification_title' => $value['name'],
						'name' => '',
						'number_of_staff' => $NumberOfStaff['number_of_staff'],
					];
					$entity[] = $result;
					$init++;
				}
			}
			$totalArray = [];
			$totalArray = [
				'id' => $init,
				'qualification_title' => 'Total',
				'name' => '',
				'number_of_staff' => $totalStaff,
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffQualificationDuties(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $QualificationTitles = TableRegistry::get('qualification_titles');
            $StaffDuties = TableRegistry::get('staff_duties');
            $StaffQualifications = TableRegistry::get('staff_qualifications');

            $QualificationTitlesData = $QualificationTitles->find()
				->select([
					$QualificationTitles->aliasField('id'),
					$QualificationTitles->aliasField('name'),
				])
				->hydrate(false)
				->toArray()
			;
			
			$init = 1;
			$totalStaff = 0;
			foreach ($QualificationTitlesData as $value) {
				
				$NumberOfStaff = $StaffDuties->find()
					->select([
						'count' => 'count(InstitutionStaffDuties.staff_id)'
					])
					->innerJoin(
					['InstitutionStaffDuties' => 'institution_staff_duties'],
					[
						'InstitutionStaffDuties.staff_duties_id = '. $StaffDuties->aliasField('id')
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionStaffDuties.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where(['InstitutionStaffDuties.institution_id' => $params['institution_id']])
					->where(['InstitutionStaffDuties.academic_period_id' => $params['academic_period_id']])
					->hydrate(false)
					->first()
				;
				
				$StaffDutiesData = $StaffDuties->find()
					->select([
						$StaffDuties->aliasField('name')
					])
					->innerJoin(
					['InstitutionStaffDuties' => 'institution_staff_duties'],
					[
						'InstitutionStaffDuties.staff_duties_id = '. $StaffDuties->aliasField('id')
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionStaffDuties.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where(['InstitutionStaffDuties.institution_id' => $params['institution_id']])
					->where(['InstitutionStaffDuties.academic_period_id' => $params['academic_period_id']])
					->hydrate(false)
					->toArray()
				;
				$result = [];
				if(!empty($StaffDutiesData)) {
					foreach ($StaffDutiesData as $data) {
						
						$totalStaff = $NumberOfStaff['count'] + $totalStaff;

						$result = [
							'id' => $init,
							'qualification_title' => $value['name'],
							'name' => $data['name'],
							'number_of_staff' => $NumberOfStaff['count'],
						];
						$entity[] = $result;
						$init++;
					}
				} else {
					$result = [
						'id' => $init,
						'qualification_title' => $value['name'],
						'name' => '',
						'number_of_staff' => $NumberOfStaff['count'],
					];
					$entity[] = $result;
					$init++;
				}
			}
			$totalArray = [];
			$totalArray = [
				'id' => $init,
				'qualification_title' => 'Total',
				'name' => '',
				'number_of_staff' => $totalStaff,
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffQualificationPositions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $QualificationTitles = TableRegistry::get('qualification_titles');
            $StaffPositionTitles = TableRegistry::get('staff_position_titles');
            $StaffQualifications = TableRegistry::get('staff_qualifications');

            $QualificationTitlesData = $QualificationTitles->find()
				->select([
					$QualificationTitles->aliasField('id'),
					$QualificationTitles->aliasField('name'),
				])
				->hydrate(false)
				->toArray()
			;
			
			$init = 1;
			$totalStaff = 0;
			foreach ($QualificationTitlesData as $value) {
				
				$NumberOfStaff = $StaffPositionTitles->find()
					->select([
						'count' => 'count(InstitutionStaff.staff_id)'
					])
					->innerJoin(
					['InstitutionPositions' => 'institution_positions'],
					[
						'InstitutionPositions.staff_position_title_id = '. $StaffPositionTitles->aliasField('id')
					]
					)
					->innerJoin(
					['InstitutionStaff' => 'institution_staff'],
					[
						'InstitutionStaff.institution_position_id = InstitutionPositions.id'
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionStaff.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where(['InstitutionStaff.institution_id' => $params['institution_id']])
					->hydrate(false)
					->first()
				;
				
				$StaffPositionTitlesData = $StaffPositionTitles->find()
					->select([
						$StaffPositionTitles->aliasField('name')
					])
					->innerJoin(
					['InstitutionPositions' => 'institution_positions'],
					[
						'InstitutionPositions.staff_position_title_id = '. $StaffPositionTitles->aliasField('id')
					]
					)
					->innerJoin(
					['InstitutionStaff' => 'institution_staff'],
					[
						'InstitutionStaff.institution_position_id = InstitutionPositions.id'
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionStaff.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where(['InstitutionStaff.institution_id' => $params['institution_id']])
					->hydrate(false)
					->toArray()
				;
				$result = [];
				if(!empty($StaffPositionTitlesData)) {
					foreach ($StaffPositionTitlesData as $data) {
						
						$totalStaff = $NumberOfStaff['count'] + $totalStaff;

						$result = [
							'id' => $init,
							'qualification_title' => $value['name'],
							'name' => $data['name'],
							'number_of_staff' => $NumberOfStaff['count'],
						];
						$entity[] = $result;
						$init++;
					}
				} else {
					$result = [
						'id' => $init,
						'qualification_title' => $value['name'],
						'name' => '',
						'number_of_staff' => $NumberOfStaff['count'],
					];
					$entity[] = $result;
					$init++;
				}
			}
			$totalArray = [];
			$totalArray = [
				'id' => $init,
				'qualification_title' => 'Total',
				'name' => '',
				'number_of_staff' => $totalStaff,
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffQualificationStaffType(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $QualificationTitles = TableRegistry::get('qualification_titles');
            $StaffTypes = TableRegistry::get('staff_types');
            $StaffQualifications = TableRegistry::get('staff_qualifications');

            $QualificationTitlesData = $QualificationTitles->find()
				->select([
					$QualificationTitles->aliasField('id'),
					$QualificationTitles->aliasField('name'),
				])
				->hydrate(false)
				->toArray()
			;
			
			$init = 1;
			$totalStaff = 0;
			foreach ($QualificationTitlesData as $value) {
				
				$NumberOfStaff = $StaffTypes->find()
					->select([
						'count' => 'count(InstitutionStaff.staff_id)'
					])
					->innerJoin(
					['InstitutionStaff' => 'institution_staff'],
					[
						'InstitutionStaff.staff_type_id = '. $StaffTypes->aliasField('id')
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionStaff.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where(['InstitutionStaff.institution_id' => $params['institution_id']])
					->hydrate(false)
					->first()
				;
				
				$StaffTypesData = $StaffTypes->find()
					->select([
						$StaffTypes->aliasField('name')
					])
					->innerJoin(
					['InstitutionStaff' => 'institution_staff'],
					[
						'InstitutionStaff.staff_type_id = '. $StaffTypes->aliasField('id')
					]
					)
					->innerJoin(
					['StaffQualifications' => 'staff_qualifications'],
					[
						'StaffQualifications.staff_id = InstitutionStaff.staff_id'
					]
					)
					->where(['StaffQualifications.qualification_title_id' => $value['id']])
					->where(['InstitutionStaff.institution_id' => $params['institution_id']])
					->hydrate(false)
					->toArray()
				;
				$result = [];
				if(!empty($StaffTypesData)) {
					foreach ($StaffTypesData as $data) {
						
						$totalStaff = $NumberOfStaff['count'] + $totalStaff;

						$result = [
							'id' => $init,
							'qualification_title' => $value['name'],
							'name' => $data['name'],
							'number_of_staff' => $NumberOfStaff['count'],
						];
						$entity[] = $result;
						$init++;
					}
				} else {
					$result = [
						'id' => $init,
						'qualification_title' => $value['name'],
						'name' => '',
						'number_of_staff' => $NumberOfStaff['count'],
					];
					$entity[] = $result;
					$init++;
				}
			}
			$totalArray = [];
			$totalArray = [
				'id' => $init,
				'qualification_title' => 'Total',
				'name' => '',
				'number_of_staff' => $totalStaff,
			];
			$entity[] = $totalArray;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseRoomTypeCount(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $RoomTypes = TableRegistry::get('room_types');
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');

            $RoomTypesData = $RoomTypes->find()
				->select([
					$RoomTypes->aliasField('id'),
				])
				->toArray()
			;
			
			foreach ($RoomTypesData as $value) {
				$InstitutionRoomsData = $InstitutionRooms->find()
					->select([
						'count' => 'count(id)'
					])
					->where([$InstitutionRooms->aliasField('room_type_id') => $value->id])
					->where([$InstitutionRooms->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionRooms->aliasField('academic_period_id') => $params['academic_period_id']])
					->hydrate(false)
					->toArray()
				;
				
				$result = [];
				foreach ($InstitutionRoomsData as $data) {
					$result = [
						'id' => $value->id,
						'count' => $data['count'],
					];
				}
				$entity[] = $result;
			}
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffPositions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $StaffPositionTitles = TableRegistry::get('staff_position_titles');
			$entity = $StaffPositionTitles
				->find()
				->select([
					'first_name' => 'Users.first_name',
					'last_name' => 'Users.last_name'
				])
				->innerJoin(
				['InstitutionPositions' => 'institution_positions'],
				[
					'InstitutionPositions.staff_position_title_id = '. $StaffPositionTitles->aliasField('id')
				]
				)
				->innerJoin(
				['InstitutionPositions' => 'institution_positions'],
				[
					'InstitutionPositions.staff_position_title_id = '. $StaffPositionTitles->aliasField('id')
				]
				)
				->innerJoin(
				['InstitutionStaff' => 'institution_staff'],
				[
					'InstitutionStaff.institution_position_id = InstitutionPositions.id'
				]
				)
				->innerJoin(
				['Users' => 'security_users'],
				[
					'Users.id = InstitutionStaff.staff_id'
				]
				)
				->where([$StaffPositionTitles->aliasField('security_role_id') => 2])
				->where(['InstitutionStaff.institution_id' => $params['institution_id']])
				->where(['InstitutionPositions.institution_id' => $params['institution_id']])
				->hydrate(false)
				->toArray()
			;
			$result = [];
            foreach ($entity as $key => $value) {
				$result = [
                    'name' => $value['first_name'].' '.$value['last_name'],
                ];
			}
            return $result;
        }
    }
	
	public function onExcelTemplateInitialiseInstitutionCommittees(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionCommittees = TableRegistry::get('institution_committees');
			$entity = $InstitutionCommittees
				->find()
				->where([$InstitutionCommittees->aliasField('academic_period_id') => $params['academic_period_id']])
				->where([$InstitutionCommittees->aliasField('institution_id') => $params['institution_id']])
				->hydrate(false)
				->first()
			;
            return $entity;
        }
    }

}
