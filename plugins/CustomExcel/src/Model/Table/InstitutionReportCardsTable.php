<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;
use DateTime;//POCOR-6328
use Cake\I18n\Time;//POCOR-6328

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
                'TotalStudents',
                'StudentTotalAbsences',
                'StaffTotalAbsences',
                'StaffQualificationDuties',
                'StaffQualificationPositions',
                'StaffQualificationStaffType',
                'InstitutionCommittees',
                'InstitutionClassRooms',
                'TeachingStaffTotalStaffRatio',
                'StudentFromEducationGrade',
                //POCOR-6328 starts
                'InfrastructureLandAccessibile',
                'InfrastructureBuildingsAccessibile',
                'InfrastructureFloorsAccessibile',
                'InfrastructureRoomsAccessibile',
                'InfrastructureLandNotAccessibile',
                'InfrastructureBuildingsNotAccessibile',
                'InfrastructureFloorsNotAccessibile',
                'InfrastructureRoomsNotAccessibile',
                'EducationProgrammes',
                //POCOR-6328 ends
                'InstitutionEducationGrade',
                'InstitutionStudentEnrolled',
                'InstitutionStudentWithdrawn',
                'InstitutionStudentTransferred',
                'InstitutionStaffCount'
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
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseTotalStudents'] = 'onExcelTemplateInitialiseTotalStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentTotalAbsences'] = 'onExcelTemplateInitialiseStudentTotalAbsences';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffTotalAbsences'] = 'onExcelTemplateInitialiseStaffTotalAbsences';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualificationDuties'] = 'onExcelTemplateInitialiseStaffQualificationDuties';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualificationPositions'] = 'onExcelTemplateInitialiseStaffQualificationPositions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffQualificationStaffType'] = 'onExcelTemplateInitialiseStaffQualificationStaffType';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionCommittees'] = 'onExcelTemplateInitialiseInstitutionCommittees';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionClassRooms'] = 'onExcelTemplateInitialiseInstitutionClassRooms';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseTeachingStaffTotalStaffRatio'] = 'onExcelTemplateInitialiseTeachingStaffTotalStaffRatio';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentFromEducationGrade'] = 'onExcelTemplateInitialiseStudentFromEducationGrade';
        //POCOR-6328 starts
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureLandAccessibile'] = 'onExcelTemplateInitialiseInfrastructureLandAccessibile';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureBuildingsAccessibile'] = 'onExcelTemplateInitialiseInfrastructureBuildingsAccessibile';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureFloorsAccessibile'] = 'onExcelTemplateInitialiseInfrastructureFloorsAccessibile';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureRoomsAccessibile'] = 'onExcelTemplateInitialiseInfrastructureRoomsAccessibile';

        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureLandNotAccessibile'] = 'onExcelTemplateInitialiseInfrastructureLandNotAccessibile';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureBuildingsNotAccessibile'] = 'onExcelTemplateInitialiseInfrastructureBuildingsNotAccessibile';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureFloorsNotAccessibile'] = 'onExcelTemplateInitialiseInfrastructureFloorsNotAccessibile';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureRoomsNotAccessibile'] = 'onExcelTemplateInitialiseInfrastructureRoomsNotAccessibile';

        $events['ExcelTemplates.Model.onExcelTemplateInitialiseEducationProgrammes'] = 'onExcelTemplateInitialiseEducationProgrammes';
        //POCOR-6328 ends
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionEducationGrade'] = 'onExcelTemplateInitialiseInstitutionEducationGrade';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentEnrolled'] = 'onExcelTemplateInitialiseInstitutionStudentEnrolled';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentWithdrawn'] = 'onExcelTemplateInitialiseInstitutionStudentWithdrawn';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentTransferred'] = 'onExcelTemplateInitialiseInstitutionStudentTransferred';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStaffCount'] = 'onExcelTemplateInitialiseInstitutionStaffCount';
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
            $entity = $Institutions->get($params['institution_id'], ['contain' => ['AreaAdministratives', 'Types', 'Genders', 'Sectors', 'Providers','Ownerships','Areas','InstitutionLands']]); //POCOR-6328 
            
			$shift_types = [1=>'Single Shift Owner',
							2=>'Single Shift Occupier',
							3=>'Multiple Shift Owner',
							4=>'Multiple Shift Occupier'
							];
			if($shift_types[$entity->shift_type]) {
				$entity->shift_type_name = $shift_types[$entity->shift_type];
			}

            $entity->date_opened = $entity->date_opened->format('Y-m-d');//POCOR-6328 
            return $entity;
		}
    }
    //POCOR-6328 starts
    public function onExcelTemplateInitialiseInfrastructureLandAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
            $entity = $InstitutionLands
               ->find()
               ->where([
                   $InstitutionLands->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionLands->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionLands->aliasField('accessibility') => 1
               ])
               ->count();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInfrastructureBuildingsAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
            $entity = $InstitutionBuildings
               ->find()
               ->where([
                   $InstitutionBuildings->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionBuildings->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionBuildings->aliasField('accessibility') => 1
               ])
               ->count();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInfrastructureFloorsAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
            $entity = $InstitutionFloors
               ->find()
               ->where([
                   $InstitutionFloors->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionFloors->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionFloors->aliasField('accessibility') => 1
               ])
               ->count();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInfrastructureRoomsAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
           $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
           $entity = $InstitutionRooms
               ->find()
               ->where([
                   $InstitutionRooms->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionRooms->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionRooms->aliasField('accessibility') => 1
               ])
               ->count();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInfrastructureLandNotAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
            $entity = $InstitutionLands
               ->find()
               ->where([
                   $InstitutionLands->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionLands->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionLands->aliasField('accessibility') => 0
               ])
               ->count();
            if($entity == ''){
               $entity = '0 ';
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInfrastructureBuildingsNotAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
            $entity = $InstitutionBuildings
               ->find()
               ->where([
                   $InstitutionBuildings->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionBuildings->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionBuildings->aliasField('accessibility') => 0
               ])
               ->count();
            if($entity == ''){
               $entity = '0 ';
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInfrastructureFloorsNotAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
            $entity = $InstitutionFloors
               ->find()
               ->where([
                   $InstitutionFloors->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionFloors->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionFloors->aliasField('accessibility') => 0
               ])
               ->count();
            if($entity == ''){
               $entity = '0 ';
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInfrastructureRoomsNotAccessibile(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
            $entity = $InstitutionRooms
               ->find()
               ->where([
                   $InstitutionRooms->aliasField('institution_id') => $params['institution_id'],
                   $InstitutionRooms->aliasField('academic_period_id') => $params['academic_period_id'],
                   $InstitutionRooms->aliasField('accessibility') => 0
               ])
               ->count();
            if($entity == ''){
               $entity = '0 ';
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseEducationProgrammes(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionGrades = TableRegistry::get('institution_grades');
            $EducationProgrammes = TableRegistry::get('education_programmes');

            $entity = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationProgrammes.name',
                ])
                ->innerJoin(
                ['EducationGrades' => 'education_grades'],
                [
                    'EducationGrades.id = '. $InstitutionGrades->aliasField('education_grade_id')
                ]
                )
                ->innerJoin(
                ['EducationProgrammes' => 'education_programmes'],
                [
                    'EducationProgrammes.id = '. 'EducationGrades.education_programme_id'
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
    //POCOR-6328 ends
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
            if($entity == ''){
                $entity = '0 ';
            }
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
            if($entity == ''){
                $entity = '0 ';
            }
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
	
	public function onExcelTemplateInitialiseTotalStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
			$entity = $InstitutionStudents
				->find()
				->contain('Users')
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
				->where([$InstitutionStudents->aliasField('student_status_id') => 1])
				->where(['Users.status' => 1])
				->group($InstitutionStudents->aliasField('student_id'))
				->count()
			;
			return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentTotalAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStudentAbsences = TableRegistry::get('institution_student_absences');
			
            $entity = $InstitutionStudentAbsences
				->find()
				->where([
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
				->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id IN') => [1,2,3],
                ])
				->count();
			
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStaffTotalAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
            $InstitutionStaffAttendances = TableRegistry::get('institution_staff_attendances');

            $totalStaff = $InstitutionStaff
				->find()
				->where([
                    $InstitutionStaff->aliasField('institution_id') => $params['institution_id'],
                ])
				->count();
				
            $staffPresent = $InstitutionStaffAttendances
				->find()
				->where([
                    $InstitutionStaffAttendances->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStaffAttendances->aliasField('institution_id') => $params['institution_id'],
                ])
				->count();
			$entity = $totalStaff - $staffPresent;
			
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
				'name' => '',
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
	
	public function onExcelTemplateInitialiseInstitutionClassRooms(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
			$entity = $InstitutionRooms
				->find()
				->contain('RoomTypes')
				->where([$InstitutionRooms->aliasField('academic_period_id') => $params['academic_period_id']])
				->where([$InstitutionRooms->aliasField('institution_id') => $params['institution_id']])
				->where('RoomTypes.classification = 1')
				->count()
			;
			
            return $entity;
        }
    }	
	
	public function onExcelTemplateInitialiseTeachingStaffTotalStaffRatio(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
			$teachingStaff = $InstitutionStaff
				->find()
				->contain('Positions.StaffPositionTitles')
				->where([$InstitutionStaff->aliasField('institution_id') => $params['institution_id']])
				->where('StaffPositionTitles.type = 1')
				->count()
			;
			
			$totalStaffs = $InstitutionStaff
				->find()
				->contain('Users')
				->where([$InstitutionStaff->aliasField('institution_id') => $params['institution_id']])
				->count()
			;
			
			if(!empty($teachingStaff) && !empty($totalStaffs)) {
				$entity = $teachingStaff/$totalStaffs;
				$entity = number_format((float)$entity, 2, '.', '');
			} else{
				$entity = 0;
			}
			
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentFromEducationGrade(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
            $SpecialNeedsServices = TableRegistry::get('SpecialNeeds.SpecialNeedsServices');

            $EducationGradesData = $InstitutionGrades->find()
				->select([
					'id' => 'EducationGrades.id',
					'name' => 'EducationGrades.name'
				])
				->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
				->where([
					'EducationSystems.academic_period_id' => $params['academic_period_id']
				])
				->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])	
				->hydrate(false)
				->toArray()
			;
            //POCOR-6330 starts
            $enrolledStudentsData = 0;
            if(empty($EducationGradesData)){
                $entity[] = [
                    'education_grade_name' =>  '',
                    'education_grade_id' =>  0,
                    'male_student_enrolment' => 0,
                    'female_student_enrolment' => 0,
                    'total_student_enrolment' => 0,
                    'male_student_repetition' => 0,
                    'female_student_repetition' => 0,
                    'total_student_repetition' => 0,
                    'male_student_dropout' => 0,
                    'female_student_dropout' => 0,
                    'total_student_dropout' => 0,
                    'total_student' => 0,
                    'female_subject_staff' => 0,
                    'subject_staff' => 0,
                    'secondary_teacher' => 0,
                    'male_student_special_need' => 0,
                    'female_student_special_need' => 0,
                    'total_student_special_need' => 0,
                    'syrian_students' => 0
                ];

                return $entity;
            }//POCOR-6330 ends
			$enrolledStudentsData = 0;
            //POCOR-6328 start
            if(empty($EducationGradesData)){
                $entity = [];
                return $entity;
            }//POCOR-6328 ends
			foreach ($EducationGradesData as $value) {
				$enrolledMaleStudentsData = $InstitutionStudents->find()
					->contain('Users')
					->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
					->where([$InstitutionStudents->aliasField('student_status_id') => 1])
					->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
					->hydrate(false)
					->count()
				;
				$enrolledFemaleStudentsData = $InstitutionStudents->find()
					->contain('Users')
					->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
					->where([$InstitutionStudents->aliasField('student_status_id') => 1])
					->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
					->hydrate(false)
					->count()
				;
				$enrolledStudentsData = $enrolledMaleStudentsData + $enrolledFemaleStudentsData;

				$dropoutMaleStudentsData = $InstitutionStudents->find()
					->contain('Users')
					->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
					->where([$InstitutionStudents->aliasField('student_status_id') => 4])
					->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
					->hydrate(false)
					->count()
				;
				$dropoutFemaleStudentsData = $InstitutionStudents->find()
					->contain('Users')
					->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
					->where([$InstitutionStudents->aliasField('student_status_id') => 4])
					->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
					->hydrate(false)
					->count()
				;
				$dropoutStudentsData = $dropoutMaleStudentsData + $dropoutFemaleStudentsData;

				$repeatedMaleStudentsData = $InstitutionStudents->find()
					->contain('Users')
					->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
					->where([$InstitutionStudents->aliasField('student_status_id') => 8])
					->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
					->hydrate(false)
					->count()
				;
				$repeatedFemaleStudentsData = $InstitutionStudents->find()
					->contain('Users')
					->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
					->where([$InstitutionStudents->aliasField('student_status_id') => 8])
					->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
					->hydrate(false)
					->count()
				;
				$repeatedStudentsData = $repeatedMaleStudentsData + $repeatedFemaleStudentsData;
				
				$institutionFemaleStaffData = $InstitutionSubjects->find()
					->innerJoin(
					['SubjectStaff' => ' institution_subject_staff'],
					[
						'SubjectStaff.institution_subject_id = '. $InstitutionSubjects->aliasField('id')
					]
					)
					->where([$InstitutionSubjects->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionSubjects->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
					->hydrate(false)
					->first()
				;
				
				$institutionStaffData = $InstitutionSubjects->find()
					->innerJoin(
					['SubjectStaff' => ' institution_subject_staff'],
					[
						'SubjectStaff.institution_subject_id = '. $InstitutionSubjects->aliasField('id')
					]
					)
					->where([$InstitutionSubjects->aliasField('education_grade_id') => $value['id']])
					->where([$InstitutionSubjects->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
					->hydrate(false)
					->count()
				;
				$secondaryTeacherData = $InstitutionClasses->find()
					->innerJoin(
					['InstitutionClassGrades' => ' institution_class_grades'],
					[
						'InstitutionClassGrades.institution_class_id = '. $InstitutionClasses->aliasField('id')
					]
					)
					->innerJoin(
					['InstitutionClassGradesSecondaryStaff' => ' institution_classes_secondary_staff'],
					[
						'InstitutionClassGradesSecondaryStaff.institution_class_id = '. $InstitutionClasses->aliasField('id')
					]
					)
					->where(['InstitutionClassGrades.education_grade_id' => $value['id']])
					->where([$InstitutionClasses->aliasField('institution_id') => $params['institution_id']])
					->where([$InstitutionClasses->aliasField('academic_period_id') => $params['academic_period_id']])
					->hydrate(false)
					->count()
				;
				$maleSpecialNeedData = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecialNeed' => 'user_special_needs_assessments'],
				[
					'SpecialNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->group([
					'Users.id'
				])
				->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
				->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
				->count()
				;
				$femaleSpecialNeedData = $InstitutionStudents
				->find()
				->contain('Users')
				->innerJoin(
				['SpecialNeed' => 'user_special_needs_assessments'],
				[
					'SpecialNeed.security_user_id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->group([
					'Users.id'
				])
				->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
				->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
				->count()
				;
				$syrianStudents = $InstitutionStudents
				->find()
				->innerJoin(
				['Users' => 'security_users'],
				[
					'Users.id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->innerJoin(
				['Nationalities' => 'nationalities'],
				[
					'Nationalities.id = Users.nationality_id'
				]
				)
				->group([
					'Users.id'
				])
				->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
				->where(['Nationalities.national_code' => 'syria'])
				->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
				->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
				->count()
				;
                //POCOR-6328 starts
                /*Subject Staff Temporary*/
                $InstitutionSubjectStaff = TableRegistry::get('institution_subject_staff');
                $InstitutionStaff = TableRegistry::get('institution_staff');
                $StaffTypes = TableRegistry::get('staff_types');
                $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
                $EducationGrades = TableRegistry::get('education_grades');
                
                $subjectStaffData = $InstitutionSubjectStaff->find()
                    ->innerJoin(
                    ['InstitutionStaff' => 'institution_staff'],
                    [
                        'InstitutionStaff.staff_id = '. $InstitutionSubjectStaff->aliasField('staff_id')
                    ]
                    )
                    ->innerJoin(
                    ['StaffTypes' => 'staff_types'],
                    [
                        'StaffTypes.id = InstitutionStaff.staff_type_id',
                    ]
                    )
                    ->innerJoin(
                    ['InstitutionSubjects' => 'institution_subjects'],
                    [
                        'InstitutionSubjects.id = '. $InstitutionSubjectStaff->aliasField('institution_subject_id')
                    ]
                    )
                    ->innerJoin(
                    ['EducationGrades' => 'education_grades'],
                    [
                        'EducationGrades.id = '. $InstitutionSubjects->aliasField('education_grade_id')
                    ]
                    )
                    ->where(['StaffTypes.international_code' => 'temporary'])
                    ->where([$InstitutionSubjects->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionSubjectStaff->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->hydrate(false)
                    ->count()
                ;

                /*Secondary Staff Temporary*/
                $InstitutionStaff = TableRegistry::get('institution_staff');
                $StaffTypes = TableRegistry::get('staff_types');
                $EducationGrades = TableRegistry::get('education_grades');
                $institutionClassesSecondaryStaff = TableRegistry::get('institution_classes_secondary_staff');
                $institutionClassGrades  = TableRegistry::get('institution_class_grades');
                $institutionClasses  = TableRegistry::get('institution_classes');
                
                $secondaryStaffData = $institutionClassesSecondaryStaff->find()
                    ->innerJoin(
                    ['InstitutionStaff' => 'institution_staff'],
                    [
                        'InstitutionStaff.staff_id = '. $institutionClassesSecondaryStaff->aliasField('secondary_staff_id')
                    ]
                    )
                    ->innerJoin(
                    ['StaffTypes' => 'staff_types'],
                    [
                        'StaffTypes.id = InstitutionStaff.staff_type_id',
                    ]
                    )
                    ->innerJoin(
                    ['InstitutionClassGrades' => 'institution_class_grades'],
                    [
                        'InstitutionClassGrades.institution_class_id = '. $institutionClassesSecondaryStaff->aliasField('institution_class_id')
                    ]
                    )
                    ->innerJoin(
                    ['EducationGrades' => 'education_grades'],
                    [
                        'EducationGrades.id = InstitutionClassGrades.education_grade_id'
                    ]
                    )
                    ->innerJoin(
                    ['institutionClasses' => 'institution_classes'],
                    [
                        'institutionClasses.id = InstitutionClassGrades.institution_class_id'
                    ]
                    )
                    ->where(['StaffTypes.international_code' => 'temporary'])
                    ->where(['InstitutionClassGrades.education_grade_id' => $value['id']])
                    ->where(['institutionClasses.institution_id' => $params['institution_id']])
                    ->where(['institutionClasses.academic_period_id' => $params['academic_period_id']])
                    ->hydrate(false)
                    ->count()
                ;

                /*Homeroom Staff Temporary*/
                $homeroomStaffData = $institutionClasses->find()
                    ->innerJoin(
                    ['InstitutionStaff' => 'institution_staff'],
                    [
                        'InstitutionStaff.staff_id = '. $institutionClasses->aliasField('staff_id ')
                    ]
                    )
                    ->innerJoin(
                    ['StaffTypes' => 'staff_types'],
                    [
                        'StaffTypes.id = InstitutionStaff.staff_type_id',
                    ]
                    )
                    ->innerJoin(
                    ['InstitutionClassGrades' => 'institution_class_grades'],
                    [
                        'InstitutionClassGrades.institution_class_id = '. $institutionClasses->aliasField('id')
                    ]
                    )
                    ->innerJoin(
                    ['EducationGrades' => 'education_grades'],
                    [
                        'EducationGrades.id = InstitutionClassGrades.education_grade_id'
                    ]
                    )
                    ->where(['StaffTypes.international_code' => 'temporary'])
                    ->where(['InstitutionClassGrades.education_grade_id' => $value['id']])
                    ->where([$institutionClasses->aliasField('institution_id') => $params['institution_id']])
                    ->where([$institutionClasses->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->hydrate(false)
                    ->count()
                ;
                $temporary_staff = $secondaryStaffData + $homeroomStaffData;
                //POCOR-6328 ends
				$entity[] = [
					'education_grade_name' => (!empty($value['name']) ? $value['name'] : ''),
					'education_grade_id' => (!empty($value['id']) ? $value['id'] : 0),
					'male_student_enrolment' => $enrolledMaleStudentsData,
					'female_student_enrolment' => $enrolledFemaleStudentsData,
					'total_student_enrolment' => $enrolledStudentsData,
					'male_student_repetition' => $repeatedMaleStudentsData,
					'female_student_repetition' => $repeatedFemaleStudentsData,
					'total_student_repetition' => $repeatedStudentsData,
					'male_student_dropout' => $dropoutMaleStudentsData,
					'female_student_dropout' => $dropoutFemaleStudentsData,
					'total_student_dropout' => $dropoutStudentsData,
					'total_student' => $enrolledStudentsData + $repeatedStudentsData + $dropoutStudentsData,
					'female_subject_staff' => !empty($institutionFemaleStaffData->total_female_students) ? $institutionFemaleStaffData->total_female_students : 0,
					'subject_staff' => $institutionStaffData,
                    'subject_staff_type_temporary' => $subjectStaffData,
					'secondary_teacher' => $secondaryTeacherData,//POCOR-6328
					'male_student_special_need' => $maleSpecialNeedData,
					'female_student_special_need' => $femaleSpecialNeedData,
					'total_student_special_need' => $maleSpecialNeedData + $femaleSpecialNeedData,
                    'staff_type_temporary' => $temporary_staff,//POCOR-6328
					'syrian_students' => $syrianStudents,
				];	
			}
            return $entity;
        }
    }
    //POCOR-6426 starts
    public function onExcelTemplateInitialiseInstitutionEducationGrade(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $entity = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray()
            ;

            $addEducationheading[] = [
                'id' =>0,
                'name' => 'Education Grade'
            ];

            $entity = array_merge($addEducationheading, $entity);

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function getAreaNameByInstitution($institution_key,$institutionIds =[]){
        $areasTbl = TableRegistry::get('areas');
        $institutionsTbl = TableRegistry::get('institutions');
        if($institution_key == 0){

            $institutions = $institutionsTbl->find()
                        ->select([
                            'area_name' => $areasTbl->aliasField('name')
                        ])
                        ->innerJoin(
                            [$areasTbl->alias() => $areasTbl->table()],
                            [
                                $areasTbl->aliasField('id').' = ' . $institutionsTbl->aliasField('area_id')
                            ]
                            )
                        ->where([$institutionsTbl->aliasField('id IN') => $institutionIds])
                        ->first()
                        ;
            return $institutions->area_name;

        }else if($institution_key == 1){
            $institutions = $institutionsTbl->find()
                        ->select([
                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                        ])
                        ->innerJoin(
                            [$areasTbl->alias() => $areasTbl->table()],
                            [
                                $areasTbl->aliasField('id').' = ' . $institutionsTbl->aliasField('area_id')
                            ]
                            )
                        ->where([$institutionsTbl->aliasField('id IN') => $institutionIds])
                        ->first();

            $areas = $areasTbl->find()
                    ->select([
                        'area_id' => $areasTbl->aliasField('id'),
                        'area_name' => $areasTbl->aliasField('name'),
                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                    ])
                    ->where([$areasTbl->aliasField('id') => $institutions->area_parent_id])
                    ->first();

            return $areas->area_name;
        }else{
            $institutions = $institutionsTbl->find()
                        ->select([
                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                        ])
                        ->innerJoin(
                            [$areasTbl->alias() => $areasTbl->table()],
                            [
                                $areasTbl->aliasField('id').' = ' . $institutionsTbl->aliasField('area_id')
                            ]
                            )
                        ->where([$institutionsTbl->aliasField('id IN') => $institutionIds])
                        ->first();

            $areasRegion = $areasTbl->find()
                    ->select([
                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                    ])
                    ->where([$areasTbl->aliasField('id') => $institutions->area_parent_id])
                    ->first();

            $areas = $areasTbl->find()
                    ->select([
                        'area_name' => $areasTbl->aliasField('name')
                    ])
                    ->where([$areasTbl->aliasField('id') => $areasRegion->area_parent_id])
                    ->first();
            return $areas->area_name;
        }
    }

    public function getStudentCountByStatus($academic_period, $education_grade_id,$institutionIds =[], $student_status_id){
        $InstitutionStudents = TableRegistry::get('institution_students');
        $InstitutionStudentsData = $InstitutionStudents->find()
                                ->select([
                                    'student_id' => $InstitutionStudents->aliasField('student_id')
                                ])
                                ->where([
                                    $InstitutionStudents->aliasField('academic_period_id') => $academic_period,
                                    $InstitutionStudents->aliasField('education_grade_id') => $education_grade_id,
                                    $InstitutionStudents->aliasField('institution_id IN') => $institutionIds,
                                    $InstitutionStudents->aliasField('student_status_id') => $student_status_id

                                ])
                                ->distinct(['student_id'])    
                                ->count()
                            ;
        return $InstitutionStudentsData;
    }
    
    public function onExcelTemplateInitialiseInstitutionStudentEnrolled(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;// for enrolled status
            $institutionsTbl = TableRegistry::get('institutions');
            $institutions = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('id') => $params['institution_id']])
                        ->first(); 

            $areasTbl = TableRegistry::get('areas');
            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevels = $areaLevelsTbl->find()->count();           

            $areas = $areasTbl->find()
                    ->select([
                        'area_id' => $areasTbl->aliasField('id'),
                        'area_name' => $areasTbl->aliasField('name'),
                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                    ])
                    ->where([$areasTbl->aliasField('id') => $institutions->area_id])
                    ->first();

            $distArr = [];
            if($areas->area_parent_id > 0){
                $distArr[0][] = $institutions->area_id; //first time we get area_id
                $k = $areas->area_parent_id;
                for($i=$areas->area_parent_id; $i>=1; $i--){
                    if($k == ''){
                        break;
                    }
                    for($j=1; $j<$areaLevels; $j++){
                        //get district's regions
                        $areas1 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->first();
                        if($areas1->area_parent_id > 0){
                            $areas2 = $areasTbl->find()
                                ->select([
                                    'area_id' => $areasTbl->aliasField('id'),
                                    'area_name' => $areasTbl->aliasField('name'),
                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                ])
                                ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                                ->toArray();
                            if(!empty($areas2)){
                                foreach ($areas2 as $ar2) {
                                    $distArr[$j][] = $ar2->area_id;//district array
                                }
                            }
                        } else {
                            //get country's regions
                            $areas3 = $areasTbl->find()
                                    ->select([
                                        'area_id' => $areasTbl->aliasField('id'),
                                        'area_name' => $areasTbl->aliasField('name'),
                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                    ])
                                    ->where([$areasTbl->aliasField('parent_id') => $k])
                                    ->toArray();

                            if(!empty($areas3)){
                                $reg = [];
                                foreach ($areas3 as $ar3) {
                                    $reg [] = $ar3->area_id; 
                                }
                                
                                if(!empty($reg)){
                                    $areas4 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                            ->toArray();
                                    if(!empty($areas4)){
                                        foreach ($areas4 as $ar4) {
                                            $distArr[$j][] = $ar4->area_id;//district array
                                        }
                                    }
                                }
                            }
                        } 
                        $k = $areas1->area_parent_id;
                    }
                }
            }
            
            if(!empty($distArr)){
                $insArr = [];
                $i=0;
                foreach ($distArr as $dis_key => $dis_val) {
                    $institutionsResult = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('area_id IN') => $dis_val])
                        ->toArray();
                    foreach ($institutionsResult as $instit) {
                        $insArr[$i][] = $instit->id;//district array
                    }

                    $i++;
                }
            }

            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevelsData = $areaLevelsTbl->find()
                            ->toArray();
           
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray()
            ;
            
            $addEducationheading[] = [
                'id' =>0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            $InstitutionStudents = TableRegistry::get('institution_students');
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 ='';
                foreach ($insArr as $insKey => $insVal) {
                    if($edu_key == 0){
                        if($insKey == 0){
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 3){
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 4){
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 5){
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else{
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }
                    }else{
                        if($insKey == 0){
                           $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }else if($insKey == 3){
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }else if($insKey == 4){
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }else if($insKey == 5){
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }else{
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }    
                    }
                }


                $entity[] = [
                        'id' => $edu_val['id'],
                        'area_level:1' =>$area_level_1,
                        'area_level:2' =>$area_level_2,
                        'area_level:3' =>$area_level_3, 
                        'area_level:4' =>$area_level_4, 
                        'area_level:5' =>$area_level_5, 
                        'area_level:6' =>$area_level_6, 
                        'area_level:7' =>$area_level_7
                    ];
            }

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            
            return $entity;
        }
    }
    
    public function onExcelTemplateInitialiseInstitutionStudentWithdrawn(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $withdrawnStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('WITHDRAWN')->first()->id;// for WITHDRAWN status
            $institutionsTbl = TableRegistry::get('institutions');
            $institutions = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('id') => $params['institution_id']])
                        ->first(); 

            $areasTbl = TableRegistry::get('areas');
            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevels = $areaLevelsTbl->find()->count();           

            $areas = $areasTbl->find()
                    ->select([
                        'area_id' => $areasTbl->aliasField('id'),
                        'area_name' => $areasTbl->aliasField('name'),
                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                    ])
                    ->where([$areasTbl->aliasField('id') => $institutions->area_id])
                    ->first();

            $distArr = [];
            if($areas->area_parent_id > 0){
                $distArr[0][] = $institutions->area_id; //first time we get area_id
                $k = $areas->area_parent_id;
                for($i=$areas->area_parent_id; $i>=1; $i--){
                    if($k == ''){
                        break;
                    }
                    for($j=1; $j<$areaLevels; $j++){
                        //get district's regions
                        $areas1 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->first();
                        if($areas1->area_parent_id > 0){
                            $areas2 = $areasTbl->find()
                                ->select([
                                    'area_id' => $areasTbl->aliasField('id'),
                                    'area_name' => $areasTbl->aliasField('name'),
                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                ])
                                ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                                ->toArray();
                            if(!empty($areas2)){
                                foreach ($areas2 as $ar2) {
                                    $distArr[$j][] = $ar2->area_id;//district array
                                }
                            }
                        } else {
                            //get country's regions
                            $areas3 = $areasTbl->find()
                                    ->select([
                                        'area_id' => $areasTbl->aliasField('id'),
                                        'area_name' => $areasTbl->aliasField('name'),
                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                    ])
                                    ->where([$areasTbl->aliasField('parent_id') => $k])
                                    ->toArray();

                            if(!empty($areas3)){
                                $reg = [];
                                foreach ($areas3 as $ar3) {
                                    $reg [] = $ar3->area_id; 
                                }
                                
                                if(!empty($reg)){
                                    $areas4 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                            ->toArray();
                                    if(!empty($areas4)){
                                        foreach ($areas4 as $ar4) {
                                            $distArr[$j][] = $ar4->area_id;//district array
                                        }
                                    }
                                }
                            }
                        } 
                        $k = $areas1->area_parent_id;
                    }
                }
            }
            
            if(!empty($distArr)){
                $insArr = [];
                $i=0;
                foreach ($distArr as $dis_key => $dis_val) {
                    $institutionsResult = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('area_id IN') => $dis_val])
                        ->toArray();
                    foreach ($institutionsResult as $instit) {
                        $insArr[$i][] = $instit->id;//district array
                    }

                    $i++;
                }
            }

            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevelsData = $areaLevelsTbl->find()
                            ->toArray();
           
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray()
            ;
            
            $addEducationheading[] = [
                'id' =>0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            $InstitutionStudents = TableRegistry::get('institution_students');
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 ='';
                foreach ($insArr as $insKey => $insVal) {
                    if($edu_key == 0){
                        if($insKey == 0){
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 3){
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 4){
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 5){
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else{
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }
                    }else{
                        if($insKey == 0){
                           $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }else if($insKey == 3){
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }else if($insKey == 4){
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }else if($insKey == 5){
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }else{
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }    
                    }
                }


                $entity[] = [
                        'id' => $edu_val['id'],
                        'area_level:1' =>$area_level_1,
                        'area_level:2' =>$area_level_2,
                        'area_level:3' =>$area_level_3, 
                        'area_level:4' =>$area_level_4, 
                        'area_level:5' =>$area_level_5, 
                        'area_level:6' =>$area_level_6, 
                        'area_level:7' =>$area_level_7
                    ];
            }

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentTransferred(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $transferredStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('TRANSFERRED')->first()->id;// for TRANSFERRED status
            $institutionsTbl = TableRegistry::get('institutions');
            $institutions = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('id') => $params['institution_id']])
                        ->first(); 

            $areasTbl = TableRegistry::get('areas');
            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevels = $areaLevelsTbl->find()->count();           

            $areas = $areasTbl->find()
                    ->select([
                        'area_id' => $areasTbl->aliasField('id'),
                        'area_name' => $areasTbl->aliasField('name'),
                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                    ])
                    ->where([$areasTbl->aliasField('id') => $institutions->area_id])
                    ->first();

            $distArr = [];
            if($areas->area_parent_id > 0){
                $distArr[0][] = $institutions->area_id; //first time we get area_id
                $k = $areas->area_parent_id;
                for($i=$areas->area_parent_id; $i>=1; $i--){
                    if($k == ''){
                        break;
                    }
                    for($j=1; $j<$areaLevels; $j++){
                        //get district's regions
                        $areas1 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->first();
                        if($areas1->area_parent_id > 0){
                            $areas2 = $areasTbl->find()
                                ->select([
                                    'area_id' => $areasTbl->aliasField('id'),
                                    'area_name' => $areasTbl->aliasField('name'),
                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                ])
                                ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                                ->toArray();
                            if(!empty($areas2)){
                                foreach ($areas2 as $ar2) {
                                    $distArr[$j][] = $ar2->area_id;//district array
                                }
                            }
                        } else {
                            //get country's regions
                            $areas3 = $areasTbl->find()
                                    ->select([
                                        'area_id' => $areasTbl->aliasField('id'),
                                        'area_name' => $areasTbl->aliasField('name'),
                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                    ])
                                    ->where([$areasTbl->aliasField('parent_id') => $k])
                                    ->toArray();

                            if(!empty($areas3)){
                                $reg = [];
                                foreach ($areas3 as $ar3) {
                                    $reg [] = $ar3->area_id; 
                                }
                                
                                if(!empty($reg)){
                                    $areas4 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                            ->toArray();
                                    if(!empty($areas4)){
                                        foreach ($areas4 as $ar4) {
                                            $distArr[$j][] = $ar4->area_id;//district array
                                        }
                                    }
                                }
                            }
                        } 
                        $k = $areas1->area_parent_id;
                    }
                }
            }
            
            if(!empty($distArr)){
                $insArr = [];
                $i=0;
                foreach ($distArr as $dis_key => $dis_val) {
                    $institutionsResult = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('area_id IN') => $dis_val])
                        ->toArray();
                    foreach ($institutionsResult as $instit) {
                        $insArr[$i][] = $instit->id;//district array
                    }

                    $i++;
                }
            }

            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevelsData = $areaLevelsTbl->find()
                            ->toArray();
           
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray()
            ;
            
            $addEducationheading[] = [
                'id' =>0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            $InstitutionStudents = TableRegistry::get('institution_students');
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 ='';
                foreach ($insArr as $insKey => $insVal) {
                    if($edu_key == 0){
                        if($insKey == 0){
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 3){
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 4){
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 5){
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else{
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }
                    }else{
                        if($insKey == 0){
                            $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        }else if($insKey == 3){
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        }else if($insKey == 4){
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        }else if($insKey == 5){
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        }else{
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus); 
                        }    
                    }
                }

                $entity[] = [
                        'id' => $edu_val['id'],
                        'area_level:1' =>$area_level_1,
                        'area_level:2' =>$area_level_2,
                        'area_level:3' =>$area_level_3, 
                        'area_level:4' =>$area_level_4, 
                        'area_level:5' =>$area_level_5, 
                        'area_level:6' =>$area_level_6, 
                        'area_level:7' =>$area_level_7
                    ];
            }

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionStaffCount(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $institutionsTbl = TableRegistry::get('institutions');
            $institutions = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('id') => $params['institution_id']])
                        ->first(); 

            $areasTbl = TableRegistry::get('areas');
            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevels = $areaLevelsTbl->find()->count();           

            $areas = $areasTbl->find()
                    ->select([
                        'area_id' => $areasTbl->aliasField('id'),
                        'area_name' => $areasTbl->aliasField('name'),
                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                    ])
                    ->where([$areasTbl->aliasField('id') => $institutions->area_id])
                    ->first();

            $distArr = [];
            if($areas->area_parent_id > 0){
                $distArr[0][] = $institutions->area_id; //first time we get area_id
                $k = $areas->area_parent_id;
                for($i=$areas->area_parent_id; $i>=1; $i--){
                    if($k == ''){
                        break;
                    }
                    for($j=1; $j<$areaLevels; $j++){
                        //get district's regions
                        $areas1 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->first();
                        if($areas1->area_parent_id > 0){
                            $areas2 = $areasTbl->find()
                                ->select([
                                    'area_id' => $areasTbl->aliasField('id'),
                                    'area_name' => $areasTbl->aliasField('name'),
                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                ])
                                ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                                ->toArray();
                            if(!empty($areas2)){
                                foreach ($areas2 as $ar2) {
                                    $distArr[$j][] = $ar2->area_id;//district array
                                }
                            }
                        } else {
                            //get country's regions
                            $areas3 = $areasTbl->find()
                                    ->select([
                                        'area_id' => $areasTbl->aliasField('id'),
                                        'area_name' => $areasTbl->aliasField('name'),
                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                    ])
                                    ->where([$areasTbl->aliasField('parent_id') => $k])
                                    ->toArray();

                            if(!empty($areas3)){
                                $reg = [];
                                foreach ($areas3 as $ar3) {
                                    $reg [] = $ar3->area_id; 
                                }
                                
                                if(!empty($reg)){
                                    $areas4 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                            ->toArray();
                                    if(!empty($areas4)){
                                        foreach ($areas4 as $ar4) {
                                            $distArr[$j][] = $ar4->area_id;//district array
                                        }
                                    }
                                }
                            }
                        } 
                        $k = $areas1->area_parent_id;
                    }
                }
            }
            
            if(!empty($distArr)){
                $insArr = [];
                $i=0;
                foreach ($distArr as $dis_key => $dis_val) {
                    $institutionsResult = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('area_id IN') => $dis_val])
                        ->toArray();
                    foreach ($institutionsResult as $instit) {
                        $insArr[$i][] = $instit->id;//district array
                    }

                    $i++;
                }
            }

            $areaLevelsTbl = TableRegistry::get('area_levels');
            $areaLevelsData = $areaLevelsTbl->find()
                            ->toArray();
           
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray()
            ;
            
            $addEducationheading[] = [
                'id' =>0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 ='';
                foreach ($insArr as $insKey => $insVal) {
                    if($edu_key == 0){
                        if($insKey == 0){
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 3){
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 4){
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else if($insKey == 5){
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }else{
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);    
                        }
                    }else{
                        if($insKey == 0){
                            $area_level_1 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 3){
                            $area_level_4 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 4){
                            $area_level_5 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 5){
                            $area_level_6 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else{
                            $area_level_7 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal); 
                        }    
                    }
                }

                $entity[] = [
                        'id' => $edu_val['id'],
                        'area_level:1' =>$area_level_1,
                        'area_level:2' =>$area_level_2,
                        'area_level:3' =>$area_level_3, 
                        'area_level:4' =>$area_level_4, 
                        'area_level:5' =>$area_level_5, 
                        'area_level:6' =>$area_level_6, 
                        'area_level:7' =>$area_level_7
                    ];
            }

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function getStaffCountByArea($academic_period, $education_grade_id,$institutionIds =[]){
        $institutionClasses = TableRegistry::get('institution_classes');
        $institutionClassGrades = TableRegistry::get('institution_class_grades');
        $institutionClassesData = $institutionClasses->find()
                                ->select([
                                    'staff_id' => $institutionClasses->aliasField('staff_id')
                                ])
                                ->leftJoin([$institutionClassGrades->alias() => $institutionClassGrades->table()], [
                                    $institutionClassGrades->aliasField('institution_class_id = ') . $institutionClasses->aliasField('id')
                                ])
                                ->where([
                                    $institutionClassGrades->aliasField('education_grade_id') => $education_grade_id,
                                    $institutionClasses->aliasField('academic_period_id') => $academic_period,
                                    $institutionClasses->aliasField('institution_id IN') => $institutionIds,
                                    $institutionClasses->aliasField('staff_id <>') => 0
                                ])
                                ->distinct(['staff_id'])    
                                ->count()
                            ;
        return $institutionClassesData;
    }
    //POCOR-6426 ends
}
