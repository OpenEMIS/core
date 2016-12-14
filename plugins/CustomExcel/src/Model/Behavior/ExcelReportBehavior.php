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

    // function name and keyword pairs
    private $advancedTypes = [
        'row' => 'repeatRows',
        'column' => 'repeatColumns',
        'match' => 'match'
    ];
    private $suppressAutoInsertNewRow = false;

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
        $model = $this->_table;
        $params = $model->getQueryString();
        $vars = $this->getVars($params, $extra);

        $results = Hash::flatten($vars);
        pr($results);
        die;
    }

    public function onRenderExcelTemplate(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $params = $model->getQueryString();
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
            $extra['placeholders'] = [];
            $this->processBasicPlaceholder($objWorksheet, $extra);

            if (!empty($extra['placeholders'])) {
                $this->processAdvancedPlaceholder($objWorksheet, $extra);
            }
        }

        // to force the first sheet active
        $objPHPExcel->setActiveSheetIndex(0);
    }

    public function renderCell($objWorksheet, $objCell, $cellCoordinate, $cellValue, $attr, $extra)
    {
        $cellStyle = $attr['style'];
        $columnWidth = $attr['columnWidth'];
        $targetColumnValue = $objWorksheet->getCell($cellCoordinate)->getColumn();

        $objWorksheet->setCellValue($cellCoordinate, $cellValue);
        $objWorksheet->duplicateStyle($cellStyle, $cellCoordinate);

        // set column width to follow placeholder
        $objWorksheet->getColumnDimension($targetColumnValue)->setAutoSize(false);
        $objWorksheet->getColumnDimension($targetColumnValue)->setWidth($columnWidth);
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
        $model = $this->_table;
        $params = $model->getQueryString();
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

    private function getAdvancedTypeKeyword($keyword)
    {
        $format = '${"%s":';
        $value = sprintf($format, $keyword);

        return $value;
    }

    private function isBasicType($str)
    {
        foreach ($this->advancedTypes as $function => $keyword) {
            $value = $this->getAdvancedTypeKeyword($keyword);

            $pos = strpos($str, $value);
            if ($pos !== false) {
                return false;
            }
        }

        return true;
    }

    private function convertPlaceHolderToArray($str)
    {
        $pos = strpos($str, '$');
        $json = substr($str, $pos+1, strlen($str));
        $jsonArray = json_decode($json, true);

        return $jsonArray;
    }

    private function extractAttr($jsonArray, $keyword, $extra)
    {
        $attr = [];

        $settings = array_key_exists($keyword, $jsonArray) ? $jsonArray[$keyword] : [];
        $displayValue = array_key_exists('displayValue', $settings) ? $settings['displayValue'] : null;
        $attr['type'] = array_key_exists('type', $settings) ? $settings['type'] : null;
        $attr['format'] = array_key_exists('format', $settings) ? $settings['format'] : null;
        $attr['children'] = array_key_exists('children', $settings) ? $settings['children'] : [];

        $placeholder = $this->formatPlaceholder($displayValue);
        $attr['data'] = !is_null($displayValue) ? Hash::extract($extra['vars'], $placeholder) : [];

        return $attr;
    }

    private function formatPlaceholder($str, $offset=1, $length=0, $replacement=['{n}'])
    {
        $placeholderArray = explode('.', $str);
        array_splice($placeholderArray, $offset, $length, $replacement);
        $placeholder = implode(".", $placeholderArray);

        return $placeholder;
    }

    private function formatFilter($filterStr, $filterValue)
    {
        $filterArray = explode(".", $filterStr);
        $filterKey = $filterArray[1];

        return "[".$filterKey."=".$filterValue."]"; 
    }

    private function splitDisplayValue($displayValue)
    {
        $displayArray = explode(".", $displayValue);
        $placeholderPrefix = current($displayArray);
        array_shift($displayArray);
        $placeholderSuffix = implode(".", $displayArray);

        return [$placeholderPrefix, $placeholderSuffix];
    }

    private function processBasicPlaceholder($objWorksheet, $extra)
    {
        $cells = $objWorksheet->getCellCollection();

        foreach ($cells as $cellCoordinate) {
            $objCell = $objWorksheet->getCell($cellCoordinate);

            if (is_object($objCell->getValue())) {
                $cellValue = $objCell->getValue()->getPlainText();
            } else {
                $cellValue = $objCell->getValue();
            }

            if (strlen($cellValue) > 0) {
                $pos = strpos($cellValue, '${');

                if ($pos !== false) {
                    // if is basic placeholder then replace first, else added into $placeholder to process later
                    if ($this->isBasicType($cellValue)) {
                        $this->string($objWorksheet, $objCell, $cellValue, $extra);
                    } else {
                        $extra['placeholders'][$cellCoordinate] = $cellValue;
                    }
                }
            }
        }
    }

    private function processAdvancedPlaceholder($objWorksheet, $extra)
    {
        foreach ($extra['placeholders'] as $cellCoordinate => $cellValue) {
            $objCell = $objWorksheet->getCell($cellCoordinate);

            foreach ($this->advancedTypes as $function => $keyword) {
                $value = $this->getAdvancedTypeKeyword($keyword);

                $pos = strpos($cellValue, $value);
                if ($pos !== false) {
                    if (method_exists($this, $function)) {
                        // pr("function: " . $function . " >>>>> keyword: " .$keyword . " >>>>> cellCoordinate: " .$cellCoordinate);
                        $jsonArray = $this->convertPlaceHolderToArray($cellValue);
                        $attr = $this->extractAttr($jsonArray, $keyword, $extra);

                        // columnIndexFromString(): Column index start from 1
                        $columnValue = $objCell->getColumn();
                        $attr['columnValue'] = $columnValue;
                        $attr['columnIndex'] = $objCell->columnIndexFromString($columnValue);
                        $attr['columnWidth'] = $objWorksheet->getColumnDimension($columnValue)->getWidth();
                        $attr['rowValue'] = $objCell->getRow();
                        $coordinate = $objCell->getCoordinate();
                        $attr['coordinate'] = $coordinate;
                        $attr['style'] = $objCell->getStyle($coordinate);

                        $this->$function($objWorksheet, $objCell, $attr, $extra);
                    } else {
                        Log::write('debug', 'Function ' . $function . ' is not exists');
                    }
                }
            }
        }
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

    private function row($objWorksheet, $objCell, $attr, $extra)
    {
        $columnValue = $attr['columnValue'];
        $rowValue = $attr['rowValue'];
        foreach ($attr['data'] as $key => $value) {
            // skip first row don't need to auto insert new row
            if (!$this->suppressAutoInsertNewRow && $rowValue != $attr['rowValue']) {
                $objWorksheet->insertNewRowBefore($rowValue);
            }

            $displayValue = $value;
            switch ($attr['type']) {
                case 'date':
                    $displayValue = !is_null($attr['format']) ? $displayValue->format($attr['format']) : $displayValue;
                    break;
            }

            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objWorksheet, $objCell, $cellCoordinate, $displayValue, $attr, $extra);

            $rowValue++;
        }

        // only insert new row for the first column which have repeat-rows
        if ($this->suppressAutoInsertNewRow == false) {
            $this->suppressAutoInsertNewRow = true;
        }
    }

    private function column($objWorksheet, $objCell, $attr, $extra)
    {
        $columnIndex = $attr['columnIndex'];
        $rowValue = $attr['rowValue'];
        $nestedColumn = array_key_exists('children', $attr) ? $attr['children'] : [];

        foreach ($attr['data'] as $key => $value) {
            // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
            $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

            if (!empty($nestedColumn)) {
                // $nestedColumnArray = array_key_exists('repeatColumns', $nestedColumn) ? $nestedColumn['repeatColumns'] : [];
                $keyword = $this->advancedTypes[__FUNCTION__];
                $nestedAttr = $this->extractAttr($nestedColumn, $keyword, $extra);

                $nestedColumnIndex = $columnIndex;
                // always output children to the immediate next row
                $nestedRowValue = $rowValue + 1;
                foreach ($nestedAttr['data'] as $nestedKey => $nestedValue) {
                    // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                    $nestedColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-1);
                    $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;

                    $this->renderCell($objWorksheet, $objCell, $nestedCellCoordinate, $nestedValue, $attr, $extra);

                    $nestedColumnIndex++;
                }

                // merge parent cell following number of columns occupied by children
                if ($nestedColumnIndex > $columnIndex) {
                    $rangeColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-2);

                    $mergeRange = $cellCoordinate.":".$rangeColumnValue.$rowValue;
                    $objWorksheet->mergeCells($mergeRange);
                    // fix border doesn't set after cell is merged
                    $cellStyle = $attr['style'];
                    $objWorksheet->duplicateStyle($cellStyle, $mergeRange);

                    $columnIndex = $nestedColumnIndex-1;
                }
            }

            $columnIndex++;
        }
    }
}
