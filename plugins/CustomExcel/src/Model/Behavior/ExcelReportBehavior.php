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
use Cake\Utility\Hash;
use Cake\Collection\Collection;
use Cake\Log\Log;

use PHPExcel_IOFactory;
use PHPExcel_Worksheet;

class ExcelReportBehavior extends Behavior
{
    protected $_defaultConfig = [
        'folder' => 'export',
        'subfolder' => 'customexcel'
    ];

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
        $events['ExcelTemplates.Model.onGetExcelTemplateVars'] = 'onGetExcelTemplateVars';
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

    public function onGetExcelTemplateVars(Event $event, ArrayObject $extra)
    {
        $controller = $event->subject();
        $params = $this->getParams($controller);
        $vars = $this->getVars($params, $extra);

        $results = Hash::flatten($vars);
        pr($results);
        die;
    }

    public function onRenderExcelTemplate(Event $event, ArrayObject $extra)
    {
        $controller = $event->subject();
        $params = $this->getParams($controller);
        $extra['vars'] = $this->getVars($params, $extra);

        $extra['file'] = $this->config('filename') . '_' . date('Ymd') . 'T' . date('His') . '.xlsx';
        $extra['path'] = WWW_ROOT . $this->config('folder') . DS . $this->config('subfolder') . DS;
        $extra['download'] = true;

        $filepath = $extra['path'] . $extra['file'];
        $extra['file_path'] = $extra['path'] . $extra['file'];

        $objPHPExcel = $this->loadExcelTemplate($extra);
        $this->generateExcel($objPHPExcel, $extra);
        $this->saveExcel($objPHPExcel, $filepath);

        if ($extra->offsetExists('tmp_file_path')) {
            // delete temporary excel file after save
            $this->deleteFile($extra['tmp_file_path']);
        }

        if ($extra['download']) {
            $this->downloadExcel($filepath);
            // delete excel file after download
            $this->deleteFile($filepath);
        }
    }

    public function loadExcelTemplate(ArrayObject $extra)
    {
        $model = $this->_table;

        $ExcelTemplates = TableRegistry::get('CustomExcel.ExcelTemplates');
        $results = $ExcelTemplates->find()->where([$ExcelTemplates->aliasField('module') => $model->registryAlias()]);

        if ($results->isEmpty()) {
            $objPHPExcel = new \PHPExcel();
        } else {
            // Read from excel template attachment then create as temporary file in server so that can read back the same file and read as PHPExcel object
            $entity = $results->first();

            if ($entity->has('file_name')) {
                $pathInfo = pathinfo($entity->file_name);
                $filename = $this->config('filename') . '_Template_' . date('Ymd') . 'T' . date('His') . '.' . $pathInfo['extension'];
                $file = $this->getFile($entity->file_content);

                // Create a temporary file
                $filepath = $extra['path'] . DS . $filename;
                $extra['tmp_file_path'] = $filepath;

                $excelTemplate = new File($filepath, true, 0777);
                $excelTemplate->write($file);
                $excelTemplate->close();
                // End create a temporary file
                try {
                    // Read back from same temporary file
                    $inputFileType = PHPExcel_IOFactory::identify($filepath);
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                    $objPHPExcel = $objReader->load($filepath);
                    // End read back from same temporary file
                } catch(Exception $e) {
                    Log::write('debug', $e->getMessage());
                }
            }
        }

        return $objPHPExcel;
    }

    public function generateExcel($objPHPExcel, ArrayObject $extra)
    {
        foreach ($objPHPExcel->getWorksheetIterator() as $objWorksheet) {
            $cells = $objWorksheet->getCellCollection();

            // $placeholder = new ArrayObject([]);
            $extra['placeholder'] = [];
            foreach ($cells as $cellCoordinate) {
                // if is basic placeholder then replace first, else added into $placeholder to process later
                $this->extractPlaceholder($objWorksheet, $cellCoordinate, $extra);
            }
            // pr($extra['placeholder']);
        }
    }

    public function saveExcel($objPHPExcel, $filepath)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filepath);
    }

    public function downloadExcel($filepath)
    {
        $filename = basename($filepath);

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filepath));
        echo file_get_contents($filepath);
    }

    public function deleteFile($filepath)
    {
        $file = new File($filepath);
        $file->delete();
    }

    public function getParams($controller)
    {
        $params = $controller->request->query;
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

    private function getFile($phpResourceFile)
    {
        $file = ''; 
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192); 
        } 
        fclose($phpResourceFile);

        return $file;
    }

    private function extractPlaceholder($objWorksheet, $cellCoordinate, $extra)
    {
        $objCell = $objWorksheet->getCell($cellCoordinate);

        if (is_object($objCell->getValue())) {
            $cellValue = $objCell->getValue()->getPlainText();
        } else {
            $cellValue = $objCell->getValue();
        }

        if (strlen($cellValue) > 0) {
            $pos = strpos($cellValue, '${');

            if ($pos !== false) {
                if ($this->isBasicType($cellValue)) {
                    $this->string($objWorksheet, $objCell, $cellValue, $extra);
                } else {
                    $extra['placeholder'][$cellCoordinate] = $cellValue;
                }
            }
        }
    }

    private function isBasicType($str)
    {
        $advancedTypes = ['${"repeatRows":', '${"repeatColumns":', '${"map":'];
        foreach ($advancedTypes as $key => $value) {
            $pos = strpos($str, $value);
            if ($pos !== false) {
                return false;
            }
        }

        return true;
    }

    private function string($objWorksheet, $objCell, $search, $extra)
    {
        $format = '${%s}';
        $vars = $extra->offsetExists('vars') ? $extra['vars'] : [];

        $strArray = explode('${', $search);
        array_shift($strArray); // first element will not contain the placeholder

        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos !== false) {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);
                $value = Hash::get($vars, $placeholder);

                if (!is_null($value)) {
                    $search = str_replace($replace, $value, $search);
                }
            }
        }

        $cellCoordinate = $objCell->getCoordinate();
        $objWorksheet->setCellValue($cellCoordinate, $search);
    }
}
