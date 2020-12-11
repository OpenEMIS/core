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
                'InstitutionReportCards'
            ]
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateAfterGenerate'] = 'onExcelTemplateAfterGenerate';
        $events['ExcelTemplates.Model.afterRenderExcelTemplate'] = 'afterRenderExcelTemplate';
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
		//echo '<pre>';print_r($extra);die;
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
    

}
