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
use Cake\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Log\Log;

use PHPExcel_IOFactory;
use PHPExcel_Worksheet;
use PHPExcel_Cell;
use PHPExcel_Cell_DataValidation;
use PHPExcel_Style_Alignment;
use PHPExcel_Worksheet_MemoryDrawing;

class ExcelReportBehavior extends Behavior
{
    protected $_defaultConfig = [
        'folder' => 'export',
        'subfolder' => 'customexcel',
        'format' => 'xlsx',
        'download' => true,
        'purge' => true,
        'wrapText' => false,
        'lockSheets' => false,
        'templateTable' => null,
        'templateTableKey' => null,
        'variableSource' => 'file'
    ];

    // function name and keyword pairs
    private $advancedTypes = [
        'row' => 'repeatRows',
        'column' => 'repeatColumns',
        'table' => 'table',
        'match' => 'match',
        'dropdown' => 'dropdown',
        'image' => 'image'
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
        $events['ExcelTemplates.Model.onRenderExcelTemplate'] = 'onRenderExcelTemplate';
        $events['ExcelTemplates.Model.onGetExcelTemplateVars'] = 'onGetExcelTemplateVars';
        return $events;
    }

    public function onGetExcelTemplateVars(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;

        if (array_key_exists('requestQuery', $extra)) {
            $params = $extra['requestQuery'];
        } else {
            $params = $model->getQueryString();
        }

        $vars = $this->getVars($params, $extra);
        $results = Hash::flatten($vars);
        pr($results);
        die;
    }

    public function onRenderExcelTemplate(Event $event, ArrayObject $extra)
    {
        ini_set('max_execution_time', 180);
        $this->renderExcelTemplate($extra);
    }

    public function renderExcelTemplate(ArrayObject $extra)
    {
        $model = $this->_table;

        if (array_key_exists('requestQuery', $extra)) {
            $params = $extra['requestQuery'];
        } else {
            $params = $model->getQueryString();
        }

        $extra['params'] = $params;
        $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateBeforeGenerate', [$params, $extra], $this);

        $extra['vars'] = $this->getVars($params, $extra);

        $extra['file'] = $this->config('filename') . '_' . date('Ymd') . 'T' . date('His') . '.' . $this->config('format');
        $extra['path'] = WWW_ROOT . $this->config('folder') . DS . $this->config('subfolder') . DS;

        $filepath = $extra['path'] . $extra['file'];
        $extra['file_path'] = $extra['path'] . $extra['file'];

        $objPHPExcel = $this->loadExcelTemplate($extra);
        $this->generateExcel($objPHPExcel, $extra);

        if ($this->config('format') == 'xlsx') {
            $this->saveExcel($objPHPExcel, $filepath);
        }

        if ($extra->offsetExists('temp_logo')) {
            // delete temporary logo
            $this->deleteFile($extra['temp_logo']);
        }

        if ($extra->offsetExists('tmp_file_path')) {
            // delete temporary excel template file after save
            $this->deleteFile($extra['tmp_file_path']);
        }

        $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateAfterGenerate', [$params, $extra], $this);

        if ($this->config('download')) {
            $this->downloadFile($filepath);
        }

        if ($this->config('purge')) {
            // delete excel file after download
            $this->deleteFile($filepath);
        }
    }

