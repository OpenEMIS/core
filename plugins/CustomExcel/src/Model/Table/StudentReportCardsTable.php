<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;

class StudentReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';
    //private $fileType = 'pdf';

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->addBehavior('CustomExcel.StudentExcelReport', [
            'templateTable' => 'ProfileTemplate.StudentTemplates',
            'templateTableKey' => 'student_profile_template_id',
            'format' => $this->fileType,
            'download' => false,
            'wrapText' => true,
            'lockSheets' => true,
            'variables' => [
                'Profiles',
				'Institutions',
				'StudentUsers',
				'StudentDemographics',
				'StudentContacts',
				'StudentNationalities',
				'StudentAreas',
				'StudentRisks',
				'StudentClasses',
				'StudentSubjects',
				'StudentExtracurriculars',
				'StudentAwards',
				'StudentBehaviours',
				'StudentAbsences',
				'StudentTotalAbsences',
				'StudentCounsellings',
				'StudentHealths',
				'StudentHealthConsultations',
				'StudentGuardians',
				'StudentHouses',
                'UserSpecialNeedsAssessments',//6680
                'UserContacts',//6680
                'StudentMoterDetails',//6680
                'InstitutionSubjectStudentsWithName',//POCOR-7316
                'AssessmentPeriods',//POCOR-7316
                'AssessmentItemResults',//POCOR-7316
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
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentUsers'] = 'onExcelTemplateInitialiseStudentUsers';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentDemographics'] = 'onExcelTemplateInitialiseStudentDemographics';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentContacts'] = 'onExcelTemplateInitialiseStudentContacts';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentNationalities'] = 'onExcelTemplateInitialiseStudentNationalities';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentAreas'] = 'onExcelTemplateInitialiseStudentAreas';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentRisks'] = 'onExcelTemplateInitialiseStudentRisks';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentClasses'] = 'onExcelTemplateInitialiseStudentClasses';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentSubjects'] = 'onExcelTemplateInitialiseStudentSubjects';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentExtracurriculars'] = 'onExcelTemplateInitialiseStudentExtracurriculars';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentAwards'] = 'onExcelTemplateInitialiseStudentAwards';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentBehaviours'] = 'onExcelTemplateInitialiseStudentBehaviours';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentAbsences'] = 'onExcelTemplateInitialiseStudentAbsences';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentTotalAbsences'] = 'onExcelTemplateInitialiseStudentTotalAbsences';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCounsellings'] = 'onExcelTemplateInitialiseStudentCounsellings';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentHealths'] = 'onExcelTemplateInitialiseStudentHealths';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentHealthConsultations'] = 'onExcelTemplateInitialiseStudentHealthConsultations';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentGuardians'] = 'onExcelTemplateInitialiseStudentGuardians';
		$events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentHouses'] = 'onExcelTemplateInitialiseStudentHouses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseUserSpecialNeedsAssessments'] = 'onExcelTemplateInitialiseUserSpecialNeedsAssessments';//6680
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseUserContacts'] = 'onExcelTemplateInitialiseUserContacts';//6680
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentMoterDetails'] = 'onExcelTemplateInitialiseStudentMoterDetails';//6680
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionSubjectStudentsWithName'] = 'onExcelTemplateInitialiseInstitutionSubjectStudentsWithName';//POCOR-7316
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentPeriods'] = 'onExcelTemplateInitialiseAssessmentPeriods';//POCOR-7316
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemResults'] = 'onExcelTemplateInitialiseAssessmentItemResults';//POCOR-7316
		return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $InstitutionStudentsProfileTemplates = TableRegistry::get('Institution.InstitutionStudentsProfileTemplates');
        if (!$InstitutionStudentsProfileTemplates->exists($params)) {
            // insert staff report card record if it does not exist
            $params['status'] = $InstitutionStudentsProfileTemplates::IN_PROGRESS;
            $params['started_on'] = date('Y-m-d H:i:s');
            $newEntity = $InstitutionStudentsProfileTemplates->newEntity($params);
            $InstitutionStudentsProfileTemplates->save($newEntity);
        } else {
            // update status to in progress if record exists
            $InstitutionStudentsProfileTemplates->updateAll([
                'status' => $InstitutionStudentsProfileTemplates::IN_PROGRESS,
                'started_on' => date('Y-m-d H:i:s')
            ], $params);
        }
    }

    public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $InstitutionStudentsProfileTemplates = TableRegistry::get('Institution.InstitutionStudentsProfileTemplates');
		$StudentReportCardData = $InstitutionStudentsProfileTemplates
            ->find()
            ->select([
                $InstitutionStudentsProfileTemplates->aliasField('academic_period_id'),
                $InstitutionStudentsProfileTemplates->aliasField('student_id'),
                $InstitutionStudentsProfileTemplates->aliasField('institution_id'),
				$InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id')
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
                'StudentTemplates' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Students' => [
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
                $InstitutionStudentsProfileTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                $InstitutionStudentsProfileTemplates->aliasField('institution_id') => $params['institution_id'],
                $InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id') => $params['student_profile_template_id'],
                $InstitutionStudentsProfileTemplates->aliasField('student_id') => $params['student_id'],
                $InstitutionStudentsProfileTemplates->aliasField('education_grade_id') => $params['education_grade_id'],
            ])
            ->first();
			
        // set filename
		$fileName = $StudentReportCardData->institution->code . '_' . $StudentReportCardData->student_template->code. '_' . $StudentReportCardData->student->openemis_no . '_' . $StudentReportCardData->student->name . '.' . $this->fileType;
		$filepath = $extra['file_path'];
        $fileContent = file_get_contents($filepath);
        $status = $InstitutionStudentsProfileTemplates::GENERATED;
		
        // save file
        $InstitutionStudentsProfileTemplates->updateAll([
            'status' => $status,
            'completed_on' => date('Y-m-d H:i:s'),
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);

        // delete staff report card process
        $StudentReportCardProcesses = TableRegistry::Get('ReportCard.StudentReportCardProcesses');
        $StudentReportCardProcesses->deleteAll([
            'student_profile_template_id' => $params['student_profile_template_id'],
            'institution_id' => $params['institution_id'],
            'education_grade_id' => $params['education_grade_id'],
            'student_id' => $params['student_id']
        ]);
    }

    public function afterRenderExcelTemplate(Event $event, ArrayObject $extra, $controller)
    {
        $params = $extra['params'];
        $url = [
            'plugin' => 'ProfileTemplate',
            'controller' => 'ProfileTemplates',
            'action' => 'StudentProfiles',
            'index',
            'institution_id' => $params['institution_id'],
            'education_grade_id' => $params['education_grade_id'],
            'student_profile_template_id' => $params['student_profile_template_id'],
            'academic_period_id' => $params['academic_period_id']
        ];

        $event->stopPropagation();
        return $controller->redirect($url);
    }
    
	public function onExcelTemplateInitialiseProfiles(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_profile_template_id', $params)) {
            $StudentTemplates = TableRegistry::get('ProfileTemplate.StudentTemplates');
            $entity = $StudentTemplates->get($params['student_profile_template_id'], ['contain' => ['AcademicPeriods']]);
			
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
            //POCOR-7316 start
            $result = [];
				$result = [
					'name' => $entity->name,
                    'address'=>$entity->address,
                    'contact'=>$entity->telephone,
                    'area'=>$entity['area_administrative']->name,
            
				];
             return $result;
           //POCOR-7316 end
            
        }
    }
	
	public function onExcelTemplateInitialiseStudentUsers(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Student = TableRegistry::get('Institution.InstitutionClassStudents');
            
            $entity = $Student
                ->find()
                ->select([
                    'id' => 'Users.id',//6680
                    'address_area_id' => 'Users.address_area_id',//6680
                    'first_name' => 'Users.first_name',
                    'last_name' => 'Users.last_name',
                    'middle_name'=>'Users.middle_name',//POCOR-7316
                    'third_name'=>'Users.third_name',//POCOR-7316
                    'email' => 'Users.email',
                    'photo_content' => 'Users.photo_content',
                    'address' => 'Users.address',
                    'date_of_birth' => 'Users.date_of_birth',
                    'identity_number' => 'Users.identity_number',
                    'gender' => 'Genders.name',
                    'openemis_no' => 'Users.openemis_no',//add openemis_no in report POCOR-6321
                ])
                ->contain([
                    'Users' => [
                        'fields' => [
                            'identity_number',
                            'first_name',
                            'last_name',
                            'middle_name',//POCOR_7316
                            'third_name',//POCOR-7316
                            'photo_content',
                            'email',
                            'address',
                            'date_of_birth',
                            'openemis_no',//add openemis_no in report POCOR-6321
                        ]
                    ]
                ])
                ->matching('Users.Genders')
                ->where([
                    $Student->aliasField('institution_id') => $params['institution_id'],
                    $Student->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Student->aliasField('education_grade_id') => $params['education_grade_id'],
                    $Student->aliasField('student_id') => $params['student_id'],

                ])
                ->first();
                //6680 starts
                $identity_number_value = '';
                if(!empty($entity)){
                    $UserIdentities = TableRegistry::get('user_identities');
                    $UserIdentitiesEntity = $UserIdentities
                        ->find()
                        ->select([
                            'id' => $UserIdentities->aliasField('id'),
                            'number' => $UserIdentities->aliasField('number'),
                            'name' => 'IdentityTypes.name',
                        ])
                        ->innerJoin(
                        [' IdentityTypes' => ' identity_types'],
                        [
                            'IdentityTypes.id ='. $UserIdentities->aliasField('identity_type_id')
                        ]
                        )
                        ->where([
                            $UserIdentities->aliasField('security_user_id') => $entity->id,
                        ])
                        ->first();
                    
                    if(!empty($UserIdentitiesEntity)){
                        $identity_number_value = $UserIdentitiesEntity->name .' { '. $UserIdentitiesEntity->number .' } ';
                    }

                    $area_name = '';
                    if(!empty($entity->address_area_id)){
                        $selectedArea = $entity->address_area_id;
                        $areaIds = [];
                        $allgetArea = $this->getParent($selectedArea, $areaIds);

                        $selectedArea1[]= $selectedArea;
                        if(!empty($allgetArea)){
                            $allselectedAreas = array_merge($selectedArea1, $allgetArea);
                        }else{
                            $allselectedAreas = $selectedArea1;
                        }

                        $Areas = TableRegistry::get('Area.AreaAdministratives');
                        $AreasRecords = $Areas
                                        ->find()->select([$Areas->aliasField('name')])
                                        ->where([ $Areas->aliasField('id IN') => $allselectedAreas])
                                        ->hydrate(false)->order([$Areas->aliasField('id DESC')])->toArray();
                        if(!empty($AreasRecords)){
                            $area_name_array = [];
                            foreach ($AreasRecords as $key => $value) {
                                $area_name_array[$key] = $value['name'];
                            }
                            $area_name = implode(' / ',$area_name_array);
                        }
                    }
                }
                $result = [];
                $result = [
                    'name' => $entity->first_name.' '.$entity->last_name,
                    'first_name'=>$entity->first_name,//POCOR_7316
                    'last_name'=>$entity->last_name,//POCOR_7316
                    'middle_name'=>$entity->middle_name,//POCOR_7316
                    'third_name'=>$entity->third_name,//POCOR_7316
                    'identity_number' => $identity_number_value,
                    'photo_content' => $entity->photo_content,
                    'email' => $entity->email,
                    'address' => $entity->address,
                    'date_of_birth' => $entity->date_of_birth,
                    'gender' => $entity->gender,
                    'openemis_no' => $entity->openemis_no,//add openemis_no in report POCOR-6321
                    'age' => date_diff(date_create($entity->date_of_birth), date_create('today'))->y .' Year',
                    'permanent_address' => $area_name,
                ];//6680 ends
            return $result;
        }
    }
    //6680 starts
    public function getParent($id, $idArray) {
        $Areas = TableRegistry::get('Area.AreaAdministratives');
        $result = $Areas->find()->where([$Areas->aliasField('id') => $id])->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['parent_id'];
            $idArray = $this->getParent($value['parent_id'], $idArray);
        }
        return $idArray;
    }//6680 ends
	
	public function onExcelTemplateInitialiseStudentDemographics(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Student = TableRegistry::get('Institution.InstitutionClassStudents');

            $entity = $Student
                ->find()
                ->select([
					'demographic_type_name' => 'DemographicTypes.name',
                ])
				->innerJoin(
				['UserDemographics' => 'user_demographics'],
				[
					'UserDemographics.security_user_id ='. $Student->aliasField('student_id')
				]
				)
				->leftJoin(
				['DemographicTypes' => 'demographic_types'],
				[
					'DemographicTypes.id = UserDemographics.demographic_types_id'
				]
				)
                ->where([
                    $Student->aliasField('institution_id') => $params['institution_id'],
                    $Student->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Student->aliasField('education_grade_id') => $params['education_grade_id'],
                    $Student->aliasField('student_id') => $params['student_id'],
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentContacts(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $UserContacts = TableRegistry::get('user_contacts');

            $entity = $UserContacts
                ->find()
                ->select([
					'contact' => $UserContacts->aliasField('value'),
                ])
                ->where([
                    $UserContacts->aliasField('security_user_id') => $params['student_id'],
                    $UserContacts->aliasField('preferred') => 1,
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentNationalities(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
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
                    $UserNationalities->aliasField('security_user_id') => $params['student_id'],
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentAreas(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
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
                    $SecurityUsers->aliasField('id') => $params['student_id'],
                ])
                ->first();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentRisks(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $InstitutionStudentRisks = TableRegistry::get('Institution.InstitutionStudentRisks');
            $StudentRisksCriterias = TableRegistry::get('Institution.StudentRisksCriterias');

            $InstitutionStudentRiskData = $InstitutionStudentRisks
                ->find()
                ->select([
					'id' => $InstitutionStudentRisks->aliasField('id'),
					'total_risk' => $InstitutionStudentRisks->aliasField('total_risk')
                ])
                ->where([
                    $InstitutionStudentRisks->aliasField('student_id') => $params['student_id'],
                ])
                ->toArray();
			
			$entity = [];	
			foreach ($InstitutionStudentRiskData as $value) {
				$studentRisksCriteriasResults = $StudentRisksCriterias->find()
				->select([
					'criteria' => 'RiskCriterias.criteria',
                ])
                ->contain(['RiskCriterias'])
                ->where([
                    $StudentRisksCriterias->aliasField('institution_student_risk_id') => $value->id,
                    $StudentRisksCriterias->aliasField('value') . ' IS NOT NULL'
                ])
                ->toArray();
				$criteriaArray = [];
				$criteria = '';
				foreach ($studentRisksCriteriasResults as $data) {
					$criteriaArray[] = $data->criteria;
				}
				$criteria = implode(",",$criteriaArray);
				$entity[] = [
					'id' => $value->id,
					'total_risk' => $value->total_risk,
					'criteria' => $criteria,
				];	
            }	
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $StudentRisksCriterias = TableRegistry::get('Institution.StudentRisksCriterias');

            $entity = $InstitutionClassStudents
                ->find()
                ->select([
					'id' => $InstitutionClassStudents->aliasField('id'),
					'name' => 'InstitutionClasses.name',
					'education_grade' => 'EducationGrades.name',
					'academic_period' => 'AcademicPeriods.name',
					'start_date' => 'InstitutionStudents.start_date',
					'end_date' => 'InstitutionStudents.end_date',
					'status' => 'StudentStatuses.name',
                ])
				->contain(['InstitutionClasses', 'EducationGrades', 'AcademicPeriods', 'StudentStatuses'])
                ->innerJoin(
				['InstitutionStudents' => 'institution_students'],
				[
					'InstitutionStudents.student_id ='. $InstitutionClassStudents->aliasField('student_id'),
					'InstitutionStudents.academic_period_id ='. $InstitutionClassStudents->aliasField('academic_period_id'),
					'InstitutionStudents.education_grade_id ='. $InstitutionClassStudents->aliasField('education_grade_id')
				]
				)
				->where([
                    $InstitutionClassStudents->aliasField('student_id') => $params['student_id'],
                    //$InstitutionClassStudents->aliasField('academic_period_id') => $params['academic_period_id'],//POCOR-5191
                    //$InstitutionClassStudents->aliasField('education_grade_id') => $params['education_grade_id'],//POCOR-5191
                    $InstitutionClassStudents->aliasField('institution_id') => $params['institution_id'],
                ])
                ->order(['InstitutionStudents.end_date'=>'DESC'])
                ->toArray();
				
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentSubjects(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');

            $entity = $InstitutionSubjectStudents
                ->find()
                ->select([
					'id' => 'InstitutionSubjects.id',
					'name' => 'InstitutionSubjects.name',
                ])
				->contain(['InstitutionSubjects'])
				->where([
                    $InstitutionSubjectStudents->aliasField('student_id') => $params['student_id'],
                    $InstitutionSubjectStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionSubjectStudents->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionSubjectStudents->aliasField('institution_id') => $params['institution_id'],
                ])
                ->toArray();
				
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentExtracurriculars(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Extracurriculars = TableRegistry::get('student_extracurriculars');

            $entity = $Extracurriculars
                ->find()
                ->select([
					'id' => $Extracurriculars->aliasField('id'),
					'name' => $Extracurriculars->aliasField('name'),
                ])
				->where([
                    $Extracurriculars->aliasField('security_user_id') => $params['student_id'],
                    $Extracurriculars->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->toArray();
				
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentAwards(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params)) {
            $UserAwards = TableRegistry::get('user_awards');

            $result = $UserAwards
                ->find()
                ->select([
					'id' => $UserAwards->aliasField('id'),
					'award' => $UserAwards->aliasField('award'),//POCOR-7316
                    'date'=>$UserAwards->aliasField('issue_date')//POCOR-7316
                ])
				->where([
                    $UserAwards->aliasField('security_user_id') => $params['student_id'],
                ])
                ->toArray();
            //POCOR-7316 starts 
			$entity=[];
            $i=1;
            foreach($result as $row){
             $entity[]=[
                'id'=>$i,
                'name'=>$row['award'],
                'date'=>$row['date']
             ];
               $i++;
            }
            //POCOR-7316 ends
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentBehaviours(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $StudentBehaviours = TableRegistry::get('student_behaviours');
            $StaffUser = TableRegistry::get('User.Users'); //POCOR-5191
            $entity = $StudentBehaviours
                ->find()
                ->select([
					'id' => $StudentBehaviours->aliasField('id'),
					'title' => $StudentBehaviours->aliasField('title'),
					'description' => $StudentBehaviours->aliasField('description'),
					'action' => $StudentBehaviours->aliasField('action'),
					'date_of_behaviour' => $StudentBehaviours->aliasField('date_of_behaviour'),
					'time_of_behaviour' => $StudentBehaviours->aliasField('time_of_behaviour'),
					'category_name' => 'StudentBehaviourCategories.name',
                    'action_taken_by' => $StaffUser->find()->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                ])
                ->LeftJoin(
                    [$StaffUser->alias() => $StaffUser->table()], [
                        $StaffUser->aliasField('id = ') . $StudentBehaviours->aliasField('modified_user_id')
                    ]
                    
                    )
				->innerJoin(
				['StudentBehaviourCategories' => 'student_behaviour_categories'],
				[
					'StudentBehaviourCategories.id ='. $StudentBehaviours->aliasField('student_behaviour_category_id'),
				]
				)
				->where([
                    $StudentBehaviours->aliasField('student_id') => $params['student_id'],
                    //$StudentBehaviours->aliasField('academic_period_id') => $params['academic_period_id'],//POCOR-5191
                    $StudentBehaviours->aliasField('institution_id') => $params['institution_id'],
                ])
                ->order([$StudentBehaviours->aliasField('date_of_behaviour') => 'DESC']) //POCOR-5191
                ->toArray();
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $InstitutionStudentAbsences = TableRegistry::get('institution_student_absences');
			
            $absencesData = $InstitutionStudentAbsences
                ->find()
				->select([
					'id' => $InstitutionStudentAbsences->aliasField('id'),
					'date' => $InstitutionStudentAbsences->aliasField('date'),
					'month' => 'MONTH(date)',
                ])
				->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
				->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id IN') => [1,2],
                ])
				->order('month')
                ->toArray();
				
			$months = array(
							1 => 'January',
							2 => 'February',
							3 => 'March',
							4 => 'April',
							5 => 'May',
							6 => 'June',
							7 => 'July',
							8 => 'August',
							9 => 'September',
							10 => 'October',
							11 => 'November',
							12 => 'December'
						);	
			
			$monthData = [];
			$entity = [];	
			foreach($absencesData as $data) {
				foreach($months as $key => $val) {
					if(!empty($months[$data->month])) { 
						if($key == $data->month) {
							$monthData[$val][] = $data->id;
						} else {
							$monthData[$val][] = '';
						}
					}
				}
			}	
			foreach($monthData as $month => $absences) {
				$number_of_days = [];	
				foreach($absences as $absence) {
					if(!empty($absence)) {
						$number_of_days[] = $absence; 
					}
				}
				$entity[] = [
					'month' => $month,
					'number_of_days' => count($number_of_days),
				];
			}			
            return $entity;
        }
    }	
	
	public function onExcelTemplateInitialiseStudentTotalAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $InstitutionStudentAbsences = TableRegistry::get('institution_student_absences');
			
            $totalExcusedAbsences = $InstitutionStudentAbsences
				->find()
				->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
				->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id') => 1,
                ])
				->count();
				
            $totalUnxcusedAbsences = $InstitutionStudentAbsences
				->find()
				->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
				->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id') => 2,
                ])
				->count();
				
            $totalLate = $InstitutionStudentAbsences
				->find()
				->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
				->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id') => 3,
                ])
				->count();
                //POCOR-5191::Start ----    cases table
                $Cases = TableRegistry::get('institution_cases');
                $CasesRecords = TableRegistry::get('institution_case_records');
                $studentAbsences = $InstitutionStudentAbsences->find()->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])->toArray();
                $studentAbsencesIdss =[];
                foreach($studentAbsences as $k =>$student){
                    $studentAbsencesIdss[$k] = $student->id;
                }
                
                if(!empty($studentAbsencesIdss)){
                    $CasesRecordsData =$CasesRecords->find()->where([
                        $CasesRecords->aliasField('record_id in') => $studentAbsencesIdss
                    ])
                    ->group(['institution_case_id'])
                    ->toArray();

                $CasesRecordsIds =[];
                foreach($CasesRecordsData as $ki =>$CasesRecordsData1){
                    $CasesRecordsIds[$ki] = $CasesRecordsData1->institution_case_id;
                }
                
                $StaffUser = TableRegistry::get('User.Users');
                
                $caseData = $Cases
                    ->find()
                    ->select([
                        'id' => $Cases->aliasField('id'),
                        'title' => $Cases->aliasField('title'),
                        'status' => 'WorkflowSteps.name',
                        
                        'assignee' => $StaffUser->find()->func()->concat([
                            'Users.first_name' => 'literal',
                            " ",
                            'Users.last_name' => 'literal'
                        ]),
                        'created' => $Cases->aliasField('created'),
                        
                    ])
                    ->LeftJoin(
                        ['WorkflowSteps' => 'workflow_steps'],
                        [
                            'WorkflowSteps.id ='. $Cases->aliasField('status_id'),
                        ]
                        )
                    ->LeftJoin(
                        [$StaffUser->alias() => $StaffUser->table()], [
                            $StaffUser->aliasField('id = ') . $Cases->aliasField('assignee_id')
                        ]
                        
                        )
                    ->where([
                        $Cases->aliasField('id in') => $CasesRecordsIds,
                    ])
                    ->toArray();
                    foreach($caseData as $ky => $caseData1){
                        $comments = $Cases->find()->select([
                            'institution_id'=>$Cases->aliasField('institution_id'),
                            'id' =>$Cases->aliasField('id'),
                            'case_number' =>$Cases->aliasField('case_number'),
                            'title'=>$Cases->aliasField('title'),
                            'comment'=>'WorkflowTransitions.comment'
                        ])
                        ->InnerJoin(
                            ['WorkflowSteps' => 'workflow_steps'],
                            [
                                'WorkflowSteps.id ='. $Cases->aliasField('status_id'),
                            ]
                            )
                        ->InnerJoin(
                            ['Workflows' => 'workflows'],
                            [
                                'Workflows.id = WorkflowSteps.workflow_id'
                            ]
                            )
                        ->InnerJoin(
                            ['WorkflowModels' => 'workflow_models'],
                            [
                                'WorkflowModels.id = Workflows.workflow_model_id'
                            ]
                            )
                        ->InnerJoin(
                            ['WorkflowTransitions' => 'workflow_transitions'],
                            [
                                'WorkflowTransitions.workflow_model_id = WorkflowModels.id'
                            ]
                            )
                        ->where([
                            'institution_id' => $params['institution_id'],
                            $Cases->aliasField('id') => $caseData1['id'],
                        ])
                        ->toArray();
                        $comm='';
                        foreach($comments as $kyu => $comment){
                            $comm .= $comment->comment.",";
                        }
                        $comm1 = rtrim($comm,',');
                        $caseData[$ky]['action_taken'] = $comm1;
                    }	
                }
                
               
                

                //POCOR-5191 :: End
				
			$entity = [
				'total_excused_absences' => $totalExcusedAbsences,
				'total_unexcused_absences' => $totalUnxcusedAbsences,
				'total_late' => $totalLate,
				'total_number_of_absences' => ($totalExcusedAbsences +$totalUnxcusedAbsences),
			];
            foreach($caseData as $ky => $caseData1){
                $entity[$ky] = $caseData1;
            }	
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentCounsellings(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Counsellings = TableRegistry::get('Institution.Counsellings');
			
            $entity = $Counsellings
                ->find()
                ->select([
					'id' => $Counsellings->aliasField('id'),
					'date' => $Counsellings->aliasField('date'),
					'intervention' => $Counsellings->aliasField('intervention'),
					'description' => $Counsellings->aliasField('description'),
					'guidance_type' => 'GuidanceTypes.name',
                ])
				->contain(['GuidanceTypes'])
				->where([
                    $Counsellings->aliasField('student_id') => $params['student_id'],
                ])
                ->toArray();
				
            return $entity;
		}
    }	
	
	public function onExcelTemplateInitialiseStudentHealths(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $UserHealths = TableRegistry::get('user_healths');
			
            $entity = $UserHealths
                ->find()
                ->select([
					'blood_type' => $UserHealths->aliasField('blood_type'),
					'doctor_name' => $UserHealths->aliasField('doctor_name'),
					'doctor_contact' => $UserHealths->aliasField('doctor_contact'),
					'medical_facility' => $UserHealths->aliasField('medical_facility'),
					'health_insurance' => $UserHealths->aliasField('health_insurance'),
                ])
				->where([
                    $UserHealths->aliasField('security_user_id') => $params['student_id'],
                ])
                ->first();
				
				if(!empty($entity->health_insurance) && ($entity->health_insurance == 0)) {
					$entity['health_insurance'] = 'No';
				}
				if(!empty($entity->health_insurance) && ($entity->health_insurance == 1)) {
					$entity['health_insurance'] = 'Yes';
				}
            return $entity;
		}
    }	
	
	public function onExcelTemplateInitialiseStudentHealthConsultations(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Consultations = TableRegistry::get('Health.Consultations');
			
            $entity = $Consultations
                ->find()
                ->select([
					'id' => $Consultations->aliasField('id'),
					'date' => $Consultations->aliasField('date'),
					'description' => $Consultations->aliasField('description'),
					'treatment' => $Consultations->aliasField('treatment'),
					'consultation_type' => 'ConsultationTypes.name',
                ])
				->contain(['ConsultationTypes'])
				->where([
                    $Consultations->aliasField('security_user_id') => $params['student_id'],
                ])
                ->toArray();
				
            return $entity;
		}
    }	
	
	public function onExcelTemplateInitialiseStudentGuardians(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Guardians = TableRegistry::get('Guardian.Students');
			
            $guardianData = $Guardians
                ->find()
                ->select([
					'id' => $Guardians->aliasField('id'),
					'relation' => 'GuardianRelations.name',
					'first_name' => 'StudentUser.first_name',
					'last_name' => 'StudentUser.last_name',
					'contact' => 'Contacts.value',
                ])
				->contain(['StudentUser', 'GuardianRelations'])
				->leftJoin(
				['Contacts' => 'user_contacts'],
				[
					'Contacts.security_user_id ='. $Guardians->aliasField('guardian_id'),
				]
				)
				->where([
                    $Guardians->aliasField('student_id') => $params['student_id'],
                ])
				->order($Guardians->aliasField('created'))
				->limit(2)
                ->toArray();
			
			$i = 1;	
			$entity = [];
			foreach($guardianData as $value) {
				$entity['relation'.$i] = $value->relation;
				$entity['name'.$i] = $value->first_name. ' '. $value->last_name;
				$entity['contact'.$i] = $value->contact;
				$i++;
			}	
            return $entity;
		}
    }
	
	public function onExcelTemplateInitialiseStudentHouses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $institutionAssociationStudent = TableRegistry::get('institution_association_student');

            $entity = $institutionAssociationStudent
                ->find()
                ->select([
					'id' => $institutionAssociationStudent->aliasField('id'),
					'name' => 'InstitutionAssociations.name',
                ])
				->innerJoin(
				['InstitutionAssociations' => 'institution_associations'],
				[
					'InstitutionAssociations.id ='. $institutionAssociationStudent->aliasField('institution_association_id'),
				]
				)
				->where([
                    $institutionAssociationStudent->aliasField('security_user_id') => $params['student_id'],
                    $institutionAssociationStudent->aliasField('academic_period_id') => $params['academic_period_id'],
                    $institutionAssociationStudent->aliasField('education_grade_id') => $params['education_grade_id'],
                ])
                ->first();
            return $entity;
        }
    }
    //6680 starts
    public function onExcelTemplateInitialiseUserSpecialNeedsAssessments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $UserSpecialNeedsAssessmentsTbl = TableRegistry::get('user_special_needs_assessments');
            $SpecialNeedTypesTbl = TableRegistry::get('special_need_types');
            $Student = TableRegistry::get('Institution.InstitutionClassStudents');

            $entity = $Student
                ->find()
                ->select([
                    'id' => $SpecialNeedTypesTbl->aliasField('id'),
                    'special_need_type' => $SpecialNeedTypesTbl->aliasField('name')
                ])
                ->innerJoin(
                [$UserSpecialNeedsAssessmentsTbl->alias() => $UserSpecialNeedsAssessmentsTbl->table()],
                [
                    $UserSpecialNeedsAssessmentsTbl->aliasField('security_user_id ='). $Student->aliasField('student_id')
                ]
                )
                ->leftJoin([$SpecialNeedTypesTbl->alias() => $SpecialNeedTypesTbl->table()],
                [
                    $SpecialNeedTypesTbl->aliasField('id =') . $UserSpecialNeedsAssessmentsTbl->aliasField('special_need_type_id')
                    
                ])
                ->where([
                    $Student->aliasField('institution_id') => $params['institution_id'],
                    $Student->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Student->aliasField('education_grade_id') => $params['education_grade_id'],
                    $Student->aliasField('student_id') => $params['student_id'],
                ])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseUserContacts(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $UserContacts = TableRegistry::get('user_contacts');

            $entity = $UserContacts
                ->find()
                ->select([
                    'id' => $UserContacts->aliasField('id'),
                    'contact' => $UserContacts->aliasField('value'),
                ])
                ->where([
                    $UserContacts->aliasField('security_user_id') => $params['student_id'],
                    $UserContacts->aliasField('preferred') => 1,
                ])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentMoterDetails(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Guardians = TableRegistry::get('Guardian.Students');
            $guardianData = $Guardians
                ->find()
                ->select([
                    'id' => $Guardians->aliasField('id'),
                    'relation' => 'GuardianRelations.name',
                    'first_name' => 'StudentUser.first_name',
                    'last_name' => 'StudentUser.last_name',
                    'contact' => 'Contacts.value',
                ])
                ->contain(['StudentUser', 'GuardianRelations'])
                ->leftJoin(
                ['Contacts' => 'user_contacts'],
                [
                    'Contacts.security_user_id ='. $Guardians->aliasField('guardian_id'),
                    'Contacts.preferred ='. 1
                ]
                )
                ->where([
                    $Guardians->aliasField('student_id') => $params['student_id'],
                    'GuardianRelations.name' => 'Mother',
                    'Contacts.preferred' => 1
                ])
                ->order($Guardians->aliasField('created'))
                ->limit(1)
                ->toArray();
            $entity = [];
            foreach($guardianData as $value) {
                $entity['mother_relation'] = $value->relation;
                $entity['mother_name'] = $value->first_name. ' '. $value->last_name;
                $entity['mother_contact'] = $value->contact;
            }   
            return $entity;
        }
    }//6680 ends
    //POCOR 7316 starts
    public function onExcelTemplateInitialiseInstitutionSubjectStudentsWithName(Event $event, array $params, ArrayObject $extra){
      
        if (array_key_exists('student_id', $params)&& array_key_exists('institution_id', $params) ){
            
            $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $Assessments=TableRegistry::get('assessments');
            $subjectObj = $SubjectStudents->find()
                           ->select([
                               "assessment_id"=> $Assessments->aliasField('id'),
                               "academic_period_name"=> 'AcademicPeriods.name',
                               "academic_period_id"=> 'AcademicPeriods.id',
                               "education_programme_name"=> 'EducationProgrammes.name',
                               "education_programme_id"=> 'EducationProgrammes.id',
                               "education_grade_name"=>'EducationGrades.name',
                               "education_grade_id"=>'EducationGrades.id',
                               "institution_subject_name"=> 'InstitutionSubjects.name',
                               "institution_subject_id"=> 'InstitutionSubjects.id',
                               "education_subject_name"=> 'EducationSubjects.name',
                               "education_subject_id"=>$SubjectStudents->aliasField('education_subject_id'),
                               "total_mark"=> $SubjectStudents->aliasField('total_mark')
                           ])
                           ->contain([
                                'EducationSubjects','InstitutionSubjects','AcademicPeriods','EducationGrades','StudentStatuses'
                           ])
                           ->matching('EducationGrades.EducationProgrammes')
                           ->InnerJoin([$Assessments->alias() => $Assessments->table()], [
                                $Assessments->aliasField('academic_period_id = ') . $SubjectStudents->aliasField('academic_period_id'),
                                $Assessments->aliasField('education_grade_id = ') . $SubjectStudents->aliasField('education_grade_id')
                           ])
                           ->where([
                                $SubjectStudents->aliasField('student_id') => $params['student_id'],
                                $SubjectStudents->aliasField('institution_id') => $params['institution_id'],
                                'StudentStatuses.id In'=>[1,6,7,8]
                           ])
                           ->toArray();
          
                  
          
            $assessment_ids=[];
            $institution_subject_student=[];
            if(!empty($subjectObj)) {
                     $i=1;
                     foreach ($subjectObj as  $subject) {
                            $id =$i;
                            $entity[] = [
                                'id' => $id,
                                'assessment_id'=>$subject['assessment_id'],
                                "academic_period_name"=>$subject["academic_period_name"],
                                "education_programme_name"=>$subject["education_programme_name"],
                                "education_grade_name"=>$subject["education_grade_name"],
                                "institution_subject_name"=>$subject["institution_subject_name"],
                                "education_subject_name"=> $subject["education_subject_name"],
                                "name"=>$subject["institution_subject_name"],
                                "subjectName"=>$subject["education_subject_name"],
                                "education_subject_id"=>$subject["education_subject_id"],
                                "total_mark"=>$subject["total_mark"], 
                            ];
                            if(!in_array($subject['assessment_id'], $assessment_ids)) {
                                 $assessment_ids[]=$subject['assessment_id'];
                            }
                            $institution_subject_student[]=[
                                'id' => $id,
                                'assessment_id'=>$subject['assessment_id'],
                                "academic_period_id"=>$subject["academic_period_id"],
                                "education_programme_id"=>$subject["education_programme_id"],
                                "education_grade_id"=>$subject["education_grade_id"],
                                "institution_subject_id"=>$subject["institution_subject_id"],
                                "education_subject_id"=>$subject["education_subject_id"],
                            ];
                           
                            $i++;
                    }
                    $extra['assessment_ids']=  $assessment_ids;
                    $extra['institution_subject_student']= $institution_subject_student;
               }
                 
            
            return $entity;
    }}   
   
    public function onExcelTemplateInitialiseAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
       
        if (array_key_exists('assessment_ids', $extra)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');

            $entity = $AssessmentPeriods->find()
                ->where([
                    $AssessmentPeriods->aliasField('assessment_id IN ') => $extra['assessment_ids'],
                ])
                ->order([$AssessmentPeriods->aliasField('start_date')])
                ->toArray();
          
            if (count($entity) > 0) {
                $extra['assessment_period'] = $entity;
            }
            return $entity;
        }
    }
     
    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
      
        
       if(array_key_exists('student_id',$params) && array_key_exists('institution_id',$params) && array_key_exists('assessment_period',$extra)&& array_key_exists('institution_subject_student',$extra) ){
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $entity=[];
            $institution_subject_student =$extra['institution_subject_student'];
            $entity=[];
           
            foreach($institution_subject_student as $row){
               
                $AssessmentResultObj= $AssessmentItemResults->find()
                                        ->where([
                                        $AssessmentItemResults->aliasField('student_id')=>$params['student_id'],
                                        $AssessmentItemResults->aliasField('institution_id')=>$params['institution_id'],
                                        $AssessmentItemResults->aliasField('assessment_id')=>$row['assessment_id'],
                                        $AssessmentItemResults->aliasField('education_subject_id')=>$row['education_subject_id'],
                                        $AssessmentItemResults->aliasField('academic_period_id')=>$row['academic_period_id'],
                                        ])
                                        ->toArray();                       
          
               if($AssessmentResultObj!=[]){
                 foreach($AssessmentResultObj as $res){
                   
                    $entity[]=["id"=>$row['id'],
                    "assessment_period_id"=>$res['assessment_period_id'],
                    "marks_formatted"=>number_format($res['marks'], 2)
                 ];
                 }}
        }
         return $entity;
    }


   }
   //POCOR-7316 ends
}

