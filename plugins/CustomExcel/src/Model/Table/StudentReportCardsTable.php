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
				//echo '<pre>';print_r($entity);die;
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
	
}