    public function loadExcelTemplate(ArrayObject $extra)
    {
        $model = $this->_table;

        if (array_key_exists('requestQuery', $extra) && array_key_exists($this->config('templateTableKey'), $extra['requestQuery'])) {
            $recordId = $extra['requestQuery'][$this->config('templateTableKey')];
        } else {
            $recordId = $model->getQueryString($this->config('templateTableKey'));
        }

        $Table = TableRegistry::get($this->config('templateTable'));

        if (empty($recordId)) {
            $objPHPExcel = new \PHPExcel();
        } else {
            // Read from excel template attachment then create as temporary file in server so that can read back the same file and read as PHPExcel object
            $entity = $Table->get($recordId);

            if ($entity->has('excel_template_name')) {
                $pathInfo = pathinfo($entity->excel_template_name);
                $filename = $this->config('filename') . '_Template_' . date('Ymd') . 'T' . date('His') . '.' . $pathInfo['extension'];
                $file = $this->getFile($entity->excel_template);

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

            // lock all sheets
            if ($this->config('lockSheets')) {
                $objWorksheet->getProtection()->setSheet(true);
            }
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
        $mergeColumns = $attr['mergeColumns'];

        $targetCell = $objWorksheet->getCell($cellCoordinate);
        $targetColumnValue = $targetCell->getColumn();
        $targetColumnIndex = PHPExcel_Cell::columnIndexFromString($targetColumnValue);
        // by default will merge to the same row, but for nested row parent cell will merge until the last row of the children
        $rangeRowValue = isset($attr['rangeRowValue']) && !empty($attr['rangeRowValue']) ? $attr['rangeRowValue'] : $targetCell->getRow();

        switch($type) {
            case 'number':
                // set to two decimal places
                if (!is_null($format) && is_numeric($cellValue)) {
                    $formatting = number_format(0, $format);
                    $cellStyle->getNumberFormat()->setFormatCode($formatting);
                }
                break;

            case 'date':
                if (!is_null($format) && !empty($cellValue)) {
                    $cellValue = $cellValue->format($format);
                }
                break;

            case 'time':
                if (!is_null($format) && !empty($cellValue)) {
                    $cellValue = $cellValue->format($format);
                }
                break;
        }

        if ($this->config('wrapText')) {
            $cellStyle->getAlignment()->setWrapText(true);
        }

        // set cell style to follow placeholder
        $objWorksheet->setCellValue($cellCoordinate, __($cellValue));
        $objWorksheet->duplicateStyle($cellStyle, $cellCoordinate);

        // set column width to follow placeholder
        $objWorksheet->getColumnDimension($targetColumnValue)->setAutoSize(false);
        $objWorksheet->getColumnDimension($targetColumnValue)->setWidth($columnWidth);

        if (!empty($mergeColumns)) {
            $rangeColumnValue = $objCell->stringFromColumnIndex(($targetColumnIndex - 1) + ($mergeColumns - 1)); //merge need to be minus 1
            $mergeRange = $cellCoordinate.":".$rangeColumnValue.$rangeRowValue;
            $objWorksheet->mergeCells($mergeRange);
        }
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

    public function renderImage($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $imagePath, $attr, $extra)
    {
        $imageWidth = $attr['imageWidth'];
        $imageMarginLeft = $attr['imageMarginLeft'];
        $imageMarginTop = $attr['imageMarginTop'];

        $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();

        if ($imagePath) {
            switch ($attr['mime_type']) {
                case 'image/png':
                    $imageResource = imagecreatefrompng($imagePath);
                    $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_PNG);
                    $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_PNG);
                    break;
                case 'image/jpeg':
                    $imageResource = imagecreatefromjpeg($imagePath);
                    $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_JPEG);
                    $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
                    break;
                case 'image/gif':
                    $imageResource = imagecreatefromgif($imagePath);
                    $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_GIF);
                    $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_GIF);
                    break;
                default:
                    $imageResource = '';
                    break;
            }
        }

        if ($imageResource) {
            //retain transparency on png/gif file
            imageAlphaBlending($imageResource, true);
            imageSaveAlpha($imageResource, true);

            $objDrawing->setImageResource($imageResource);
            $objDrawing->setWidth($imageWidth); 
            $objDrawing->setCoordinates($cellCoordinate); 
            $objDrawing->setOffsetX($imageMarginLeft); 
            $objDrawing->setOffsetY($imageMarginTop); 
            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
        }
    }

    public function saveExcel($objPHPExcel, $filepath)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filepath);
    }

    public function downloadFile($filepath)
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

        $variableValues = new ArrayObject([]);
        if ($this->config('variableSource') == 'database') {
            $event = $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateInitialiseQueryVariables', [$params, $extra], $this);
            if ($event->isStopped()) { return $event->result; }
            if ($event->result) {
                $variableValues = $event->result;
            }

        } else if ($this->config('variableSource') == 'file') {
            $variables = $this->config('variables');

            foreach ($variables as $var) {
                $event = $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateInitialise'.$var, [$params, $extra], $this);
                if ($event->isStopped()) { return $event->result; }
                if ($event->result) {
                    $variableValues[$var] = $event->result;
                }
            }
        }

        $variableValues = $variableValues->getArrayCopy();
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
        $placeholderArray = explode(".", $placeholder);
        if (end($placeholderArray) == 'i') {
            array_pop($placeholderArray);   // remove i
            $placeholder = implode(".", $placeholderArray);
            $formattedPlaceholder = $this->formatPlaceholder($placeholder);
            $placeholderData = !is_null($placeholder) ? Hash::extract($extra['vars'], $formattedPlaceholder) : [];

            $count = 1;
            foreach ($placeholderData as $key => $value) {
                $placeholderData[$key] = $count++;
            }
        } else {
            $formattedPlaceholder = $this->formatPlaceholder($placeholder);
            $placeholderId = $this->splitDisplayValue($placeholder)[0].'.id';
            $formattedPlaceholderId = $this->formatPlaceholder($placeholderId);

            // check if data has id
            $idData = !is_null($placeholderId) ? Hash::extract($extra['vars'], $formattedPlaceholderId) : [];
            $valueData = !is_null($placeholderId) ? Hash::extract($extra['vars'], $formattedPlaceholder) : [];
            $equal = count($idData) == count($valueData);

            if (!empty($idData) && $equal) {
                // get id and value as key-value pair
                // selected field needs to be present in vars if not there will be a key-value number mismatch (be careful of using contain)
                $placeholderData = !is_null($placeholder) ? Hash::combine($extra['vars'], $formattedPlaceholderId, $formattedPlaceholder) : [];
            } else {
                // only get value
                $placeholderData = !is_null($placeholder) ? Hash::extract($extra['vars'], $formattedPlaceholder) : [];
            }
        }

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
        $attr['filter'] = array_key_exists('filter', $settings) ? $settings['filter'] : null;
        $attr['displayColumns'] = array_key_exists('displayColumns', $settings) ? $settings['displayColumns'] : [];
        $attr['source'] = array_key_exists('source', $settings) ? $settings['source'] : null;
        $attr['showHeaders'] = array_key_exists('showHeaders', $settings) ? $settings['showHeaders'] : false;
        $attr['insertRows'] = array_key_exists('insertRows', $settings) ? $settings['insertRows'] : false;
        $attr['mergeColumns'] = array_key_exists('mergeColumns', $settings) ? $settings['mergeColumns'] : null;
        $attr['imageWidth'] = array_key_exists('imageWidth', $settings) ? $settings['imageWidth'] : null;
        $attr['imageMarginLeft'] = array_key_exists('imageMarginLeft', $settings) ? $settings['imageMarginLeft'] : null;
        $attr['imageMarginTop'] = array_key_exists('imageMarginTop', $settings) ? $settings['imageMarginTop'] : null;

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
        $attr['columnIndex'] = PHPExcel_Cell::columnIndexFromString($columnValue);
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
                        $columnIndex = PHPExcel_Cell::columnIndexFromString($columnValue);
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
            $placeholders = [];
            foreach ($extra['placeholders'] as $columnIndex => $rowsObj) {
                foreach($rowsObj as $rowIndex => $obj) {
                    if ($rowIndex >= $affectedRowValue) {
                        // logic to shift coordinate of unprocessed placeholder below if it is affected after auto insert new row
                        $newRowIndex = $rowIndex + 1;
                        $placeholders[$columnIndex][$newRowIndex] = $extra['placeholders'][$columnIndex][$rowIndex];
                    } else {
                        // if is not affected, the stay as it is
                        $placeholders[$columnIndex][$rowIndex] = $extra['placeholders'][$columnIndex][$rowIndex];
                    }
                }
            }

            $extra['placeholders'] = $placeholders;
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
                } else {
                    // replace placeholder as blank if data is empty
                    $search = '';
                }
            }
        }

        $cellCoordinate = $objCell->getCoordinate();
        $cellStyle = $objCell->getStyle($cellCoordinate);

        if ($this->config('wrapText')) {
            $cellStyle->getAlignment()->setWrapText(true);
        }

        $objWorksheet->setCellValue($cellCoordinate, $search);
        $objWorksheet->duplicateStyle($cellStyle, $cellCoordinate);
    }

    private function row($objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        $columnIndex = $attr['columnIndex'];
        $columnValue = $attr['columnValue'];
        $rowValue = $attr['rowValue'];
        $nestedRow = array_key_exists('children', $attr) ? $attr['children'] : [];

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                // skip first row don't need to auto insert new row
                if ($rowValue != $attr['rowValue']) {
                    $objWorksheet->insertNewRowBefore($rowValue);
                    $this->updatePlaceholderCoordinate(null, $rowValue, $extra);
                }

                $cellCoordinate = $columnValue.$rowValue;
                $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                if (!empty($nestedRow)) {
                    $rowValue = $this->nestedRow($nestedRow, $key, $rowValue, $columnIndex, $objPHPExcel, $objWorksheet, $objCell, $attr, $extra);
                }

                $rowValue++;
            }
        } else {
            // replace placeholder as blank if data is empty
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }
    }

    private function nestedRow($nestedRow, $parentKey, $rowValue, $columnIndex, $objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        $keyword = $this->advancedTypes['row'];
        $nestedAttr = $this->extractPlaceholderAttr($nestedRow, $keyword, $extra);
        $secondNestedRow = array_key_exists('children', $nestedAttr) ? $nestedAttr['children'] : [];

        $nestedRowValue = $rowValue;
        // always output children to the immediate next column
        $nestedColumnIndex = $columnIndex + 1;
        // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
        $nestedColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-1);

        // set column width to width of next column
        $attr['columnWidth'] = $objWorksheet->getColumnDimension($nestedColumnValue)->getWidth();

        $filter = array_key_exists('filter', $nestedAttr) ? $nestedAttr['filter'] : null;
        if (!is_null($filter)) {
            list($placeholderPrefix, $placeholderSuffix) = $this->splitDisplayValue($nestedAttr['displayValue']);
            $filterStr = $this->formatFilter($filter);

            $placeholderFormat = $this->formatPlaceholder($placeholderPrefix).$filterStr.".";
            $placeholder = sprintf($placeholderFormat.$placeholderSuffix, $parentKey);
            $placeholderId = sprintf($placeholderFormat.'id', $parentKey);
            $nestedData = Hash::combine($extra['vars'], $placeholderId, $placeholder);
        } else {
            $nestedData = $nestedAttr['data'];
        }

        foreach ($nestedData as $nestedKey => $nestedValue) {
            if ($nestedRowValue != $rowValue) {
                $objWorksheet->insertNewRowBefore($nestedRowValue);
                $this->updatePlaceholderCoordinate(null, $nestedRowValue, $extra);
            }

            $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $nestedCellCoordinate, $nestedValue, $attr, $extra);

            if (!empty($secondNestedRow)) {
                $nestedRowValue = $this->nestedRow($secondNestedRow, $nestedKey, $nestedRowValue, $nestedColumnIndex, $objPHPExcel, $objWorksheet, $objCell, $attr, $extra);
            }

            $nestedRowValue++;
        }

        // if nested row occupied more rows than the parent, then merge parent cell following number of rows occupied by children
        if ($nestedRowValue > $rowValue) {
            $rangeRowValue = $nestedRowValue-1;
            $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
            $mergeRange = $columnValue.$rowValue.":".$columnValue.$rangeRowValue;

            $objWorksheet->mergeCells($mergeRange);
            // fix border doesn't set after cell is merged
            $cellStyle = $attr['style'];
            $cellStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objWorksheet->duplicateStyle($cellStyle, $mergeRange);

            $rowValue = $nestedRowValue-1;
        }

        return $rowValue;
    }

    private function column($objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        $columnIndex = $attr['columnIndex'];
        $rowValue = $attr['rowValue'];
        $nestedColumn = array_key_exists('children', $attr) ? $attr['children'] : [];

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                if ($columnIndex != $attr['columnIndex']) {
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
                        if ($nestedColumnIndex != $columnIndex) {
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
        } else {
            // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
            $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);

            // replace placeholder as blank if data is empty
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }

    }

    private function table($objPHPExcel, $objWorksheet, $objCell, $attr, $extra)
    {
        $rowValue = $attr['rowValue'];
        $columnIndex = $attr['columnIndex'];
        $source = $attr['source'];
        $displayColumns = $attr['displayColumns'];
        $showHeaders = $attr['showHeaders'];
        $insertRows = $attr['insertRows'];

        if ($showHeaders) {
            foreach($displayColumns as $key => $column) {
                $header = Inflector::humanize($key);

                // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                $cellCoordinate = $columnValue.$rowValue;
                $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $header, $attr, $extra);
                $columnIndex++;
            }

            $rowValue++;
        }

        if (array_key_exists($source, $extra['vars']) && !empty($extra['vars'][$source])) {
            $sourceVars = $extra['vars'][$source];

            foreach ($sourceVars as $vars) {
                // reset columnIndex after every loop of row
                $columnIndex = $attr['columnIndex'];

                // skip first row don't need to auto insert new row
                if ($insertRows && $rowValue != $attr['rowValue']) {
                    $objWorksheet->insertNewRowBefore($rowValue);
                    $this->updatePlaceholderCoordinate(null, $rowValue, $extra);
                }

                foreach ($displayColumns as $column) {
                    $value = null;
                    if (array_key_exists('displayValue', $column)) {
                        $field = $this->splitDisplayValue($column['displayValue'])[1];
                        $value = Hash::get($vars, $field);
                    }

                    $attr['type'] = array_key_exists('type', $column) ? $column['type'] : null;
                    $attr['format'] = array_key_exists('format', $column) ? $column['format'] : null;

                    // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                    $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                    $cellCoordinate = $columnValue.$rowValue;
                    $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                    $columnIndex++;
                }

                $rowValue++;
            }
        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
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

        $nestedRowsArray = isset($rowsArray['children']['rows']) ? $rowsArray['children']['rows'] : [];
        $nestedfilter = array_key_exists('filter', $nestedRowsArray) ? $nestedRowsArray['filter'] : null;
        $nestedMatchFrom = array_key_exists('matchFrom', $nestedRowsArray) ? $nestedRowsArray['matchFrom'] : [];
        $nestedMatchTo = array_key_exists('matchTo', $nestedRowsArray) ? $nestedRowsArray['matchTo'] : [];
        $nestedRowData = !empty($nestedMatchFrom) ? $this->getPlaceholderData($nestedMatchFrom, $extra) : [];

        $filterStr = $this->formatFilter($matchTo);
        if (!empty($nestedRowData)) {
            $filterStr .= $this->formatFilter($nestedMatchTo);
        }
        $attr['filterStr'] = array_key_exists('filterStr', $attr) ? $attr['filterStr'].$filterStr : $filterStr;

        $rowValue = $attr['rowValue'];

        if (!empty($rowData)) {
            foreach ($rowData as $key => $value) {
                // reset columnIndex after every loop of row
                $columnIndex = $attr['columnIndex'];
                if (!empty($columnsArray)) {
                    $this->matchColumns($objPHPExcel, $objWorksheet, $objCell, $attr, $columnsArray, $columnIndex, $rowValue, $value, $extra);
                    $rowValue++;
                } else {
                    if (!empty($nestedRowsArray)) {
                        if (!is_null($nestedfilter) && !empty($nestedMatchFrom)) {
                            $nestedDataFilter = $this->formatFilter($nestedfilter);
                            list($placeholderPrefix, $placeholderSuffix) = $this->splitDisplayValue($nestedMatchFrom);
                            $dataPlaceholderFormat = $this->formatPlaceholder($placeholderPrefix).$nestedDataFilter.".".$placeholderSuffix;
                            $dataPlaceholder = sprintf($dataPlaceholderFormat, $value);
                            $nestedRowData = Hash::extract($extra['vars'], $dataPlaceholder);
                        }

                        foreach ($nestedRowData as $nestedKey => $nestedValue) {
                            $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']).$attr['filterStr'].".".$attr['placeholderSuffix'];
                            $placeholder = sprintf($placeholderFormat, $value, $nestedValue);

                            $matchData = Hash::extract($extra['vars'], $placeholder);
                            $matchValue = !empty($matchData) ? current($matchData) : '';

                            // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                            $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                            $nestedCellCoordinate = $columnValue.$rowValue;

                            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $nestedCellCoordinate, $matchValue, $attr, $extra);
                            $rowValue++;
                        }

                    } else {
                        $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']).$attr['filterStr'].".".$attr['placeholderSuffix'];
                        $placeholder = sprintf($placeholderFormat, $value);

                        $matchData = Hash::extract($extra['vars'], $placeholder);
                        $matchValue = !empty($matchData) ? current($matchData) : '';

                        // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                        $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                        $cellCoordinate = $columnValue.$rowValue;

                        $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
                        $rowValue++;
                    }
                }
            }

        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
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
        $nestedColumnData = !empty($nestedMatchFrom) ? $this->getPlaceholderData($nestedMatchFrom, $extra) : [];

        $filterStr = $this->formatFilter($matchTo);
        if (!empty($nestedColumnData)) {
            $filterStr .= $this->formatFilter($nestedMatchTo);
        }
        $attr['filterStr'] = array_key_exists('filterStr', $attr) ? $attr['filterStr'].$filterStr : $filterStr;

        if (!empty($columnData)) {
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
                    $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']).$attr['filterStr'].".".$attr['placeholderSuffix'];
                    if (!is_null($filterValue)) {
                        $placeholder = sprintf($placeholderFormat, $filterValue, $value);
                    } else {
                        $placeholder = sprintf($placeholderFormat, $value);
                    }
                    $matchData = Hash::extract($extra['vars'], $placeholder);
                    $matchValue = !empty($matchData) ? current($matchData) : '';

                    // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                    $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                    $cellCoordinate = $columnValue.$rowValue;

                    $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
                    $columnIndex++;
                }
            }

        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
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

    private function image($objPHPExcel, $objWorksheet, $objCell, $attr,ArrayObject $extra)
    {
        $columnValue = $attr['columnValue'];
        $rowValue = $attr['rowValue'];
        $cellCoordinate = $columnValue.$rowValue;

        $attr['imageWidth'] = array_key_exists('imageWidth', $attr) ? $attr['imageWidth'] : 50;
        $attr['imageMarginLeft'] = array_key_exists('imageMarginLeft', $attr) ? $attr['imageMarginLeft'] : 0;
        $attr['imageMarginTop'] = array_key_exists('imageMarginTop', $attr) ? $attr['imageMarginTop'] : 0;

        $data = Hash::extract($extra['vars'], $attr['displayValue']);
        $imageContent = current($data);

        //for institution logo
        if ($attr['displayValue'] == 'Institutions.logo_content' ) {
            if (is_resource($imageContent)) {
                $institutionId = Hash::extract($extra['vars'], 'Institutions.id');
                $institutionId = current($institutionId);

                $mimeType = mime_content_type($imageContent);
                $exp = explode('/', $mimeType);
                $logoExt = end($exp);

                $attr['mime_type'] = $mimeType;

                $tempImagePath = TMP . "temp_logo_$institutionId.$logoExt";

                if (!file_exists($tempImagePath)) {
                    file_put_contents($tempImagePath, stream_get_contents($imageContent));
                    $extra['temp_logo'] = $tempImagePath;
                }
            } else {
                $tempImagePath = ROOT . DS . 'plugins' . DS . 'ReportCard' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.png';
                $attr['mime_type'] = 'image/png';
            }
        }

        $this->renderImage($objPHPExcel, $objWorksheet, $objCell, $cellCoordinate, $tempImagePath, $attr, $extra);

        // set to empty to remove the placeholder
        $objWorksheet->setCellValue($cellCoordinate, '');
    }
}