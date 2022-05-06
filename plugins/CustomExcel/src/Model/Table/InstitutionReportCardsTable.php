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
use Cake\Datasource\ConnectionManager;

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
                //POCOR-6426 starts
                'InstitutionEducationGrade',
                'InstitutionStudentEnrolled',
                'InstitutionStudentWithdrawn',
                'InstitutionStudentTransferred',
                'InstitutionStaffCount',
                'InstitutionRoomTypes',
                'InstitutionRoomTypesCount',
                //POCOR-6426 ends
                'InstitutionAreaName',//POCOR-6481
                'NonTeachingStaffCount',//POCOR-6481
                'InstitutionCustomFields',//POCOR-6519
                'InstitutionCustomFieldValues',//POCOR-6519
                'ReportStudentAssessmentSummary',//POCOR-6519
                'InfrastructureRoomCustomFields',//POCOR-6519
                'InstitutionStudentRepeater',//POCOR-6691
                'InstitutionRooms',//POCOR-6691
                'InstitutionRoomsArea', //POCOR-6691
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
        //POCOR-6426 starts
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionEducationGrade'] = 'onExcelTemplateInitialiseInstitutionEducationGrade';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentEnrolled'] = 'onExcelTemplateInitialiseInstitutionStudentEnrolled';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentWithdrawn'] = 'onExcelTemplateInitialiseInstitutionStudentWithdrawn';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentTransferred'] = 'onExcelTemplateInitialiseInstitutionStudentTransferred';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStaffCount'] = 'onExcelTemplateInitialiseInstitutionStaffCount';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionRoomTypes'] = 'onExcelTemplateInitialiseInstitutionRoomTypes';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionRoomTypesCount'] = 'onExcelTemplateInitialiseInstitutionRoomTypesCount';
        //POCOR-6426 ends
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionAreaName'] = 'onExcelTemplateInitialiseInstitutionAreaName';//POCOR-6481
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseNonTeachingStaffCount'] = 'onExcelTemplateInitialiseNonTeachingStaffCount';//POCOR-6481
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionCustomFields'] = 'onExcelTemplateInitialiseInstitutionCustomFields';//POCOR-6519
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionCustomFieldValues'] = 'onExcelTemplateInitialiseInstitutionCustomFieldValues';//POCOR-6519
        //$events['ExcelTemplates.Model.onExcelTemplateInitialiseReportStudentAssessmentSummary'] = 'onExcelTemplateInitialiseReportStudentAssessmentSummary';//POCOR-6519
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureRoomCustomFields'] = 'onExcelTemplateInitialiseInfrastructureRoomCustomFields';//POCOR-6519
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentRepeater'] = 'onExcelTemplateInitialiseInstitutionStudentRepeater';//POCOR-6691
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionRooms'] = 'onExcelTemplateInitialiseInstitutionRooms';//POCOR-6691
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionRoomsArea'] = 'onExcelTemplateInitialiseInstitutionRoomsArea';//POCOR-6691
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
            //POCOR-6519 starts
            $entity->shift_type_name = '';
            if($entity->shift_type != 0){
                if($shift_types[$entity->shift_type]) {
                    $entity->shift_type_name = $shift_types[$entity->shift_type];
                }
            }//POCOR-6519 ends
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
            //POCOR-6520 Starts change in query
            $entity = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationProgrammes.name'
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
                        'EducationProgrammes.id = '. 'EducationGrades.education_programme_id',
                    ]
                )
                ->innerJoin(
                    ['EducationCycles' => 'education_cycles'],
                    [
                        'EducationCycles.id = EducationProgrammes.education_cycle_id',
                    ]
                )
                ->innerJoin(
                    ['EducationLevels' => 'education_levels'],
                    [
                        'EducationLevels.id = EducationCycles.education_level_id',
                    ]
                )
                ->innerJoin(
                    ['EducationSystems' => 'education_systems'],
                    [
                        'EducationSystems.id = EducationLevels.education_system_id',
                    ]
                )
                ->innerJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = EducationSystems.academic_period_id',
                    ]
                )
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->hydrate(false)
                ->toArray()
            ;
            //POCOR-6520 Ends
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
                ->where([$InstitutionStaffs->aliasField('staff_status_id') => 1])//POCOR-6520
				->group($InstitutionStaffs->aliasField('staff_id'))//POCOR-6520
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
                ->where([$InstitutionStaffs->aliasField('staff_status_id') => 1])//POCOR-6520
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
            //POCOR-6520 Starts change in query
            $entity = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
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
                        'EducationProgrammes.id = '. 'EducationGrades.education_programme_id',
                    ]
                )
                ->innerJoin(
                    ['EducationCycles' => 'education_cycles'],
                    [
                        'EducationCycles.id = EducationProgrammes.education_cycle_id',
                    ]
                )
                ->innerJoin(
                    ['EducationLevels' => 'education_levels'],
                    [
                        'EducationLevels.id = EducationCycles.education_level_id',
                    ]
                )
                ->innerJoin(
                    ['EducationSystems' => 'education_systems'],
                    [
                        'EducationSystems.id = EducationLevels.education_system_id',
                    ]
                )
                ->innerJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = EducationSystems.academic_period_id',
                    ]
                )
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->hydrate(false)
                ->toArray()
            ;//POCOR-6520 Ends
			
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
            //POCOR-6520 Starts change in query
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
                ->innerJoin(
                    ['EducationProgrammes' => 'education_programmes'],
                    [
                        'EducationProgrammes.id = '. 'EducationGrades.education_programme_id',
                    ]
                )
                ->innerJoin(
                    ['EducationCycles' => 'education_cycles'],
                    [
                        'EducationCycles.id = EducationProgrammes.education_cycle_id',
                    ]
                )
                ->innerJoin(
                    ['EducationLevels' => 'education_levels'],
                    [
                        'EducationLevels.id = EducationCycles.education_level_id',
                    ]
                )
                ->innerJoin(
                    ['EducationSystems' => 'education_systems'],
                    [
                        'EducationSystems.id = EducationLevels.education_system_id',
                    ]
                )
                ->innerJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = EducationSystems.academic_period_id',
                    ]
                )
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->hydrate(false)
                ->toArray()
            ;//POCOR-6520 Ends
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
            //POCOR-6520 Starts change in query
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
                ->innerJoin(
                    ['EducationProgrammes' => 'education_programmes'],
                    [
                        'EducationProgrammes.id = '. 'EducationGrades.education_programme_id',
                    ]
                )
                ->innerJoin(
                    ['EducationCycles' => 'education_cycles'],
                    [
                        'EducationCycles.id = EducationProgrammes.education_cycle_id',
                    ]
                )
                ->innerJoin(
                    ['EducationLevels' => 'education_levels'],
                    [
                        'EducationLevels.id = EducationCycles.education_level_id',
                    ]
                )
                ->innerJoin(
                    ['EducationSystems' => 'education_systems'],
                    [
                        'EducationSystems.id = EducationLevels.education_system_id',
                    ]
                )
                ->innerJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = EducationSystems.academic_period_id',
                    ]
                )
                ->where([
                    'EducationSystems.academic_period_id' => $params['academic_period_id']
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])    
                ->hydrate(false)
                ->toArray()
            ;//POCOR-6520 Ends
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
                
                $InstitutionSubjectsData = $InstitutionSubjects->find()
                    ->select([
                        $InstitutionSubjects->aliasField('id'),
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
                    ->group([$InstitutionSubjects->aliasField('name')])//POCOR-6520 starts
                    ->toArray()
                ;
                $result = [];
                if(!empty($InstitutionSubjectsData)) {
                    foreach ($InstitutionSubjectsData as $data) {
                        //POCOR-6520 starts
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
                            ->where(['InstitutionSubjectStaff.institution_subject_id' => $data['id']])
                            ->hydrate(false)
                            ->first()
                        ;

                        if(!empty($NumberOfStaff)){
                            $totalStaff = $NumberOfStaff['number_of_staff'] + $totalStaff;
                        }else{
                            $totalStaff = 0 + $totalStaff;
                        }
                        //POCOR-6520 ends
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
                        'number_of_staff' => 0,//POCOR-6520
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
                
                $StaffPositionTitlesData = $StaffPositionTitles->find()
                    ->select([
                        'id' => 'InstitutionPositions.id', //POCOR-6520
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
                    ->group([$StaffPositionTitles->aliasField('name')])//POCOR-6520 starts
                    ->hydrate(false)
                    ->toArray()
                ;
                
                $result = [];
                if(!empty($StaffPositionTitlesData)) {
                    foreach ($StaffPositionTitlesData as $data) {
                        //POCOR-6520 starts
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
                            ->where(['InstitutionStaff.institution_position_id' => $data['id']])
                            ->where(['InstitutionStaff.staff_status_id' => 1])
                            ->hydrate(false)
                            ->first()
                        ;
                        if(!empty($NumberOfStaff)){
                            $totalStaff = $NumberOfStaff['count'] + $totalStaff;
                        }else{
                            $totalStaff = 0 + $totalStaff;
                        }
                        //POCOR-6520 ends
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
                        'number_of_staff' => 0,//POCOR-6520
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
				
				$StaffTypesData = $StaffTypes->find()
					->select([
						$StaffTypes->aliasField('id'),//POCOR-6520
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
                    ->group([$StaffTypes->aliasField('name')])//POCOR-6520 starts
					->hydrate(false)
					->toArray()
				;
				$result = [];
				if(!empty($StaffTypesData)) {
					foreach ($StaffTypesData as $data) {
                        //POCOR-6520 starts
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
                            ->where(['InstitutionStaff.staff_type_id' => $data['id']])
                            ->where(['InstitutionStaff.staff_status_id' => 1])
                            ->hydrate(false)
                            ->first()
                        ;
                        if(!empty($NumberOfStaff)){
                            $totalStaff = $NumberOfStaff['count'] + $totalStaff;
                        }else{
                            $totalStaff = 0 + $totalStaff;
                        }
						//POCOR-6520 ends
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
						'number_of_staff' => 0,
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
                ->where([$InstitutionStaff->aliasField('staff_status_id !=') => 2])//POCOR-6520
                ->group($InstitutionStaff->aliasField('staff_id'))//POCOR-6520
				->count()
			;
			
			$totalStaffs = $InstitutionStaff
				->find()
				->contain('Users')
				->where([$InstitutionStaff->aliasField('institution_id') => $params['institution_id']])
                ->group($InstitutionStaff->aliasField('staff_id'))//POCOR-6520
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
				)//6520 starts
                ->innerJoin(
                    [$SpecialNeedsServices->alias() => $SpecialNeedsServices->table()],
                    [
                        $SpecialNeedsServices->aliasField('security_user_id = ') . $InstitutionStudents->aliasField('student_id')
                    ]
                )//6520 ends
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
				)//6520 starts
                ->innerJoin(
                    [$SpecialNeedsServices->alias() => $SpecialNeedsServices->table()],
                    [
                        $SpecialNeedsServices->aliasField('security_user_id = ') . $InstitutionStudents->aliasField('student_id')
                    ]
                )//6520 ends
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
        $AreaLevel1Tbl = TableRegistry::get('areas');
        $AreaLevel2Tbl = TableRegistry::get('areas');
        $AreaLevel3Tbl = TableRegistry::get('areas');
        $AreaLevel4Tbl = TableRegistry::get('areas');
        
        $institutionsTbl = TableRegistry::get('institutions');
        $institutions = $institutionsTbl->find()
                        ->select([
                            'area_id' => $institutionsTbl->aliasField('area_id')
                        ])
                        ->where([$institutionsTbl->aliasField('id IN') => $institutionIds])
                        ->first();
        if($institution_key == 0){
            $areasData = $AreaLevel1Tbl->find()
                    ->select([
                        'AreaLevel1_name' => $AreaLevel1Tbl->aliasField('name'),
                    ])
                    ->where([$AreaLevel1Tbl->aliasField('id') => $institutions->area_id])
                    ->first();             
            return $areasData->AreaLevel1_name; 
        }else if($institution_key == 1){
            $areasData = $AreaLevel1Tbl->find()
                    ->select([
                        'AreaLevel1_name' => $AreaLevel1Tbl->aliasField('name'),
                        'AreaLevel2_name' => 'AreaLevel2.name'
                    ])
                    ->innerJoin(
                        ['AreaLevel2' => $AreaLevel2Tbl->table()],
                        [
                            'AreaLevel2.id'.' = ' . $AreaLevel1Tbl->aliasField('parent_id')
                        ]
                    )
                    ->where([$AreaLevel4Tbl->aliasField('id') => $institutions->area_id])
                    ->first();             
            return $areasData->AreaLevel2_name; 
            /*SELECT A.name AS District, B.name AS State, C.name AS Country
            FROM areas A, areas B, areas C
            WHERE  A.name = 'District 8' AND A.parent_id = B.id AND B.parent_id = C.id*/
        }else if($institution_key == 2){
            $areasData = $AreaLevel1Tbl->find()
                    ->select([
                        'AreaLevel1_name' => $AreaLevel1Tbl->aliasField('name'),
                        'AreaLevel2_name' => 'AreaLevel2.name',
                        'AreaLevel3_name' => 'AreaLevel3.name'
                    ])
                    ->innerJoin(
                        ['AreaLevel2' => $AreaLevel2Tbl->table()],
                        [
                            'AreaLevel2.id'.' = ' . $AreaLevel1Tbl->aliasField('parent_id')
                        ]
                    )
                    ->innerJoin(
                        ['AreaLevel3' => $AreaLevel3Tbl->table()],
                        [
                            'AreaLevel3.id'.' = ' . 'AreaLevel2.parent_id'
                        ]
                    )
                    ->where([$AreaLevel1Tbl->aliasField('id') => $institutions->area_id])
                    ->first();             
            return $areasData->AreaLevel3_name;        
        }else if($institution_key == 3){
            $areasData = $AreaLevel1Tbl->find()
                    ->select([
                        'AreaLevel1_name' => $AreaLevel1Tbl->aliasField('name'),
                        'AreaLevel2_name' => 'AreaLevel2.name',
                        'AreaLevel3_name' => 'AreaLevel3.name',
                        'AreaLevel4_name' => 'AreaLevel4.name'
                    ])
                    ->innerJoin(
                        ['AreaLevel2' => $AreaLevel2Tbl->table()],
                        [
                            'AreaLevel2.id'.' = ' . $AreaLevel1Tbl->aliasField('parent_id')
                        ]
                    )
                    ->innerJoin(
                        ['AreaLevel3' => $AreaLevel3Tbl->table()],
                        [
                            'AreaLevel3.id'.' = ' . 'AreaLevel2.parent_id'
                        ]
                    )
                    ->innerJoin(
                        ['AreaLevel4' => $AreaLevel4Tbl->table()],
                        [
                            'AreaLevel4.id'.' = ' . 'AreaLevel3.parent_id'
                        ]
                    )
                    ->where([$AreaLevel1Tbl->aliasField('id') => $institutions->area_id])
                    ->first();             
            return $areasData->AreaLevel4_name;        
        }else{
            $areasTbl = TableRegistry::get('areas');
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $distArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $distArr[$j][] = $ar4->area_id;//district array
                                            }
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $distArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $distArr[$j][] = $ar4->area_id;//district array
                                            }
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $distArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $distArr[$j][] = $ar4->area_id;//district array
                                            }
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $distArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $distArr[$j][] = $ar4->area_id;//district array
                                            }
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
        $institution_id = implode(',', $institutionIds );
        $connection = ConnectionManager::get('default');
        $institutionClassesData = $connection->execute("SELECT
                                *
                            FROM
                                (
                                SELECT
                                    `institution_classes`.`staff_id` AS `staff_id`,
                                    education_grade_id,
                                    academic_period_id,
                                    institution_id
                                FROM
                                    `institution_classes` `institution_classes`
                                LEFT JOIN `institution_class_grades` `institution_class_grades` ON
                                    `institution_class_grades`.`institution_class_id` = `institution_classes`.`id`
                                UNION
                            SELECT
                                `institution_classes_secondary_staff`.`secondary_staff_id` AS `staff_id`,
                                education_grade_id,
                                academic_period_id,
                                institution_id
                            FROM
                                `institution_classes` `institution_classes`
                            INNER JOIN institution_class_grades ON `institution_class_grades`.`institution_class_id` = `institution_classes`.`id`
                            LEFT JOIN institution_classes_secondary_staff ON `institution_classes_secondary_staff`.`institution_class_id` = `institution_classes`.`id`
                            WHERE
                                `institution_classes_secondary_staff`.`secondary_staff_id` IS NOT NULL
                            ) AS education_grade_staff
                            WHERE
                                (
                                        `education_grade_staff`.`education_grade_id` = $education_grade_id AND `education_grade_staff`.`academic_period_id` = $academic_period AND `education_grade_staff`.`institution_id` IN ($institution_id) AND `education_grade_staff`.`staff_id` != 0
                                )
                            GROUP BY
                                staff_id,
                                education_grade_id,
                                academic_period_id");
        return count($institutionClassesData);
    }
    
    public function onExcelTemplateInitialiseInstitutionRoomTypes(Event $event, array $params, ArrayObject $extra)
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

            $addRoomheading[] = [
                'id' => 0,
                'name' => 'Room Type'
            ];

            $entity = array_merge($addRoomheading, $entity);

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionRoomTypesCount(Event $event, array $params, ArrayObject $extra)
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $distArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $distArr[$j][] = $ar4->area_id;//district array
                                            }
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
           
            $RoomTypes = TableRegistry::get('room_types');
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');

            $RoomTypeData = $RoomTypes->find()
                            ->select([
                                $RoomTypes->aliasField('id'),
                                $RoomTypes->aliasField('name')
                            ])
                            ->hydrate(false)
                            ->toArray()
                        ;

            $addRoomheading[] = [
                'id' => 0,
                'name' => 'Room Type'
            ];

            $RoomTypeData = array_merge($addRoomheading, $RoomTypeData);
            foreach ($RoomTypeData as $edu_key => $edu_val) {
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
                            $area_level_1 = $this->getRoomCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getRoomCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getRoomCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 3){
                            $area_level_4 = $this->getRoomCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 4){
                            $area_level_5 = $this->getRoomCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else if($insKey == 5){
                            $area_level_6 = $this->getRoomCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }else{
                            $area_level_7 = $this->getRoomCountByArea($params['academic_period_id'], $edu_val['id'], $insVal); 
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

    public function getRoomCountByArea($academic_period, $room_type_id, $institutionIds =[]){
        $institutionRooms = TableRegistry::get('institution_rooms');
        $roomTypes = TableRegistry::get('room_types');
        $institutionRoomsData = $institutionRooms->find()
                                ->select([
                                    'count_room_type' => $institutionRooms->aliasField('id')
                                ])
                                ->where([
                                    $institutionRooms->aliasField('academic_period_id') => $academic_period,
                                    $institutionRooms->aliasField('institution_id IN') => $institutionIds,
                                    $institutionRooms->aliasField('room_status_id') => 1,
                                    $institutionRooms->aliasField('room_type_id') => $room_type_id,
                                ])
                                ->count()
                            ;
        return $institutionRoomsData;
    }
    //POCOR-6426 ends

    //POCOR-6481 starts
    public function getAreaName($institution_id){
        $institutionsTbl = TableRegistry::get('institutions');
        $institutions = $institutionsTbl->find()
                    ->where([$institutionsTbl->aliasField('id') => $institution_id])
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

        $areaLevelArr = [];
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $areaLevelArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $areaLevelArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $areaLevelArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $areaLevelArr[$j][] = $ar4->area_id;//district array
                                            }
                                        }
                                    }
                                }
                            }
                        } 
                        $k = $areas1->area_parent_id;
                    }
                }
            }
        $levelArr=[];
        if(!empty($areaLevelArr)){
            foreach ($areaLevelArr as $akey => $aval) {
                $levelArr[] = $aval[0];
            }
        }
        return $levelArr;
    }

    public function onExcelTemplateInitialiseInstitutionAreaName(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $levelArr = $this->getAreaName($params['institution_id']);
            $areasTbl = TableRegistry::get('areas');
            $entity = $areasTbl->find()
                        ->select([
                            $areasTbl->aliasField('id'),
                            $areasTbl->aliasField('name'),
                        ])
                        ->where([$areasTbl->aliasField('id IN') => $levelArr])
                        ->order([$areasTbl->aliasField('id') => 'DESC'])
                        ->hydrate(false)
                        ->toArray();

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseNonTeachingStaffCount(Event $event, array $params, ArrayObject $extra)
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
            //get area names
            $AreaNameData = $this->getAreaName($params['institution_id']);
            foreach ($AreaNameData as $area_key => $area_val) {
                $area_level ='';
                foreach ($insArr as $insKey => $insVal) {
                    if($area_key == $insKey){
                        $area_level = $this->getNonTeachingStaffCountByArea($params['academic_period_id'], $insVal);
                        break;
                    }
                }
                $entity[] = [
                        'id' => $area_val,
                        'area_level' =>$area_level,
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

    public function getNonTeachingStaffCountByArea($academic_period, $institutionIds =[]){
        $institution_id = implode(',', $institutionIds );
        $connection = ConnectionManager::get('default');
        $NonTeachingStaffData = $connection->execute("SELECT
                            academic_periods.id,
                            academic_periods.code,
                            academic_periods.name,
                            institution_staff.*
                        FROM
                            institution_staff
                        INNER JOIN institutions ON `institutions`.`id` = `institution_staff`.`institution_id`
                        INNER JOIN institution_positions ON `institution_positions`.`id` = `institution_staff`.`institution_position_id`
                        INNER JOIN staff_position_titles ON staff_position_titles.id = institution_positions.staff_position_title_id
                        INNER JOIN areas ON `areas`.`id` = `institutions`.`area_id`
                        INNER JOIN academic_periods ON(
                                (
                                    (
                                        `institution_staff`.`end_date` IS NOT NULL AND `institution_staff`.`start_date` <= `academic_periods`.`start_date` AND `institution_staff`.`end_date` >= `academic_periods`.`start_date`
                                    ) OR(
                                        `institution_staff`.`end_date` IS NOT NULL AND `institution_staff`.`start_date` <= `academic_periods`.`end_date` AND `institution_staff`.`end_date` >= `academic_periods`.`end_date`
                                    ) OR(
                                        `institution_staff`.`end_date` IS NOT NULL AND `institution_staff`.`start_date` >= `academic_periods`.`start_date` AND `institution_staff`.`end_date` <= `academic_periods`.`end_date`
                                    )
                                ) OR(
                                    `institution_staff`.`end_date` IS NULL AND `institution_staff`.`start_date` <= `academic_periods`.`end_date` 
                                )
                            )
                        WHERE
                            staff_status_id = 1 AND academic_periods.academic_period_level_id != -1 AND staff_position_titles.type = 0 AND `institution_staff`.`institution_id` IN ($institution_id) AND academic_periods.id = $academic_period
                        ORDER BY
                            institution_staff.staff_id ASC");
    
        return count($NonTeachingStaffData);
    }
    //POCOR-6481 ends  
    
    /**
     * create placeholder to display custom field names in Institutions Custom Fields
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function onExcelTemplateInitialiseInstitutionCustomFields(Event $event, array $params, ArrayObject $extra){
        if (array_key_exists('institution_id', $params)) {
            $InstitutionCustomFieldValues = TableRegistry::get('institution_custom_field_values');
            $InstitutionCustomFields = TableRegistry::get('institution_custom_fields');
            $entity = $InstitutionCustomFieldValues
                        ->find()
                        ->select([
                                'id' => $InstitutionCustomFields->aliasField('id'),
                                'name' => $InstitutionCustomFields->aliasField('name')
                            ])
                        ->leftJoin(
                            [$InstitutionCustomFields->alias() => $InstitutionCustomFields->table()],
                            [
                                $InstitutionCustomFields->aliasField('id ='). $InstitutionCustomFieldValues->aliasField('institution_custom_field_id')
                            ]
                        )
                        ->where([$InstitutionCustomFieldValues->aliasField('institution_id') => $params['institution_id']])
                        ->group([$InstitutionCustomFields->aliasField('id')])
                        ->hydrate(false)
                        ->toArray();
            return $entity;
        }
    }
    /**
     * create placeholder to display custom field values and results in Institutions Custom Fields
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function onExcelTemplateInitialiseInstitutionCustomFieldValues(Event $event, array $params, ArrayObject $extra){
        if (array_key_exists('institution_id', $params)) {
            $InstitutionCustomFieldValues = TableRegistry::get('institution_custom_field_values');
            $InstitutionCustomFields = TableRegistry::get('institution_custom_fields');
            $institutionCustomFieldOptions = TableRegistry::get('institution_custom_field_options');
            $InstitutionCustomFieldResult = $InstitutionCustomFieldValues
                        ->find()
                        ->select([
                                'id' => $InstitutionCustomFields->aliasField('id'),
                                'name' => $InstitutionCustomFields->aliasField('name')
                            ])
                        ->leftJoin(
                            [$InstitutionCustomFields->alias() => $InstitutionCustomFields->table()],
                            [
                                $InstitutionCustomFields->aliasField('id ='). $InstitutionCustomFieldValues->aliasField('institution_custom_field_id')
                            ]
                        )
                        ->where([$InstitutionCustomFieldValues->aliasField('institution_id') => $params['institution_id']])
                        ->group([$InstitutionCustomFields->aliasField('id')])
                        ->hydrate(false)
                        ->toArray();
            
            $field_arr = [];
            foreach ($InstitutionCustomFieldResult as $key => $value) {
                $field_arr[] = $this->getInstitutionCustomFieldValues($params['institution_id'], $value['id']);    
            }

            $result = [];
            if(!empty($field_arr)){
                foreach ($field_arr as $field_key => $field_val) {
                    $result[$field_key]['id'] = $field_val[0]['id'];
                    if($field_val[0]['field_type'] == 'CHECKBOX'){
                        $check_num = [];
                        foreach ($field_val as $f_k => $f_v) {
                            $check_data = $institutionCustomFieldOptions
                                        ->find()
                                        ->select([
                                                'name' => $institutionCustomFieldOptions->aliasField('name')
                                            ])
                                        ->where([$institutionCustomFieldOptions->aliasField('id IN') => $f_v['number_value']])
                                        ->hydrate(false)
                                        ->toArray();
                            $check_num[] = $check_data[0]['name'];
                        }
                        $checkbox = implode(',', $check_num);                    
                        $result[$field_key]['name'] = !empty($checkbox) ? $checkbox : '';
                    }else if($field_val[0]['field_type'] == 'TEXT'){
                        $result[$field_key]['name'] = !empty($field_val[0]['text_value']) ? $field_val[0]['text_value'] : ' ';
                    }else if($field_val[0]['field_type'] == 'NUMBER'){
                        $result[$field_key]['name'] = !empty($field_val[0]['number_value']) ? $field_val[0]['number_value'].' ' : '0 ';
                    }else if($field_val[0]['field_type'] == 'DECIMAL'){
                        $result[$field_key]['name'] = !empty($field_val[0]['decimal_value']) ? $field_val[0]['decimal_value'].' ' : '0.00 ';
                    }else if($field_val[0]['field_type'] == 'TEXTAREA'){
                        $result[$field_key]['name'] = !empty($field_val[0]['textarea_value']) ? $field_val[0]['textarea_value'] : '';
                    }else if($field_val[0]['field_type'] == 'DROPDOWN'){
                        $check_data = $institutionCustomFieldOptions
                                        ->find()
                                        ->select([
                                                'name' => $institutionCustomFieldOptions->aliasField('name')
                                            ])
                                        ->where([$institutionCustomFieldOptions->aliasField('id IN') => $field_val[0]['number_value']])
                                        ->hydrate(false)
                                        ->toArray();
                        $result[$field_key]['name'] = !empty($check_data[0]['name']) ? $check_data[0]['name'] : '';
                    }else if($field_val[0]['field_type'] == 'DATE'){
                        $result[$field_key]['name'] = !empty($field_val[0]['date_value']) ? date("Y-m-d", strtotime($field_val[0]['date_value'])) : '';
                    }else if($field_val[0]['field_type'] == 'TIME'){
                        $result[$field_key]['name'] = !empty($field_val[0]['time_value']) ? date("H: i: s", strtotime($field_val[0]['time_value'])) : '';
                    }else if($field_val[0]['field_type'] == 'COORDINATES'){
                        if(!empty($field_val[0]['text_value'])){
                            $cordinate = json_decode($field_val[0]['text_value'], true);
                            $result[$field_key]['name'] = 'latitude: '.$cordinate['latitude'] .', longitude: '.$cordinate['longitude'] ;
                        }else{
                            $result[$field_key]['name'] = '';
                        }
                    } 
                }
            }
            
            $entity = [];
            foreach ($result as $e_key => $e_val) {
                $entity[] = [
                    'id' => $e_val['id'],
                    'data' => $e_val['name']
                ];
            }
            return $entity;
        }
    } 
    /**
     * Get Institution Custom Field Values
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function getInstitutionCustomFieldValues($institution_id, $institution_custom_field_id){
        $InstitutionCustomFieldValues = TableRegistry::get('institution_custom_field_values');
        $InstitutionCustomFields = TableRegistry::get('institution_custom_fields');
        $institutionCustomFieldOptions = TableRegistry::get('institution_custom_field_options');
        $InstitutionCustomFieldValues = $InstitutionCustomFieldValues
                        ->find()
                        ->select([
                            'text_value' => $InstitutionCustomFieldValues->aliasField('text_value'),
                            'number_value' => $InstitutionCustomFieldValues->aliasField('number_value'),
                            'decimal_value' => $InstitutionCustomFieldValues->aliasField('decimal_value'),
                            'textarea_value' => $InstitutionCustomFieldValues->aliasField('textarea_value'),
                            'date_value' => $InstitutionCustomFieldValues->aliasField('date_value'),
                            'time_value' => $InstitutionCustomFieldValues->aliasField('time_value'),
                            'id' => $InstitutionCustomFields->aliasField('id'),
                            'field_type' => $InstitutionCustomFields->aliasField('field_type')
                        ])
                        ->leftJoin(
                            [$InstitutionCustomFields->alias() => $InstitutionCustomFields->table()],
                            [
                                $InstitutionCustomFields->aliasField('id ='). $InstitutionCustomFieldValues->aliasField('institution_custom_field_id')
                            ]
                        )
                        ->where([$InstitutionCustomFieldValues->aliasField('institution_id') => $institution_id])
                        ->where([$InstitutionCustomFieldValues->aliasField('institution_custom_field_id') => $institution_custom_field_id])
                        ->hydrate(false)
                        ->toArray();
        return $InstitutionCustomFieldValues;
    }
    /**
     * Create a placeholder to display institution data from this table report_student_assessment_summary
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    // public function onExcelTemplateInitialiseReportStudentAssessmentSummary(Event $event, array $params, ArrayObject $extra)
    // {
    //     if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
    //         $ReportStudentAssessmentSummary = TableRegistry::get('report_student_assessment_summary');
    //         $AssessmentSummaryData = $ReportStudentAssessmentSummary->find()
    //             ->select([
    //             'id' => $ReportStudentAssessmentSummary->aliasField('id'),
    //             'academic_period_code' => $ReportStudentAssessmentSummary->aliasField('academic_period_code'),
    //             'academic_period_name' => $ReportStudentAssessmentSummary->aliasField('academic_period_name'),
    //             'area_code' => $ReportStudentAssessmentSummary->aliasField('area_code'),
    //             'area_name' => $ReportStudentAssessmentSummary->aliasField('area_name'),
    //             'institution_code' => $ReportStudentAssessmentSummary->aliasField('institution_code'),
    //             'institution_name' => $ReportStudentAssessmentSummary->aliasField('institution_name'),
    //             'grade_code' => $ReportStudentAssessmentSummary->aliasField('grade_code'),
    //             'grade_name' => $ReportStudentAssessmentSummary->aliasField('grade_name'),
    //             'subject_code' => $ReportStudentAssessmentSummary->aliasField('subject_code'),
    //             'subject_name' => $ReportStudentAssessmentSummary->aliasField('subject_name'),
    //             'subject_weight' => $ReportStudentAssessmentSummary->aliasField('subject_weight'),
    //             'assessment_code' => $ReportStudentAssessmentSummary->aliasField('assessment_code'),
    //             'assessment_name' => $ReportStudentAssessmentSummary->aliasField('assessment_name'),
    //             'period_code' => $ReportStudentAssessmentSummary->aliasField('period_code'),
    //             'period_name' => $ReportStudentAssessmentSummary->aliasField('period_name'),
    //             'period_weight' => $ReportStudentAssessmentSummary->aliasField('period_weight'),
    //             'average_marks' => $ReportStudentAssessmentSummary->aliasField('average_marks')
    //             ])
    //             ->where([$ReportStudentAssessmentSummary->aliasField('institution_id') => $params['institution_id']])    
    //             ->where([$ReportStudentAssessmentSummary->aliasField('academic_period_id') => $params['academic_period_id']])    
    //             ->hydrate(false)
    //             ->toArray(); 
    //         $entity = [];
    //         if(empty($AssessmentSummaryData)){
    //             return $entity;
    //         }

    //         foreach ($AssessmentSummaryData as $e_key => $e_val) {
    //             $entity[] = [
    //                 'id' => $e_val['id'],
    //                 'academic_period_code' => (!empty($e_val['academic_period_code']) ? $e_val['academic_period_code'] : ''),
    //                 'academic_period_name' => (!empty($e_val['academic_period_name']) ? $e_val['academic_period_name'] : ''),
    //                 'area_code' => (!empty($e_val['area_code']) ? $e_val['area_code'] : ''),
    //                 'area_name' => (!empty($e_val['area_name']) ? $e_val['area_name'] : ''),
    //                 'institution_code' => (!empty($e_val['institution_code']) ? $e_val['institution_code'] : ''),
    //                 'institution_name' => (!empty($e_val['institution_name']) ? $e_val['institution_name'] : ''),
    //                 'grade_code' => (!empty($e_val['grade_code']) ? $e_val['grade_code'] : ''),
    //                 'grade_name' => (!empty($e_val['grade_name']) ? $e_val['grade_name'] : ''),
    //                 'subject_code' => (!empty($e_val['subject_code']) ? $e_val['subject_code'] : ''),
    //                 'subject_name' => (!empty($e_val['subject_name']) ? $e_val['subject_name'] : ''),
    //                 'subject_weight' => (!empty($e_val['subject_weight']) ? $e_val['subject_weight'] : ''),
    //                 'assessment_code' => (!empty($e_val['assessment_code']) ? $e_val['assessment_code'] : ''),
    //                 'assessment_name' => (!empty($e_val['assessment_name']) ? $e_val['assessment_name'] : ''),
    //                 'period_code' => (!empty($e_val['period_code']) ? $e_val['period_code'] : ''),
    //                 'period_name' => (!empty($e_val['period_name']) ? $e_val['period_name'] : ''),
    //                 'period_weight' => (!empty($e_val['period_weight']) ? $e_val['period_weight'] : ''),
    //                 'average_marks' => (!empty($e_val['average_marks']) ? $e_val['average_marks'].' ' : '')
    //             ];
    //         }
    //         return $entity;
    //     }
    // }
    /**
     * Create a placeholder to display custom field values and results in Infrastructure Room Custom Fields
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function onExcelTemplateInitialiseInfrastructureRoomCustomFields(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionRooms = TableRegistry::get('institution_rooms');
            $RoomTypes = TableRegistry::get('room_types');
            $RoomCustomFieldValues = TableRegistry::get('room_custom_field_values');
            $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
            $InstitutionRoomsData = $InstitutionRooms->find()
                ->select([
                    'id' => $InstitutionRooms->aliasField('id'),
                    'code' => $InstitutionRooms->aliasField('code'),
                    'name' => $InstitutionRooms->aliasField('name'),
                    'area' => $InstitutionRooms->aliasField('area'),
                    'room_type' => $RoomTypes->aliasField('name')
                ])
                ->LeftJoin([$RoomTypes->alias() => $RoomTypes->table()], [
                    $InstitutionRooms->aliasField('room_type_id') . '= ' . $RoomTypes->aliasField('id')
                ])
                ->where([$InstitutionRooms->aliasField('institution_id') => $params['institution_id']])    
                ->where([$InstitutionRooms->aliasField('academic_period_id') => $params['academic_period_id']])  
                ->hydrate(false)
                ->toArray()
                ;
            
            $entity = [];
            if(empty($InstitutionRoomsData)){
                return $entity;
            }
            $i=0;
            foreach ($InstitutionRoomsData as $e_key => $e_val) {
                $RoomCustomFieldValuesData = $RoomCustomFieldValues->find()
                        ->select([
                            'id' => $RoomCustomFieldValues->aliasField('id'),
                            'infrastructure_custom_field_id' => $RoomCustomFieldValues->aliasField('infrastructure_custom_field_id'),
                            'custom_field_name' => $InfrastructureCustomFields->aliasField('name')
                        ])
                        ->LeftJoin([$InfrastructureCustomFields->alias() => $InfrastructureCustomFields->table()], [
                            $RoomCustomFieldValues->aliasField('infrastructure_custom_field_id') . '= ' . $InfrastructureCustomFields->aliasField('id')
                        ])
                        ->where([$RoomCustomFieldValues->aliasField('institution_room_id') => $e_val['id']])
                        ->group([$RoomCustomFieldValues->aliasField('infrastructure_custom_field_id')]) 
                        ->hydrate(false)
                        ->toArray(); 
                if(!empty($RoomCustomFieldValuesData)){
                    foreach ($RoomCustomFieldValuesData as $r_key => $r_val) {
                        //get Custom fields Values by room _id and infrastructure_custom_field_id 
                        $val_result = $this->getInfrastructureRoomCustomFieldValues($e_val['id'], $r_val['infrastructure_custom_field_id']);    
                        $entity[$i] = [
                            'id' => $r_val['id'],
                            'code' => (!empty($e_val['code']) ? $e_val['code'] : ''),
                            'name' => (!empty($e_val['name']) ? $e_val['name'] : ''),
                            'area' => (!empty($e_val['area']) ? $e_val['area'] : ''),
                            'room_type' => (!empty($e_val['room_type']) ? $e_val['room_type'] : ''),
                            'infrastructure_custom_field_id' => $r_val['infrastructure_custom_field_id'],
                            'custom_field_name' => $r_val['custom_field_name'],
                            'custom_field_value' => $val_result
                        ];
                        $i++;
                    }                     
                }else{
                    $entity[$i] = [
                        'id' => $e_val['id'],
                        'code' => (!empty($e_val['code']) ? $e_val['code'] : ''),
                        'name' => (!empty($e_val['name']) ? $e_val['name'] : ''),
                        'area' => (!empty($e_val['area']) ? $e_val['area'] : ''),
                        'room_type' => (!empty($e_val['room_type']) ? $e_val['room_type'] : ''),
                        'infrastructure_custom_field_id' => '',
                        'custom_field_name' => '',
                        'custom_field_value' => ''
                    ];
                    $i++;
                }
            }
            return $entity; 
        }
    }
    /**
     * Get Infrastructure Room Custom Field Values using params $room_id & $room_custom_field_id)
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function getInfrastructureRoomCustomFieldValues($room_id, $room_custom_field_id){
        $RoomCustomFieldTbl = TableRegistry::get('room_custom_field_values');
        $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
        $InfrastructureCustomFieldOptions = TableRegistry::get('infrastructure_custom_field_options');
        $RoomCustomFieldValues[] = $RoomCustomFieldTbl
                        ->find()
                        ->select([
                                'text_value' => $RoomCustomFieldTbl->aliasField('text_value'),
                                'number_value' => $RoomCustomFieldTbl->aliasField('number_value'),
                                'decimal_value' => $RoomCustomFieldTbl->aliasField('decimal_value'),
                                'textarea_value' => $RoomCustomFieldTbl->aliasField('textarea_value'),
                                'date_value' => $RoomCustomFieldTbl->aliasField('date_value'),
                                'time_value' => $RoomCustomFieldTbl->aliasField('time_value'),
                                'institution_room_id' => $RoomCustomFieldTbl->aliasField('institution_room_id'),
                                'infrastructure_custom_field_id' => $RoomCustomFieldTbl->aliasField('infrastructure_custom_field_id'),
                                'id' => $InfrastructureCustomFields->aliasField('id'),
                                'field_type' => $InfrastructureCustomFields->aliasField('field_type'),
                            ])
                        ->LeftJoin([$InfrastructureCustomFields->alias() => $InfrastructureCustomFields->table()], [
                            $RoomCustomFieldTbl->aliasField('infrastructure_custom_field_id') . '= ' . $InfrastructureCustomFields->aliasField('id')
                        ])
                        ->where([$RoomCustomFieldTbl->aliasField('institution_room_id') => $room_id])
                        ->where([$RoomCustomFieldTbl->aliasField('infrastructure_custom_field_id') => $room_custom_field_id])
                        ->hydrate(false)
                        ->toArray();
        $result = [];
        if(!empty($RoomCustomFieldValues)){
            foreach ($RoomCustomFieldValues as $field_key => $field_val) {
                if($field_val[0]['field_type'] == 'CHECKBOX'){
                    $check_num = [];
                    foreach ($field_val as $f_k => $f_v) {
                        $check_data = $InfrastructureCustomFieldOptions
                                    ->find()
                                    ->select([
                                            'name' => $InfrastructureCustomFieldOptions->aliasField('name')
                                        ])
                                    ->where([$InfrastructureCustomFieldOptions->aliasField('id IN') => $f_v['number_value']])
                                    ->hydrate(false)
                                    ->toArray();
                        $check_num[] = $check_data[0]['name'];
                    }
                    $checkbox = implode(',', $check_num);                    
                    $result['name'] = !empty($checkbox) ? $checkbox : '';
                }else if($field_val[0]['field_type'] == 'TEXT'){
                    $result['name'] = !empty($field_val[0]['text_value']) ? $field_val[0]['text_value'] : ' ';
                }else if($field_val[0]['field_type'] == 'NUMBER'){
                    $result['name'] = !empty($field_val[0]['number_value']) ? $field_val[0]['number_value'].' ' : '0 ';
                }else if($field_val[0]['field_type'] == 'DECIMAL'){
                    $result['name'] = !empty($field_val[0]['decimal_value']) ? $field_val[0]['decimal_value'].' ' : '0.00 ';
                }else if($field_val[0]['field_type'] == 'TEXTAREA'){
                    $result['name'] = !empty($field_val[0]['textarea_value']) ? $field_val[0]['textarea_value'] : '';
                }else if($field_val[0]['field_type'] == 'DROPDOWN'){
                    $check_data = $InfrastructureCustomFieldOptions
                                    ->find()
                                    ->select([
                                            'name' => $InfrastructureCustomFieldOptions->aliasField('name')
                                        ])
                                    ->where([$InfrastructureCustomFieldOptions->aliasField('id IN') => $field_val[0]['number_value']])
                                    ->hydrate(false)
                                    ->toArray();
                    $result['name'] = !empty($check_data[0]['name']) ? $check_data[0]['name'] : '';
                }else if($field_val[0]['field_type'] == 'DATE'){
                    $result['name'] = !empty($field_val[0]['date_value']) ? date("Y-m-d", strtotime($field_val[0]['date_value'])) : '';
                }else if($field_val[0]['field_type'] == 'TIME'){
                    $result['name'] = !empty($field_val[0]['time_value']) ? date("H: i: s", strtotime($field_val[0]['time_value'])) : '';
                }else if($field_val[0]['field_type'] == 'COORDINATES'){
                    if(!empty($field_val[0]['text_value'])){
                        $cordinate = json_decode($field_val[0]['text_value'], true);
                        $result['name'] = 'latitude: '.$cordinate['latitude'] .', longitude: '.$cordinate['longitude'] ;
                    }else{
                        $result['name'] = '';
                    }
                } 
            }
        }  
        return $result['name'];
    }//POCOR-6519 ends

    /**
     * Fetching total count of repeated students
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6691
    */
    public function onExcelTemplateInitialiseInstitutionStudentRepeater(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $repeatedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('REPEATED')->first()->id;// for REPEATED status
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $distArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $distArr[$j][] = $ar4->area_id;//district array
                                            }
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
                            $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        }else if($insKey == 3){
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        }else if($insKey == 4){
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        }else if($insKey == 5){
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        }else{
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus); 
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

    /**
     * Fetching Institution Rooms on the bases of area
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6691
    */
    public function onExcelTemplateInitialiseInstitutionRooms(Event $event, array $params, ArrayObject $extra)
    {
       if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
            $entity = [
                [
                    'id' => 0,
                    'area' => 'Room Area'
                ],
                [
                    'id' => 1,
                    'area' => 'Cumulative Size'
                ]
            ];
            return $entity;
        }
    }

    /**
     * Fetching total count of Institution Rooms on the bases of area
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6691
    */
    public function onExcelTemplateInitialiseInstitutionRoomsArea(Event $event, array $params, ArrayObject $extra)
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
                                    $areas5 = $areasTbl->find()
                                            ->select([
                                                'area_id' => $areasTbl->aliasField('id'),
                                                'area_name' => $areasTbl->aliasField('name'),
                                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                                            ])
                                            ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                            ->toArray();
                                    if(!empty($areas5)){
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array     
                                        }
                                    }else{
                                        $distArr[$j][] = $ar2->area_id;//district array
                                    }
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
                                            $areas6 = $areasTbl->find()
                                                    ->select([
                                                        'area_id' => $areasTbl->aliasField('id'),
                                                        'area_name' => $areasTbl->aliasField('name'),
                                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                    ])
                                                    ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                    ->toArray();
                                            if(!empty($areas6)){
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array     
                                                }
                                            }else{
                                                $distArr[$j][] = $ar4->area_id;//district array
                                            }
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
            $RoomTypeData = [
                [
                    'id' => 0,
                    'area' => 'Room Area'
                ],
                [
                    'id' => 1,
                    'area' => 'Cumulative Size'
                ]
            ];

            //$RoomTypeData = array_merge($addRoomAreaheading, $RoomTypeData);
            foreach ($RoomTypeData as $edu_key => $edu_val) {
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
                            $area_level_1 = $this->getRoomCountByAreaCol($params['academic_period_id'], $insVal);
                        }else if($insKey == 1){
                            $area_level_2 = $this->getRoomCountByAreaCol($params['academic_period_id'], $insVal);
                        }else if($insKey == 2){
                            $area_level_3 = $this->getRoomCountByAreaCol($params['academic_period_id'], $insVal);
                        }else if($insKey == 3){
                            $area_level_4 = $this->getRoomCountByAreaCol($params['academic_period_id'], $insVal);
                        }else if($insKey == 4){
                            $area_level_5 = $this->getRoomCountByAreaCol($params['academic_period_id'], $insVal);
                        }else if($insKey == 5){
                            $area_level_6 = $this->getRoomCountByAreaCol($params['academic_period_id'], $insVal);
                        }else{
                            $area_level_7 = $this->getRoomCountByAreaCol($params['academic_period_id'], $insVal); 
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

    /**
     * Fetching Institution Room's area
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6691
    */
    public function getRoomCountByAreaCol($academic_period, $institutionIds =[]){
        $institutionRooms = TableRegistry::get('institution_rooms');
        $institutionRoomsAreaData = $institutionRooms->find()
                                ->select(['room_area' => $this->find()->func()->sum($institutionRooms->aliasField('area'))])
                                ->where([
                                    $institutionRooms->aliasField('academic_period_id') => $academic_period,
                                    $institutionRooms->aliasField('institution_id IN') => $institutionIds,
                                ])
                                ->first();
        $sumOfRoomsArea = $institutionRoomsAreaData['room_area'];
        
        return $sumOfRoomsArea;
    }
    /*POCOR-6691 ends*/
}
