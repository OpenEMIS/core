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
        $fileName = $StaffReportCardData->academic_period->name . '_' . $StaffReportCardData->staff_template->code. '_' . $StaffReportCardData->institution->name . '.' . $this->fileType;
        $filepath = $extra['file_path'];
        $fileContent = file_get_contents($filepath);
        $status = $StaffReportCards::GENERATED;
		//echo '<pre>';print_r($fileContent);die;
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
}
