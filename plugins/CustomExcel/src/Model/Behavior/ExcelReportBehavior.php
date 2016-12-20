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
use PHPExcel_Cell;
use PHPExcel_Cell_DataValidation;

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
        'match' => 'match',
        'dropdown' => 'dropdown'
    ];
    private $suppressAutoInsertNewRow = false;
    private $suppressAutoInsertNewColumn = false;

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
            $this->processWorksheet($objPHPExcel, $objWorksheet, $extra);
        }

        // to force the first sheet active
        $objPHPExcel->setActiveSheetIndex(0);
    }

    public function renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $cellValue, $attr, $extra)
    {
        $type = $attr['type'];
        $format = $attr['format'];
        $cellStyle = $attr['style'];
        $columnWidth = $attr['columnWidth'];
        $targetColumnValue = $objWorksheet->getCell($cellCoordinate)->getColumn();

        switch($type) {
            case 'number':
                // set to two decimal places
                if (!is_null($format)) {
                    $formatting = number_format(0, $format);
                    $cellStyle->getNumberFormat()->setFormatCode($formatting);
                }
                break;
            case 'date':
                $cellValue = !is_null($format) ? $cellValue->format($format) : $cellValue;
                break;
        }

        // set cell style to follow placeholder
        $objWorksheet->setCellValue($cellCoordinate, $cellValue);
        $objWorksheet->duplicateStyle($cellStyle, $cellCoordinate);

        // set column width to follow placeholder
        $objWorksheet->getColumnDimension($targetColumnValue)->setAutoSize(false);
        $objWorksheet->getColumnDimension($targetColumnValue)->setWidth($columnWidth);
    }

    public function renderDropdown($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $cellValue, $attr, $extra)
    {
        $_attr = [
            'source' => '',
            'promptTitle' => __('Select from list'),
            'prompt' => __('Please select a value from the dropdown list.'),
            'errorTitle' => __('Input error'),
            'error' => __('Value is not in list.')
        ];
        $_attr = array_merge($_attr, $attr['dropdown']);

        $objValidation = $objWorksheet->getCell($cellCoordinate)->getDataValidation();
        $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
        $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
        $objValidation->setAllowBlank(false);
        $objValidation->setShowInputMessage(true);
        $objValidation->setShowErrorMessage(true);
        $objValidation->setShowDropDown(true);
        $objValidation->setPromptTitle($_attr['promptTitle']);
        $objValidation->setPrompt($_attr['prompt']);
        $objValidation->setErrorTitle($_attr['errorTitle']);
        $objValidation->setError($_attr['error']);

        if (is_array($_attr['source'])) {
            $list = implode(",", $_attr['source']);
            $format = '"%s"';
            $value = sprintf($format, $list);

            $objValidation->setFormula1($value);
        } else {
            list($sheetName, $coordinate) = explode(".", $_attr['source']);
            $referencesWorksheet = $objPHPExcel->getSheetByName($sheetName);
            $referencesCell = $referencesWorksheet->getCell($coordinate);
            $columnValue = $referencesCell->getColumn();
            $rowValue = $referencesCell->getRow();
            $highestRow = $referencesWorksheet->getHighestRow($columnValue);

            $listLocation = sprintf('%s!$%s$%s:$%s$%s', "'$sheetName'", $columnValue, $rowValue, $columnValue, $highestRow);
            $objValidation->setFormula1($listLocation);
        }

        // set to empty to remove the placeholder
        $objWorksheet->setCellValue($cellCoordinate, $cellValue);
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

    private function getPlaceholderData($placeholder, $extra)
    {
        $formattedPlaceholder = $this->formatPlaceholder($placeholder);
        $placeholderData = !is_null($placeholder) ? Hash::extract($extra['vars'], $formattedPlaceholder) : [];

        return $placeholderData;
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

    private function extractPlaceholderAttr($jsonArray, $keyword, $extra)
    {
        $attr = [];

        $settings = array_key_exists($keyword, $jsonArray) ? $jsonArray[$keyword] : [];
        $displayValue = array_key_exists('displayValue', $settings) ? $settings['displayValue'] : null;
        $attr['displayValue'] = array_key_exists('displayValue', $settings) ? $settings['displayValue'] : null;
        $attr['type'] = array_key_exists('type', $settings) ? $settings['type'] : null;
        $attr['format'] = array_key_exists('format', $settings) ? $settings['format'] : null;
        $attr['children'] = array_key_exists('children', $settings) ? $settings['children'] : [];
        $attr['rows'] = array_key_exists('rows', $settings) ? $settings['rows'] : [];
        $attr['columns'] = array_key_exists('columns', $settings) ? $settings['columns'] : [];

        // Start attributes  for dropdown
        $dropdownAttrs = ['source', 'promptTitle', 'prompt', 'errorTitle', 'error'];
        $attr['dropdown'] = [];
        foreach ($dropdownAttrs as $attrName) {
            if (array_key_exists($attrName, $settings)) {
                $attr['dropdown'][$attrName] = $settings[$attrName];
            }
        }
        // End attributes  for dropdown

        $attr['data'] = $this->getPlaceholderData($displayValue, $extra);

        return $attr;
    }

    private function extractCellAttr($objWorksheet, $objCell)
    {
        $attr = [];

        // columnIndexFromString(): Column index start from 1
        $columnValue = $objCell->getColumn();
        $attr['columnValue'] = $columnValue;
        $attr['columnIndex'] = $objCell->columnIndexFromString($columnValue);
        $attr['columnWidth'] = $objWorksheet->getColumnDimension($columnValue)->getWidth();
        $attr['rowValue'] = $objCell->getRow();
        $coordinate = $objCell->getCoordinate();
        $attr['coordinate'] = $coordinate;
        $attr['style'] = $objCell->getStyle($coordinate);

        return $attr;
    }

    private function formatPlaceholder($str, $offset=1, $length=0, $replacement=['{n}'])
    {
        $placeholderArray = explode('.', $str);
        array_splice($placeholderArray, $offset, $length, $replacement);
        $placeholder = implode(".", $placeholderArray);

        return $placeholder;
    }

    private function formatFilter($filterStr)
    {
        $value = null;

        $filterArray = explode(".", $filterStr);
        if (sizeof($filterArray) == 2) {
            $filterKey = $filterArray[1];
            $value = "[$filterKey=%s]";
        } else {
            $value = "[$filterStr=%s]";
        }

        return $value;
    }

    private function splitDisplayValue($displayValue)
    {
        $displayArray = explode(".", $displayValue);
        $placeholderPrefix = current($displayArray);
        array_shift($displayArray);
        $placeholderSuffix = implode(".", $displayArray);

        return [$placeholderPrefix, $placeholderSuffix];
    }

    private function processWorksheet($objPHPExcel, $objWorksheet, $extra)
    {
        $extra['placeholders'] = [];
        $this->processBasicPlaceholder($objPHPExcel, $objWorksheet, $extra);

        if (!empty($extra['placeholders'])) {
            $this->processAdvancedPlaceholder($objPHPExcel, $objWorksheet, $extra);
        }
    }

    private function processBasicPlaceholder($objPHPExcel, $objWorksheet, $extra)
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
                        Log::write('debug', $cellCoordinate . ' - ' . $cellValue);
                        $this->string($objPHPExcel, $objWorksheet, $objCell, $cellValue, $extra);
                    } else {
                        $columnValue = $objCell->getColumn();
                        $rowValue = $objCell->getRow();
                        $columnIndex = $objCell->columnIndexFromString($columnValue);
                        $extra['placeholders'][$columnIndex][$rowValue] = $cellValue;
                    }
                }
            }
        }
    }

    private function processAdvancedPlaceholder($objPHPExcel, $objWorksheet, $extra)
    {
        // sort by column index so that to process the first column first
        ksort($extra['placeholders']);

        while(!empty($extra['placeholders'])) {
            $columnIndex = key($extra['placeholders']);
            $columnValue = PHPExcel_Cell::stringFromColumnIndex($columnIndex-1);
            $rowsObj = current($extra['placeholders']);
            $rowValue = key($rowsObj);
            $cellValue = current($rowsObj);
            
            $cellCoordinate = $columnValue.$rowValue;
            $objCell = $objWorksheet->getCell($cellCoordinate);

            foreach ($this->advancedTypes as $function => $keyword) {
                $value = $this->getAdvancedTypeKeyword($keyword);

                $pos = strpos($cellValue, $value);
                if ($pos !== false) {
                    if (method_exists($this, $function)) {
                        $jsonArray = $this->convertPlaceHolderToArray($cellValue);
                        if (!empty($jsonArray)) {
                            $placeHolderAttr = $this->extractPlaceholderAttr($jsonArray, $keyword, $extra);
                            $cellAttr = $this->extractCellAttr($objWorksheet, $objCell);
                            $attr = array_merge($placeHolderAttr, $cellAttr);

                            Log::write('debug', $cellCoordinate . ' - ' . $cellValue);
                            $this->$function($objPHPExcel, $objWorksheet, $objCell, $attr, $extra);
                        } else {
                            Log::write('debug', $cellCoordinate . ' - ' . $cellValue . ' is not a valid json format');
                        }
                    } else {
                        Log::write('debug', 'Function ' . $function . ' is not exists');
                    }
                }
            }

            unset($extra['placeholders'][$columnIndex][$rowValue]);
            if (empty($extra['placeholders'][$columnIndex])) {
                unset($extra['placeholders'][$columnIndex]);
            }
        }
    }

    private function updatePlaceholderCoordinate($affectedColumnValue=null, $affectedRowValue=null, $extra)
    {
        if (!is_null($affectedColumnValue)) {
            $affectedColumnIndex = PHPExcel_Cell::columnIndexFromString($affectedColumnValue);

            $placeholders = [];
            foreach ($extra['placeholders'] as $columnIndex => $rowsObj) {
                if ($columnIndex >= $affectedColumnIndex) {
                    // logic to shift coordinate of unprocessed placeholder to right if it is affected after auto insert new column
                    $newColumnIndex = $columnIndex + 1;
                    $placeholders[$newColumnIndex] = $extra['placeholders'][$columnIndex];
                } else {
                    // if is not affected, the stay as it is
                    $placeholders[$columnIndex] = $extra['placeholders'][$columnIndex];
                }
            }

            $extra['placeholders'] = $placeholders;
        } else if (!is_null($affectedRowValue)) {
            // to-do: logic to shift coordinate of unprocessed placeholder to down if it is affected after auto insert new row
        }
    }

    private function string($objPHPExcel, $objWorksheet, $objCell, $search, $extra)
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

    private function row($objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        $columnValue = $attr['columnValue'];
        $rowValue = $attr['rowValue'];
        foreach ($attr['data'] as $key => $value) {
            // skip first row don't need to auto insert new row
            if (!$this->suppressAutoInsertNewRow && $rowValue != $attr['rowValue']) {
                $objWorksheet->insertNewRowBefore($rowValue);
            }

            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

            $rowValue++;
        }

        // only insert new row for the first column which have repeat-rows
        if ($this->suppressAutoInsertNewRow == false) {
            $this->suppressAutoInsertNewRow = true;
        }
    }

    private function column($objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        $columnIndex = $attr['columnIndex'];
        $rowValue = $attr['rowValue'];
        $nestedColumn = array_key_exists('children', $attr) ? $attr['children'] : [];

        foreach ($attr['data'] as $key => $value) {
            // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
            $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
            if (!$this->suppressAutoInsertNewColumn && $columnIndex != $attr['columnIndex']) {
                $objWorksheet->insertNewColumnBefore($columnValue);
                $this->updatePlaceholderCoordinate($columnValue, null, $extra);
            }
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

            if (!empty($nestedColumn)) {
                $keyword = $this->advancedTypes[__FUNCTION__];
                $nestedAttr = $this->extractPlaceholderAttr($nestedColumn, $keyword, $extra);

                $nestedColumnIndex = $columnIndex;
                // always output children to the immediate next row
                $nestedRowValue = $rowValue + 1;
                foreach ($nestedAttr['data'] as $nestedKey => $nestedValue) {
                    // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                    $nestedColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-1);
                    if (!$this->suppressAutoInsertNewColumn && $nestedColumnIndex != $columnIndex) {
                        $objWorksheet->insertNewColumnBefore($nestedColumnValue);
                        $this->updatePlaceholderCoordinate($nestedColumnValue, null, $extra);
                    }

                    $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
                    $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $nestedCellCoordinate, $nestedValue, $attr, $extra);

                    $nestedColumnIndex++;
                }

                // if nested column occupied more columns than the parent, then merge parent cell following number of columns occupied by children
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

        // only insert new row for the first column which have repeat-rows
        if ($this->suppressAutoInsertNewColumn == false) {
            $this->suppressAutoInsertNewColumn = true;
        }
    }

    private function match($objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        list($attr['placeholderPrefix'], $attr['placeholderSuffix']) = $this->splitDisplayValue($attr['displayValue']);

        $rowsArray = array_key_exists('rows', $attr) ? $attr['rows'] : [];
        $columnsArray = array_key_exists('columns', $attr) ? $attr['columns'] : [];
        $data = array_key_exists('data', $attr) ? $attr['data'] : [];

        if (!empty($rowsArray)) {
            $this->matchRows($objPHPExcel, $objWorksheet, $objCell, $attr, $rowsArray, $columnsArray, $extra);
        } else {
            $columnIndex = $attr['columnIndex'];
            $rowValue = $attr['rowValue'];
            $this->matchColumns($objPHPExcel, $objWorksheet, $objCell, $attr, $columnsArray, $columnIndex, $rowValue, null, $extra);
        }
    }

    private function matchRows($objPHPExcel, $objWorksheet, $objCell, $attr, $rowsArray=[], $columnsArray=[], $extra)
    {
        $matchFrom = array_key_exists('matchFrom', $rowsArray) ? $rowsArray['matchFrom'] : [];
        $matchTo = array_key_exists('matchTo', $rowsArray) ? $rowsArray['matchTo'] : [];
        $rowData = $this->getPlaceholderData($matchFrom, $extra);

        $filterStr = $this->formatFilter($matchTo);
        $attr['filterStr'] = array_key_exists('filterStr', $attr) ? $attr['filterStr'].$filterStr : $filterStr;

        $rowValue = $attr['rowValue'];
        foreach ($rowData as $key => $value) {
            // reset columnIndex after every loop of row
            $columnIndex = $attr['columnIndex'];
            if (!empty($columnsArray)) {
                $this->matchColumns($objPHPExcel, $objWorksheet, $objCell, $attr, $columnsArray, $columnIndex, $rowValue, $value, $extra);
            } else {
                $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']).$attr['filterStr'].".".$attr['placeholderSuffix'];
                $placeholder = sprintf($placeholderFormat, $value);

                $matchData = Hash::extract($extra['vars'], $placeholder);
                $matchValue = !empty($matchData) ? current($matchData) : '';

                // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                $cellCoordinate = $columnValue.$rowValue;

                $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
            }

            $rowValue++;
        }
    }

    private function matchColumns($objPHPExcel, $objWorksheet, $objCell, $attr, $columnsArray=[], &$columnIndex, &$rowValue, $filterValue=null, $extra)
    {
        $matchFrom = array_key_exists('matchFrom', $columnsArray) ? $columnsArray['matchFrom'] : [];
        $matchTo = array_key_exists('matchTo', $columnsArray) ? $columnsArray['matchTo'] : [];
        $columnData = $this->getPlaceholderData($matchFrom, $extra);

        $nestedColumnsArray = isset($columnsArray['children']['columns']) ? $columnsArray['children']['columns'] : [];
        $nestedMatchFrom = array_key_exists('matchFrom', $nestedColumnsArray) ? $nestedColumnsArray['matchFrom'] : [];
        $nestedMatchTo = array_key_exists('matchTo', $nestedColumnsArray) ? $nestedColumnsArray['matchTo'] : [];
        $nestedColumnData = $this->getPlaceholderData($nestedMatchFrom, $extra);

        $filterStr = $this->formatFilter($matchTo);
        if (!empty($nestedColumnData)) {
            $filterStr .= $this->formatFilter($nestedMatchTo);
        }
        $attr['filterStr'] = array_key_exists('filterStr', $attr) ? $attr['filterStr'].$filterStr : $filterStr;

        foreach ($columnData as $key => $value) {
            if (!empty($nestedColumnsArray)) {
                foreach ($nestedColumnData as $nestedKey => $nestedValue) {
                    $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']).$attr['filterStr'].".".$attr['placeholderSuffix'];
                    if (!is_null($filterValue)) {
                        $placeholder = sprintf($placeholderFormat, $filterValue, $value, $nestedValue);
                    } else {
                        $placeholder = sprintf($placeholderFormat, $value, $nestedValue);
                    }

                    $matchData = Hash::extract($extra['vars'], $placeholder);
                    $matchValue = !empty($matchData) ? current($matchData) : '';

                    // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                    $nestedColumnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                    $nestedCellCoordinate = $nestedColumnValue.$rowValue;

                    $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $nestedCellCoordinate, $matchValue, $attr, $extra);
                    $columnIndex++;
                }
            } else {
                // to-do: get value and write to cell
            }
        }
    }

    private function dropdown($objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        $matchFrom = array_key_exists('rows', $attr) ? $attr['rows'] : [];
        $rowData = $this->getPlaceholderData($matchFrom, $extra);

        if (!empty($rowData)) {
            $columnValue = $attr['columnValue'];
            $rowValue = $attr['rowValue'];
            foreach ($rowData as $key => $value) {
                $cellCoordinate = $columnValue.$rowValue;
                $this->renderDropdown($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
                $rowValue++;
            }
        } else {
            $cellCoordinate = $attr['coordinate'];
            $this->renderDropdown($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }
    }
}
