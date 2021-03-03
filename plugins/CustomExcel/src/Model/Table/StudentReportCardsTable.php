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
	
}
