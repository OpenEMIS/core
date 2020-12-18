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
		
		return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $InstitutionReportCards = TableRegistry::get('Institution.InstitutionReportCards');
        if (!$InstitutionReportCards->exists($params)) {
            // insert student report card record if it does not exist
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

        // delete report card process
        $ReportCardProcesses = TableRegistry::Get('ReportCard.ReportCardProcesses');
        $ReportCardProcesses->deleteAll([
            'report_card_id' => $params['report_card_id'],
            'institution_id' => $params['institution_id']
        ]);
    }

    public function afterRenderExcelTemplate(Event $event, ArrayObject $extra, $controller)
    {
        $params = $extra['params'];
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'ReportCardStatuses',
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
				$entity = 0;
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
	
	public function onExcelTemplateInitialiseSpecialNeedMaleStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $gender_id = 1; // male
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecailNeed' => 'user_special_needs_assessments'],
				[
					'SpecailNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->where([$InstitutionStudents->Users->aliasField('gender_id') => $gender_id])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseSpecialNeedFemaleStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $gender_id = 2; // female
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecailNeed' => 'user_special_needs_assessments'],
				[
					'SpecailNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->where([$InstitutionStudents->Users->aliasField('gender_id') => $gender_id])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseSpecialNeedTotalStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecailNeed' => 'user_special_needs_assessments'],
				[
					'SpecailNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
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

            $entity = $RoomTypes->find()
				->select([
					$RoomTypes->aliasField('name')
				])
				->toArray()
			;
            return $entity;
        }
    }

}
