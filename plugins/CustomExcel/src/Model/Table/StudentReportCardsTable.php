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
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentUsers(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $Student = TableRegistry::get('Institution.InstitutionClassStudents');

            $entity = $Student
                ->find()
                ->select([
					'first_name' => 'Users.first_name',
					'last_name' => 'Users.last_name',
					'email' => 'Users.email',
					'photo_content' => 'Users.photo_content',
					'address' => 'Users.address',
					'date_of_birth' => 'Users.date_of_birth',
					'identity_number' => 'Users.identity_number',
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
				
				$result = [];
				$result = [
					'name' => $entity->first_name.' '.$entity->last_name,
					'identity_number' => $entity->identity_number,
					'photo_content' => $entity->photo_content,
					'email' => $entity->email,
					'address' => $entity->address,
					'date_of_birth' => $entity->date_of_birth,
					'gender' => $entity->gender,
				];
            return $result;
        }
    }
	
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
                    $InstitutionClassStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionClassStudents->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionClassStudents->aliasField('institution_id') => $params['institution_id'],
                ])
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
            $Extracurriculars = TableRegistry::get('Student.Extracurriculars');

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

            $entity = $UserAwards
                ->find()
                ->select([
					'id' => $UserAwards->aliasField('id'),
					'award' => $UserAwards->aliasField('award'),
                ])
				->where([
                    $UserAwards->aliasField('security_user_id') => $params['student_id'],
                ])
                ->toArray();
				
            return $entity;
        }
    }
	
	public function onExcelTemplateInitialiseStudentBehaviours(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {
            $StudentBehaviours = TableRegistry::get('student_behaviours');

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
                ])
				->innerJoin(
				['StudentBehaviourCategories' => 'student_behaviour_categories'],
				[
					'StudentBehaviourCategories.id ='. $StudentBehaviours->aliasField('student_behaviour_category_id'),
				]
				)
				->where([
                    $StudentBehaviours->aliasField('student_id') => $params['student_id'],
                    $StudentBehaviours->aliasField('academic_period_id') => $params['academic_period_id'],
                    $StudentBehaviours->aliasField('institution_id') => $params['institution_id'],
                ])
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
				
			$entity = [
				'total_excused_absences' => $totalExcusedAbsences,
				'total_unexcused_absences' => $totalUnxcusedAbsences,
				'total_late' => $totalLate,
				'total_number_of_absences' => ($totalExcusedAbsences +$totalUnxcusedAbsences),
			];
				
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
}
