<?php
namespace CustomExcel\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Log\Log;

use PHPExcel_IOFactory;
use PHPExcel_Worksheet;

class ExcelReportBehavior extends Behavior
{
    protected $_defaultConfig = [
        'folder' => 'export',
        'subfolder' => 'customexcel'
    ];

    private $vars = [];

	public function initialize(array $config)
	{
		parent::initialize($config);

        $model = $this->_table;
        $folder = WWW_ROOT . $this->config('folder');
        $subfolder = WWW_ROOT . $this->config('folder') . DS . $this->config('subfolder');
        if (!array_key_exists('filename', $config)) {
            $this->config('filename', $model->alias());
        }

        new Folder($folder, true, 0777);
        new Folder($subfolder, true, 0777);
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
        $events['ExcelTemplates.Model.initializeData'] = 'initializeExcelTemplateData';
        $events['ExcelTemplates.Model.onRenderExcelTemplate'] = 'onRenderExcelTemplate';
		return $events;
    }

    public function initializeExcelTemplateData(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $registryAlias = $model->registryAlias();

        $ExcelTemplates = TableRegistry::get('CustomExcel.ExcelTemplates');
        $excelTemplateResults = $ExcelTemplates->find()
            ->where([$ExcelTemplates->aliasField('module') => $registryAlias])
            ->all();

        if ($excelTemplateResults->isEmpty()) {
            $excelTemplateEntity = $ExcelTemplates->newEntity([
                'module' => $registryAlias
            ]);

            if (!$ExcelTemplates->save($excelTemplateEntity)) {
                Log::write('debug', $excelTemplateEntity->errors());
            }
        }
    }

    public function onRenderExcelTemplate(Event $event, ArrayObject $extra)
    {
        $controller = $event->subject();
        $controller = $event->subject();
        $params = $this->getParams($controller);
        $this->vars = $this->getVars($params, $extra);

        // to-do
        // $this->loadExcelTemplate();
        // $this->generateExcel();
        // $this->saveExcel();
        // $this->downloadExcel();
    }

    public function getParams($controller)
    {
        $params = $controller->request->query;
        $session = $controller->request->session();

        if ($session->check('Institution.Institutions.id')) {
            $params['institution_id'] = $session->read('Institution.Institutions.id'); 
        }

        return $params;
    }

    public function getVars($params, ArrayObject $extra)
    {
        $model = $this->_table;

        $variables = $this->config('variables');
        $variableValues = [];
        foreach ($variables as $var) {
            $event = $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateInitialise'.$var, [$params, $extra], $this);
            if ($event->isStopped()) { return $event->result; }
            if ($event->result) {
                $variableValues[$var] = $event->result;
            }
        }

        return $variableValues;
    }
}
