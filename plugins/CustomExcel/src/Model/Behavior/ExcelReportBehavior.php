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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExcelReportBehavior extends Behavior
{
    use PdfReportTrait;

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

    private $libraryTypes = [
        'xlsx' => 'Xlsx',
        'pdf' => 'Mpdf'
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
        $format = $this->config('format');

        if (array_key_exists('requestQuery', $extra)) {
            $params = $extra['requestQuery'];
        } else {
            $params = $model->getQueryString();
        }

        $extra['params'] = $params;
        $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateBeforeGenerate', [$params, $extra], $this);

        $extra['vars'] = $this->getVars($params, $extra);


        $extra['file'] = $this->config('filename') . '_' . date('Ymd') . 'T' . date('His') . '.' . $format;
        $extra['path'] = WWW_ROOT . $this->config('folder') . DS . $this->config('subfolder') . DS;

        $temppath = tempnam($extra['path'], $this->config('filename') . '_');
        $extra['file_path'] = $temppath;


        $objSpreadsheet = $this->loadExcelTemplate($extra);
        $this->generateExcel($objSpreadsheet, $extra);

        Log::write('debug', 'ExcelReportBehavior >>> renderExcelTemplate');


        $this->saveFile($objSpreadsheet, $temppath, $format, $params['student_id']);
		
        if ($extra->offsetExists('temp_logo')) {
            // delete temporary logo
            $this->deleteFile($extra['temp_logo']);
        }

        if ($extra->offsetExists('image_resource')) {
            imagedestroy($extra['image_resource']);
        }

        if ($extra->offsetExists('tmp_file_path')) {
            // delete temporary excel template file after save
            $this->deleteFile($extra['tmp_file_path']);
        }

        $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateAfterGenerate', [$params, $extra], $this);

        if (!empty($params['student_id'])) {
			$pdfFilePath = WWW_ROOT . $this->config('folder') . DS . $this->config('subfolder') . DS . $this->config('filename') . '_' . $params['student_id'].'.txt';
            $pdfFileContent = file_get_contents($pdfFilePath);
			
			$StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
			// save Pdf file
			$StudentsReportCards->updateAll([
				'file_content_pdf' => $pdfFileContent
			], $params);
			
			$this->deleteFile($pdfFilePath);
        }
		
		if ($this->config('download')) {
            $tempfile = new File($temppath);
            $tempinfo = $tempfile->info();
            $tempcontent = $tempfile->read();
            $tempfile->close();

            $this->downloadFile($tempcontent, $extra['file'], $tempinfo['filesize']);
        }

        if ($this->config('purge')) {
            // delete excel file after download
            $this->deleteFile($temppath);
        }

        gc_collect_cycles();
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
            $objSpreadsheet = new Spreadsheet();
        } else {
            // Read from excel template attachment then create as temporary file in server so that can read back the same file and read as Spreadsheet object
            $entity = $Table->get($recordId);

            if ($entity->has('excel_template_name')) {
                $file = $this->getFile($entity->excel_template);

                // Create a temporary file
                $filepath = tempnam($extra['path'], $this->config('filename') . '_Template_');
                $extra['tmp_file_path'] = $filepath;

                $excelTemplate = new File($filepath, true, 0777);
                $excelTemplate->write($file);
                $excelTemplate->close();
                // End create a temporary file
                try {
                    // Read back from same temporary file
                    $inputFileType = IOFactory::identify($filepath);
                    $objReader = IOFactory::createReader($inputFileType);
                    $objSpreadsheet = $objReader->load($filepath);
                    // End read back from same temporary file
                } catch(Exception $e) {
                    Log::write('debug', $e->getMessage());
                }
            }
        }
        return $objSpreadsheet;
    }

    public function generateExcel($objSpreadsheet, ArrayObject $extra)
    {
        foreach ($objSpreadsheet->getWorksheetIterator() as $objWorksheet) {
            $this->processWorksheet($objSpreadsheet, $objWorksheet, $extra);

            // lock all sheets
            if ($this->config('lockSheets')) {
                $objWorksheet->getProtection()->setSheet(true);
            }
        }

        // to force the first sheet active
        $objSpreadsheet->setActiveSheetIndex(0);
    }

    public function renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $cellValue, $attr, $extra)
    {
        $type = $attr['type'];
        $format = $attr['format'];
        $cellStyle = $attr['style'];
        $columnWidth = $attr['columnWidth'];

        $targetCell = $objWorksheet->getCell($cellCoordinate);
        $targetColumnValue = $targetCell->getColumn();
        $targetRowValue = $targetCell->getRow();


        //To identify Last Col and Row
        $this->checkLastColumn($targetColumnValue);
        $this->checkLastRow($targetRowValue);

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
        $objWorksheet->getCell($cellCoordinate)->setValue($cellValue);
        $objWorksheet->duplicateStyle($cellStyle, $cellCoordinate);

        // set column width to follow placeholder
        $objWorksheet->getColumnDimension($targetColumnValue)->setAutoSize(false);
        $objWorksheet->getColumnDimension($targetColumnValue)->setWidth($columnWidth);
    }

    public function renderDropdown($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $cellValue, $attr, $extra)
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
        $objValidation->setType(DataValidation::TYPE_LIST);
        $objValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
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
            $referencesWorksheet = $objSpreadsheet->getSheetByName($sheetName);
            $referencesCell = $referencesWorksheet->getCell($coordinate);
            $columnValue = $referencesCell->getColumn();
            $rowValue = $referencesCell->getRow();
            $highestRow = $referencesWorksheet->getHighestRow($columnValue);

            $listLocation = sprintf('%s!$%s$%s:$%s$%s', "'$sheetName'", $columnValue, $rowValue, $columnValue, $highestRow);
            $objValidation->setFormula1($listLocation);
        }

        // set to empty to remove the placeholder
        $objWorksheet->getCell($cellCoordinate)->setValue($cellValue);
    }

    public function renderImage($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $imagePath, $attr, $extra)
    {
        $imageWidth = $attr['imageWidth'];
        $imageMarginLeft = $attr['imageMarginLeft'];
        $imageMarginTop = $attr['imageMarginTop'];

        $objDrawing = new MemoryDrawing();

        if (!array_key_exists('image_resource', $extra) && $imagePath) {
            switch ($attr['mime_type']) {
                case 'image/png':
                    $imageResource = imagecreatefrompng($imagePath);
                    $objDrawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
                    $objDrawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
                    break;
                case 'image/jpeg':
                    $imageResource = imagecreatefromjpeg($imagePath);
                    $objDrawing->setMimeType(MemoryDrawing::MIMETYPE_JPEG);
                    $objDrawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
                    break;
                case 'image/gif':
                    $imageResource = imagecreatefromgif($imagePath);
                    $objDrawing->setMimeType(MemoryDrawing::MIMETYPE_GIF);
                    $objDrawing->setRenderingFunction(MemoryDrawing::RENDERING_GIF);
                    break;
                default:
                    $imageResource = '';
                    break;
            }
            $extra['image_resource'] = $imageResource;
        }

        if (isset($extra['image_resource'])) {
            //retain transparency on png/gif file
            imageAlphaBlending($extra['image_resource'], true);
            imageSaveAlpha($extra['image_resource'], true);

            $objDrawing->setImageResource($extra['image_resource']);
            $objDrawing->setWidth($imageWidth);
            $objDrawing->setCoordinates($cellCoordinate);
            $objDrawing->setOffsetX($imageMarginLeft);
            $objDrawing->setOffsetY($imageMarginTop);
            $objDrawing->setWorksheet($objSpreadsheet->getActiveSheet());
        }
    }

    public function saveFile($objSpreadsheet, $filepath, $format, $student_id)
    {
        Log::write('debug', 'ExcelReportBehavior >>> saveFile: '.$format);
        $objWriter = IOFactory::createWriter($objSpreadsheet, $this->libraryTypes[$format]);

        if ($format == 'pdf') {
            $this->savePDF($objSpreadsheet, $filepath, $student_id);
        } else {
			// pdf
			if(!empty($student_id)) {
				$this->savePDF($objSpreadsheet, $filepath, $student_id);
			}
            // xlsx
            $objWriter->save($filepath);
        }

        $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
        $objWriter->save($filepath);
        $objSpreadsheet->disconnectWorksheets();
        unset($objWriter, $objSpreadsheet);
        gc_collect_cycles();

    }

    public function downloadFile($filecontent, $filename, $filesize)
    {
        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . $filesize);
        echo $filecontent;
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
        $attr['mergeColumns'] = array_key_exists('mergeColumns', $settings) ? $settings['mergeColumns'] : 1;
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

        $columnValue = $objCell->getColumn();
        $attr['columnValue'] = $columnValue;
        $attr['columnIndex'] = Coordinate::columnIndexFromString($columnValue);
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

    private function processWorksheet($objSpreadsheet, $objWorksheet, $extra)
    {
        if ($this->currentWorksheet !== $objWorksheet) {
            $this->currentWorksheetIndex++;
            $this->currentWorksheet = $objWorksheet;
        }

        $extra['placeholders'] = [];
        $this->processBasicPlaceholder($objSpreadsheet, $objWorksheet, $extra);

        if (!empty($extra['placeholders'])) {
            $this->processAdvancedPlaceholder($objSpreadsheet, $objWorksheet, $extra);
        }
    }

    private function processBasicPlaceholder($objSpreadsheet, $objWorksheet, $extra)
    {
        $cellCollection = $objWorksheet->getCellCollection();
        $cells = $cellCollection->getCoordinates();

        foreach ($cells as $cellCoordinate) {
            $objCell = $objWorksheet->getCell($cellCoordinate);
            if (is_object($objCell->getValue())) {
                $cellValue = $objCell->getValue()->getPlainText();
            } else {
                $cellValue = $objCell->getValue();
            }

            if (strlen($cellValue) > 0) {
                $this->checkLastRow($objCell->getRow());

                $pos = strpos($cellValue, '${');

                if ($pos !== false) {
                    // if is basic placeholder then replace first, else added into $placeholder to process later
                    if ($this->isBasicType($cellValue)) {
                        Log::write('debug', $cellCoordinate . ' - ' . $cellValue);
                        $this->string($objSpreadsheet, $objWorksheet, $objCell, $cellValue, $extra);
                    } else {
                        $columnValue = $objCell->getColumn();
                        $rowValue = $objCell->getRow();
                        $columnIndex = Coordinate::columnIndexFromString($columnValue);
                        $extra['placeholders'][$columnIndex][$rowValue] = $cellValue;
                    }
                }
            }
        }

    }

    private function processAdvancedPlaceholder($objSpreadsheet, $objWorksheet, $extra)
    {
        // sort by column index so that to process the first column first
        ksort($extra['placeholders']);

        while(!empty($extra['placeholders'])) {
            $columnIndex = key($extra['placeholders']);
            $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
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
                            $this->$function($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
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
            $affectedColumnIndex = Coordinate::columnIndexFromString($affectedColumnValue);

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

    private function string($objSpreadsheet, $objWorksheet, $objCell, $search, $extra)
    {
        $format = '${%s}';
        $vars = $extra->offsetExists('vars') ? $extra['vars'] : [];
        $placeHolderAttr = $this->convertPlaceHolderToArray($search);

        if (empty($placeHolderAttr)) {
            // basic type without formating
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

            $objWorksheet->getCell($cellCoordinate)->setValue($search);
            $objWorksheet->duplicateStyle($cellStyle, $cellCoordinate);
        } else {
            // basic types with formating
            $cellCoordinate = $objCell->getCoordinate();
            $cellAttr = $this->extractCellAttr($objWorksheet, $objCell);
            $placeholder = $placeHolderAttr['displayValue'];
            $replace = sprintf($format, $placeholder);

            $flattenVar = Hash::flatten($vars, '.');
            if (array_key_exists($placeholder, $flattenVar)) {
                $value = $flattenVar[$placeholder];
            } else {
                $value = '';
            }

            $attr = array_merge($placeHolderAttr, $cellAttr);
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);
        }
    }

    private function mergeRange($fromColumn, $fromRow, $toColumn, $toRow, $objWorksheet, $attr)
    {
        if (($fromColumn != $toColumn) || ($fromRow != $toRow)) {
            $fromColumnValue = Coordinate::stringFromColumnIndex($fromColumn);
            $toColumnValue = Coordinate::stringFromColumnIndex($toColumn);
            $mergeRange = $fromColumnValue.$fromRow.":".$toColumnValue.$toRow;

            $objWorksheet->mergeCells($mergeRange);

            // fix border doesn't set after cell is merged
            $cellStyle = $attr['style'];
            $cellStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $objWorksheet->duplicateStyle($cellStyle, $mergeRange);
        }
    }

    private function row($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        $rowValue = $attr['rowValue'];
        $columnIndex = $attr['columnIndex'];
        $columnValue = $attr['columnValue'];
        $nestedRow = array_key_exists('children', $attr) ? $attr['children'] : [];

        $mergeColumns = $attr['mergeColumns'];
        $mergeColumnIndex = $columnIndex + ($mergeColumns - 1);

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                // skip first row don't need to auto insert new row
                if ($rowValue != $attr['rowValue']) {
                    $objWorksheet->insertNewRowBefore($rowValue);
                    $this->updatePlaceholderCoordinate(null, $rowValue, $extra);
                }

                $cellCoordinate = $columnValue.$rowValue;
                $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                if (!empty($nestedRow)) {
                    $nestedRowValue = $this->nestedRow($nestedRow, $key, $rowValue, $columnIndex, $mergeColumns, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
                }

                // merge range based on mergeColumns attr and nestedRowValue
                $mergeRowValue = isset($nestedRowValue) ? $nestedRowValue : $rowValue;
                $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $mergeRowValue, $objWorksheet, $attr);
                $rowValue = $mergeRowValue;

                $rowValue++;
            }
        } else {
            // replace placeholder as blank if data is empty
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);

            // mergeColumns even if there is no data
            $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);

            // set nestedRow parentKey = -1 to allow mergeColumns to apply even for empty nested cells
            if (!empty($nestedRow)) {
                $this->nestedRow($nestedRow, -1, $rowValue, $columnIndex, $mergeColumns, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
            }
        }
    }

    private function nestedRow($nestedRow, $parentKey, $parentRowValue, $parentColumnIndex, $parentMergeColumns, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        $nestedAttr = $this->extractPlaceholderAttr($nestedRow, $this->advancedTypes['row'], $extra);
        $filter = array_key_exists('filter', $nestedAttr) ? $nestedAttr['filter'] : null;
        $secondNestedRow = array_key_exists('children', $nestedAttr) ? $nestedAttr['children'] : [];

        $nestedRowValue = $parentRowValue;
        $nestedColumnIndex = $parentColumnIndex + ($parentMergeColumns - 1) + 1; // always output children to the immediate next column
        $nestedColumnValue = Coordinate::stringFromColumnIndex($nestedColumnIndex);

        $nestedMergeColumns = $nestedAttr['mergeColumns'];
        $mergeColumnIndex = $nestedColumnIndex + ($nestedMergeColumns - 1);

        // set column width to width of first nested column
        $attr['columnWidth'] = $objWorksheet->getColumnDimension($nestedColumnValue)->getWidth();

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

        if (!empty($nestedData)) {
            foreach ($nestedData as $nestedKey => $nestedValue) {
                // skip first row don't need to auto insert new row
                if ($nestedRowValue != $parentRowValue) {
                    $objWorksheet->insertNewRowBefore($nestedRowValue);
                    $this->updatePlaceholderCoordinate(null, $nestedRowValue, $extra);
                }

                $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
                $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, $nestedValue, $attr, $extra);

                if (!empty($secondNestedRow)) {
                    $secondNestedRowValue = $this->nestedRow($secondNestedRow, $nestedKey, $nestedRowValue, $nestedColumnIndex, $nestedMergeColumns, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
                }

                // merge range based on mergeColumns attr and secondNestedRowValue
                $mergeRowValue = isset($secondNestedRowValue) ? $secondNestedRowValue : $nestedRowValue;
                $this->mergeRange($nestedColumnIndex, $nestedRowValue, $mergeColumnIndex, $mergeRowValue, $objWorksheet, $attr);
                $nestedRowValue = $mergeRowValue;

                $nestedRowValue++;
            }

            // -1 due to the last $nestedRowValue++
            $nestedRowValue = $nestedRowValue - 1;

        } else {
            // renderCell as empty to set style
            $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, "", $attr, $extra);

            // mergeColumns even if there is no data
            $this->mergeRange($nestedColumnIndex, $nestedRowValue, $mergeColumnIndex, $nestedRowValue, $objWorksheet, $attr);

            // set nestedRow parentKey = -1 to allow mergeColumns to apply even for empty nested cells
            if (!empty($secondNestedRow)) {
                $this->nestedRow($secondNestedRow, -1, $nestedRowValue, $nestedColumnIndex, $nestedMergeColumns, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
            }
        }
        return $nestedRowValue;
    }

    private function column($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        $rowValue = $attr['rowValue'];
        $columnIndex = $attr['columnIndex'];
        $columnValue = $attr['columnValue'];
        $mergeColumns = $attr['mergeColumns'];
        $nestedColumn = array_key_exists('children', $attr) ? $attr['children'] : [];

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);

                // skip first column don't need to auto insert new column
                if ($columnIndex != $attr['columnIndex']) {
                    $objWorksheet->insertNewColumnBefore($columnValue);
                    $this->updatePlaceholderCoordinate($columnValue, null, $extra);
                }

                $cellCoordinate = $columnValue.$rowValue;
                $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                if (!empty($nestedColumn)) {
                    $nestedAttr = $this->extractPlaceholderAttr($nestedColumn, $this->advancedTypes[__FUNCTION__], $extra);
                    $nestedMergeColumns = $nestedAttr['mergeColumns'];
                    $nestedRowValue = $rowValue + 1; // always output children to the immediate next row
                    $nestedColumnIndex = $columnIndex;

                    if (!empty($nestedAttr['data'])) {
                        foreach ($nestedAttr['data'] as $nestedKey => $nestedValue) {
                            $nestedColumnValue = Coordinate::stringFromColumnIndex($nestedColumnIndex);
                            if ($nestedColumnIndex != $columnIndex) {
                                $objWorksheet->insertNewColumnBefore($nestedColumnValue);
                                $this->updatePlaceholderCoordinate($nestedColumnValue, null, $extra);
                            }

                            $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
                            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, $nestedValue, $attr, $extra);

                            // merge range based on mergeColumns attr
                            $nestedMergeColumnIndex = $nestedColumnIndex + ($nestedMergeColumns - 1);
                            $this->mergeRange($nestedColumnIndex, $nestedRowValue, $nestedMergeColumnIndex, $nestedRowValue, $objWorksheet, $attr);
                            $nestedColumnIndex = $nestedMergeColumnIndex;

                            $nestedColumnIndex++;
                        }

                        // -1 due to the last $nestedRowValue++
                        $nestedColumnIndex = $nestedColumnIndex - 1;
                    } else {
                        // renderCell as empty to set style
                        $nestedColumnValue = Coordinate::stringFromColumnIndex($nestedColumnIndex);
                        $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
                        $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, "", $attr, $extra);

                        // mergeColumns even if there is no data
                        $nestedMergeColumnIndex = $nestedColumnIndex + ($nestedMergeColumns - 1);
                        $this->mergeRange($nestedColumnIndex, $nestedRowValue, $nestedMergeColumnIndex, $nestedRowValue, $objWorksheet, $attr);

                        $nestedColumnIndex = $nestedMergeColumnIndex;
                    }
                }

                // mergeColumns attr from parent will only be used if there is no nested column
                $mergeColumnIndex = isset($nestedColumnIndex) ? $nestedColumnIndex : $columnIndex + ($mergeColumns - 1);
                $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);
                $columnIndex = $mergeColumnIndex;

                $columnIndex++;
            }
        } else {
            // replace placeholder as blank if data is empty
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);

            // mergeColumns even if there is no data
            $mergeColumnIndex = $columnIndex + ($mergeColumns - 1);
            $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);
        }
    }

    private function table($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
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

                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                $cellCoordinate = $columnValue.$rowValue;
                $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $header, $attr, $extra);
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

                    $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoordinate = $columnValue.$rowValue;
                    $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                    $columnIndex++;
                }

                $rowValue++;
            }
        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }
    }

    private function match($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        list($attr['placeholderPrefix'], $attr['placeholderSuffix']) = $this->splitDisplayValue($attr['displayValue']);

        $rowsArray = array_key_exists('rows', $attr) ? $attr['rows'] : [];
        $columnsArray = array_key_exists('columns', $attr) ? $attr['columns'] : [];

        if (!empty($rowsArray)) {
            $this->matchRows($objSpreadsheet, $objWorksheet, $objCell, $attr, $rowsArray, $columnsArray, $extra);
        } else {
            $columnIndex = $attr['columnIndex'];
            $rowValue = $attr['rowValue'];
            $this->matchColumns($objSpreadsheet, $objWorksheet, $objCell, $attr, $columnsArray, $columnIndex, $rowValue, null, $extra);
        }
    }

    private function matchRows($objSpreadsheet, $objWorksheet, $objCell, $attr, $rowsArray=[], $columnsArray=[], $extra)
    {
        $matchFrom = array_key_exists('matchFrom', $rowsArray) ? $rowsArray['matchFrom'] : [];
        $matchTo = array_key_exists('matchTo', $rowsArray) ? $rowsArray['matchTo'] : [];
        $rowData = $this->getPlaceholderData($matchFrom, $extra);
        $nestedRow = isset($rowsArray['children']) ? $rowsArray['children'] : [];

        $filterStr = $this->formatFilter($matchTo);
        $attr['filterStr'] = array_key_exists('filterStr', $attr) ? $attr['filterStr'].$filterStr : $filterStr;

        $columnIndex = $attr['columnIndex'];
        $rowValue = $attr['rowValue'];
        $mergeColumns = $attr['mergeColumns'];
        $mergeColumnIndex = $columnIndex + ($mergeColumns - 1);

        if (!empty($rowData)) {
            foreach ($rowData as $key => $value) {
                // reset columnIndex after every loop of row
                $columnIndex = $attr['columnIndex'];
                if (!empty($columnsArray)) {
                    $this->matchColumns($objSpreadsheet, $objWorksheet, $objCell, $attr, $columnsArray, $columnIndex, $rowValue, $value, $extra);
                    $rowValue++;
                } else {
                    if (!empty($nestedRow)) {
                        $printedMatchFilter = sprintf($attr['filterStr'], $key);
                        $rowValue = $this->nestedMatchRow($nestedRow, $printedMatchFilter, $key, $rowValue, $columnIndex, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
                    } else {
                        $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']).$attr['filterStr'].".".$attr['placeholderSuffix'];
                        $placeholder = sprintf($placeholderFormat, $value);

                        $matchData = Hash::extract($extra['vars'], $placeholder);
                        $matchValue = !empty($matchData) ? current($matchData) : '';

                        $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                        $cellCoordinate = $columnValue.$rowValue;
                        $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
                        $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);

                        $rowValue++;
                    }
                }
            }

        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
            $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);
        }
    }

    private function nestedMatchRow($nestedRow, $matchFilter, $parentKey, $rowValue, $columnIndex, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        if (array_key_exists('rows', $nestedRow)) {
            $nestedAttr = $nestedRow['rows'];
            $nestedFilter = array_key_exists('filter', $nestedAttr) ? $nestedAttr['filter'] : null; // used to filter nested match row data
            $nestedMatchFrom = array_key_exists('matchFrom', $nestedAttr) ? $nestedAttr['matchFrom'] : [];
            $nestedMatchTo = array_key_exists('matchTo', $nestedAttr) ? $nestedAttr['matchTo'] : [];
            $nestedMergeBy = array_key_exists('mergeBy', $nestedAttr) ? $nestedAttr['mergeBy'] : [];
            $secondNestedRow = array_key_exists('children', $nestedAttr) ? $nestedAttr['children'] : [];

            $mergeColumns = $attr['mergeColumns'];
            $mergeColumnIndex = $columnIndex + ($mergeColumns - 1);

            $variableMatchFilter = $matchFilter.$this->formatFilter($nestedMatchTo); // used to filter matching results

            $nestedData = [];
            if (!empty($nestedMatchFrom)) {
                if (!is_null($nestedFilter)) {
                    list($placeholderPrefix, $placeholderSuffix) = $this->splitDisplayValue($nestedMatchFrom);
                    $nestedDataFilter = $this->formatFilter($nestedFilter);
                    $placeholderFormat = $this->formatPlaceholder($placeholderPrefix).$nestedDataFilter.".";

                    $placeholder = sprintf($placeholderFormat.$placeholderSuffix, $parentKey);
                    $placeholderId = sprintf($placeholderFormat.'id', $parentKey);
                    $nestedData = Hash::combine($extra['vars'], $placeholderId, $placeholder);
                } else {
                    $nestedData = $this->getPlaceholderData($nestedMatchFrom, $extra);
                }
            }

            if (!empty($nestedData)) {
                foreach ($nestedData as $nestedKey => $nestedValue) {
                    $printedMatchFilter = sprintf($variableMatchFilter, $nestedValue);

                    if (!empty($secondNestedRow)) {
                        $rowValue = $this->nestedMatchRow($secondNestedRow, $printedMatchFilter, $nestedKey, $rowValue, $columnIndex, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
                    } else {
                        $mergeRowCount = 0;
                        if (!empty($nestedMergeBy)) {
                            $mergeRowCount = $this->countMergeData($nestedMergeBy, $nestedKey, $mergeRowCount, $extra);
                        }

                        // printedMatchFilter already contains all key values, no need for sprintf again
                        $placeholder = $this->formatPlaceholder($attr['placeholderPrefix']).$printedMatchFilter.".".$attr['placeholderSuffix'];

                        $matchData = Hash::extract($extra['vars'], $placeholder);
                        $matchValue = !empty($matchData) ? current($matchData) : '';

                        $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                        $nestedCellCoordinate = $columnValue.$rowValue;

                        $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, $matchValue, $attr, $extra);

                        $mergeRowValue = ($mergeRowCount > 1) ? $rowValue + ($mergeRowCount - 1) : $rowValue;
                        $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $mergeRowValue, $objWorksheet, $attr);

                        $rowValue = $mergeRowValue;
                        $rowValue++;
                    }
                }
            } else {
                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                $nestedCellCoordinate = $columnValue.$rowValue;
                $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, "", $attr, $extra);
                $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);
                $rowValue++;
            }
        }
        return $rowValue;
    }

    private function matchColumns($objSpreadsheet, $objWorksheet, $objCell, $attr, $columnsArray=[], &$columnIndex, &$rowValue, $filterValue=null, $extra)
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

                        $nestedColumnValue = Coordinate::stringFromColumnIndex($columnIndex);
                        $nestedCellCoordinate = $nestedColumnValue.$rowValue;

                        $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, $matchValue, $attr, $extra);
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

                    $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoordinate = $columnValue.$rowValue;

                    $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
                    $columnIndex++;
                }
            }

        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue.$rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }
    }

    private function countMergeData($mergeAttr, $parentKey, $mergeCount, $extra)
    {
        $mergeFrom = array_key_exists('mergeFrom', $mergeAttr) ? $mergeAttr['mergeFrom'] : [];
        $filter = array_key_exists('filter', $mergeAttr) ? $mergeAttr['filter'] : null;
        $nestedMergeBy = array_key_exists('mergeBy', $mergeAttr) ? $mergeAttr['mergeBy'] : [];

        $data = [];
        if (!empty($mergeFrom)) {
            if (!is_null($filter)) {
                list($placeholderPrefix, $placeholderSuffix) = $this->splitDisplayValue($mergeFrom);
                $formattedFilter = $this->formatFilter($filter);
                $placeholderFormat = $this->formatPlaceholder($placeholderPrefix).$formattedFilter.".";

                $placeholder = sprintf($placeholderFormat.$placeholderSuffix, $parentKey);
                $placeholderId = sprintf($placeholderFormat.'id', $parentKey);
                $data = Hash::combine($extra['vars'], $placeholderId, $placeholder);
            } else {
                $data = $this->getPlaceholderData($mergeFrom, $extra);
            }
        }

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (!empty($nestedMergeBy)) {
                    $mergeCount = $this->countMergeData($nestedMergeBy, $key, $mergeCount, $extra);
                } else {
                    $mergeCount++;
                }
            }
        } else {
            $mergeCount++;
        }
        return $mergeCount;
    }

    private function dropdown($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        $matchFrom = array_key_exists('rows', $attr) ? $attr['rows'] : [];
        $rowData = $this->getPlaceholderData($matchFrom, $extra);

        if (!empty($rowData)) {
            $columnValue = $attr['columnValue'];
            $rowValue = $attr['rowValue'];
            foreach ($rowData as $key => $value) {
                $cellCoordinate = $columnValue.$rowValue;
                $this->renderDropdown($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
                $rowValue++;
            }
        } else {
            $cellCoordinate = $attr['coordinate'];
            $this->renderDropdown($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }
    }

    private function image($objSpreadsheet, $objWorksheet, $objCell, $attr, ArrayObject $extra)
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

        $this->renderImage($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $tempImagePath, $attr, $extra);

        // set to empty to remove the placeholder
        $objWorksheet->getCell($cellCoordinate)->setValue('');
    }
}
