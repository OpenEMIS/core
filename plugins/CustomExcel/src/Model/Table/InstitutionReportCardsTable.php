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
use Cake\Log\Log;//POCOR-8073
use Cake\I18n\Time;//POCOR-6328
use Cake\Datasource\ConnectionManager;

class InstitutionReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';
    private $insArr = null; // POCOR-8073
    private $_dynamicFieldName = 'result_type';
    //private $fileType = 'pdf';

    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);
        ini_set("pcre.backtrack_limit", "50000000"); //POCOR-6744

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
                'InstitutionShiftType',
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
                'StudentDetails',//POCOR-6646 - registering function
                'InstitutionStudentRepeater',//POCOR-6691
                'InstitutionRooms',//POCOR-6691
                'InstitutionRoomsArea', //POCOR-6691
                'InstitutionOwnerOccupier', //POCOR-7328
                'InstitutionStudentPromoted', //POCOR-7328
                //'InstitutionEducationProgramme', //POCOR-7378
                'StaffFromEducationProgramme', //POCOR-7378
                'JordonSchoolShifts', //POCOR-7411
                'TotalNonTeachingStaffs', //POCOR-7411
                'InfrastructureLandCustomFields',//POCOR-7421
                'InstitutionClassroomArea',//POCOR-7421
                'SchoolStudentsTotalAbsenceDays',//POCOR-7421
                'SchoolStaffTotalAbsenceDays',//POCOR-7421
                'AreaStudentsTotalAbsenceDays',//POCOR-7421
                'AreaStaffTotalAbsenceDays',//POCOR-7421
                //'LastYearEducationGrade',//POCOR-7421
                'LastYearInstitutionStudentEnrolled',//POCOR-7421
                'LastYearInstitutionStudentPromoted',//POCOR-7421
                'LastYearInstitutionStudentWithdrawn',//POCOR-7421
                'LastYearInstitutionStudentRepeated',//POCOR-7421
                'LastYearInstitutionEducationGrade',//POCOR-7421*
                'LastYearStudentEnrolledByArea',//POCOR-7421
                'LastYearStudentPromotedByArea',//POCOR-7421
                'LastYearStudentWithdrawnByArea',//POCOR-7421
                'LastYearStudentRepeatedByArea',//POCOR-7421
                'TeachingStaffTotalAbsences',//POCOR-7449
                'AreaTeachingStaffTotalAbsenceDays',//POCOR-7449
                'PublicHolidays',//POCOR-7694
                'GeneralStudentDetails',//POCOR-8182
                'StudentCustomFieldName',//POCOR-8182
                'StudentCustomFieldValueAnswer',//POCOR-8182
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
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionShiftType'] = 'onExcelTemplateInitialiseInstitutionShiftType';
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
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseReportStudentAssessmentSummary'] = 'onExcelTemplateInitialiseReportStudentAssessmentSummary';//POCOR-6519
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureRoomCustomFields'] = 'onExcelTemplateInitialiseInfrastructureRoomCustomFields';//POCOR-6519
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentDetails'] = 'onExcelTemplateInitialiseStudentDetails';//POCOR-6646 - triggering event
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentRepeater'] = 'onExcelTemplateInitialiseInstitutionStudentRepeater';//POCOR-6691
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionRooms'] = 'onExcelTemplateInitialiseInstitutionRooms';//POCOR-6691
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionRoomsArea'] = 'onExcelTemplateInitialiseInstitutionRoomsArea';//POCOR-6691
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionOwnerOccupier'] = 'onExcelTemplateInitialiseInstitutionOwnerOccupier';//POCOR-7328
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentPromoted'] = 'onExcelTemplateInitialiseInstitutionStudentPromoted';//POCOR-7328
        //$events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionEducationProgramme'] = 'onExcelTemplateInitialiseInstitutionEducationProgramme';//POCOR-7378
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffFromEducationProgramme'] = 'onExcelTemplateInitialiseStaffFromEducationProgramme';//POCOR-7378
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseJordonSchoolShifts'] = 'onExcelTemplateInitialiseJordonSchoolShifts';//POCOR-7411
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseTotalNonTeachingStaffs'] = 'onExcelTemplateInitialiseTotalNonTeachingStaffs';//POCOR-7411
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureLandCustomFields'] = 'onExcelTemplateInitialiseInfrastructureLandCustomFields';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionClassroomArea'] = 'onExcelTemplateInitialiseInstitutionClassroomArea';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseSchoolStudentsTotalAbsenceDays'] = 'onExcelTemplateInitialiseSchoolStudentsTotalAbsenceDays';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseSchoolStaffTotalAbsenceDays'] = 'onExcelTemplateInitialiseSchoolStaffTotalAbsenceDays';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAreaStudentsTotalAbsenceDays'] = 'onExcelTemplateInitialiseAreaStudentsTotalAbsenceDays';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAreaStaffTotalAbsenceDays'] = 'onExcelTemplateInitialiseAreaStaffTotalAbsenceDays';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearInstitutionStudentEnrolled'] = 'onExcelTemplateInitialiseLastYearInstitutionStudentEnrolled';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearInstitutionStudentPromoted'] = 'onExcelTemplateInitialiseLastYearInstitutionStudentPromoted';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearInstitutionStudentWithdrawn'] = 'onExcelTemplateInitialiseLastYearInstitutionStudentWithdrawn';//POCOR-7421
        //$events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearEducationGrade'] = 'onExcelTemplateInitialiseLastYearEducationGrade';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearInstitutionStudentRepeated'] = 'onExcelTemplateInitialiseLastYearInstitutionStudentRepeated';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearInstitutionEducationGrade'] = 'onExcelTemplateInitialiseLastYearInstitutionEducationGrade';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearStudentEnrolledByArea'] = 'onExcelTemplateInitialiseLastYearStudentEnrolledByArea';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearStudentPromotedByArea'] = 'onExcelTemplateInitialiseLastYearStudentPromotedByArea';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearStudentWithdrawnByArea'] = 'onExcelTemplateInitialiseLastYearStudentWithdrawnByArea';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseLastYearStudentRepeatedByArea'] = 'onExcelTemplateInitialiseLastYearStudentRepeatedByArea';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseTeachingStaffTotalAbsences'] = 'onExcelTemplateInitialiseTeachingStaffTotalAbsences';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAreaTeachingStaffTotalAbsenceDays'] = 'onExcelTemplateInitialiseAreaTeachingStaffTotalAbsenceDays';//POCOR-7421
        $events['ExcelTemplates.Model.onExcelTemplateInitialisePublicHolidays'] = 'onExcelTemplateInitialisePublicHolidays';//POCOR-7694
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGeneralStudentDetails'] = 'onExcelTemplateInitialiseGeneralStudentDetails';//POCOR-8182
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCustomFieldName'] = 'onExcelTemplateInitialiseStudentCustomFieldName';//POCOR-8182
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCustomFieldValueAnswer'] = 'onExcelTemplateInitialiseStudentCustomFieldValueAnswer';//POCOR-8182

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
        $fileName = $institutionReportCardData->academic_period->name . '_' . $institutionReportCardData->profile_template->code . '_' . $institutionReportCardData->institution->name . '.' . $this->fileType;
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
            $entity = $Institutions->get($params['institution_id'], ['contain' => ['AreaAdministratives', 'Types', 'Genders', 'Sectors', 'Providers', 'Ownerships', 'Areas', 'InstitutionLands']]); //POCOR-6328

            $shift_types = [1 => 'Single Shift Owner',
                2 => 'Single Shift Occupier',
                3 => 'Multiple Shift Owner',
                4 => 'Multiple Shift Occupier'
            ];
            //POCOR-6519 starts
            $entity->shift_type_name = '';
            if ($entity->shift_type != 0) {
                if ($shift_types[$entity->shift_type]) {
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
            if ($entity == '') {
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
            if ($entity == '') {
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
            if ($entity == '') {
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
            if ($entity == '') {
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
                        'EducationGrades.id = ' . $InstitutionGrades->aliasField('education_grade_id')
                    ]
                )
                ->innerJoin(
                    ['EducationProgrammes' => 'education_programmes'],
                    [
                        'EducationProgrammes.id = ' . 'EducationGrades.education_programme_id'
                    ]
                )
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])
                ->hydrate(false)
                ->toArray();

            $totalArray = [];
            $totalArray = [
                'id' => count($entity) + 1,
                'name' => 'Total',
            ];
            $entity[] = $totalArray;
            return $entity;
        }
    }//POCOR-6328 ends

    public function onExcelTemplateInitialiseInstitutionLands(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            //POCOR-7411 Starts
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT SUM(subq.area_size) area_size
                                            FROM (
                                                SELECT land_area.area_size
                                                FROM institution_shifts
                                                LEFT JOIN
                                                (
                                                    SELECT institution_lands.institution_id, SUM(institution_lands.area) area_size
                                                    FROM institution_lands
                                                    WHERE institution_lands.academic_period_id = " . $params['academic_period_id'] . " AND institution_lands.land_status_id = 1
                                                    GROUP BY institution_lands.institution_id
                                                ) land_area
                                                ON land_area.institution_id = institution_shifts.institution_id
                                                WHERE institution_shifts.academic_period_id = " . $params['academic_period_id'] . " AND institution_shifts.location_institution_id = " . $params['institution_id'] . "
                                                GROUP BY institution_shifts.institution_id, institution_shifts.location_institution_id) subq")->fetch('assoc');
            $entity['area'] = '';
            if (!empty($entity)) {
                $entity['area'] = $entity['area_size'];
            }
            return $entity;//POCOR-7411 ends            
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
            if ($entity == '') {
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
                        'InfrastructureWashSanitationQuantities.infrastructure_wash_sanitation_id = ' . $InfrastructureWashSanitations->aliasField('id')
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

    //POCOR -7272  Shift Type start
    public function onExcelTemplateInitialiseInstitutionShiftType(Event $event, array $params, ArrayObject $extra)
    {

        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {

            $academic_period = $params['academic_period_id'];
            $institution_id = $params['institution_id'];

            $connection = ConnectionManager::get('default');
            $result = $connection->execute("SELECT `Institutions`.`id` AS `institution_id`, `academic_periods`.`id` AS `academic_periods`,
    ( CASE WHEN IFNULL(owner_shifts.owner_count, 0) = 1 THEN 'Single Shift Owner' WHEN IFNULL(owner_shifts.owner_count, 0) > 1 THEN 'Multiple Shift Owner' WHEN IFNULL(owner_shifts.owner_count, 0) = 0 AND IFNULL(occupier_shifts.occupier_count, 0
        ) = 1 THEN 'Single Shift Occupier' WHEN IFNULL(owner_shifts.owner_count, 0) = 0 AND IFNULL( occupier_shifts.occupier_count,
            0) > 1 THEN 'Multiple Shift Occupier' ELSE 'Others' END) AS `occupier`
            FROM
                `institutions` `Institutions`
            INNER JOIN `academic_periods` `academic_periods` ON
                (
                    (
                        (
                            Institutions.date_closed IS NOT NULL AND `Institutions`.`date_opened` <= academic_periods.start_date AND `Institutions`.`date_closed` >= academic_periods.start_date
                        ) OR(
                            Institutions.date_closed IS NOT NULL AND `Institutions`.`date_opened` <= academic_periods.end_date AND `Institutions`.`date_closed` >= academic_periods.end_date
                        ) OR(
                            Institutions.date_closed IS NOT NULL AND `Institutions`.`date_opened` >= academic_periods.start_date AND `Institutions`.`date_closed` <= academic_periods.end_date
                        )
                    ) OR(
                        Institutions.date_closed IS NULL AND `Institutions`.`date_opened` <= academic_periods.end_date
                    )
                )
            LEFT JOIN(
                SELECT institution_id,
                    academic_period_id,
                    COUNT(institution_id) AS owner_count
                FROM
                    institution_shifts
                GROUP BY
                    institution_id,
                    academic_period_id
            ) `owner_shifts`
            ON
                (
                    Institutions.id = owner_shifts.institution_id AND academic_periods.id = owner_shifts.academic_period_id
                )
            LEFT JOIN(
                SELECT location_institution_id AS institution_id,
                    academic_period_id,
                    COUNT(location_institution_id) AS occupier_count
                FROM
                    institution_shifts
                GROUP BY
                    location_institution_id,
                    academic_period_id
            ) `occupier_shifts`
            ON
                (
                    Institutions.id = occupier_shifts.institution_id AND academic_periods.id = occupier_shifts.academic_period_id
                )
            WHERE
                (
                    `academic_periods`.`id` =" . $academic_period . " AND `Institutions`.`institution_status_id` = 1 AND `Institutions`.`id` =" . $institution_id . "
                )
                ")->fetch('assoc');
            return $result;
        }
    }

    //POCOR-7272 Shift Type ends
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
                        'InfrastructureWashSanitationQuantities.infrastructure_wash_sanitation_id = ' . $InfrastructureWashSanitations->aliasField('id')
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
                ->count();
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
                        'InfrastructureWashSanitationQuantities.infrastructure_wash_sanitation_id = ' . $InfrastructureWashSanitations->aliasField('id')
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
            if (!empty($totalStudent) && !empty($totalStudentToilet)) {
                $entity = $totalStudent / $totalStudentToilet;
                $entity = number_format((float)$entity, 2, '.', '');
            } else {
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
        //POCOR-8013 rewritten
        if (isset($params['institution_id'])) {
            $ReportCards = TableRegistry::get('ReportCard.ReportCards');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $staffRoleId = $SecurityRoles->getPrincipalRoleId();
            $institutionId = $params['institution_id'];
            //POCOR-8093 to fetch staff position
            $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
            $staffPosnId = $StaffPositionTitles->getPrincipalRoleId();
            $staff = $ReportCards::getInstitutionSecurityStaff($institutionId, $staffPosnId);
            return $staff;
        }
    }

    public function onExcelTemplateInitialiseDeputyPrincipal(Event $event, array $params, ArrayObject $extra)
    {
        //POCOR-8013 rewritten
        if (isset($params['institution_id'])) {
            $ReportCards = TableRegistry::get('ReportCard.ReportCards');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $staffRoleId = $SecurityRoles->getDeputyPrincipalRoleId();
            $institutionId = $params['institution_id'];
            //POCOR-8093 to fetch staff position
            $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
            $staffPosnId = $StaffPositionTitles->getDeputyPrincipalRoleId();
            $staff = $ReportCards::getInstitutionSecurityStaff($institutionId, $staffPosnId);
            return $staff;
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
                ->count();
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
                ->count();
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
                ->count();
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
                ->count();

            $totalStaffs = $InstitutionStaffs
                ->find()
                ->contain('Users')
                ->where([$InstitutionStaffs->aliasField('institution_id') => $params['institution_id']])
                ->count();
            if (!empty($totalStudents) && !empty($totalStaffs)) {
                $entity = $totalStudents / $totalStaffs;
                $entity = number_format((float)$entity, 2, '.', '');
            } else {
                $entity = 0;
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseTotalStaffs(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT institution_staff.staff_id
                        ,COUNT(DISTINCT(institution_staff.staff_id)) total_staff
                    FROM institution_staff
                    INNER JOIN academic_periods
                    ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                    WHERE institution_staff.institution_id = " . $params['institution_id'] . "
                    AND academic_periods.id = " . $params['academic_period_id'] . "
                    AND institution_staff.staff_status_id = 1")->fetch();
            $totalStaff = 0;
            if (!empty($entity)) {
                $totalStaff = $entity[1];
            }
            return $totalStaff;
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
                ->count();
            return $entity;
        }
    }

    //POCOR-7449 Starts
    public function onExcelTemplateInitialiseStudentTotalAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $absenceDaysData = $connection->execute("SELECT SUM(subq.absence_days) absence_days FROM ( SELECT COUNT(DISTINCT(institution_student_absence_details.date)) absence_days FROM institution_student_absence_details WHERE institution_student_absence_details.academic_period_id = " . $params['academic_period_id'] . " AND institution_student_absence_details.institution_id = " . $params['institution_id'] . " AND institution_student_absence_details.absence_type_id != 3 GROUP BY institution_student_absence_details.student_id ) subq")->fetch();
            $absenceDays = " 0";
            if (!empty($absenceDaysData)) {
                $absenceDays = $absenceDaysData[0];
            }
            return $absenceDays;
        }
    }

    public function onExcelTemplateInitialiseStaffTotalAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $schoolStaffAbsentDaysData = $connection->execute("SELECT ROUND(IFNULL(SUM(institution_staff_leave.number_of_days), 0), 0) school_staff_absent_days FROM institution_staff_leave WHERE institution_staff_leave.academic_period_id = " . $params['academic_period_id'] . " AND institution_staff_leave.institution_id = " . $params['institution_id'] . "")->fetch();
            $schoolStaffAbsentDays = " 0";
            if (!empty($schoolStaffAbsentDaysData)) {
                $schoolStaffAbsentDays = $schoolStaffAbsentDaysData[0];
            }
            return $schoolStaffAbsentDays;
        }
    }//POCOR-7449 Ends

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
                        'SpecialNeed.security_user_id = ' . $InstitutionStudents->aliasField('student_id')
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
                ->count();
            if (!empty($entity)) {
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
                        'SpecialNeed.security_user_id = ' . $InstitutionStudents->aliasField('student_id')
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
                ->count();
            if (!empty($entity)) {
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
                        'SpecialNeed.security_user_id = ' . $InstitutionStudents->aliasField('student_id')
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
                ->count();
            if (!empty($entity)) {
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
                        'Institution.id = ' . $InstitutionBudgets->aliasField('institution_id')
                    ]
                )
                ->where([$InstitutionBudgets->aliasField('institution_id') => $params['institution_id']])
                ->where([$InstitutionBudgets->aliasField('academic_period_id') => $params['academic_period_id']])
                ->first();
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
                        'Institution.id = ' . $InstitutionExpenditures->aliasField('institution_id')
                    ]
                )
                ->where([$InstitutionExpenditures->aliasField('institution_id') => $params['institution_id']])
                ->where([$InstitutionExpenditures->aliasField('academic_period_id') => $params['academic_period_id']])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseRoomTypes(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $RoomTypesData = $connection->execute("SELECT room_area.room_type_name, room_area.room_count
                                FROM institution_shifts
                                LEFT JOIN
                                (
                                    SELECT institution_rooms.room_type_id, institution_rooms.institution_id, room_types.name room_type_name, COUNT(DISTINCT(institution_rooms.id)) room_count
                                    FROM institution_rooms
                                    INNER JOIN room_types
                                    ON room_types.id = institution_rooms.room_type_id
                                    WHERE institution_rooms.academic_period_id = " . $params['academic_period_id'] . " AND institution_rooms.room_status_id = 1
                                    GROUP BY institution_rooms.institution_id, institution_rooms.room_type_id
                                ) room_area
                                ON room_area.institution_id = institution_shifts.institution_id
                                WHERE institution_shifts.academic_period_id = " . $params['academic_period_id'] . " AND institution_shifts.location_institution_id = " . $params['institution_id'] . "
                                GROUP BY institution_shifts.institution_id, institution_shifts.location_institution_id, room_area.room_type_id")->fetchAll(\PDO::FETCH_ASSOC);
            $entity = $result = [];
            if (!empty($RoomTypesData)) {
                foreach ($RoomTypesData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'room_type_name' => !empty($data['room_type_name']) ? $data['room_type_name'] : '',
                        'room_count' => !empty($data['room_count']) ? $data['room_count'] : '',
                    ];
                    $entity[] = $result;
                }
            }
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
                        'EducationGrades.id = ' . $InstitutionGrades->aliasField('education_grade_id')
                    ]
                )
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])
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
                        'EducationGrades.id = ' . $InstitutionGrades->aliasField('education_grade_id')
                    ]
                )
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])
                ->hydrate(false)
                ->toArray();
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
                    ->toArray();

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
            $connection = ConnectionManager::get('default');
            $EducationGradeClassesData = $connection->execute("SELECT grades_info.education_programme_name, grades_info.education_grade_name,IFNULL(students_info.count_students, 0) count_students, IFNULL(classes_info.count_classes, 0) count_classes
                FROM 
                (
                    SELECT education_programmes.name education_programme_name, education_grades.name education_grade_name, education_grades.id education_grade_id
                    FROM institution_grades
                    INNER JOIN education_grades
                    ON education_grades.id = institution_grades.education_grade_id
                    INNER JOIN education_programmes
                    ON education_programmes.id = education_grades.education_programme_id
                    WHERE institution_grades.academic_period_id = " . $params['academic_period_id'] . " AND institution_grades.institution_id = " . $params['institution_id'] . "
                ) grades_info
                LEFT JOIN 
                (
                    SELECT institution_students.education_grade_id, COUNT(DISTINCT(institution_students.student_id)) count_students
                    FROM institution_students
                    INNER JOIN academic_periods
                    ON academic_periods.id = institution_students.academic_period_id
                    WHERE institution_students.academic_period_id = " . $params['academic_period_id'] . " AND institution_students.institution_id = " . $params['institution_id'] . "
                    AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8)) 
                    GROUP BY institution_students.education_grade_id 
                ) students_info
                ON students_info.education_grade_id = grades_info.education_grade_id
                LEFT JOIN 
                (
                    SELECT institution_class_grades.education_grade_id, COUNT(DISTINCT(institution_classes.id)) count_classes
                    FROM institution_class_grades
                    INNER JOIN institution_classes
                    ON institution_classes.id = institution_class_grades.institution_class_id
                    WHERE institution_classes.academic_period_id = " . $params['academic_period_id'] . " AND institution_classes.institution_id = " . $params['institution_id'] . "
                    GROUP BY institution_class_grades.education_grade_id
                ) classes_info 
                ON classes_info.education_grade_id = grades_info.education_grade_id")->fetchAll(\PDO::FETCH_ASSOC);

            $entity = $result = [];
            if (!empty($EducationGradeClassesData)) {
                foreach ($EducationGradeClassesData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'programme_name' => !empty($data['education_programme_name']) ? $data['education_programme_name'] : '',
                        'grade_name' => !empty($data['education_grade_name']) ? $data['education_grade_name'] : '',
                        'count_students' => !empty($data['count_students']) ? $data['count_students'] : 0,
                        'count_classes' => !empty($data['count_classes']) ? $data['count_classes'] : 0
                    ];
                    $entity[] = $result;
                }
            }
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
                    'education_grade_name' => 'EducationGrades.name',
                    'education_grade_id' => 'EducationGrades.id',
                ])
                ->innerJoin(
                    ['EducationGrades' => 'education_grades'],
                    [
                        'EducationGrades.id = ' . $InstitutionSubjects->aliasField('education_grade_id')
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
                ->toArray();

            $result = [];
            $total_students = 0;
            foreach ($InstitutionSubjectsData as $data) {

                $InstitutionSubjectStudent = $InstitutionSubjects
                    ->find()
                    ->select([
                        $InstitutionSubjects->aliasField('id'),
                        $InstitutionSubjects->aliasField('name'),
                        'education_grade_name' => 'EducationGrades.name',
                        'education_grade_id' => 'EducationGrades.id',
                        'total_male_students' => 'SUM(total_male_students)',
                        'total_female_students' => 'SUM(total_female_students)',
                    ])
                    ->innerJoin(
                        ['EducationGrades' => 'education_grades'],
                        [
                            'EducationGrades.id = ' . $InstitutionSubjects->aliasField('education_grade_id')
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
                'id' => (!empty($data['id']) ? $data['id'] : 0) + 1,
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
                ->toArray();
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
                ->toArray();

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
                            'InstitutionSubjectStaff.institution_subject_id = ' . $InstitutionSubjects->aliasField('id')
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
                    ->first();

                $InstitutionSubjectsData = $InstitutionSubjects->find()
                    ->select([
                        $InstitutionSubjects->aliasField('name')
                    ])
                    ->innerJoin(
                        ['InstitutionSubjectStaff' => 'institution_subject_staff'],
                        [
                            'InstitutionSubjectStaff.institution_subject_id = ' . $InstitutionSubjects->aliasField('id')
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
                    ->toArray();
                $result = [];
                if (!empty($InstitutionSubjectsData)) {
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
                ->toArray();

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
                            'InstitutionStaffDuties.staff_duties_id = ' . $StaffDuties->aliasField('id')
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
                    ->first();

                $StaffDutiesData = $StaffDuties->find()
                    ->select([
                        $StaffDuties->aliasField('name')
                    ])
                    ->innerJoin(
                        ['InstitutionStaffDuties' => 'institution_staff_duties'],
                        [
                            'InstitutionStaffDuties.staff_duties_id = ' . $StaffDuties->aliasField('id')
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
                    ->toArray();
                $result = [];
                if (!empty($StaffDutiesData)) {
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
                ->toArray();

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
                            'InstitutionPositions.staff_position_title_id = ' . $StaffPositionTitles->aliasField('id')
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
                    ->first();

                $StaffPositionTitlesData = $StaffPositionTitles->find()
                    ->select([
                        $StaffPositionTitles->aliasField('name')
                    ])
                    ->innerJoin(
                        ['InstitutionPositions' => 'institution_positions'],
                        [
                            'InstitutionPositions.staff_position_title_id = ' . $StaffPositionTitles->aliasField('id')
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
                    ->toArray();
                $result = [];
                if (!empty($StaffPositionTitlesData)) {
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
                ->toArray();

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
                            'InstitutionStaff.staff_type_id = ' . $StaffTypes->aliasField('id')
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
                    ->first();

                $StaffTypesData = $StaffTypes->find()
                    ->select([
                        $StaffTypes->aliasField('name')
                    ])
                    ->innerJoin(
                        ['InstitutionStaff' => 'institution_staff'],
                        [
                            'InstitutionStaff.staff_type_id = ' . $StaffTypes->aliasField('id')
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
                    ->toArray();
                $result = [];
                if (!empty($StaffTypesData)) {
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
                ->toArray();
            foreach ($RoomTypesData as $value) {
                $InstitutionRoomsData = $InstitutionRooms->find()
                    ->select([
                        'count' => 'count(id)'
                    ])
                    ->where([$InstitutionRooms->aliasField('room_type_id') => $value->id])
                    ->where([$InstitutionRooms->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionRooms->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->hydrate(false)
                    ->toArray();

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
                        'InstitutionPositions.staff_position_title_id = ' . $StaffPositionTitles->aliasField('id')
                    ]
                )
                ->innerJoin(
                    ['InstitutionPositions' => 'institution_positions'],
                    [
                        'InstitutionPositions.staff_position_title_id = ' . $StaffPositionTitles->aliasField('id')
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
                ->toArray();
            $result = [];
            foreach ($entity as $key => $value) {
                $result = [
                    'name' => $value['first_name'] . ' ' . $value['last_name'],
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
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionClassRooms(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');//POCOR-7642 starts
            $RoomTypesData = $connection->execute("SELECT MAX(subq.nb_classrooms) nb_classrooms
                                                    FROM 
                                                    (
                                                        SELECT land_area.room_type_name, land_area.nb_classrooms
                                                        FROM institution_shifts
                                                        LEFT JOIN
                                                        (
                                                            SELECT institution_rooms.institution_id, room_types.name room_type_name, COUNT(DISTINCT(institution_rooms.id)) nb_classrooms
                                                            FROM institution_rooms
                                                            INNER JOIN room_types
                                                            ON room_types.id = institution_rooms.room_type_id
                                                            WHERE institution_rooms.academic_period_id = " . $params['academic_period_id'] . " 
                                                            AND institution_rooms.room_status_id = 1
                                                            AND room_types.classification = 1
                                                            GROUP BY institution_rooms.institution_id
                                                        ) land_area
                                                        ON land_area.institution_id = institution_shifts.institution_id
                                                        WHERE institution_shifts.academic_period_id = " . $params['academic_period_id'] . "
                                                        AND institution_shifts.location_institution_id = " . $params['institution_id'] . "
                                                        GROUP BY institution_shifts.institution_id
                                                            ,institution_shifts.location_institution_id
                                                    ) subq")->fetchAll(\PDO::FETCH_ASSOC);//POCOR-7642 ends

            $entity = ($RoomTypesData) ? $RoomTypesData[0]['nb_classrooms'] : 0;
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
                ->count();

            $totalStaffs = $InstitutionStaff
                ->find()
                ->contain('Users')
                ->where([$InstitutionStaff->aliasField('institution_id') => $params['institution_id']])
                ->count();

            if (!empty($teachingStaff) && !empty($totalStaffs)) {
                $entity = $teachingStaff / $totalStaffs;
                $entity = number_format((float)$entity, 2, '.', '');
            } else {
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
                ->toArray();
            //POCOR-6330 starts
            $enrolledStudentsData = 0;
            if (empty($EducationGradesData)) {
                $entity[] = [
                    'education_grade_name' => '',
                    'education_grade_id' => 0,
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
                    'syrian_students' => 0,
                    'jordanian_students' => 0,//POCOR-7272
                    'male_student_promotion' => 0,//POCOR-7272
                    'female_student_promotion' => 0,//POCOR-7272
                    'total_student_promotion' => 0//POCOR-7272
                ];

                return $entity;
            }//POCOR-6330 ends
            $enrolledStudentsData = 0;
            //POCOR-6328 start
            if (empty($EducationGradesData)) {
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
                    ->count();
                $enrolledFemaleStudentsData = $InstitutionStudents->find()
                    ->contain('Users')
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->where([$InstitutionStudents->aliasField('student_status_id') => 1])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
                    ->hydrate(false)
                    ->count();
                $enrolledStudentsData = $enrolledMaleStudentsData + $enrolledFemaleStudentsData;

                $dropoutMaleStudentsData = $InstitutionStudents->find()
                    ->contain('Users')
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->where([$InstitutionStudents->aliasField('student_status_id') => 4])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
                    ->hydrate(false)
                    ->count();
                $dropoutFemaleStudentsData = $InstitutionStudents->find()
                    ->contain('Users')
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->where([$InstitutionStudents->aliasField('student_status_id') => 4])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
                    ->hydrate(false)
                    ->count();
                $dropoutStudentsData = $dropoutMaleStudentsData + $dropoutFemaleStudentsData;

                $repeatedMaleStudentsData = $InstitutionStudents->find()
                    ->contain('Users')
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->where([$InstitutionStudents->aliasField('student_status_id') => 8])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
                    ->hydrate(false)
                    ->count();
                $repeatedFemaleStudentsData = $InstitutionStudents->find()
                    ->contain('Users')
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->where([$InstitutionStudents->aliasField('student_status_id') => 8])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
                    ->hydrate(false)
                    ->count();
                $repeatedStudentsData = $repeatedMaleStudentsData + $repeatedFemaleStudentsData;

                $institutionFemaleStaffData = $InstitutionSubjects->find()
                    ->innerJoin(
                        ['SubjectStaff' => ' institution_subject_staff'],
                        [
                            'SubjectStaff.institution_subject_id = ' . $InstitutionSubjects->aliasField('id')
                        ]
                    )
                    ->where([$InstitutionSubjects->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionSubjects->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->hydrate(false)
                    ->first();

                $institutionStaffData = $InstitutionSubjects->find()
                    ->innerJoin(
                        ['SubjectStaff' => 'institution_subject_staff'],
                        [
                            'SubjectStaff.institution_subject_id = ' . $InstitutionSubjects->aliasField('id')
                        ]
                    )
                    ->where([$InstitutionSubjects->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionSubjects->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->hydrate(false)
                    ->count();
                $secondaryTeacherData = $InstitutionClasses->find()
                    ->innerJoin(
                        ['InstitutionClassGrades' => 'institution_class_grades'],
                        [
                            'InstitutionClassGrades.institution_class_id = ' . $InstitutionClasses->aliasField('id')
                        ]
                    )
                    ->innerJoin(
                        ['InstitutionClassGradesSecondaryStaff' => 'institution_classes_secondary_staff'],
                        [
                            'InstitutionClassGradesSecondaryStaff.institution_class_id = ' . $InstitutionClasses->aliasField('id')
                        ]
                    )
                    ->where(['InstitutionClassGrades.education_grade_id' => $value['id']])
                    ->where([$InstitutionClasses->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionClasses->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->hydrate(false)
                    ->count();
                $maleSpecialNeedData = $InstitutionStudents
                    ->find()
                    ->contain('Users')
                    ->innerJoin(
                        ['SpecialNeed' => 'user_special_needs_assessments'],
                        [
                            'SpecialNeed.security_user_id = ' . $InstitutionStudents->aliasField('student_id')
                        ]
                    )
                    ->group([
                        'Users.id'
                    ])
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->count();
                $femaleSpecialNeedData = $InstitutionStudents
                    ->find()
                    ->contain('Users')
                    ->innerJoin(
                        ['SpecialNeed' => 'user_special_needs_assessments'],
                        [
                            'SpecialNeed.security_user_id = ' . $InstitutionStudents->aliasField('student_id')
                        ]
                    )
                    ->group([
                        'Users.id'
                    ])
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->count();
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
                            'InstitutionStaff.staff_id = ' . $InstitutionSubjectStaff->aliasField('staff_id')
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
                            'InstitutionSubjects.id = ' . $InstitutionSubjectStaff->aliasField('institution_subject_id')
                        ]
                    )
                    ->innerJoin(
                        ['EducationGrades' => 'education_grades'],
                        [
                            'EducationGrades.id = ' . $InstitutionSubjects->aliasField('education_grade_id')
                        ]
                    )
                    ->where(['StaffTypes.international_code' => 'temporary'])
                    ->where([$InstitutionSubjects->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionSubjectStaff->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionSubjects->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->hydrate(false)
                    ->count();

                /*Secondary Staff Temporary*/
                $InstitutionStaff = TableRegistry::get('institution_staff');
                $StaffTypes = TableRegistry::get('staff_types');
                $EducationGrades = TableRegistry::get('education_grades');
                $institutionClassesSecondaryStaff = TableRegistry::get('institution_classes_secondary_staff');
                $institutionClassGrades = TableRegistry::get('institution_class_grades');
                $institutionClasses = TableRegistry::get('institution_classes');

                $secondaryStaffData = $institutionClassesSecondaryStaff->find()
                    ->innerJoin(
                        ['InstitutionStaff' => 'institution_staff'],
                        [
                            'InstitutionStaff.staff_id = ' . $institutionClassesSecondaryStaff->aliasField('secondary_staff_id')
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
                            'InstitutionClassGrades.institution_class_id = ' . $institutionClassesSecondaryStaff->aliasField('institution_class_id')
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
                    ->count();

                /*Homeroom Staff Temporary*/
                $homeroomStaffData = $institutionClasses->find()
                    ->innerJoin(
                        ['InstitutionStaff' => 'institution_staff'],
                        [
                            'InstitutionStaff.staff_id = ' . $institutionClasses->aliasField('staff_id ')
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
                            'InstitutionClassGrades.institution_class_id = ' . $institutionClasses->aliasField('id')
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
                    ->count();
                $temporary_staff = $secondaryStaffData + $homeroomStaffData;

                //POCOR-7421  Jordian/Syrian Student Start
                $connection = ConnectionManager::get('default');
                $query = "SELECT institution_students.education_grade_id, IFNULL(COUNT(DISTINCT(student_nationalities.jordanian_students)), 0) jordanian_students, IFNULL(COUNT(DISTINCT(student_nationalities.syrian_students)), 0) syrian_students
                    FROM institution_students
                    LEFT JOIN
                    (
                        SELECT  user_nationalities.security_user_id, nationalities.name nationality_name, CASE WHEN nationalities.international_code = 'Jordan' THEN user_nationalities.security_user_id END jordanian_students, CASE WHEN nationalities.international_code = 'Syria' THEN user_nationalities.security_user_id END syrian_students
                        FROM user_nationalities
                        INNER JOIN nationalities
                            ON nationalities.id = user_nationalities.nationality_id
                        WHERE user_nationalities.preferred = 1 AND nationalities.international_code IN ('Jordan', 'Syria')
                        GROUP BY  user_nationalities.security_user_id
                    ) AS student_nationalities
                        ON student_nationalities.security_user_id = institution_students.student_id
                    INNER JOIN academic_periods
                        ON academic_periods.id = institution_students.academic_period_id
                    WHERE academic_periods.id = " . $params['academic_period_id'] . " AND institution_students.institution_id = " . $params['institution_id'] . "  AND institution_students.education_grade_id = " . $value['id'] . " AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
                    GROUP BY institution_students.education_grade_id";
                $result = $connection->execute($query)->fetch('assoc');
                $jordanian_students = isset($result['jordanian_students']) ? $result['jordanian_students'] : 0;
                $syrian_students = isset($result['syrian_students']) ? $result['syrian_students'] : 0;
                // POCOR-7421  Jordian/Syrian Student End
                //POCOR-7272 Male/Female/Total Student Promotion Rate Start
                $maleStudentPromoted = $InstitutionStudents->find()
                    ->contain('Users')
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->where([$InstitutionStudents->aliasField('student_status_id') => 7])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 1])
                    ->hydrate(false)
                    ->count();
                $femaleStudentPromoted = $InstitutionStudents->find()
                    ->contain('Users')
                    ->where([$InstitutionStudents->aliasField('education_grade_id') => $value['id']])
                    ->where([$InstitutionStudents->aliasField('institution_id') => $params['institution_id']])
                    ->where([$InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id']])
                    ->where([$InstitutionStudents->aliasField('student_status_id') => 7])
                    ->where([$InstitutionStudents->Users->aliasField('gender_id') => 2])
                    ->hydrate(false)
                    ->count();
                $totalStudentPromoted = $maleStudentPromoted + $femaleStudentPromoted;
                //POCOR-7272 Male/Female/Total Student Promotion Rate End
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
                    'syrian_students' => $syrian_students,//POCOR-7421
                    'jordanian_students' => $jordanian_students,//POCOR-7421
                    'male_student_promotion' => $maleStudentPromoted,//POCOR-7272
                    'female_student_promotion' => $femaleStudentPromoted,//POCOR-7272
                    'total_student_promotion' => $totalStudentPromoted//POCOR-7272
                ];
            }

            return $entity;
        }
    }
    //POCOR-7421 starts
    /*public function getLastYearStudentStatus($institutionId, $education_grade_id, $status){
        $LastYearConnection = ConnectionManager::get('default');
        $query = "SELECT education_grades.name education_grade_name, COUNT(DISTINCT(institution_students.student_id)) last_year_students_status
                FROM institution_students
                INNER JOIN institutions
                    ON institutions.id = institution_students.institution_id
                INNER JOIN education_grades
                    ON education_grades.id = institution_students.education_grade_id
                INNER JOIN academic_periods
                    ON academic_periods.id = institution_students.academic_period_id
                INNER JOIN 
                (
                    SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
                        ,@current_year_id
                    FROM
                    (
                        SELECT operational_academic_periods_1.academic_period_id, @previous_start_year := MAX(academic_periods.start_date) previous_start_year
                        FROM 
                        (
                            SELECT institution_students.academic_period_id
                            FROM institution_students
                            GROUP BY institution_students.academic_period_id
                        ) operational_academic_periods_1
                        INNER JOIN academic_periods
                            ON academic_periods.id = operational_academic_periods_1.academic_period_id
                        LEFT JOIN 
                        (
                            SELECT @current_year_id := academic_periods.id current_academic_periods_id, @current_start_year := academic_periods.start_date curent_start_date
                            FROM 
                            (
                                SELECT institution_students.academic_period_id
                                FROM institution_students
                                GROUP BY institution_students.academic_period_id
                            ) operational_academic_periods
                            INNER JOIN academic_periods
                                ON academic_periods.id = operational_academic_periods.academic_period_id
                            WHERE academic_periods.current = 1
                        ) t
                            ON t.current_academic_periods_id = @current_year_id
                        WHERE academic_periods.start_date < @current_start_year
                    ) subq
                    INNER JOIN
                    (
                        SELECT operational_academic_periods_1.academic_period_id, academic_periods.start_date start_year
                        FROM 
                        (
                            SELECT institution_students.academic_period_id
                            FROM institution_students
                            GROUP BY institution_students.academic_period_id
                        ) operational_academic_periods_1
                        INNER JOIN academic_periods
                            ON academic_periods.id = operational_academic_periods_1.academic_period_id
                        LEFT JOIN 
                        (
                            SELECT @current_year_id := academic_periods.id current_academic_periods_id, @current_start_year := academic_periods.start_date curent_start_date
                            FROM 
                            (
                                SELECT institution_students.academic_period_id
                                FROM institution_students
                                GROUP BY institution_students.academic_period_id
                            ) operational_academic_periods
                            INNER JOIN academic_periods
                                ON academic_periods.id = operational_academic_periods.academic_period_id
                            WHERE academic_periods.current = 1
                        ) t
                            ON t.current_academic_periods_id = @current_year_id
                        WHERE academic_periods.start_date < @current_start_year
                    ) previous_current_join
                        ON previous_current_join.start_year = @previous_start_year
                ) academic_period_info
                WHERE academic_periods.id = @previous_year_id AND institutions.id = ". $institutionId ." AND institution_students.education_grade_id = ". $education_grade_id ."  AND institution_students.student_status_id IN (".$status.") GROUP BY education_grades.id";
        $result=$LastYearConnection->execute($query)->fetch('assoc');
        $lastYearStudentStatus= !empty($result) ? $result['last_year_students_status'] : 0;
        return $lastYearStudentStatus;
    }*/ //POCOR-7421 ends

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
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
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

    public function getAreaNameByInstitution($institution_key, $institutionIds = [])
    {
        $areasTbl = TableRegistry::get('areas');
        $institutionsTbl = TableRegistry::get('institutions');
        if ($institution_key == 0) {

            $institutions = $institutionsTbl->find()
                ->select([
                    'area_name' => $areasTbl->aliasField('name')
                ])
                ->innerJoin(
                    [$areasTbl->alias() => $areasTbl->table()],
                    [
                        $areasTbl->aliasField('id') . ' = ' . $institutionsTbl->aliasField('area_id')
                    ]
                )
                ->where([$institutionsTbl->aliasField('id IN') => $institutionIds])
                ->first();
            return $institutions->area_name;

        } else if ($institution_key == 1) {
            $institutions = $institutionsTbl->find()
                ->select([
                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                ])
                ->innerJoin(
                    [$areasTbl->alias() => $areasTbl->table()],
                    [
                        $areasTbl->aliasField('id') . ' = ' . $institutionsTbl->aliasField('area_id')
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
        } else {
            $institutions = $institutionsTbl->find()
                ->select([
                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                ])
                ->innerJoin(
                    [$areasTbl->alias() => $areasTbl->table()],
                    [
                        $areasTbl->aliasField('id') . ' = ' . $institutionsTbl->aliasField('area_id')
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

    public function getStudentCountByStatus($academic_period, $education_grade_id, $institutionIds = [], $student_status_id)
    {
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
            ->count();
        return $InstitutionStudentsData;
    }

    //POCOR-8005
    public function getStudentCountByPromoteGraduateStatus($academic_period, $education_grade_id, $institutionIds = [], $student_status_id)
    {
        $studentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $promoStatus = $studentStatusesTable->find('all')
            ->where(['code IN' => ['PROMOTED', 'GRADUATED']])->toArray();
        $promotedStatusIds = [];
        foreach ($promoStatus as $value) {
            $promotedStatusIds[]['id'] = $value['id'];
        }
        $promotedStatus = array_column($promotedStatusIds, 'id');
        $InstitutionStudents = TableRegistry::get('institution_students');
        $InstitutionStudentsData = $InstitutionStudents->find()
            ->select([
                'student_id' => $InstitutionStudents->aliasField('student_id')
            ])
            ->where([
                $InstitutionStudents->aliasField('academic_period_id') => $academic_period,
                $InstitutionStudents->aliasField('education_grade_id') => $education_grade_id,
                $InstitutionStudents->aliasField('institution_id IN') => $institutionIds,
                $InstitutionStudents->aliasField('student_status_id IN') => $promotedStatus

            ])
            ->distinct(['student_id'])
            ->count();
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
            if ($areas->area_parent_id > 0) {
                $distArr[0][] = $institutions->area_id; //first time we get area_id
                $k = $areas->area_parent_id;
                for ($i = $areas->area_parent_id; $i >= 1; $i--) {
                    if ($k == '') {
                        break;
                    }
                    for ($j = 1; $j < $areaLevels; $j++) {
                        //get district's regions
                        $areas1 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->first();
                        if ($areas1->area_parent_id > 0) {
                            $areas2 = $areasTbl->find()
                                ->select([
                                    'area_id' => $areasTbl->aliasField('id'),
                                    'area_name' => $areasTbl->aliasField('name'),
                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                ])
                                ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                                ->toArray();

                            if (!empty($areas2)) {
                                foreach ($areas2 as $ar2) {
                                    $areas5 = $areasTbl->find()
                                        ->select([
                                            'area_id' => $areasTbl->aliasField('id'),
                                            'area_name' => $areasTbl->aliasField('name'),
                                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                                        ])
                                        ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                        ->toArray();
                                    if (!empty($areas5)) {
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array
                                        }
                                    } else {
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

                            if (!empty($areas3)) {
                                $reg = [];
                                foreach ($areas3 as $ar3) {
                                    $reg [] = $ar3->area_id;
                                }
                                if (!empty($reg)) {
                                    $areas4 = $areasTbl->find()
                                        ->select([
                                            'area_id' => $areasTbl->aliasField('id'),
                                            'area_name' => $areasTbl->aliasField('name'),
                                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                                        ])
                                        ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                        ->toArray();
                                    if (!empty($areas4)) {
                                        foreach ($areas4 as $ar4) {
                                            $areas6 = $areasTbl->find()
                                                ->select([
                                                    'area_id' => $areasTbl->aliasField('id'),
                                                    'area_name' => $areasTbl->aliasField('name'),
                                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                ])
                                                ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                ->toArray();
                                            if (!empty($areas6)) {
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array
                                                }
                                            } else {
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

            if (!empty($distArr)) {
                $insArr = [];
                $i = 0;
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
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            $InstitutionStudents = TableRegistry::get('institution_students');
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $enrolledStatus);
                        }
                    }
                }


                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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
            if ($areas->area_parent_id > 0) {
                $distArr[0][] = $institutions->area_id; //first time we get area_id
                $k = $areas->area_parent_id;
                for ($i = $areas->area_parent_id; $i >= 1; $i--) {
                    if ($k == '') {
                        break;
                    }
                    for ($j = 1; $j < $areaLevels; $j++) {
                        //get district's regions
                        $areas1 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->first();
                        if ($areas1->area_parent_id > 0) {
                            $areas2 = $areasTbl->find()
                                ->select([
                                    'area_id' => $areasTbl->aliasField('id'),
                                    'area_name' => $areasTbl->aliasField('name'),
                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                ])
                                ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                                ->toArray();

                            if (!empty($areas2)) {
                                foreach ($areas2 as $ar2) {
                                    $areas5 = $areasTbl->find()
                                        ->select([
                                            'area_id' => $areasTbl->aliasField('id'),
                                            'area_name' => $areasTbl->aliasField('name'),
                                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                                        ])
                                        ->where([$areasTbl->aliasField('parent_id') => $ar2->area_id])
                                        ->toArray();
                                    if (!empty($areas5)) {
                                        foreach ($areas5 as $ar5) {
                                            $distArr[$j][] = $ar5->area_id;//district array
                                        }
                                    } else {
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

                            if (!empty($areas3)) {
                                $reg = [];
                                foreach ($areas3 as $ar3) {
                                    $reg [] = $ar3->area_id;
                                }
                                if (!empty($reg)) {
                                    $areas4 = $areasTbl->find()
                                        ->select([
                                            'area_id' => $areasTbl->aliasField('id'),
                                            'area_name' => $areasTbl->aliasField('name'),
                                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                                        ])
                                        ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                        ->toArray();
                                    if (!empty($areas4)) {
                                        foreach ($areas4 as $ar4) {
                                            $areas6 = $areasTbl->find()
                                                ->select([
                                                    'area_id' => $areasTbl->aliasField('id'),
                                                    'area_name' => $areasTbl->aliasField('name'),
                                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                                ])
                                                ->where([$areasTbl->aliasField('parent_id') => $ar4->area_id])
                                                ->toArray();
                                            if (!empty($areas6)) {
                                                foreach ($areas6 as $ar6) {
                                                    $distArr[$j][] = $ar6->area_id;//district array
                                                }
                                            } else {
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

            if (!empty($distArr)) {
                $insArr = [];
                $i = 0;
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
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            $InstitutionStudents = TableRegistry::get('institution_students');
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $withdrawnStatus);
                        }
                    }
                }


                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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
            if ($areas->area_parent_id > 0) {
                $distArr[0][] = $institutions->area_id; //first time we get area_id
                $k = $areas->area_parent_id;
                for ($i = $areas->area_parent_id; $i >= 1; $i--) {
                    if ($k == '') {
                        break;
                    }
                    for ($j = 1; $j < $areaLevels; $j++) {
                        //get district's regions
                        $areas1 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->first();
                        if ($areas1->area_parent_id > 0) {
                            $areas2 = $areasTbl->find()
                                ->select([
                                    'area_id' => $areasTbl->aliasField('id'),
                                    'area_name' => $areasTbl->aliasField('name'),
                                    'area_parent_id' => $areasTbl->aliasField('parent_id')
                                ])
                                ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                                ->toArray();
                            if (!empty($areas2)) {
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

                            if (!empty($areas3)) {
                                $reg = [];
                                foreach ($areas3 as $ar3) {
                                    $reg [] = $ar3->area_id;
                                }

                                if (!empty($reg)) {
                                    $areas4 = $areasTbl->find()
                                        ->select([
                                            'area_id' => $areasTbl->aliasField('id'),
                                            'area_name' => $areasTbl->aliasField('name'),
                                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                                        ])
                                        ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                        ->toArray();
                                    if (!empty($areas4)) {
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

            if (!empty($distArr)) {
                $insArr = [];
                $i = 0;
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
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            $InstitutionStudents = TableRegistry::get('institution_students');
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $transferredStatus);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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
            // POCOR-8073 Unify all
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);

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
                ->where([$InstitutionGrades->aliasField('institution_id') => $institution_id])
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        } else {
                            $area_level_7 = $this->getStaffCountByArea($params['academic_period_id'], $edu_val['id'], $insVal);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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

    public function getStaffCountByArea($academic_period, $education_grade_id, $institutionIds = [])
    {
        $institution_id = implode(',', $institutionIds);
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
                ->toArray();

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
            // POCOR-8073 Unify all
            $academic_period_id = $params['academic_period_id'];
            $institution_id = $params['institution_id'];

            $insArr = $this->getDistrictInstitutionArray($institution_id);

            $RoomTypes = TableRegistry::get('room_types');
            $RoomTypeData = $RoomTypes->find()
                ->select([
                    $RoomTypes->aliasField('id'),
                    $RoomTypes->aliasField('name')
                ])
                ->hydrate(false)
                ->toArray();

            $addRoomheading[] = [
                'id' => 0,
                'name' => 'Room Type'
            ];

            $RoomTypeData = array_merge($addRoomheading, $RoomTypeData);
            foreach ($RoomTypeData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {

                        if ($insKey == 0) {
                            $area_level_1 = $this->getRoomCountByArea($academic_period_id
                                , $edu_val['id'], $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getRoomCountByArea($academic_period_id, $edu_val['id'], $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getRoomCountByArea($academic_period_id, $edu_val['id'], $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getRoomCountByArea($academic_period_id, $edu_val['id'], $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getRoomCountByArea($academic_period_id, $edu_val['id'], $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getRoomCountByArea($academic_period_id, $edu_val['id'], $insVal);
                        } else {
                            $area_level_7 = $this->getRoomCountByArea($academic_period_id, $edu_val['id'], $insVal);
                        }
                    }
                }
                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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

    public function getRoomCountByArea($academic_period, $room_type_id, $institutionIds = [])
    {
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
            ->count();
        return $institutionRoomsData;
    }
    //POCOR-6426 ends

    //POCOR-6481 starts
    public function getAreaName($institution_id)
    {
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
        if ($areas->area_parent_id > 0) {
            $areaLevelArr[0][] = $institutions->area_id; //first time we get area_id
            $k = $areas->area_parent_id;
            for ($i = $areas->area_parent_id; $i >= 1; $i--) {
                if ($k == '') {
                    break;
                }
                for ($j = 1; $j < $areaLevels; $j++) {
                    //get district's regions
                    $areas1 = $areasTbl->find()
                        ->select([
                            'area_id' => $areasTbl->aliasField('id'),
                            'area_name' => $areasTbl->aliasField('name'),
                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                        ])
                        ->where([$areasTbl->aliasField('id') => $k])
                        ->first();
                    if ($areas1->area_parent_id > 0) {
                        $areas2 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('id') => $areas1->area_id])
                            ->toArray();
                        if (!empty($areas2)) {
                            foreach ($areas2 as $ar2) {
                                $areaLevelArr[$j][] = $ar2->area_id;//district array
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
                            ->where([$areasTbl->aliasField('id') => $k])
                            ->toArray();

                        if (!empty($areas3)) {
                            $reg = [];
                            foreach ($areas3 as $ar3) {
                                $reg [] = $ar3->area_id;
                            }

                            if (!empty($reg)) {
                                $areas4 = $areasTbl->find()
                                    ->select([
                                        'area_id' => $areasTbl->aliasField('id'),
                                        'area_name' => $areasTbl->aliasField('name'),
                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                    ])
                                    ->where([$areasTbl->aliasField('id IN') => $reg])
                                    ->toArray();
                                if (!empty($areas4)) {
                                    foreach ($areas4 as $ar4) {
                                        $areaLevelArr[$j][] = $ar4->area_id;//district array
                                    }
                                }
                            }
                        }
                    }
                    $k = $areas1->area_parent_id;
                }
            }
        }
        $levelArr = [];
        if (!empty($areaLevelArr)) {
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
// POCOR-8073 Unify all
            $academic_period_id = $params['academic_period_id'];
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);


            //get area names
            $AreaNameData = $this->getAreaName($institution_id);
            foreach ($AreaNameData as $area_key => $area_val) {
                $area_level = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($area_key == $insKey) {

                        $area_level = $this->getNonTeachingStaffCountByArea($academic_period_id, $insVal);
                        break;
                    }
                }
                $entity[] = [
                    'id' => $area_val,
                    'area_level' => $area_level,
                ];
            }

            $totalArray = [
                'id' => count($entity) + 1,
                'name' => '',
            ];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function getNonTeachingStaffCountByArea($academic_period, $institutionIds = [])
    {
        $institution_id = implode(',', $institutionIds);
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
    public function onExcelTemplateInitialiseInstitutionCustomFields(Event $event, array $params, ArrayObject $extra)
    {
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
                        $InstitutionCustomFields->aliasField('id =') . $InstitutionCustomFieldValues->aliasField('institution_custom_field_id')
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
    public function onExcelTemplateInitialiseInstitutionCustomFieldValues(Event $event, array $params, ArrayObject $extra)
    {
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
                        $InstitutionCustomFields->aliasField('id =') . $InstitutionCustomFieldValues->aliasField('institution_custom_field_id')
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
            if (!empty($field_arr)) {
                foreach ($field_arr as $field_key => $field_val) {
                    $result[$field_key]['id'] = $field_val[0]['id'];
                    if ($field_val[0]['field_type'] == 'CHECKBOX') {
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
                    } else if ($field_val[0]['field_type'] == 'TEXT') {
                        $result[$field_key]['name'] = !empty($field_val[0]['text_value']) ? $field_val[0]['text_value'] : ' ';
                    } else if ($field_val[0]['field_type'] == 'NUMBER') {
                        $result[$field_key]['name'] = !empty($field_val[0]['number_value']) ? $field_val[0]['number_value'] . ' ' : '0 ';
                    } else if ($field_val[0]['field_type'] == 'DECIMAL') {
                        $result[$field_key]['name'] = !empty($field_val[0]['decimal_value']) ? $field_val[0]['decimal_value'] . ' ' : '0.00 ';
                    } else if ($field_val[0]['field_type'] == 'TEXTAREA') {
                        $result[$field_key]['name'] = !empty($field_val[0]['textarea_value']) ? $field_val[0]['textarea_value'] : '';
                    } else if ($field_val[0]['field_type'] == 'DROPDOWN') {
                        $check_data = $institutionCustomFieldOptions
                            ->find()
                            ->select([
                                'name' => $institutionCustomFieldOptions->aliasField('name')
                            ])
                            ->where([$institutionCustomFieldOptions->aliasField('id IN') => $field_val[0]['number_value']])
                            ->hydrate(false)
                            ->toArray();
                        $result[$field_key]['name'] = !empty($check_data[0]['name']) ? $check_data[0]['name'] : '';
                    } else if ($field_val[0]['field_type'] == 'DATE') {
                        $result[$field_key]['name'] = !empty($field_val[0]['date_value']) ? date("Y-m-d", strtotime($field_val[0]['date_value'])) : '';
                    } else if ($field_val[0]['field_type'] == 'TIME') {
                        $result[$field_key]['name'] = !empty($field_val[0]['time_value']) ? date("H: i: s", strtotime($field_val[0]['time_value'])) : '';
                    } else if ($field_val[0]['field_type'] == 'COORDINATES') {
                        if (!empty($field_val[0]['text_value'])) {
                            $cordinate = json_decode($field_val[0]['text_value'], true);
                            $result[$field_key]['name'] = 'latitude: ' . $cordinate['latitude'] . ', longitude: ' . $cordinate['longitude'];
                        } else {
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
    public function getInstitutionCustomFieldValues($institution_id, $institution_custom_field_id)
    {
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
                    $InstitutionCustomFields->aliasField('id =') . $InstitutionCustomFieldValues->aliasField('institution_custom_field_id')
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
    public function onExcelTemplateInitialiseReportStudentAssessmentSummary(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $ReportStudentAssessmentSummary = TableRegistry::get('report_student_assessment_summary');
            $AssessmentSummaryData = $ReportStudentAssessmentSummary->find()
                ->select([
                    //'id' => $ReportStudentAssessmentSummary->aliasField('id'),
                    'academic_period_code' => $ReportStudentAssessmentSummary->aliasField('academic_period_code'),
                    'academic_period_name' => $ReportStudentAssessmentSummary->aliasField('academic_period_name'),
                    'area_code' => $ReportStudentAssessmentSummary->aliasField('area_code'),
                    'area_name' => $ReportStudentAssessmentSummary->aliasField('area_name'),
                    'institution_code' => $ReportStudentAssessmentSummary->aliasField('institution_code'),
                    'institution_name' => $ReportStudentAssessmentSummary->aliasField('institution_name'),
                    'grade_code' => $ReportStudentAssessmentSummary->aliasField('grade_code'),
                    'grade_name' => $ReportStudentAssessmentSummary->aliasField('grade_name'),
                    'subject_code' => $ReportStudentAssessmentSummary->aliasField('subject_code'),
                    'subject_name' => $ReportStudentAssessmentSummary->aliasField('subject_name'),
                    'subject_weight' => $ReportStudentAssessmentSummary->aliasField('subject_weight'),
                    'assessment_code' => $ReportStudentAssessmentSummary->aliasField('assessment_code'),
                    'assessment_name' => $ReportStudentAssessmentSummary->aliasField('assessment_name'),
                    'period_code' => $ReportStudentAssessmentSummary->aliasField('period_code'),
                    'period_name' => $ReportStudentAssessmentSummary->aliasField('period_name'),
                    'period_weight' => $ReportStudentAssessmentSummary->aliasField('period_weight'),
                    'average_marks' => $ReportStudentAssessmentSummary->aliasField('average_mark')//POCOR-6708-alter column name as per table column average_mark
                ])
                ->where([$ReportStudentAssessmentSummary->aliasField('institution_id') => $params['institution_id']])
                ->where([$ReportStudentAssessmentSummary->aliasField('academic_period_id') => $params['academic_period_id']])
                ->hydrate(false)
                ->toArray();
            $entity = [];
            if (empty($AssessmentSummaryData)) {
                return $entity;
            }

            foreach ($AssessmentSummaryData as $e_key => $e_val) {
                $entity[] = [
                    'id' => $e_key,
                    'academic_period_code' => (!empty($e_val['academic_period_code']) ? $e_val['academic_period_code'] : ''),
                    'academic_period_name' => (!empty($e_val['academic_period_name']) ? $e_val['academic_period_name'] : ''),
                    'area_code' => (!empty($e_val['area_code']) ? $e_val['area_code'] : ''),
                    'area_name' => (!empty($e_val['area_name']) ? $e_val['area_name'] : ''),
                    'institution_code' => (!empty($e_val['institution_code']) ? $e_val['institution_code'] : ''),
                    'institution_name' => (!empty($e_val['institution_name']) ? $e_val['institution_name'] : ''),
                    'grade_code' => (!empty($e_val['grade_code']) ? $e_val['grade_code'] : ''),
                    'grade_name' => (!empty($e_val['grade_name']) ? $e_val['grade_name'] : ''),
                    'subject_code' => (!empty($e_val['subject_code']) ? $e_val['subject_code'] : ''),
                    'subject_name' => (!empty($e_val['subject_name']) ? $e_val['subject_name'] : ''),
                    'subject_weight' => (!empty($e_val['subject_weight']) ? $e_val['subject_weight'] : ''),
                    'assessment_code' => (!empty($e_val['assessment_code']) ? $e_val['assessment_code'] : ''),
                    'assessment_name' => (!empty($e_val['assessment_name']) ? $e_val['assessment_name'] : ''),
                    'period_code' => (!empty($e_val['period_code']) ? $e_val['period_code'] : ''),
                    'period_name' => (!empty($e_val['period_name']) ? $e_val['period_name'] : ''),
                    'period_weight' => (!empty($e_val['period_weight']) ? $e_val['period_weight'] : ''),
                    'average_marks' => (!empty($e_val['average_marks']) ? $e_val['average_marks'] . ' ' : '')
                ];
            }
            return $entity;
        }
    }

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
                ->toArray();

            $entity = [];
            if (empty($InstitutionRoomsData)) {
                return $entity;
            }
            $i = 0;
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
                if (!empty($RoomCustomFieldValuesData)) {
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
                } else {
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
    public function getInfrastructureRoomCustomFieldValues($room_id, $room_custom_field_id)
    {
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
        if (!empty($RoomCustomFieldValues)) {
            foreach ($RoomCustomFieldValues as $field_key => $field_val) {
                if ($field_val[0]['field_type'] == 'CHECKBOX') {
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
                } else if ($field_val[0]['field_type'] == 'TEXT') {
                    $result['name'] = !empty($field_val[0]['text_value']) ? $field_val[0]['text_value'] : ' ';
                } else if ($field_val[0]['field_type'] == 'NUMBER') {
                    $result['name'] = !empty($field_val[0]['number_value']) ? $field_val[0]['number_value'] . ' ' : '0 ';
                } else if ($field_val[0]['field_type'] == 'DECIMAL') {
                    $result['name'] = !empty($field_val[0]['decimal_value']) ? $field_val[0]['decimal_value'] . ' ' : '0.00 ';
                } else if ($field_val[0]['field_type'] == 'TEXTAREA') {
                    $result['name'] = !empty($field_val[0]['textarea_value']) ? $field_val[0]['textarea_value'] : '';
                } else if ($field_val[0]['field_type'] == 'DROPDOWN') {
                    $check_data = $InfrastructureCustomFieldOptions
                        ->find()
                        ->select([
                            'name' => $InfrastructureCustomFieldOptions->aliasField('name')
                        ])
                        ->where([$InfrastructureCustomFieldOptions->aliasField('id IN') => $field_val[0]['number_value']])
                        ->hydrate(false)
                        ->toArray();
                    $result['name'] = !empty($check_data[0]['name']) ? $check_data[0]['name'] : '';
                } else if ($field_val[0]['field_type'] == 'DATE') {
                    $result['name'] = !empty($field_val[0]['date_value']) ? date("Y-m-d", strtotime($field_val[0]['date_value'])) : '';
                } else if ($field_val[0]['field_type'] == 'TIME') {
                    $result['name'] = !empty($field_val[0]['time_value']) ? date("H: i: s", strtotime($field_val[0]['time_value'])) : '';
                } else if ($field_val[0]['field_type'] == 'COORDINATES') {
                    if (!empty($field_val[0]['text_value'])) {
                        $cordinate = json_decode($field_val[0]['text_value'], true);
                        $result['name'] = 'latitude: ' . $cordinate['latitude'] . ', longitude: ' . $cordinate['longitude'];
                    } else {
                        $result['name'] = '';
                    }
                }
            }
        }
        return $result['name'];
    }//POCOR-6519 ends

    /**
     * fetching data to display on institution profile generated report
     * @return array
     * @ticket POCOR-6646
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     */
    public function onExcelTemplateInitialiseStudentDetails(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $Users = TableRegistry::get('User.Users');
            $studentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $studentAbsencesDay = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
            $studentAssessmentSummary = TableRegistry::get('report_student_assessment_summary');
            $userIdentity = TableRegistry::get('User.Identities');
            $studentData = $studentAssessmentSummary->find()
                ->select([
                    'openemis_no' => $Users->aliasField('openemis_no'),
                    'student_name' => $studentAssessmentSummary->aliasField('student_name'),
                    'grade_name' => $studentAssessmentSummary->aliasField('grade_name'),
                    'class_name' => $studentAssessmentSummary->aliasField('institution_classes_name'),
                    'subject_name' => $studentAssessmentSummary->aliasField('subject_name'),
                    'homeroom_teacher' => $studentAssessmentSummary->aliasField('homeroom_teacher_name'),
                    'individual_result' => $studentAssessmentSummary->aliasField('latest_mark'),
                    'avg_marks' => $studentAssessmentSummary->aliasField('average_mark'),
                    'student_id' => $studentAssessmentSummary->aliasField('student_id'),
                    'institution_average_mark' => $studentAssessmentSummary->aliasField('institution_average_mark'),//POCOR-6742- added new column into the report
                    'area_average_mark' => $studentAssessmentSummary->aliasField('area_average_mark'),//POCOR-6742- added new column into the report
                ])
                ->innerJoin([$Users->alias() => $Users->table()], [
                    $studentAssessmentSummary->aliasField('student_id =') . $Users->aliasField('id')
                ])
                ->order([$studentAssessmentSummary->aliasField('student_name')])
                ->where([
                    $studentAssessmentSummary->aliasField('academic_period_id') => $params['academic_period_id'],
                    $studentAssessmentSummary->aliasField('institution_id') => $params['institution_id']
                ])
                ->hydrate(false)
                ->toArray();

            $result = [];
            $entity = [];
            if (!empty($studentData)) {
                foreach ($studentData as $key => $data) {
                    $identityObj = $userIdentity->find()
                        ->select(['identity_number' => $userIdentity->aliasField('number')])
                        ->leftJoin(['IdentityTypes' => 'identity_types'], [
                            'IdentityTypes.id = ' . $userIdentity->aliasField('identity_type_id'),
                            'IdentityTypes.default =' . 1
                        ])
                        ->where([$userIdentity->aliasField('security_user_id') => $data['student_id']])
                        ->hydrate(false)
                        ->first();
                    $absenceDays = $studentAbsencesDay->find()
                        ->select(['absent_days' => $studentAbsencesDay->aliasField('absent_days')])
                        ->where([
                            $studentAbsencesDay->aliasField('institution_id') => $params['institution_id'],
                            $studentAbsencesDay->aliasField('student_id') => $data['student_id']
                        ])
                        ->hydrate(false)
                        ->toArray();
                    $absenceDaysArr = [];
                    if (!empty($absenceDays)) {
                        foreach ($absenceDays as $days) {
                            $absenceDaysArr[] = $days['absent_days'];
                        }
                    }
                    $absenceDaysCount = array_sum($absenceDaysArr);
                    $result = [
                        'id' => $key,
                        'grade_name' => !empty($data['grade_name']) ? $data['grade_name'] : '',
                        'openemis_no' => !empty($data['openemis_no']) ? $data['openemis_no'] : '',
                        'identity_number' => !empty($identityObj['identity_number']) ? $identityObj['identity_number'] : '',
                        'student_name' => $data['student_name'],
                        'class_name' => !empty($data['class_name']) ? $data['class_name'] : '',
                        'subject_name' => !empty($data['subject_name']) ? $data['subject_name'] : '',
                        'homeroom_teacher' => !empty($data['homeroom_teacher']) ? $data['homeroom_teacher'] : '',
                        'absence_day' => !empty($absenceDaysCount) ? $absenceDaysCount : 0,
                        'individual_result' => !empty($data['individual_result']) ? $data['individual_result'] : 0,
                        'average_marks' => $data['avg_marks'],
                        'institution_average_mark' => $data['institution_average_mark'],//POCOR-6742- added new column into the report
                        'area_average_mark' => $data['area_average_mark'],//POCOR-6742- added new column into the report
                    ];
                    $entity[] = $result;
                }
            }

            return $entity;
        }
    }
    /*POCOR-6646 ends*/
    /*
     * Fetching total count of repeated students
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6691
    */
    public function onExcelTemplateInitialiseInstitutionStudentRepeater(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $repeatedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('REPEATED')->first()->id;// for REPEATED status
// POCOR-8073 Unify all
            $academic_period_id = $params['academic_period_id'];
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);

            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $academic_period_id
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institution_id])
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);

            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountByStatus($params['academic_period_id'], $edu_val['id'], $insVal, $repeatedStatus);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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
     * @return array
     * @ticket POCOR-6691
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
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
     * @return array
     * @ticket POCOR-6691
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     */
    public function onExcelTemplateInitialiseInstitutionRoomsArea(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
// POCOR-8073 Unify all
            $academic_period_id = $params['academic_period_id'];
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);

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
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getRoomCountByAreaCol($academic_period_id, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getRoomCountByAreaCol($academic_period_id, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getRoomCountByAreaCol($academic_period_id, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getRoomCountByAreaCol($academic_period_id, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getRoomCountByAreaCol($academic_period_id, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getRoomCountByAreaCol($academic_period_id, $insVal);
                        } else {
                            $area_level_7 = $this->getRoomCountByAreaCol($academic_period_id, $insVal);
                        }
                    }
                }
                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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
     * @return array
     * @ticket POCOR-6691
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     */
    public function getRoomCountByAreaCol($academic_period, $institutionIds = [])
    {
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

    /*
     * Get institutions occupier and owner name
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-7328
    */
    public function onExcelTemplateInitialiseInstitutionOwnerOccupier(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $OwnerOccupierData[] = $connection->execute("SELECT MAX(`OwnerInfo`.`name`) AS `owner_name`, MAX(`OccupierInfo`.`name`) AS `occupier_name` FROM `institution_shifts` `institution_shifts` LEFT JOIN `institutions` `OwnerInfo` ON `OwnerInfo`.`id` = `institution_shifts`.`institution_id` LEFT JOIN `institutions` `OccupierInfo` ON `OccupierInfo`.`id` = `institution_shifts`.`location_institution_id` WHERE ((`institution_shifts`.`institution_id` = " . $params['institution_id'] . " OR `institution_shifts`.`location_institution_id` = " . $params['institution_id'] . ") AND `institution_shifts`.`academic_period_id` = " . $params['academic_period_id'] . " AND `institution_shifts`.`institution_id` != `institution_shifts`.`location_institution_id`) GROUP BY `institution_shifts`.`institution_id`, `institution_shifts`.`location_institution_id`")->fetch('assoc');

            $entity = $result = [];
            if (!empty($OwnerOccupierData)) {
                foreach ($OwnerOccupierData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'owner_name' => !empty($data['owner_name']) ? $data['owner_name'] : '',
                        'occupier_name' => !empty($data['occupier_name']) ? $data['occupier_name'] : '',
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }

    /*
     * Fetching total count of promoted students
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-7328
    */
    public function onExcelTemplateInitialiseInstitutionStudentPromoted(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $promotedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('PROMOTED')->first()->id;// for PROMOTED status
// POCOR-8073 Unify all
            $academic_period_id = $params['academic_period_id'];
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);

            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $academic_period_id
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institution_id])
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
                'name' => 'Grade'
            ];

            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            $InstitutionStudents = TableRegistry::get('institution_students');
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountByPromoteGraduateStatus($academic_period_id, $edu_val['id'], $insVal, $promotedStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountByPromoteGraduateStatus($academic_period_id, $edu_val['id'], $insVal, $promotedStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountByPromoteGraduateStatus($academic_period_id, $edu_val['id'], $insVal, $promotedStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountByPromoteGraduateStatus($academic_period_id, $edu_val['id'], $insVal, $promotedStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountByPromoteGraduateStatus($academic_period_id, $edu_val['id'], $insVal, $promotedStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountByPromoteGraduateStatus($academic_period_id, $edu_val['id'], $insVal, $promotedStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountByPromoteGraduateStatus($academic_period_id, $edu_val['id'], $insVal, $promotedStatus);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
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
    //POCOR-7378 starts  Note: this function is not for use now becuase of POCOR-7421
    /*public function onExcelTemplateInitialiseInstitutionEducationProgramme(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $EducationProgrameData = $connection->execute("SELECT academic_periods.id period_id,academic_periods.name period_name,academic_periods.code period_code,education_grades.id grade_id, education_grades.name grade_name, education_programmes.id programme_id, education_programmes.name programme_name FROM education_grades
                INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
                INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                WHERE academic_periods.id = ".$params['academic_period_id']." GROUP BY `education_programmes`.`id`
                ORDER BY academic_periods.order ASC,education_levels.order ASC,education_cycles.order ASC,education_programmes.order ASC,education_grades.order ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $entity = $result = [];
            if (!empty($EducationProgrameData)) {
               foreach ($EducationProgrameData as $data) {
                    $result = [
                        'programme_id' => !empty($data['programme_id']) ? $data['programme_id'] : '',
                        'programme_name' => !empty($data['programme_name']) ? $data['programme_name'] : '',
                    ];
                    $entity[] = $result;
               }
            }
            return $entity;
        }
    }*/
    //POCOR-7449 Starts
    public function onExcelTemplateInitialiseStaffFromEducationProgramme(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $StaffFromEducationProgrammeData = $connection->execute("SELECT education_grades.education_programme_id, education_programmes.name programme_name, COUNT(DISTINCT(CASE WHEN security_users.gender_id = 1 THEN institution_subject_staff.staff_id END)) male_teaching_staff, COUNT(DISTINCT(CASE WHEN security_users.gender_id = 2 THEN institution_subject_staff.staff_id END)) female_teaching_staff, COUNT(DISTINCT(CASE WHEN security_users.gender_id IN (1, 2) THEN institution_subject_staff.staff_id END)) total_teaching_staff, COUNT(DISTINCT(CASE WHEN fulltime_staff.staff_id IS NOT NULL AND fulltime_staff.full_staff_positions < 1 AND fulltime_staff.temp_staff_positions > 0 THEN institution_subject_staff.staff_id END)) temp_teaching_staff
                FROM institution_subject_staff
                INNER JOIN institution_subjects
                    ON institution_subjects.id = institution_subject_staff.institution_subject_id
                INNER JOIN education_grades
                    ON education_grades.id = institution_subjects.education_grade_id
                INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                INNER JOIN security_users
                    ON security_users.id = institution_subject_staff.staff_id
                INNER JOIN 
                (
                    SELECT institution_staff.staff_id
                    FROM institution_staff
                    INNER JOIN institution_positions
                        ON institution_positions.id = institution_staff.institution_position_id
                    INNER JOIN staff_position_titles 
                        ON staff_position_titles.id = institution_positions.staff_position_title_id
                    INNER JOIN academic_periods
                        ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                    WHERE institution_staff.institution_id = " . $params['institution_id'] . " AND staff_position_titles.type = 1 AND academic_periods.id = " . $params['academic_period_id'] . " AND institution_staff.staff_status_id = 1
                    GROUP BY institution_staff.staff_id
                ) teaching_staff_info
                    ON teaching_staff_info.staff_id = institution_subject_staff.staff_id
                LEFT JOIN 
                (
                    SELECT institution_staff.staff_id, SUM(CASE WHEN staff_types.international_code = 'temporary' THEN 1 ELSE 0 END) temp_staff_positions, SUM(CASE WHEN staff_types.international_code != 'temporary' OR staff_types.international_code IS NULL THEN 1 ELSE 0 END) full_staff_positions, staff_types.international_code
                    FROM institution_staff
                    INNER JOIN staff_types
                        ON staff_types.id = institution_staff.staff_type_id
                    INNER JOIN academic_periods
                        ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                    WHERE institution_staff.institution_id = " . $params['institution_id'] . " AND academic_periods.id = " . $params['academic_period_id'] . " AND institution_staff.staff_status_id = 1
                    GROUP BY institution_staff.staff_id
                ) fulltime_staff
                    ON fulltime_staff.staff_id = institution_subject_staff.staff_id
                WHERE institution_subject_staff.institution_id = " . $params['institution_id'] . " AND institution_subjects.academic_period_id = " . $params['academic_period_id'] . " 
                GROUP BY education_grades.education_programme_id")->fetchAll(\PDO::FETCH_ASSOC);
            $entity = $result = [];
            if (!empty($StaffFromEducationProgrammeData)) {
                foreach ($StaffFromEducationProgrammeData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'programme_id' => !empty($data['education_programme_id']) ? $data['education_programme_id'] : '',
                        'programme_name' => !empty($data['programme_name']) ? $data['programme_name'] : '',
                        'subject_staff' => !empty($data['total_teaching_staff']) ? $data['total_teaching_staff'] : 0,
                        'male_subject_staff' => !empty($data['male_teaching_staff']) ? $data['male_teaching_staff'] : 0,
                        'female_subject_staff' => !empty($data['female_teaching_staff']) ? $data['female_teaching_staff'] : 0,
                        'subject_staff_type_temporary' => !empty($data['temp_teaching_staff']) ? $data['temp_teaching_staff'] : 0,
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }//POCOR-7449 ends

    //POCOR-7411 Starts
    public function onExcelTemplateInitialiseJordonSchoolShifts(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $JordonSchoolShiftData = $connection->execute("SELECT IF(shift_data.shift_type IS NULL, 'لا يوجد فترة', IF( shift_data.shift_type = '','Other Shifts', shift_data.shift_type)) jordan_shift FROM institutions
                    LEFT JOIN(
                        SELECT institutions.id institutions_id, institutions.code institutions_code, institutions.name institutions_name,
                            IF( institution_owner.owner_type IS NULL OR institution_owner.owner_type = '', institution_occupier.occupier_type,
                                institution_owner.owner_type ) shift_type
                        FROM
                            institutions
                        LEFT JOIN(
                            SELECT institution_shifts.location_institution_id,
                                IF( COUNT(*) = 1 AND institution_shifts.institution_id != institution_shifts.location_institution_id AND institution_shifts.shift_option_id = 2, 'فترة مسائية', '') occupier_type
                            FROM
                                institution_shifts
                            WHERE
                                institution_shifts.academic_period_id = " . $params['academic_period_id'] . "
                            GROUP BY
                                institution_shifts.location_institution_id
                        ) institution_occupier
                    ON
                        institution_occupier.location_institution_id = institutions.id
                    LEFT JOIN(
                        SELECT institution_shifts.*,
                            IF(COUNT(*) = 1 AND institution_shifts.institution_id = institution_shifts.location_institution_id AND institution_shifts.shift_option_id = 1, 'فترة واحدة',
                                IF( COUNT(*) = 2 AND COUNT( DISTINCT( institution_shifts.location_institution_id )) = 2,
                                    'فترة صباحية','')) owner_type
                        FROM
                            institution_shifts
                        WHERE
                            institution_shifts.academic_period_id = " . $params['academic_period_id'] . "
                        GROUP BY
                            institution_shifts.institution_id
                    ) institution_owner
                    ON
                        institution_owner.institution_id = institutions.id
                    WHERE
                        institutions.classification = 1
                    GROUP BY
                        institutions.id
                    ) shift_data
                    ON
                        shift_data.institutions_id = institutions.id
                    WHERE
                        institutions.id = " . $params['institution_id'] . "")->fetch('assoc');

            return !empty($JordonSchoolShiftData) ? $JordonSchoolShiftData : '';
        }
    }

    public function onExcelTemplateInitialiseTotalNonTeachingStaffs(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT COUNT(DISTINCT(subq.staff_id)) non_teaching_staff_count
                            FROM ( SELECT institution_staff.staff_id, SUM(CASE WHEN staff_position_titles.type = 0 THEN 1 ELSE 0 END) non_teaching_count, SUM(CASE WHEN staff_position_titles.type = 1 THEN 1 ELSE 0 END) teaching_count
                                FROM institution_staff
                                INNER JOIN academic_periods
                                    ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                                INNER JOIN institution_positions
                                    ON institution_positions.id = institution_staff.institution_position_id
                                INNER JOIN staff_position_titles
                                    ON staff_position_titles.id = institution_positions.staff_position_title_id
                                WHERE institution_staff.institution_id = " . $params['institution_id'] . " AND academic_periods.id = " . $params['academic_period_id'] . " AND institution_staff.staff_status_id = 1
                                GROUP BY institution_staff.staff_id
                            ) subq
                            WHERE subq.teaching_count = 0 AND subq.non_teaching_count > 0")->fetch();
            return !empty($entity[0]) ? $entity[0] : " 0";
        }
    }//POCOR-7411 ends

    //POCOR-7421 Starts
    public function onExcelTemplateInitialiseInfrastructureLandCustomFields(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $InfrastructureLandCustomFieldsData = $connection->execute("SELECT MAX(land_info.institution_land_code) institution_land_code, MAX(land_info.institution_land_name) institution_land_name, MAX(land_info.land_area) land_area, MAX(land_info.land_type_name) land_type_name, MAX(land_info.infrastructure_custom_field_name) infrastructure_custom_field_name, MAX(land_info.custom_field_values) custom_field_values
                    FROM institution_shifts
                    LEFT JOIN
                    (
                        SELECT institution_lands.code institution_land_code, institution_lands.name institution_land_name, institution_lands.area land_area, land_types.name land_type_name, infrastructure_custom_fields.name infrastructure_custom_field_name
                            ,IFNULL(CASE 
                                WHEN infrastructure_custom_fields.field_type = 'CHECKBOX' THEN IF(land_custom_field_values.text_value IS NULL,GROUP_CONCAT(DISTINCT(infrastructure_custom_field_options.name)),GROUP_CONCAT(DISTINCT(land_custom_field_values.text_value)))
                                WHEN infrastructure_custom_fields.field_type = 'DATE' THEN land_custom_field_values.date_value
                                WHEN infrastructure_custom_fields.field_type = 'DECIMAL' THEN land_custom_field_values.decimal_value
                                WHEN infrastructure_custom_fields.field_type = 'DROPDOWN' THEN IF(land_custom_field_values.text_value IS NULL,infrastructure_custom_field_options.name,land_custom_field_values.text_value)
                                WHEN infrastructure_custom_fields.field_type = 'FILE' THEN land_custom_field_values.text_value
                                WHEN infrastructure_custom_fields.field_type = 'NUMBER' THEN IF(land_custom_field_values.text_value IS NULL, land_custom_field_values.number_value, IF(land_custom_field_values.number_value IS NULL, land_custom_field_values.text_value, GREATEST(land_custom_field_values.text_value, land_custom_field_values.number_value)))
                                WHEN infrastructure_custom_fields.field_type = 'TEXT' THEN land_custom_field_values.text_value
                                WHEN infrastructure_custom_fields.field_type = 'TEXTAREA' THEN land_custom_field_values.textarea_value
                                END, '') custom_field_values, institution_lands.institution_id, land_custom_field_values.infrastructure_custom_field_id, land_custom_field_values.institution_land_id
                        FROM land_custom_field_values
                        LEFT JOIN infrastructure_custom_field_options
                            ON infrastructure_custom_field_options.infrastructure_custom_field_id = land_custom_field_values.infrastructure_custom_field_id AND infrastructure_custom_field_options.id = land_custom_field_values.number_value
                        INNER JOIN infrastructure_custom_fields
                            ON infrastructure_custom_fields.id = land_custom_field_values.infrastructure_custom_field_id
                        INNER JOIN institution_lands
                            ON institution_lands.id = land_custom_field_values.institution_land_id
                        INNER JOIN land_types
                            ON land_types.id = institution_lands.land_type_id
                        WHERE institution_lands.academic_period_id = " . $params['academic_period_id'] . " AND institution_lands.land_status_id = 1
                        GROUP BY  land_custom_field_values.infrastructure_custom_field_id, land_custom_field_values.institution_land_id
                    ) land_info
                    ON land_info.institution_id = institution_shifts.institution_id
                    WHERE institution_shifts.academic_period_id = " . $params['academic_period_id'] . " AND institution_shifts.location_institution_id = " . $params['institution_id'] . "
                    GROUP BY institution_shifts.institution_id, institution_shifts.location_institution_id, land_info.infrastructure_custom_field_id, land_info.institution_land_id")->fetchAll(\PDO::FETCH_ASSOC);
            $entity = $result = [];
            if (!empty($InfrastructureLandCustomFieldsData)) {
                foreach ($InfrastructureLandCustomFieldsData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'institution_land_code' => !empty($data['institution_land_code']) ? $data['institution_land_code'] : '',
                        'institution_land_name' => !empty($data['institution_land_name']) ? $data['institution_land_name'] : '',
                        'land_area' => !empty($data['land_area']) ? $data['land_area'] : '',
                        'land_type_name' => !empty($data['land_type_name']) ? $data['land_type_name'] : '',
                        'infrastructure_custom_field_name' => !empty($data['infrastructure_custom_field_name']) ? $data['infrastructure_custom_field_name'] : '',
                        'custom_field_values' => !empty($data['custom_field_values']) ? $data['custom_field_values'] : '',
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionClassroomArea(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT SUM(subq.area_size) area_size
                        FROM 
                        (
                            SELECT land_area.area_size
                            FROM institution_shifts
                            LEFT JOIN
                            (
                                SELECT institution_rooms.institution_id, SUM(institution_rooms.area) area_size
                                FROM institution_rooms
                                INNER JOIN room_types
                                    ON room_types.id = institution_rooms.room_type_id
                                WHERE institution_rooms.academic_period_id = " . $params['academic_period_id'] . " AND institution_rooms.room_status_id = 1 AND room_types.classification = 1
                                GROUP BY institution_rooms.institution_id
                            ) land_area
                                ON land_area.institution_id = institution_shifts.institution_id
                            WHERE institution_shifts.academic_period_id = " . $params['academic_period_id'] . " AND institution_shifts.location_institution_id = " . $params['institution_id'] . " 
                            GROUP BY institution_shifts.institution_id, institution_shifts.location_institution_id
                        ) subq")->fetch('assoc');
            if (!empty($entity)) {
                $entity['area_size'] = $entity['area_size'];
            }
            return !empty($entity) ? $entity : " 0";
        }
    }

    public function onExcelTemplateInitialiseSchoolStudentsTotalAbsenceDays(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT SUM(institution_student_absence_days.absent_days) school_absence_days
                                            FROM institution_student_absence_days
                                            INNER JOIN institutions
                                                ON institutions.id = institution_student_absence_days.institution_id
                                            INNER JOIN academic_periods
                                                ON (((institutions.date_closed IS NOT NULL AND institutions.date_opened <= academic_periods.start_date AND institutions.date_closed >= academic_periods.start_date) OR (institutions.date_closed IS NOT NULL AND institutions.date_opened <= academic_periods.end_date AND institutions.date_closed >= academic_periods.end_date) OR (institutions.date_closed IS NOT NULL AND institutions.date_opened >= academic_periods.start_date AND institutions.date_closed <= academic_periods.end_date)) OR (institutions.date_closed IS NULL AND institutions.date_opened <= academic_periods.end_date))
                                            WHERE academic_periods.id = " . $params['academic_period_id'] . " AND institutions.id = " . $params['institution_id'] . " AND institution_student_absence_days.start_date BETWEEN academic_periods.start_date AND academic_periods.end_date")->fetch();
            return !empty($entity[0]) ? $entity[0] : " 0";
        }
    }

    public function onExcelTemplateInitialiseSchoolStaffTotalAbsenceDays(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT ROUND(IFNULL(SUM(institution_staff_leave.number_of_days), 0), 0) school_staff_absent_days
                                            FROM institution_staff_leave
                                            WHERE institution_staff_leave.academic_period_id = " . $params['academic_period_id'] . " AND institution_staff_leave.institution_id = " . $params['institution_id'] . "")->fetch();
            return !empty($entity[0]) ? $entity[0] : " 0";
        }
    }

    public function onExcelTemplateInitialiseAreaStudentsTotalAbsenceDays(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $AreaId = $this->getAreaIdByInstitutionId($params['institution_id']);
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT SUM(institution_student_absence_days.absent_days) area_absence_days
                                            FROM institution_student_absence_days
                                            INNER JOIN institutions
                                                ON institutions.id = institution_student_absence_days.institution_id
                                            INNER JOIN academic_periods
                                                ON (((institutions.date_closed IS NOT NULL AND institutions.date_opened <= academic_periods.start_date AND institutions.date_closed >= academic_periods.start_date) OR (institutions.date_closed IS NOT NULL AND institutions.date_opened <= academic_periods.end_date AND institutions.date_closed >= academic_periods.end_date) OR (institutions.date_closed IS NOT NULL AND institutions.date_opened >= academic_periods.start_date AND institutions.date_closed <= academic_periods.end_date)) OR (institutions.date_closed IS NULL AND institutions.date_opened <= academic_periods.end_date))
                                            WHERE academic_periods.id = " . $params['academic_period_id'] . "
                                            AND institutions.area_id = " . $AreaId . "
                                            AND institution_student_absence_days.start_date BETWEEN academic_periods.start_date AND academic_periods.end_date")->fetch();
            return !empty($entity[0]) ? $entity[0] : " 0";
        }
    }

    public function onExcelTemplateInitialiseAreaStaffTotalAbsenceDays(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $AreaId = $this->getAreaIdByInstitutionId($params['institution_id']);
            $connection = ConnectionManager::get('default');
            $entity = $connection->execute("SELECT ROUND(IFNULL(SUM(institution_staff_leave.number_of_days), 0), 0) area_staff_absent_days
                                            FROM institution_staff_leave
                                            INNER JOIN institutions
                                                ON institutions.id = institution_staff_leave.institution_id
                                            WHERE institution_staff_leave.academic_period_id = " . $params['academic_period_id'] . " AND institutions.area_id = " . $AreaId . "")->fetch();
            return !empty($entity[0]) ? $entity[0] : " 0";
        }
    }

    private function getAreaIdByInstitutionId($institutionId)
    {
        $InstitutionTbl = TableRegistry::get('institutions');
        $getInstData = $InstitutionTbl->find()->where([$InstitutionTbl->aliasField('id') => $institutionId])->first();
        if (!empty($getInstData)) {
            $AreaId = $getInstData->area_id;
        }
        return $AreaId;
    }

    public function onExcelTemplateInitialiseLastYearInstitutionEducationGrade(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $LastYearPeriodId = $this->getLastYearId();
            $entity = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $LastYearPeriodId
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])
                ->group([
                    'EducationGrades.id'
                ])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = [
                'id' => 0,
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

    private function getLastYearId()
    {
        $connection = ConnectionManager::get('default');
        $entity = $connection->execute("SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
                                            ,@current_year_id
                                        FROM
                                        (
                                            SELECT operational_academic_periods_1.academic_period_id, @previous_start_year := MAX(academic_periods.start_date) previous_start_year
                                            FROM 
                                            (
                                                SELECT institution_students.academic_period_id
                                                FROM institution_students
                                                GROUP BY institution_students.academic_period_id
                                            ) operational_academic_periods_1
                                            INNER JOIN academic_periods
                                                ON academic_periods.id = operational_academic_periods_1.academic_period_id
                                            LEFT JOIN 
                                            (
                                                SELECT @current_year_id := academic_periods.id current_academic_periods_id, @current_start_year := academic_periods.start_date curent_start_date
                                                FROM 
                                                (
                                                    SELECT institution_students.academic_period_id
                                                    FROM institution_students
                                                    GROUP BY institution_students.academic_period_id
                                                ) operational_academic_periods
                                                INNER JOIN academic_periods
                                                    ON academic_periods.id = operational_academic_periods.academic_period_id
                                                WHERE academic_periods.current = 1
                                            ) t
                                                ON t.current_academic_periods_id = @current_year_id
                                            WHERE academic_periods.start_date < @current_start_year
                                        ) subq
                                        INNER JOIN
                                        (
                                            SELECT operational_academic_periods_1.academic_period_id, academic_periods.start_date start_year
                                            FROM 
                                            (
                                                SELECT institution_students.academic_period_id
                                                FROM institution_students
                                                GROUP BY institution_students.academic_period_id
                                            ) operational_academic_periods_1
                                            INNER JOIN academic_periods
                                                ON academic_periods.id = operational_academic_periods_1.academic_period_id
                                            LEFT JOIN 
                                            (
                                                SELECT @current_year_id := academic_periods.id current_academic_periods_id, @current_start_year := academic_periods.start_date curent_start_date
                                                FROM 
                                                (
                                                    SELECT institution_students.academic_period_id
                                                    FROM institution_students
                                                    GROUP BY institution_students.academic_period_id
                                                ) operational_academic_periods
                                                INNER JOIN academic_periods
                                                    ON academic_periods.id = operational_academic_periods.academic_period_id
                                                WHERE academic_periods.current = 1
                                            ) t
                                                ON t.current_academic_periods_id = @current_year_id
                                            WHERE academic_periods.start_date < @current_start_year
                                        ) previous_current_join
                                            ON previous_current_join.start_year = @previous_start_year")->fetch();
        return !empty($entity) ? $entity[0] : 0;
    }

    public function onExcelTemplateInitialiseLastYearInstitutionStudentEnrolled(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $EnrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;// for enrolled status
            $EnrolledData = $this->lastYearCurrentInstitutionStudentStatus($params['institution_id'], $EnrolledStatus);
            $entity = $result = [];
            if (!empty($EnrolledData)) {
                foreach ($EnrolledData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'education_grade_name' => !empty($data['education_grade_name']) ? $data['education_grade_name'] : '',
                        'last_year_enrolled_students' => !empty($data['last_year_status_students']) ? $data['last_year_status_students'] : '',
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseLastYearInstitutionStudentPromoted(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $PromotedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('PROMOTED')->first()->id;// for Promoted status
            $GraduatedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('GRADUATED')->first()->id;// for Graduated status
            $CombinedStatus = '"' . $PromotedStatus . "," . $GraduatedStatus . '"';
            //$PromotedData = $this->lastYearCurrentInstitutionStudentStatus($params['institution_id'], $CombinedStatus);
            $PromotedData = $this->lastYearCurrentInstitutionStudentStatusPomotedAndGraduated($params['institution_id'], $CombinedStatus);
            $entity = $result = [];
            if (!empty($PromotedData)) {
                foreach ($PromotedData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'education_grade_name' => !empty($data['education_grade_name']) ? $data['education_grade_name'] : '',
                        'last_year_promoted_students' => !empty($data['last_year_status_students']) ? $data['last_year_status_students'] : '',
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseLastYearInstitutionStudentWithdrawn(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $WithdrawnStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('WITHDRAWN')->first()->id; // for Withdrawn status
            $WithdrawnData = $this->lastYearCurrentInstitutionStudentStatus($params['institution_id'], $WithdrawnStatus);
            $entity = $result = [];
            if (!empty($WithdrawnData)) {
                foreach ($WithdrawnData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'education_grade_name' => !empty($data['education_grade_name']) ? $data['education_grade_name'] : '',
                        'last_year_withdrawn_students' => !empty($data['last_year_status_students']) ? $data['last_year_status_students'] : '',
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseLastYearInstitutionStudentRepeated(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $RepeatedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('REPEATED')->first()->id;// for Repeated status
            $RepeatedData = $this->lastYearCurrentInstitutionStudentStatus($params['institution_id'], $RepeatedStatus);
            $entity = $result = [];
            if (!empty($RepeatedData)) {
                foreach ($RepeatedData as $key => $data) {
                    $result = [
                        'id' => $key,
                        'education_grade_name' => !empty($data['education_grade_name']) ? $data['education_grade_name'] : '',
                        'last_year_repeated_students' => !empty($data['last_year_status_students']) ? $data['last_year_status_students'] : '',
                    ];
                    $entity[] = $result;
                }
            }
            return $entity;
        }
    }

    public function lastYearCurrentInstitutionStudentStatus($institutionId, $studentStatus)
    {
        $connection = ConnectionManager::get('default');
        $StudentData = $connection->execute("SELECT education_grades.name education_grade_name, COUNT(DISTINCT(institution_students.student_id)) last_year_status_students
                        FROM institution_students
                        INNER JOIN institutions
                            ON institutions.id = institution_students.institution_id
                        INNER JOIN education_grades
                            ON education_grades.id = institution_students.education_grade_id
                        INNER JOIN academic_periods
                            ON academic_periods.id = institution_students.academic_period_id
                        INNER JOIN 
                        (
                            SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
                                ,@current_year_id
                            FROM
                            (
                                SELECT operational_academic_periods_1.academic_period_id
                                    ,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
                                FROM 
                                (
                                    SELECT institution_students.academic_period_id
                                    FROM institution_students
                                    GROUP BY institution_students.academic_period_id
                                ) operational_academic_periods_1
                                INNER JOIN academic_periods
                                    ON academic_periods.id = operational_academic_periods_1.academic_period_id
                                LEFT JOIN 
                                (
                                    SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                        ,@current_start_year := academic_periods.start_date curent_start_date
                                    FROM 
                                    (
                                        SELECT institution_students.academic_period_id
                                        FROM institution_students
                                        GROUP BY institution_students.academic_period_id
                                    ) operational_academic_periods
                                    INNER JOIN academic_periods
                                        ON academic_periods.id = operational_academic_periods.academic_period_id
                                    WHERE academic_periods.current = 1
                                ) t
                                ON t.current_academic_periods_id = @current_year_id
                                WHERE academic_periods.start_date < @current_start_year
                            ) subq
                            INNER JOIN
                            (
                                SELECT operational_academic_periods_1.academic_period_id, academic_periods.start_date start_year
                                FROM 
                                (
                                    SELECT institution_students.academic_period_id
                                    FROM institution_students
                                    GROUP BY institution_students.academic_period_id
                                ) operational_academic_periods_1
                                INNER JOIN academic_periods
                                    ON academic_periods.id = operational_academic_periods_1.academic_period_id
                                LEFT JOIN 
                                (
                                    SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                        ,@current_start_year := academic_periods.start_date curent_start_date
                                    FROM 
                                    (
                                        SELECT institution_students.academic_period_id
                                        FROM institution_students
                                        GROUP BY institution_students.academic_period_id
                                    ) operational_academic_periods
                                    INNER JOIN academic_periods
                                        ON academic_periods.id = operational_academic_periods.academic_period_id
                                    WHERE academic_periods.current = 1
                                ) t
                                    ON t.current_academic_periods_id = @current_year_id
                                WHERE academic_periods.start_date < @current_start_year
                            ) previous_current_join
                            ON previous_current_join.start_year = @previous_start_year
                        ) academic_period_info
                        WHERE academic_periods.id = @previous_year_id AND institutions.id = " . $institutionId . " AND institution_students.student_status_id IN (" . $studentStatus . ")
                        GROUP BY education_grades.id")->fetchAll(\PDO::FETCH_ASSOC);
        return $StudentData;
    }

    public function lastYearCurrentInstitutionStudentStatusPomotedAndGraduated($institutionId, $studentStatus)
    {
        $PromotedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('PROMOTED')->first()->id;// for Promoted status
        $GraduatedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('GRADUATED')->first()->id;// for Graduated status
        $studentStatusIds = '"' . $GraduatedStatus . "," . $PromotedStatus . '"';
        $studentStatus = "'" . trim($studentStatusIds, '"') . "'";
        $studentStatus = '6,7';
        $connection = ConnectionManager::get('default');
        $StudentData = $connection->execute("SELECT education_grades.name education_grade_name, COUNT(DISTINCT(institution_students.student_id)) last_year_status_students
                        FROM institution_students
                        INNER JOIN institutions
                            ON institutions.id = institution_students.institution_id
                        INNER JOIN education_grades
                            ON education_grades.id = institution_students.education_grade_id
                        INNER JOIN academic_periods
                            ON academic_periods.id = institution_students.academic_period_id
                        INNER JOIN 
                        (
                            SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
                                ,@current_year_id
                            FROM
                            (
                                SELECT operational_academic_periods_1.academic_period_id
                                    ,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
                                FROM 
                                (
                                    SELECT institution_students.academic_period_id
                                    FROM institution_students
                                    GROUP BY institution_students.academic_period_id
                                ) operational_academic_periods_1
                                INNER JOIN academic_periods
                                    ON academic_periods.id = operational_academic_periods_1.academic_period_id
                                LEFT JOIN 
                                (
                                    SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                        ,@current_start_year := academic_periods.start_date curent_start_date
                                    FROM 
                                    (
                                        SELECT institution_students.academic_period_id
                                        FROM institution_students
                                        GROUP BY institution_students.academic_period_id
                                    ) operational_academic_periods
                                    INNER JOIN academic_periods
                                        ON academic_periods.id = operational_academic_periods.academic_period_id
                                    WHERE academic_periods.current = 1
                                ) t
                                ON t.current_academic_periods_id = @current_year_id
                                WHERE academic_periods.start_date < @current_start_year
                            ) subq
                            INNER JOIN
                            (
                                SELECT operational_academic_periods_1.academic_period_id, academic_periods.start_date start_year
                                FROM 
                                (
                                    SELECT institution_students.academic_period_id
                                    FROM institution_students
                                    GROUP BY institution_students.academic_period_id
                                ) operational_academic_periods_1
                                INNER JOIN academic_periods
                                    ON academic_periods.id = operational_academic_periods_1.academic_period_id
                                LEFT JOIN 
                                (
                                    SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                        ,@current_start_year := academic_periods.start_date curent_start_date
                                    FROM 
                                    (
                                        SELECT institution_students.academic_period_id
                                        FROM institution_students
                                        GROUP BY institution_students.academic_period_id
                                    ) operational_academic_periods
                                    INNER JOIN academic_periods
                                        ON academic_periods.id = operational_academic_periods.academic_period_id
                                    WHERE academic_periods.current = 1
                                ) t
                                    ON t.current_academic_periods_id = @current_year_id
                                WHERE academic_periods.start_date < @current_start_year
                            ) previous_current_join
                            ON previous_current_join.start_year = @previous_start_year
                        ) academic_period_info
                        WHERE academic_periods.id = @previous_year_id AND institutions.id = " . $institutionId . " AND institution_students.student_status_id IN (" . $studentStatus . ")
                        GROUP BY education_grades.id")->fetchAll(\PDO::FETCH_ASSOC);
        return $StudentData;
    }

    /*public function onExcelTemplateInitialiseLastYearEducationGrade(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $LastYearPeriodId = $this->getLastYearId();
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $LastYearPeriodId
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $params['institution_id']])
                ->hydrate(false)
                ->toArray()
            ; 
            $enrolledStudentsData = 0;
            if(empty($EducationGradesData)){
                $entity[] = [
                    'education_grade_name' =>  '',
                    'education_grade_id' =>  0,
                    'total_student_enrolment_last_year'=> 0,
                    'total_student_promoted_last_year'=> 0,
                    'total_student_withdrawn_last_year'=> 0
                ];
                return $entity;
            }
            $enrolledStudentsData = 0;
            if(empty($EducationGradesData)){
                $entity = [];
                return $entity;
            }
            foreach ($EducationGradesData as $value) {
                $forEnrolledStatus = "1, 6, 7, 8";
                $lastYearEnrolledStudents= $this->getLastYearStudentStatus($params['institution_id'], $value['id'], $forEnrolledStatus);

                $forPromotedStatus = 7;
                $lastYearPromotedStudents= $this->getLastYearStudentStatus($params['institution_id'], $value['id'], $forPromotedStatus);

                $forWithdrawnStatus = 4;
                $lastYearWithdrawnStudents= $this->getLastYearStudentStatus($params['institution_id'], $value['id'], $forWithdrawnStatus);

                $entity[] = [
                    'education_grade_id' => (!empty($value['id']) ? $value['id'] : 0),
                    'education_grade_name' => (!empty($value['name']) ? $value['name'] : ''),
                    'total_student_enrolment_last_year' => $lastYearEnrolledStudents,
                    'total_student_promoted_last_year' => $lastYearPromotedStudents,
                    'total_student_withdrawn_last_year' => $lastYearWithdrawnStudents
                ];
            }
            return $entity;
        }
    }*/

    public function onExcelTemplateInitialiseLastYearStudentEnrolledByArea(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;// for enrolled status
// POCOR-8073 Unify all
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);
            $LastYearPeriodId = $this->getLastYearId();
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $LastYearPeriodId
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institution_id])
                ->group([
                    'EducationGrades.id'
                ])
                ->order(['EducationGrades.id ASC'])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = ['id' => 0, 'name' => 'Grade'];
            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $enrolledStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $enrolledStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $enrolledStatus);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'name' => $edu_val['name'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
                ];
            }


            $totalArray = ['id' => count($entity) + 1];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseLastYearStudentPromotedByArea(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $PromotedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('PROMOTED')->first()->id;// for Promoted status
            $GraduatedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('GRADUATED')->first()->id;// for Graduated status
            $combArr[] = $PromotedStatus;
            $combArr[] = $GraduatedStatus;
            $CombinedStatus = implode(',', $combArr);
            // POCOR-8073 Unify all
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);
            $LastYearPeriodId = $this->getLastYearId();
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $LastYearPeriodId
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institution_id])
                ->group([
                    'EducationGrades.id'
                ])
                ->order(['EducationGrades.id ASC'])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = ['id' => 0, 'name' => 'Grade'];
            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $CombinedStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $CombinedStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $CombinedStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $CombinedStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $CombinedStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $CombinedStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $CombinedStatus);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'name' => $edu_val['name'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
                ];
            }
            $totalArray = [];
            $totalArray = ['id' => count($entity) + 1];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseLastYearStudentWithdrawnByArea(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $WithdrawnStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('WITHDRAWN')->first()->id;// for Withdrawn status
            // POCOR-8073 Unify all
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);
            $LastYearPeriodId = $this->getLastYearId();
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $LastYearPeriodId
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institution_id])
                ->group([
                    'EducationGrades.id'
                ])
                ->order(['EducationGrades.id ASC'])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = ['id' => 0, 'name' => 'Grade'];
            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $WithdrawnStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $WithdrawnStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $WithdrawnStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $WithdrawnStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $WithdrawnStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $WithdrawnStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $WithdrawnStatus);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'name' => $edu_val['name'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
                ];
            }

            $totalArray = ['id' => count($entity) + 1];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseLastYearStudentRepeatedByArea(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $RepeatedStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('REPEATED')->first()->id;// for Repeated status
            // POCOR-8073 Unify all
            $institution_id = $params['institution_id'];
            $insArr = $this->getDistrictInstitutionArray($institution_id);
            $LastYearPeriodId = $this->getLastYearId();
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGradesData = $InstitutionGrades->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->where([
                    'EducationSystems.academic_period_id' => $LastYearPeriodId
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institution_id])
                ->group([
                    'EducationGrades.id'
                ])
                ->order(['EducationGrades.id ASC'])
                ->hydrate(false)
                ->toArray();

            $addEducationheading[] = ['id' => 0, 'name' => 'Grade'];
            $EducationGradesData = array_merge($addEducationheading, $EducationGradesData);
            foreach ($EducationGradesData as $edu_key => $edu_val) {
                $area_level_1 = $area_level_2 = $area_level_3 = $area_level_4 = $area_level_5 = $area_level_6 = $area_level_7 = '';
                foreach ($insArr as $insKey => $insVal) {
                    if ($edu_key == 0) {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getAreaNameByInstitution($insKey, $insVal);
                        } else {
                            $area_level_7 = $this->getAreaNameByInstitution($insKey, $insVal);
                        }
                    } else {
                        if ($insKey == 0) {
                            $area_level_1 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $RepeatedStatus);
                        } else if ($insKey == 1) {
                            $area_level_2 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $RepeatedStatus);
                        } else if ($insKey == 2) {
                            $area_level_3 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $RepeatedStatus);
                        } else if ($insKey == 3) {
                            $area_level_4 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $RepeatedStatus);
                        } else if ($insKey == 4) {
                            $area_level_5 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $RepeatedStatus);
                        } else if ($insKey == 5) {
                            $area_level_6 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $RepeatedStatus);
                        } else {
                            $area_level_7 = $this->getStudentCountStatusByArea($edu_val['id'], $insVal, $RepeatedStatus);
                        }
                    }
                }

                $entity[] = [
                    'id' => $edu_val['id'],
                    'name' => $edu_val['name'],
                    'area_level:1' => $area_level_1,
                    'area_level:2' => $area_level_2,
                    'area_level:3' => $area_level_3,
                    'area_level:4' => $area_level_4,
                    'area_level:5' => $area_level_5,
                    'area_level:6' => $area_level_6,
                    'area_level:7' => $area_level_7
                ];
            }

            $totalArray = [];
            $totalArray = ['id' => count($entity) + 1];
            $entity[] = $totalArray;
            return $entity;
        }
    }

    public function getStudentCountStatusByArea($educationGradeId, $institutionIds = [], $studentStatus)
    {
        $connection = ConnectionManager::get('default');
        $StudentData = $connection->execute("SELECT institution_students.education_grade_id, COUNT(DISTINCT(institution_students.student_id)) result
                FROM institution_students
                INNER JOIN institutions
                ON institutions.id = institution_students.institution_id
                INNER JOIN areas
                ON areas.id = institutions.area_id
                INNER JOIN 
                (
                    SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
                    ,@current_year_id
                    FROM
                    (
                        SELECT operational_academic_periods_1.academic_period_id
                            ,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
                        FROM 
                        (
                            SELECT institution_students.academic_period_id
                            FROM institution_students
                            GROUP BY institution_students.academic_period_id
                        ) operational_academic_periods_1
                        INNER JOIN academic_periods
                        ON academic_periods.id = operational_academic_periods_1.academic_period_id
                        LEFT JOIN 
                        (
                            SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                ,@current_start_year := academic_periods.start_date curent_start_date
                            FROM 
                            (
                                SELECT institution_students.academic_period_id
                                FROM institution_students
                                GROUP BY institution_students.academic_period_id
                            ) operational_academic_periods
                            INNER JOIN academic_periods
                            ON academic_periods.id = operational_academic_periods.academic_period_id
                            WHERE academic_periods.current = 1
                        ) t
                        ON t.current_academic_periods_id = @current_year_id
                        WHERE academic_periods.start_date < @current_start_year
                    ) subq
                    INNER JOIN
                    (
                        SELECT operational_academic_periods_1.academic_period_id
                            ,academic_periods.start_date start_year
                        FROM 
                        (
                            SELECT institution_students.academic_period_id
                            FROM institution_students
                            GROUP BY institution_students.academic_period_id
                        ) operational_academic_periods_1
                        INNER JOIN academic_periods
                        ON academic_periods.id = operational_academic_periods_1.academic_period_id
                        LEFT JOIN 
                        (
                            SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                ,@current_start_year := academic_periods.start_date curent_start_date
                            FROM 
                            (
                                SELECT institution_students.academic_period_id
                                FROM institution_students
                                GROUP BY institution_students.academic_period_id
                            ) operational_academic_periods
                            INNER JOIN academic_periods
                            ON academic_periods.id = operational_academic_periods.academic_period_id
                            WHERE academic_periods.current = 1
                        ) t
                        ON t.current_academic_periods_id = @current_year_id
                        WHERE academic_periods.start_date < @current_start_year
                    ) previous_current_join
                    ON previous_current_join.start_year = @previous_start_year
                ) academic_period_info
                WHERE institution_students.institution_id IN (" . implode(",", $institutionIds) . ") AND institution_students.academic_period_id = @previous_year_id AND institution_students.education_grade_id = " . $educationGradeId . " AND institution_students.student_status_id IN (" . $studentStatus . ")
                GROUP BY institution_students.education_grade_id")->fetch('assoc');
        $data = 0;
        if (!empty($StudentData)) {
            $data = $StudentData['result'];
        }
        return $data;
    }
    //POCOR-7421 Ends
    //POCOR-7449 Starts
    public function onExcelTemplateInitialiseTeachingStaffTotalAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $StaffLeaveData = $connection->execute("SELECT ROUND(IFNULL(SUM(institution_staff_leave.number_of_days), 0), 0) school_staff_absent_days
                            FROM institution_staff_leave
                            INNER JOIN 
                            (
                                SELECT institution_staff.staff_id
                                FROM institution_staff
                                INNER JOIN institution_positions
                                    ON institution_positions.id = institution_staff.institution_position_id
                                INNER JOIN staff_position_titles 
                                    ON staff_position_titles.id = institution_positions.staff_position_title_id
                                INNER JOIN academic_periods
                                    ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                                WHERE institution_staff.institution_id = " . $params['institution_id'] . " AND staff_position_titles.type = 1 AND academic_periods.id = " . $params['academic_period_id'] . " AND institution_staff.staff_status_id = 1
                                GROUP BY institution_staff.staff_id
                            ) teaching_staff_info
                                ON teaching_staff_info.staff_id = institution_staff_leave.staff_id
                            WHERE institution_staff_leave.academic_period_id = " . $params['academic_period_id'] . " AND institution_staff_leave.institution_id = " . $params['institution_id'] . "")->fetch('assoc');
            $data = " 0";
            if (!empty($StaffLeaveData)) {
                $data = !empty($StaffLeaveData['school_staff_absent_days']) ? $StaffLeaveData['school_staff_absent_days'] : " 0";
            }
            return $data;
        }
    }

    public function onExcelTemplateInitialiseAreaTeachingStaffTotalAbsenceDays(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $AreaId = $this->getAreaIdByInstitutionId($params['institution_id']);
            $connection = ConnectionManager::get('default');
            $AreaStaffAbsentDaysData = $connection->execute("SELECT ROUND(IFNULL(SUM(institution_staff_leave.number_of_days), 0), 0) area_staff_absent_days
                                FROM institution_staff_leave
                                INNER JOIN institutions
                                    ON institutions.id = institution_staff_leave.institution_id
                                INNER JOIN 
                                (
                                    SELECT institution_staff.staff_id
                                    FROM institution_staff
                                    INNER JOIN institutions
                                        ON institutions.id = institution_staff.institution_id
                                    INNER JOIN institution_positions
                                        ON institution_positions.id = institution_staff.institution_position_id
                                    INNER JOIN staff_position_titles 
                                        ON staff_position_titles.id = institution_positions.staff_position_title_id
                                    INNER JOIN academic_periods
                                        ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                                    WHERE institutions.area_id = " . $AreaId . " AND staff_position_titles.type = 1 AND academic_periods.id = " . $params['academic_period_id'] . " AND institution_staff.staff_status_id = 1
                                    GROUP BY institution_staff.staff_id
                                ) teaching_staff_info
                                    ON teaching_staff_info.staff_id = institution_staff_leave.staff_id
                                WHERE institution_staff_leave.academic_period_id = " . $params['academic_period_id'] . " AND institutions.area_id = " . $AreaId . "")->fetch('assoc');
            $data = " 0";
            if (!empty($AreaStaffAbsentDaysData)) {
                $data = !empty($AreaStaffAbsentDaysData['area_staff_absent_days']) ? $AreaStaffAbsentDaysData['area_staff_absent_days'] : " 0";
            }
            return $data;
        }
    }//POCOR-7449 Ends

    //POCOR-7694 Starts
    public function onExcelTemplateInitialisePublicHolidays(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $connection = ConnectionManager::get('default');
            $PublicHolidaysData = $connection->execute("SELECT COUNT(DISTINCT(calendar_event_dates.date)) holidays
                                            FROM calendar_event_dates
                                            INNER JOIN calendar_events
                                            ON calendar_events.id = calendar_event_dates.calendar_event_id
                                            CROSS JOIN 
                                            (
                                                SELECT MAX(CASE WHEN config_items.code = 'first_day_of_week' THEN IF(LENGTH(config_items.value) = 0, config_items.default_value, config_items.value) END) first_day_of_week
                                                    ,MAX(CASE WHEN config_items.code = 'days_per_week' THEN IF(LENGTH(config_items.value) = 0, config_items.default_value, config_items.value) END) days_per_week
                                                FROM config_items
                                            ) working_days
                                            WHERE calendar_events.academic_period_id = " . $params['academic_period_id'] . "
                                            AND calendar_events.institution_id = -1
                                            AND DAYOFWEEK(calendar_event_dates.date) BETWEEN working_days.first_day_of_week + 1 AND working_days.first_day_of_week + days_per_week")->fetch('assoc');

            $count = !empty($PublicHolidaysData['holidays']) ? $PublicHolidaysData['holidays'] : " 0";
            return $count;
        }
    }
    //POCOR-7694 Ends
    // POCOR-8073 Start
    /**
     * @param $institution_id
     * @return array
     */
    private function getInstitutionAreaIds($institution_id)
    {
        $institutionsTbl = TableRegistry::get('institutions');
        $institution = $institutionsTbl->find()
            ->where([$institutionsTbl->aliasField('id') => $institution_id])
            ->first();
        $institution_area_id = $institution->area_id;
        $areasTbl = TableRegistry::get('areas');
        $institution_area = $areasTbl->find()
            ->select([
                'area_id' => $areasTbl->aliasField('id'),
                'area_name' => $areasTbl->aliasField('name'),
                'area_parent_id' => $areasTbl->aliasField('parent_id')
            ])
            ->where([$areasTbl->aliasField('id') => $institution_area_id])
            ->first();
        $institution_parent_area_id = $institution_area->area_parent_id;
        return array($institution_area_id, $institution_parent_area_id);
    }

    /**
     * @param $institution_parent_area_id
     * @param $institution_area_id
     * @return array
     */
    private function getDistrictArray($institution_parent_area_id, $institution_area_id)
    {
        $distArr = [];
        $areasTbl = TableRegistry::get('areas');
        $areaLevelsTbl = TableRegistry::get('area_levels');
        $areaLevelsCount = $areaLevelsTbl->find()->count();

        if ($institution_parent_area_id > 0) {
            $distArr[0][] = $institution_area_id; //first time we get area_id
            $k = $institution_parent_area_id;
            for ($i = $institution_parent_area_id; $i >= 1; $i--) {
                if ($k == '') {
                    break;
                }
                for ($j = 1; $j < $areaLevelsCount; $j++) {
                    //get district's regions
                    $areas1 = $areasTbl->find()
                        ->select([
                            'area_id' => $areasTbl->aliasField('id'),
                            'area_name' => $areasTbl->aliasField('name'),
                            'area_parent_id' => $areasTbl->aliasField('parent_id')
                        ])
                        ->where([$areasTbl->aliasField('id') => $k])
                        ->first();
                    if ($areas1->area_parent_id > 0) {
                        $areas2 = $areasTbl->find()
                            ->select([
                                'area_id' => $areasTbl->aliasField('id'),
                                'area_name' => $areasTbl->aliasField('name'),
                                'area_parent_id' => $areasTbl->aliasField('parent_id')
                            ])
                            ->where([$areasTbl->aliasField('parent_id') => $areas1->area_id])
                            ->toArray();
                        if (!empty($areas2)) {
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

                        if (!empty($areas3)) {
                            $reg = [];
                            foreach ($areas3 as $ar3) {
                                $reg [] = $ar3->area_id;
                            }

                            if (!empty($reg)) {
                                $areas4 = $areasTbl->find()
                                    ->select([
                                        'area_id' => $areasTbl->aliasField('id'),
                                        'area_name' => $areasTbl->aliasField('name'),
                                        'area_parent_id' => $areasTbl->aliasField('parent_id')
                                    ])
                                    ->where([$areasTbl->aliasField('parent_id IN') => $reg])
                                    ->toArray();
                                if (!empty($areas4)) {
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
        return $distArr;
    }

    /**
     * @param $institution_id
     * @return array
     */

    private function getDistrictInstitutionArray($institution_id)
    {
        $insArr = $this->insArr;
        if ($insArr === null) {
            list($institution_area_id, $institution_parent_area_id) = $this->getInstitutionAreaIds($institution_id);

            $distArr = $this->getDistrictArray($institution_parent_area_id, $institution_area_id);

            $insArr = [];
            $institutionsTbl = TableRegistry::get('institutions');
            if (!empty($distArr)) {
                $i = 0;
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
        }
        return $insArr;
    }// POCOR-8073 Start

    /** POCOR-8182 
     * get student details 
     * return array
     */ 
    public function onExcelTemplateInitialiseGeneralStudentDetails(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $Users = TableRegistry::get('Security.Users');
            $institutionStudents = TableRegistry::get('Institution.InstitutionStudents');
            $studentFieldTable = TableRegistry::get('StudentCustomFieldValues.StudentCustomFieldValues');
            $studentCustomFieldOptions = TableRegistry::get('StudentCustomFieldValues.StudentCustomFieldOptions');
            $studentCustomFields = TableRegistry::get('StudentCustomFieldValues.StudentCustomFields');

            $connection = ConnectionManager::get('default');
            $student_Details = $connection->execute("SELECT security_users.id AS 'userId',
                                    security_users.openemis_no, security_users.first_name
                                ,security_users.last_name
                                ,IFNULL(security_users.address, '') student_address
                                ,security_users.date_of_birth
                            FROM institution_students
                            INNER JOIN security_users
                            ON security_users.id = institution_students.student_id
                            INNER JOIN academic_periods
                            ON academic_periods.id = institution_students.academic_period_id
                            INNER JOIN
                            (
                                SELECT profile_templates.id profile_template_id
                                FROM profile_templates
                                WHERE profile_templates.id = " . $params['report_card_id'] . "
                                AND profile_templates.academic_period_id = " . $params['academic_period_id'] . "
                            ) profile_details
                            WHERE institution_students.academic_period_id = " . $params['academic_period_id'] . "
                            AND institution_students.institution_id = " . $params['institution_id'] . "
                            AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))")->fetchAll('assoc');

            $allData = [];
            $entity = []; 
            if (!empty($student_Details)) {
                foreach ($student_Details as $data) {
                    $userId = $data['userId'];
                    $entity[] = [
                        'id' => $data['userId'],
                        'student_id' => $data['userId'],
                        'openemis_no' => $data['openemis_no'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'student_address' => $data['student_address'],
                        'date_of_birth' => $data['date_of_birth']
                    ];
                }
            }
            
           return $entity;
        }
    }

    /** POCOR-8182 
     * get student custom Name 
     * return array
     */ 
    public function onExcelTemplateInitialiseStudentCustomFieldName(Event $event, array $params, ArrayObject $extra)
    {
            $CustomFields = TableRegistry::get('StudentCustomField.StudentCustomFields');
            $studentCustomFormsFields = TableRegistry::get('StudentCustomField.StudentCustomFormsFields');
            $customFieldData = $studentCustomFormsFields->find()->select([
                'custom_field_id' => 'studentCustomField.id',
                'custom_field' => 'studentCustomField.name'
            ])->innerJoin(
                    ['studentCustomField' => 'student_custom_fields'],
                    ['studentCustomField.id = ' . $studentCustomFormsFields->aliasField('student_custom_field_id')]
                )->group($studentCustomFormsFields->aliasfield('student_custom_field_id'))->toArray();
            
           $entity = [];
            if(!empty($customFieldData)) {
                foreach($customFieldData as $data) {
                    $custom_field_id = $data->custom_field_id;
                    $custom_field = $data->custom_field;
                    $entity[] = [
                       'id' => $custom_field_id,
                        'name' => $custom_field
                    ];

                }
            }
            return $entity; 
    }

    /** POCOR-8182 
     * get student custom Field value answer
     * mathch with student_id 
     */ 
    public function onExcelTemplateInitialiseStudentCustomFieldValueAnswer(Event $event, array $params, ArrayObject $extra)
    {
        
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $Users = TableRegistry::get('Security.Users');
            $institutionStudents = TableRegistry::get('Institution.InstitutionStudents');
            $studentFieldTable = TableRegistry::get('StudentCustomFieldValues.StudentCustomFieldValues');
            $studentCustomFieldOptions = TableRegistry::get('StudentCustomFieldValues.StudentCustomFieldOptions');
            $studentCustomFields = TableRegistry::get('StudentCustomFieldValues.StudentCustomFields');

            $connection = ConnectionManager::get('default');
            $student_Details = $connection->execute("SELECT security_users.id AS 'userId',
                                    security_users.openemis_no, security_users.first_name
                                ,security_users.last_name
                                ,IFNULL(security_users.address, '') student_address
                                ,security_users.date_of_birth
                            FROM institution_students
                            INNER JOIN security_users
                            ON security_users.id = institution_students.student_id
                            INNER JOIN academic_periods
                            ON academic_periods.id = institution_students.academic_period_id
                            INNER JOIN
                            (
                                SELECT profile_templates.id profile_template_id
                                FROM profile_templates
                                WHERE profile_templates.id = " . $params['report_card_id'] . "
                                AND profile_templates.academic_period_id = " . $params['academic_period_id'] . "
                            ) profile_details
                            WHERE institution_students.academic_period_id = " . $params['academic_period_id'] . "
                            AND institution_students.institution_id = " . $params['institution_id'] . "
                            AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))")->fetchAll('assoc');

            $allData = [];
            if (!empty($student_Details)) {
                foreach ($student_Details as $data) {
                    $userId = $data['userId'];
                    $result = [
                       // 'id' => $data['userId'],
                        'student_id' => $data['userId'],
                    ];

                    $getStudentCustomValue = $this->getCustomFieldValue($userId);
                    $allData[] = array_merge($result, $getStudentCustomValue);
                }
            }
             
        $entity = []; 
            if (is_array($allData)) {
                foreach ($allData as $key => $value) {
                    foreach($value as $k => $v){
                        if (is_array($v) && isset($v['name'])) {
                            $entity[] = [
                                   'id' => $v['id'],
                                    'name' => $v['name'],
                                    'student_id' => $v['student_id'],
                                    'student_custom_field_id' => $v['student_custom_field_id']
                                ];
                        }
                    }
                }
               
            }
           
           return $entity;
        }
    }

    /** POCOR-8182 
     * get student custom Field value field based on field type
     * match student_id and student_custom_field_id in placeholder
     */ 
    private function getCustomFieldValue($userId) 
    {
        $CustomFields = TableRegistry::get('student_custom_fields');
        $Users = TableRegistry::get('Security.Users');
        $institutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $studentFieldTable = TableRegistry::get('StudentCustomFieldValues.StudentCustomFieldValues');
        $studentCustomFieldOptions = TableRegistry::get('StudentCustomFieldValues.StudentCustomFieldOptions');
        $studentCustomFields = TableRegistry::get('StudentCustomFieldValues.StudentCustomFields');

        $customFieldData = $CustomFields->find()
            ->select(['custom_field_id' => 'id', 'name' => 'name', 'field_type' => 'field_type'])
            ->group('id')
            ->toArray();
        $setAllResult = [];

        foreach ($customFieldData as $field_key => $field_val) {
            $custom_field_id = $field_val['custom_field_id'];
            $fieldType = $field_val['field_type'];
            $questionName = $field_val['name'];
            $customResult[$custom_field_id] = ['id' => $custom_field_id, 'name' => '', 'questionName' => $questionName,'student_id' => '','student_custom_field_id' => ''];
            $guardianData = $studentFieldTable->find()
                ->select([
                       // 'id'                           => $studentFieldTable->aliasField('id'),
                        
                        'student_id'                     => $studentFieldTable->aliasField('student_id'),
                        'student_custom_field_id'        => $studentFieldTable->aliasField('student_custom_field_id'),
                        'text_value'                     => $studentFieldTable->aliasField('text_value'),
                        'number_value'                   => $studentFieldTable->aliasField('number_value'),
                        'decimal_value'                  => $studentFieldTable->aliasField('decimal_value'),
                        'textarea_value'                 => $studentFieldTable->aliasField('textarea_value'),
                        'date_value'                     => $studentFieldTable->aliasField('date_value'),
                        'time_value'                     => $studentFieldTable->aliasField('time_value'),
                        'checkbox_value_text'            => 'studentCustomFieldOptions.name',
                        'id'                             => 'studentCustomField.id',
                        'question_name'                  => 'studentCustomField.name',
                        'field_type'                     => 'studentCustomField.field_type',
                        'field_description'              => 'studentCustomField.description',
                        'question_field_type'            => 'studentCustomField.field_type',
                    ])
                ->leftJoin(
                    ['studentCustomField' => 'student_custom_fields'],
                    ['studentCustomField.id = ' . $studentFieldTable->aliasField('student_custom_field_id')]
                )
                ->leftJoin(
                    ['studentCustomFieldOptions' => 'student_custom_field_options'],
                    ['studentCustomFieldOptions.id = ' . $studentFieldTable->aliasField('number_value')]
                )
                ->where([
                    $studentFieldTable->aliasField('student_id') => $userId,
                    $studentFieldTable->aliasField('student_custom_field_id') => $custom_field_id
                    //$studentFieldTable->aliasField('student_custom_field_id') => $fieldType
                ])
                ->toArray();
            if(!empty($guardianData)){
                foreach ($guardianData as $f_v) {
                    $fieldType = $f_v['field_type'];
                    switch ($fieldType) {
                        case 'TEXT':

                            $customResult[$custom_field_id]['name'] = !empty($f_v['text_value']) ? $f_v['text_value'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                            
                            break;
                        case 'CHECKBOX':
                            $existingCheckboxValue = trim($customResult[$f_v['student_custom_field_id']]['name'], ',') . ',' . $f_v['checkbox_value_text'];
                            $customResult[$f_v['student_custom_field_id']]['name'] = trim($existingCheckboxValue, ',');
                            $customResult[$f_v['student_custom_field_id']]['questionName'] = !empty($f_v['question_name']) ? $f_v['question_name'] : ' ';
                            break;
                        case 'NUMBER':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['number_value']) ? $f_v['number_value'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                            
                            break;
                        case 'DECIMAL':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['decimal_value']) ? $f_v['decimal_value'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                            
                            break;
                        case 'TEXTAREA':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['textarea_value']) ? $f_v['textarea_value'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                           
                            break;
                        case 'DROPDOWN':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['checkbox_value_text']) ? $f_v['checkbox_value_text'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                           
                            break;
                        case 'DATE':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['date_value']) ? date('Y-m-d', strtotime($f_v['date_value'])) : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                            
                            break;
                        case 'TIME':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['time_value']) ? $f_v['time_value'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                            break;
                        case 'COORDINATES':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['text_value']) ? $f_v['text_value'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                            
                            break;
                        case 'NOTE':
                            $customResult[$custom_field_id]['name'] = !empty($f_v['field_description']) ? $f_v['field_description'] : ' ';
                            $customResult[$custom_field_id]['student_custom_field_id'] = !empty($f_v['student_custom_field_id']) ? $f_v['student_custom_field_id'] : ' ';
                            
                            break;
                    }
                }
            }
        }
         
        if (is_array($customResult)) {

            foreach ($customResult as $e_key => $e_val) {
                $setAllResult[] = [
                    'id' => $e_val['id'],
                    'name' => $e_val['name'],
                    'questionName' => $e_val['questionName'],
                    'student_id' => $userId,
                    'student_custom_field_id' => $e_val['student_custom_field_id'],
                ];
            }
        } else {
            $setAllResult = [];
        }

        return $setAllResult;

    }


}
