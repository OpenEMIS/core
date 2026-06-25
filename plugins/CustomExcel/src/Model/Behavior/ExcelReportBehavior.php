<?php

namespace CustomExcel\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Cake\Event\EventInterface;

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

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $model = $this->_table;
        $folder = WWW_ROOT . $this->getConfig('folder');
        $subfolder = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder');
        if (!isset($config['filename'])) {
            $this->setConfig('filename', $model->getAlias());
        }

        new Folder($folder, true, 0777);
        new Folder($subfolder, true, 0777);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onRenderExcelTemplate'] = 'onRenderExcelTemplate';
        $events['ExcelTemplates.Model.onGetExcelTemplateVars'] = 'onGetExcelTemplateVars';
        return $events;
    }

    public function onGetExcelTemplateVars(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->_table;

        $params = isset($extra['requestQuery']) ? $extra['requestQuery'] : $model->getQueryString();

        $vars = $this->getVars($params, $extra);
        $results = Hash::flatten($vars);
        // pr($results);
        //die;
    }

    public function getVars($params, ArrayObject $extra)
    {
//        Log::debug('@ExcelReportBehavior::getVars START variableSource=' . $this->getConfig('variableSource')); //[TEMP-LOG]
        $model = $this->_table;

        $variableValues = new ArrayObject([]);
        if ($this->getConfig('variableSource') == 'database') {
//            Log::debug('@ExcelReportBehavior::getVars dispatching onExcelTemplateInitialiseQueryVariables'); //[TEMP-LOG]
            $event = $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateInitialiseQueryVariables', [$params, $extra], $this);
            if ($event->isStopped()) {
//                Log::debug('@ExcelReportBehavior::getVars event stopped, returning result'); //[TEMP-LOG];
                return $event->getResult();
            }
            if ($event->getResult()) {
                $variableValues = $event->getResult();
//                Log::debug('@ExcelReportBehavior::getVars got result from event, keys=' . implode(',', array_keys($variableValues->getArrayCopy()))); //[TEMP-LOG];
            }

        } else if ($this->getConfig('variableSource') == 'file') {
            $variables = $this->getConfig('variables');
//            Log::debug('@ExcelReportBehavior::getVars file source, processing ' . count($variables) . ' variables'); //[TEMP-LOG];
            foreach ($variables as $var) {
//                Log::debug('@ExcelReportBehavior::getVars dispatching onExcelTemplateInitialise' . $var); //[TEMP-LOG];
                $event = $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateInitialise' . $var, [$params, $extra], $this);
                if ($event->isStopped()) {
//                    Log::debug('@ExcelReportBehavior::getVars event stopped for ' . $var . ', returning'); //[TEMP-LOG];
                    return $event->getResult();
                }
                if ($event->getResult()) {
                    $variableValues[$var] = $event->getResult();
//                    Log::debug('@ExcelReportBehavior::getVars got result for ' . $var); //[TEMP-LOG];
                } else {
//                    Log::debug('@ExcelReportBehavior::getVars no result for ' . $var); //[TEMP-LOG];
                }
            }
        } else {
//            Log::warning('@ExcelReportBehavior::getVars unknown variableSource=' . $this->getConfig('variableSource')); //[TEMP-LOG];
        }

        $variableValues = $variableValues->getArrayCopy();
//        Log::debug('@ExcelReportBehavior::getVars final count=' . count($variableValues) . ' keys=' . implode(',', array_keys($variableValues))); //[TEMP-LOG]
        return $variableValues;
    }

    //POCOR-8568[Here added  Event $event]

    public function onRenderExcelTemplate(EventInterface $event, ArrayObject $extra)
    {
//        Log::debug('@ExcelReportBehavior::onRenderExcelTemplate START'); //[TEMP-LOG]
        ini_set('max_execution_time', 360);
        $this->renderExcelTemplate($extra, $event);
//        Log::debug('@ExcelReportBehavior::onRenderExcelTemplate END'); //[TEMP-LOG]
    }

    //POCOR-8568[Here added  EventInterface $event]

    /**
     * @throws \Exception
     */
    public function renderExcelTemplate(ArrayObject $extra, EventInterface $event = null) //POCOR-8588
    {
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate ENTRY className=' . ($extra['className'] ?? 'N/A')); //[TEMP-LOG]
        $model = $this->_table;
        $format = $this->getConfig('format');
        $paramVal = '';
        if (isset($extra['requestQuery'])) {
            $params = $extra['requestQuery'];
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate using requestQuery params: ' . json_encode($params)); //[TEMP-LOG]
        } else {
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate using model->getQueryString()'); //[TEMP-LOG]
            $params = $model->getQueryString();
            if (empty($params)) {
                //Log::debug('@ExcelReportBehavior::renderExcelTemplate getQueryString empty, decoding from query string'); //[TEMP-LOG]
                $params = $model->paramsDecode($event->getSubject()->getRequest()->getQuery('queryString'));
            }
            $paramVal = isset($params['assessment_id']) ? $params['assessment_id'] : 'N/A'; //POCOR-6908
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate assessment_id=' . $paramVal); //[TEMP-LOG]
        }

        //Log::debug('@ExcelReportBehavior::renderExcelTemplate final params: ' . json_encode($params)); //[TEMP-LOG]
        $extra['params'] = $params;

        //Log::debug('@ExcelReportBehavior::renderExcelTemplate dispatching onExcelTemplateBeforeGenerate'); //[TEMP-LOG]
        $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateBeforeGenerate', [$params, $extra], $this); // POCOR-7443
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate back from onExcelTemplateBeforeGenerate'); //[TEMP-LOG]

        //Log::debug('@ExcelReportBehavior::renderExcelTemplate calling getVars'); //[TEMP-LOG]
        $extra['vars'] = $this->getVars($params, $extra);
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate getVars completed, vars count=' . count($extra['vars'])); //[TEMP-LOG]

        $extra['file'] = $this->getConfig('filename') . '_' . date('Ymd') . 'T' . date('His') . '.' . $format;
        $extra['path'] = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS;
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate file=' . $extra['file'] . ' path=' . $extra['path']); //[TEMP-LOG]

        $temppath = tempnam($extra['path'], $this->getConfig('filename') . '_');
        $extra['file_path'] = $temppath;
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate temp file created: ' . $temppath); //[TEMP-LOG]

        //Log::debug('@ExcelReportBehavior::renderExcelTemplate calling loadExcelTemplate'); //[TEMP-LOG]
        $objSpreadsheet = $this->loadExcelTemplate($extra, $event);
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate back from loadExcelTemplate'); //[TEMP-LOG]

        //Log::debug('@ExcelReportBehavior::renderExcelTemplate calling generateExcel'); //[TEMP-LOG]
        $this->generateExcel($objSpreadsheet, $extra);
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate back from generateExcel'); //[TEMP-LOG]

        if (!empty($paramVal)) { // POCOR-6908
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate calling saveFileAssessment with assessment_id=' . $paramVal); //[TEMP-LOG]
            $this->saveFileAssessment($objSpreadsheet, $temppath, $format, $params['student_id'], $paramVal);
        } else {
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate calling saveFile with report_card_id=' . ($params['report_card_id'] ?? 'N/A')); //[TEMP-LOG]
            $this->saveFile($objSpreadsheet, $temppath, $format, $params['student_id'], $params['report_card_id']);
        }

        if ($extra->offsetExists('temp_logo')) {
            // delete temporary logo
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate deleting temp_logo'); //[TEMP-LOG]
            $this->deleteFile($extra['temp_logo']);
        }

        if ($extra->offsetExists('image_resource')) {
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate destroying image_resource'); //[TEMP-LOG]
            imagedestroy($extra['image_resource']);
        }

        if ($extra->offsetExists('tmp_file_path')) {
            // delete temporary excel template file after save
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate deleting tmp_file_path=' . $extra['tmp_file_path']); //[TEMP-LOG]
            $this->deleteFile($extra['tmp_file_path']);
        }

        //Log::debug('@ExcelReportBehavior::renderExcelTemplate dispatching onExcelTemplateAfterGenerate'); //[TEMP-LOG]
        $model->dispatchEvent('ExcelTemplates.Model.onExcelTemplateAfterGenerate', [$params, $extra], $this);
        //Log::debug('@ExcelReportBehavior::renderExcelTemplate back from onExcelTemplateAfterGenerate'); //[TEMP-LOG]

        if (!empty($params['student_id'])) {
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate handling PDF for student_id=' . $params['student_id']); //[TEMP-LOG]
            $pdfFilePath = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS . $this->getConfig('filename') . '_' . $params['student_id'] . '.txt';
            $pdfFileContent = file_get_contents($pdfFilePath);
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate PDF content size=' . strlen($pdfFileContent) . ' bytes'); //[TEMP-LOG]

            $StudentsReportCards = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsReportCards');
            // save Pdf file
            $updateResult = $StudentsReportCards->updateAll([
                'file_content_pdf' => $pdfFileContent,
                'status' => 3//POCOR-7530
            ], $params);
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate updated InstitutionStudentsReportCards with PDF, affected rows=' . $updateResult); //[TEMP-LOG]

            $this->deleteFile($pdfFilePath);
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate deleted PDF temp file'); //[TEMP-LOG]
        } else {
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate no student_id, skipping PDF'); //[TEMP-LOG]
        }

        if ($this->getConfig('download')) {
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate download=true, reading temp file'); //[TEMP-LOG]
            $tempcontent = file_get_contents($temppath);
            $tempinfo = ['filesize' => strlen($tempcontent)];
            //Log::debug('@ExcelReportBehavior::renderExcelTemplate calling downloadFile, size=' . $tempinfo['filesize']); //[TEMP-LOG]
            $this->downloadFile($tempcontent, $extra['file'], $tempinfo['filesize']);
//            Log::debug('@ExcelReportBehavior::renderExcelTemplate back from downloadFile'); //[TEMP-LOG]
        } else {
//            Log::debug('@ExcelReportBehavior::renderExcelTemplate download=false, skipping download'); //[TEMP-LOG]
        }

        if ($this->getConfig('purge')) {
//            Log::debug('@ExcelReportBehavior::renderExcelTemplate purge=true, deleting temp file'); //[TEMP-LOG];
            // delete excel file after download
            $this->deleteFile($temppath);
//            Log::debug('@ExcelReportBehavior::renderExcelTemplate temp file deleted'); //[TEMP-LOG]
        } else {
//            Log::debug('@ExcelReportBehavior::renderExcelTemplate purge=false, keeping temp file'); //[TEMP-LOG];
        }

        gc_collect_cycles();
//        Log::debug('@ExcelReportBehavior::renderExcelTemplate EXIT'); //[TEMP-LOG]
    }


    public function loadExcelTemplate(ArrayObject $extra, ?EventInterface $event = null) //POCOR-8588
   {
//        Log::debug('@ExcelReportBehavior::loadExcelTemplate START'); //[TEMP-LOG]
        $model = $this->_table;
        if (isset($extra['requestQuery']) && isset($extra['requestQuery'][$this->getConfig('templateTableKey')])) {
            $recordId = $extra['requestQuery'][$this->getConfig('templateTableKey')];
//            Log::debug('@ExcelReportBehavior::loadExcelTemplate got recordId from requestQuery: ' . $recordId); //[TEMP-LOG]
        } else {
            //$recordId = $model->getQueryString($this->getConfig('templateTableKey'));
            $params = $model->getQueryString();
            if (empty($params)) {
//                Log::debug('@ExcelReportBehavior::loadExcelTemplate getQueryString empty, decoding from request'); //[TEMP-LOG];
                $params = $model->paramsDecode($event->getSubject()->getRequest()->getQuery('queryString'));
            }
            $recordId = $params[$this->getConfig('templateTableKey')];
//            Log::debug('@ExcelReportBehavior::loadExcelTemplate got recordId from params: ' . $recordId); //[TEMP-LOG]
        }

        $Table = TableRegistry::getTableLocator()->get($this->getConfig('templateTable'));
//        Log::debug('@ExcelReportBehavior::loadExcelTemplate fetched template table: ' . $this->getConfig('templateTable')); //[TEMP-LOG]

        if (empty($recordId)) {
//            Log::debug('@ExcelReportBehavior::loadExcelTemplate no recordId, creating blank Spreadsheet'); //[TEMP-LOG]
            $objSpreadsheet = new Spreadsheet();
        } else {
//            Log::debug('@ExcelReportBehavior::loadExcelTemplate fetching template entity id=' . $recordId); //[TEMP-LOG]
            // Read from excel template attachment then create as temporary file in server so that can read back the same file and read as Spreadsheet object
            try {
                $entity = $Table->get($recordId);
//                Log::debug('@ExcelReportBehavior::loadExcelTemplate loaded template entity, has excel_template=' . ($entity->has('excel_template') ? 'yes' : 'no')); //[TEMP-LOG]
            } catch (\Exception $e) {
//                Log::error('@ExcelReportBehavior::loadExcelTemplate FAILED to fetch template record id=' . $recordId . ': ' . $e->getMessage()); //[TEMP-LOG];
                throw $e;
            }

            if ($entity->has('excel_template_name')) {
//                Log::debug('@ExcelReportBehavior::loadExcelTemplate template name=' . $entity->excel_template_name); //[TEMP-LOG]
                $file = $this->getFile($entity->excel_template);
                if ($file === false || empty($file)) {
                    throw new \Exception('Invalid file content.');
                }
//                Log::debug('@ExcelReportBehavior::loadExcelTemplate template content size=' . strlen($file) . ' bytes'); //[TEMP-LOG]

                // Create a temporary file with the correct extension
                $filepath = tempnam($extra['path'], $this->getConfig('filename') . '_Template_') . '.xls';
                $extra['tmp_file_path'] = $filepath;
//                Log::debug('@ExcelReportBehavior::loadExcelTemplate wrote temp file: ' . $filepath); //[TEMP-LOG]

                file_put_contents($filepath, $file);
                // Bake image transparency into the template via Laravel artisan
                exec(PHP_BINARY . ' ' . escapeshellarg(ROOT . DS . 'api' . DS . 'artisan') . ' reportcards:fix-transparency ' . escapeshellarg($filepath) . ' 2>&1');
//                Log::debug('@ExcelReportBehavior::loadExcelTemplate ran fix-transparency command'); //[TEMP-LOG]

                try {
                    // Read back from the same temporary file
                    $inputFileType = IOFactory::identify($filepath);
                    $objReader = IOFactory::createReader($inputFileType);
                    $objSpreadsheet = $objReader->load($filepath);
//                    Log::debug('@ExcelReportBehavior::loadExcelTemplate loaded spreadsheet from temp file, type=' . $inputFileType); //[TEMP-LOG]
                } catch (\Exception $e) {
//                    Log::error('@ExcelReportBehavior::loadExcelTemplate FAILED to load spreadsheet: ' . $e->getMessage()); //[TEMP-LOG];
                    throw $e;
                }
            } else {
//                Log::debug('@ExcelReportBehavior::loadExcelTemplate entity has no excel_template_name, returning empty Spreadsheet'); //[TEMP-LOG]
                $objSpreadsheet = new Spreadsheet();
            }
        }
//        Log::debug('@ExcelReportBehavior::loadExcelTemplate END'); //[TEMP-LOG]
        return $objSpreadsheet;
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

    public function generateExcel($objSpreadsheet, ArrayObject $extra)
    {
//        Log::debug('@ExcelReportBehavior::generateExcel START'); //[TEMP-LOG]
        $worksheetCount = 0;
        foreach ($objSpreadsheet->getWorksheetIterator() as $objWorksheet) {
            $worksheetCount++;
//            Log::debug('@ExcelReportBehavior::generateExcel processing worksheet ' . $worksheetCount . ': ' . $objWorksheet->getTitle()); //[TEMP-LOG]
            $this->processWorksheet($objSpreadsheet, $objWorksheet, $extra);

            // lock all sheets
            if ($this->getConfig('lockSheets')) {
                $objWorksheet->getProtection()->setSheet(true);
//                Log::debug('@ExcelReportBehavior::generateExcel locked sheet ' . $objWorksheet->getTitle()); //[TEMP-LOG]
            }
        }
//        Log::debug('@ExcelReportBehavior::generateExcel processed ' . $worksheetCount . ' worksheets'); //[TEMP-LOG]

        // to force the first sheet active
        $objSpreadsheet->setActiveSheetIndex(0);
//        Log::debug('@ExcelReportBehavior::generateExcel set active sheet index to 0'); //[TEMP-LOG]
//        Log::debug('@ExcelReportBehavior::generateExcel END'); //[TEMP-LOG]
    }

    private function processWorksheet($objSpreadsheet, $objWorksheet, $extra)
    {
//        Log::debug('@ExcelReportBehavior::processWorksheet START sheet=' . $objWorksheet->getTitle() . ' dimension=' . $objWorksheet->getHighestRow() . 'x' . $objWorksheet->getHighestColumn()); //[TEMP-LOG]
        if ($this->currentWorksheet !== $objWorksheet) {
            $this->currentWorksheetIndex++;
            $this->currentWorksheet = $objWorksheet;
        }

        $extra['placeholders'] = [];
        $this->processBasicPlaceholder($objSpreadsheet, $objWorksheet, $extra);
//        Log::debug('@ExcelReportBehavior::processWorksheet after processBasicPlaceholder, placeholders count=' . count($extra['placeholders'])); //[TEMP-LOG]

        if (!empty($extra['placeholders'])) {
            $this->processAdvancedPlaceholder($objSpreadsheet, $objWorksheet, $extra);
//            Log::debug('@ExcelReportBehavior::processWorksheet after processAdvancedPlaceholder'); //[TEMP-LOG]
        } else {
//            Log::debug('@ExcelReportBehavior::processWorksheet no advanced placeholders'); //[TEMP-LOG]
        }
//        Log::debug('@ExcelReportBehavior::processWorksheet END'); //[TEMP-LOG]
    }

//    public function renderImage($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $imagePath, $attr, $extra)
//    {
//        $imageWidth = $attr['imageWidth'];
//        $imageMarginLeft = $attr['imageMarginLeft'];
//        $imageMarginTop = $attr['imageMarginTop'];
//
//        $objDrawing = new MemoryDrawing();
//
//        if (!isset($extra['image_resource']) && $imagePath) {
//            switch ($attr['mime_type']) {
//                case 'image/png':
//                    $imageResource = imagecreatefrompng($imagePath);
//                    $objDrawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
//                    $objDrawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
//                    break;
//                case 'image/jpeg':
//                    $imageResource = imagecreatefromjpeg($imagePath);
//                    $objDrawing->setMimeType(MemoryDrawing::MIMETYPE_JPEG);
//                    $objDrawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
//                    break;
//                case 'image/gif':
//                    $imageResource = imagecreatefromgif($imagePath);
//                    $objDrawing->setMimeType(MemoryDrawing::MIMETYPE_GIF);
//                    $objDrawing->setRenderingFunction(MemoryDrawing::RENDERING_GIF);
//                    break;
//                default:
//                    $imageResource = '';
//                    break;
//            }
//            $extra['image_resource'] = $imageResource;
//        }
//
//        if (isset($extra['image_resource'])) {
//            //retain transparency on png/gif file
//            imageAlphaBlending($extra['image_resource'], true);
//            imageSaveAlpha($extra['image_resource'], true);
//
//            $objDrawing->setImageResource($extra['image_resource']);
//            $objDrawing->setWidth($imageWidth);
//            $objDrawing->setCoordinates($cellCoordinate);
//            $objDrawing->setOffsetX($imageMarginLeft);
//            $objDrawing->setOffsetY($imageMarginTop);
//            $objDrawing->setWorksheet($objSpreadsheet->getActiveSheet());
//        }
//    }

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
                        //Log::debug('@ExcelReportBehavior::processBasicPlaceholder cell=' . $cellCoordinate . ' data=' . $cellValue); //[TEMP-LOG]
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

    private function getAdvancedTypeKeyword($keyword)
    {
        $format = '${"%s":';
        $value = sprintf($format, $keyword);

        return $value;
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
                    $value = $this->getVarSafe($vars, $placeholder);

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

            if ($this->getConfig('wrapText')) {
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
            $value = isset($flattenVar[$placeholder]) ? $flattenVar[$placeholder] : '';

            $attr = array_merge($placeHolderAttr, $cellAttr);
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);
        }
    }

    private function convertPlaceHolderToArray($str)
    {
        $pos = strpos($str, '$');
        $json = substr($str, $pos + 1, strlen($str));
        $jsonArray = json_decode($json, true);

        return $jsonArray;
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

    public function renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $cellValue, $attr, $extra)
    {
        $type = $attr['type'];
        $format = $attr['format'];
        $cellStyle = $attr['style'];
        $columnWidth = $attr['columnWidth'];

        $targetCell = $objWorksheet->getCell($cellCoordinate);
        $targetColumnValue = $targetCell->getColumn();
        $targetRowValue = $targetCell->getRow();

        // Track last col/row
        $this->checkLastColumn($targetColumnValue);
        $this->checkLastRow($targetRowValue);

        // ---------------------------
        // SAFETY WRAPPER
        // ---------------------------
        try {

            switch ($type) {

                case 'number':
                    if (!is_null($format) && is_numeric($cellValue)) {
                        // correct Excel format for numeric decimals
                        $cellStyle
                            ->getNumberFormat()
                            ->setFormatCode('0.' . str_repeat('0', (int)$format));
                    }
                    break;

                case 'date':
                    if (!is_null($format) && !empty($cellValue)) {
                        if ($cellValue instanceof \DateTimeInterface) {
                            $cellValue = $cellValue->format($format);
                        } else {
                            // Non-date object provided
                            throw new \Exception("Invalid date value for $cellCoordinate");
                        }
                    }
                    break;

                case 'time':
                    if (!is_null($format) && !empty($cellValue)) {
                        if ($cellValue instanceof \DateTimeInterface) {
                            $cellValue = $cellValue->format($format);
                        } else {
                            throw new \Exception("Invalid time value for $cellCoordinate");
                        }
                    }
                    break;
            }

        } catch (\Throwable $e) {

            // Log the issue
            Log::warning("ExcelReport renderCell error at $cellCoordinate: " . $e->getMessage());

            // Fallback to safe empty cell
            $cellValue = "";
        }

        // ---------------------------
        // ALWAYS APPLY BASIC STYLE AND VALUE
        // ---------------------------
        if ($this->getConfig('wrapText')) {
            $cellStyle->getAlignment()->setWrapText(true);
        }
        if(is_array($cellValue)){
            $cellValue = $cellValue[0];
        }
        $objWorksheet->setCellValue($cellCoordinate, $cellValue);
        $objWorksheet->duplicateStyle($cellStyle, $cellCoordinate);

        // Column width
        $objWorksheet->getColumnDimension($targetColumnValue)->setAutoSize(false);
        $objWorksheet->getColumnDimension($targetColumnValue)->setWidth($columnWidth);
    }


    private function processAdvancedPlaceholder($objSpreadsheet, $objWorksheet, $extra)
    {
        // sort by column index so that to process the first column first
        ksort($extra['placeholders']);

        while (!empty($extra['placeholders'])) {
            $columnIndex = key($extra['placeholders']);
            $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
            $rowsObj = current($extra['placeholders']);
            $rowValue = key($rowsObj);
            $cellValue = current($rowsObj);

            $cellCoordinate = $columnValue . $rowValue;
            $objCell = $objWorksheet->getCell($cellCoordinate);

            foreach ($this->advancedTypes as $function => $keyword) {
                $value = $this->getAdvancedTypeKeyword($keyword);
                $pos = strpos($cellValue, $value);
                if ($pos !== false) {
                    if ($function == 'table') {
                        $function = 'tableData';//POCOR-8529
                    }
                    if (method_exists($this, $function)) {
                        $jsonArray = $this->convertPlaceHolderToArray($cellValue);
                        if (!empty($jsonArray)) {
                            $placeHolderAttr = $this->extractPlaceholderAttr($jsonArray, $keyword, $extra);
                            $cellAttr = $this->extractCellAttr($objWorksheet, $objCell);
                            $attr = array_merge($placeHolderAttr, $cellAttr);

                            //Log::debug('@ExcelReportBehavior::processAdvancedPlaceholder cell=' . $cellCoordinate . ' data=' . $cellValue); //[TEMP-LOG]
                            $this->$function($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
                        } else {
                            //Log::debug('@ExcelReportBehavior::processAdvancedPlaceholder cell=' . $cellCoordinate . ' data=' . $cellValue . ' is not a valid json format'); //[TEMP-LOG]
                        }
                    } else {
                        //Log::debug('@ExcelReportBehavior::processAdvancedPlaceholder function=' . $function . ' is not exists'); //[TEMP-LOG]
                    }
                }
            }

            unset($extra['placeholders'][$columnIndex][$rowValue]);
            if (empty($extra['placeholders'][$columnIndex])) {
                unset($extra['placeholders'][$columnIndex]);
            }
        }
    }

    private function extractPlaceholderAttr($jsonArray, $keyword, $extra)
    {
        $attr = [];

        $settings = isset($jsonArray[$keyword]) ? $jsonArray[$keyword] : [];
        $displayValue = isset($settings['displayValue']) ? $settings['displayValue'] : null;
        $attr['displayValue'] = isset($settings['displayValue']) ? $settings['displayValue'] : null;
        $attr['type'] = isset($settings['type']) ? $settings['type'] : null;
        $attr['format'] = isset($settings['format']) ? $settings['format'] : null;
        $attr['children'] = isset($settings['children']) ? $settings['children'] : [];
        $attr['rows'] = isset($settings['rows']) ? $settings['rows'] : [];
        $attr['columns'] = isset($settings['columns']) ? $settings['columns'] : [];
        $attr['filter'] = isset($settings['filter']) ? $settings['filter'] : null;
        $attr['displayColumns'] = isset($settings['displayColumns']) ? $settings['displayColumns'] : [];
        $attr['source'] = isset($settings['source']) ? $settings['source'] : null;
        $attr['showHeaders'] = isset($settings['showHeaders']) ? $settings['showHeaders'] : false;
        $attr['insertRows'] = isset($settings['insertRows']) ? $settings['insertRows'] : false;
        $attr['mergeColumns'] = isset($settings['mergeColumns']) ? $settings['mergeColumns'] : 1;
        $attr['imageWidth'] = isset($settings['imageWidth']) ? $settings['imageWidth'] : null;
        $attr['imageMarginLeft'] = isset($settings['imageMarginLeft']) ? $settings['imageMarginLeft'] : null;
        $attr['imageMarginTop'] = isset($settings['imageMarginTop']) ? $settings['imageMarginTop'] : null;

        // Start attributes  for dropdown
        $dropdownAttrs = ['source', 'promptTitle', 'prompt', 'errorTitle', 'error'];
        $attr['dropdown'] = [];
        foreach ($dropdownAttrs as $attrName) {
            if (isset($settings[$attrName])) {
                $attr['dropdown'][$attrName] = $settings[$attrName];
            }
        }
        // End attributes  for dropdown

        $attr['data'] = $this->getPlaceholderData($displayValue, $extra);

        return $attr;
    }

    private function getPlaceholderData($placeholder, $extra)
    {
        $placeholderArray = explode(".", $placeholder);
        if (end($placeholderArray) == 'i') {
            array_pop($placeholderArray);   // remove i
            $placeholder = implode(".", $placeholderArray);
            $formattedPlaceholder = $this->formatPlaceholder($placeholder);
            $placeholderData = !is_null($placeholder) ?

                $this->extractVarSafe($extra['vars'], $formattedPlaceholder) :
                [];

            $count = 1;
            foreach ($placeholderData as $key => $value) {
                $placeholderData[$key] = $count++;
            }
        } else {
            $formattedPlaceholder = $this->formatPlaceholder($placeholder);
            $placeholderId = $this->splitDisplayValue($placeholder)[0] . '.id';
            $formattedPlaceholderId = $this->formatPlaceholder($placeholderId);

            // check if data has id
            $idData = !is_null($placeholderId) ? $this->extractVarSafe($extra['vars'], $formattedPlaceholderId) : [];
            $valueData = !is_null($placeholderId) ? $this->extractVarSafe($extra['vars'], $formattedPlaceholder) : [];
            $equal = count($idData) == count($valueData);

            if (!empty($idData) && $equal) {
                // get id and value as key-value pair
                // selected field needs to be present in vars if not there will be a key-value number mismatch (be careful of using contain)
                $placeholderData = !is_null($placeholder) ?
                    $this->combineVarSafe($extra['vars'], $formattedPlaceholderId, $formattedPlaceholder) :
                    [];
            } else {
                // only get value
                $placeholderData = !is_null($placeholder) ? $this->extractVarSafe($extra['vars'], $formattedPlaceholder) : [];
            }
        }

        return $placeholderData;
    }

    private function formatPlaceholder($str, $offset = 1, $length = 0, $replacement = ['{n}'])
    {
        $placeholderArray = explode('.', $str);
        array_splice($placeholderArray, $offset, $length, $replacement);
        $placeholder = implode(".", $placeholderArray);

        return $placeholder;
    }

    private function splitDisplayValue($displayValue)
    {
        $displayArray = explode(".", $displayValue);
        $placeholderPrefix = current($displayArray);
        array_shift($displayArray);
        $placeholderSuffix = implode(".", $displayArray);

        return [$placeholderPrefix, $placeholderSuffix];
    }

    /**
     * POCOR-6908
     */
    // public function saveFileAssessment($objSpreadsheet, $filepath, $format, $student_id, $paramVal)
    // {
    //     Log::write('debug', 'ExcelReportBehavior >>> saveFile: ' . $format);
    //     $objWriter = IOFactory::createWriter($objSpreadsheet, $this->libraryTypes[$format]);

    //     if ($format == 'pdf') {
    //         $this->savePDFAssessment($objSpreadsheet, $filepath, $student_id, $paramVal);
    //         return; // POCOR-9336
    //     } else {
    //         // pdf
    //         if (!empty($student_id)) {
    //             $this->savePDFAssessment($objSpreadsheet, $filepath, $student_id);
    //         }
    //         // xlsx
    //         $objWriter->save($filepath);
    //     }

    //     $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
    //     $objWriter->save($filepath);
    //     $objSpreadsheet->disconnectWorksheets();
    //     unset($objWriter, $objSpreadsheet);
    //     gc_collect_cycles();

    // }/

    public function saveFileAssessment($objSpreadsheet, $filepath, $format, $student_id, $paramVal)
    {
        Log::write('debug', 'ExcelReportBehavior >>> saveFileAssessment format: ' . $format);

        if ($format === 'pdf') {
            $this->savePDFAssessment($objSpreadsheet, $filepath, $student_id, $paramVal);
            return;
        }

        // Save PDF copy if needed
        if (!empty($student_id)) {
            $this->savePDFAssessment($objSpreadsheet, $filepath, $student_id);
        }

        // ✅ FORCE XLSX — DO NOT use libraryTypes mapping
        $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');

        Log::write('debug', 'Excel writer class: ' . get_class($objWriter));

        $objWriter->save($filepath);

        $objSpreadsheet->disconnectWorksheets();
        unset($objWriter, $objSpreadsheet);
        gc_collect_cycles();
    }

    public function saveFile($objSpreadsheet, $filepath, $format, $student_id, $report_card_id)
    {
        ////Log::debug('ExcelReportBehavior >>> saveFile: ' . $format); //[TEMP-LOG]

        // Write XLSX FIRST (before any LibreOffice modifies it)
        $writer = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
        $writer->save($filepath);

        // Now that XLSX is safely saved — create copy for PDF
        $pdfSpreadsheet = IOFactory::load($filepath);

        if ($format === 'pdf' || !empty($student_id)) {
            $this->savePDF($pdfSpreadsheet, $filepath, $student_id, $report_card_id);
        }
        $writer = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
        $writer->save($filepath);
        // Cleanup
        $objSpreadsheet->disconnectWorksheets();
        $pdfSpreadsheet->disconnectWorksheets();
        gc_collect_cycles();
    }

    public function deleteFile($filepath)
    {
        $file = new File($filepath);
        $file->delete();
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

    public function getParams($controller)
    {
        $model = $this->_table;
        $params = $model->getQueryString();
        return $params;
    }

    public function tableData($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra) // POCOR-9336
    {
        $rowValue = $attr['rowValue'];
        $columnIndex = $attr['columnIndex'];
        $source = $attr['source'];
        $displayColumns = $attr['displayColumns'];
        $showHeaders = $attr['showHeaders'];
        $insertRows = $attr['insertRows'];

        if ($showHeaders) {
            foreach ($displayColumns as $key => $column) {
                $header = Inflector::humanize($key);

                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                $cellCoordinate = $columnValue . $rowValue;
                $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $header, $attr, $extra);
                $columnIndex++;
            }

            $rowValue++;
        }

        if (isset($extra['vars'][$source]) && !empty($extra['vars'][$source])) {
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
                    if (isset($column['displayValue'])) {
                        $field = $this->splitDisplayValue($column['displayValue'])[1];
                        $value = $this->getVarSafe($vars, $field);
                    }

                    $attr['type'] = isset($column['type']) ? $column['type'] : null;
                    $attr['format'] = isset($column['format']) ? $column['format'] : null;

                    $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoordinate = $columnValue . $rowValue;
                    $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $value, $attr, $extra);

                    $columnIndex++;
                }

                $rowValue++;
            }
        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }

    }

    private function row($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        $rowValue = $attr['rowValue'];
        $columnIndex = $attr['columnIndex'];
        $columnValue = $attr['columnValue'];
        $nestedRow = isset($attr['children']) ? $attr['children'] : [];

        $mergeColumns = $attr['mergeColumns'];
        $mergeColumnIndex = $columnIndex + ($mergeColumns - 1);

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                // skip first row don't need to auto insert new row
                if ($rowValue != $attr['rowValue']) {
                    $objWorksheet->insertNewRowBefore($rowValue);
                    $this->updatePlaceholderCoordinate(null, $rowValue, $extra);
                }

                $cellCoordinate = $columnValue . $rowValue;
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
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);

            // mergeColumns even if there is no data
            $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);

            // set nestedRow parentKey = -1 to allow mergeColumns to apply even for empty nested cells
            if (!empty($nestedRow)) {
                $this->nestedRow($nestedRow, -1, $rowValue, $columnIndex, $mergeColumns, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra);
            }
        }
    }

    private function updatePlaceholderCoordinate($affectedColumnValue = null, $affectedRowValue = null, $extra)
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
                foreach ($rowsObj as $rowIndex => $obj) {
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

    private function nestedRow($nestedRow, $parentKey, $parentRowValue, $parentColumnIndex, $parentMergeColumns, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        $nestedAttr = $this->extractPlaceholderAttr($nestedRow, $this->advancedTypes['row'], $extra);
        $filter = isset($nestedAttr['filter']) ? $nestedAttr['filter'] : null;
        $secondNestedRow = isset($nestedAttr['children']) ? $nestedAttr['children'] : [];

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

            $placeholderFormat = $this->formatPlaceholder($placeholderPrefix) . $filterStr . ".";
            $placeholder = sprintf($placeholderFormat . $placeholderSuffix, $parentKey);
            $placeholderId = sprintf($placeholderFormat . 'id', $parentKey);
            $nestedData = $this->combineVarSafe($extra['vars'], $placeholderId, $placeholder);
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

                $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
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
            $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
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

    private function mergeRange($fromColumn, $fromRow, $toColumn, $toRow, $objWorksheet, $attr)
    {
        if (($fromColumn != $toColumn) || ($fromRow != $toRow)) {
            $fromColumnValue = Coordinate::stringFromColumnIndex($fromColumn);
            $toColumnValue = Coordinate::stringFromColumnIndex($toColumn);
            $mergeRange = $fromColumnValue . $fromRow . ":" . $toColumnValue . $toRow;

            $objWorksheet->mergeCells($mergeRange);

            // fix border doesn't set after cell is merged
            $cellStyle = $attr['style'];
            $cellStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $objWorksheet->duplicateStyle($cellStyle, $mergeRange);
        }
    }

    private function column($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        $rowValue = $attr['rowValue'];
        $columnIndex = $attr['columnIndex'];
        $columnValue = $attr['columnValue'];
        $mergeColumns = $attr['mergeColumns'];
        $nestedColumn = isset($attr['children']) ? $attr['children'] : [];

        if (!empty($attr['data'])) {
            foreach ($attr['data'] as $key => $value) {
                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);

                // skip first column don't need to auto insert new column
                if ($columnIndex != $attr['columnIndex']) {
                    $objWorksheet->insertNewColumnBefore($columnValue);
                    $this->updatePlaceholderCoordinate($columnValue, null, $extra);
                }

                $cellCoordinate = $columnValue . $rowValue;
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

                            $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
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
                        $nestedCellCoordinate = $nestedColumnValue . $nestedRowValue;
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
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);

            // mergeColumns even if there is no data
            $mergeColumnIndex = $columnIndex + ($mergeColumns - 1);
            $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);
        }
    }

    private function match($objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        list($attr['placeholderPrefix'], $attr['placeholderSuffix']) =
            $this->splitDisplayValue($attr['displayValue']);

        $rowsArray = $attr['rows'] ?? [];
        $columnsArray = $attr['columns'] ?? [];

        // -----------------------------------------------------------------
        // 1) SAFETY CHECK — prevent crashes if root data set is empty
        // -----------------------------------------------------------------
        $rootData = $this->getPlaceholderData($attr['displayValue'], $extra);

        if ($this->isEmptyMatchData($rootData)) {
            // render one blank cell and exit
            $cellCoordinate = $attr['columnValue'] . $attr['rowValue'];
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
            return;
        }

        // -----------------------------------------------------------------
        // 2) Normal behaviour — matchRows or matchColumns
        // -----------------------------------------------------------------
        if (!empty($rowsArray)) {
            $this->matchRows($objSpreadsheet, $objWorksheet, $objCell, $attr, $rowsArray, $columnsArray, $extra);
        } else {
            $columnIndex = $attr['columnIndex'];
            $rowValue    = $attr['rowValue'];
            $this->matchColumns(
                $objSpreadsheet,
                $objWorksheet,
                $objCell,
                $attr,
                $columnsArray,
                $columnIndex,
                $rowValue,
                null,
                $extra
            );
        }
    }

    private function isEmptyMatchData($arr)
    {
        if (empty($arr)) {
            return true;
        }
        foreach ($arr as $v) {
            if (!empty($v)) {
                return false;
            }
        }
        return true;
    }
    private function matchRows($objSpreadsheet, $objWorksheet, $objCell, $attr, $rowsArray = [], $columnsArray = [], $extra)
    {
        $matchFrom = isset($rowsArray['matchFrom']) ? $rowsArray['matchFrom'] : [];
        $matchTo = isset($rowsArray['matchTo']) ? $rowsArray['matchTo'] : [];
        $rowData = $this->getPlaceholderData($matchFrom, $extra);
        $nestedRow = isset($rowsArray['children']) ? $rowsArray['children'] : [];

        $filterStr = $this->formatFilter($matchTo);
        $attr['filterStr'] = isset($attr['filterStr']) ? $attr['filterStr'] . $filterStr : $filterStr;

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
                        $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']) . $attr['filterStr'] . "." . $attr['placeholderSuffix'];
                        $placeholder = sprintf($placeholderFormat, $value);

                        $matchData = $this->extractVarSafe($extra['vars'], $placeholder);
                        $matchValue = !empty($matchData) ? current($matchData) : '';

                        $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                        $cellCoordinate = $columnValue . $rowValue;
                        $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
                        $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);

                        $rowValue++;
                    }
                }
            }

        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
            $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);
        }
    }

    private function matchColumns($objSpreadsheet, $objWorksheet, $objCell, $attr, $columnsArray = [], &$columnIndex, &$rowValue, $filterValue = null, $extra)
    {
        $matchFrom = isset($columnsArray['matchFrom']) ? $columnsArray['matchFrom'] : [];
        $matchTo = isset($columnsArray['matchTo']) ? $columnsArray['matchTo'] : [];
        $columnData = $this->getPlaceholderData($matchFrom, $extra);

        $nestedColumnsArray = isset($columnsArray['children']['columns']) ? $columnsArray['children']['columns'] : [];
        $nestedMatchFrom = isset($nestedColumnsArray['matchFrom']) ? $nestedColumnsArray['matchFrom'] : [];
        $nestedMatchTo = isset($nestedColumnsArray['matchTo']) ? $nestedColumnsArray['matchTo'] : [];
        $nestedColumnData = !empty($nestedMatchFrom) ? $this->getPlaceholderData($nestedMatchFrom, $extra) : [];

        $filterStr = $this->formatFilter($matchTo);
        if (!empty($nestedColumnData)) {
            $filterStr .= $this->formatFilter($nestedMatchTo);
        }
        $attr['filterStr'] = isset($attr['filterStr']) ? $attr['filterStr'] . $filterStr : $filterStr;

        if (!empty($columnData)) {
            foreach ($columnData as $key => $value) {
                if (!empty($nestedColumnsArray)) {
                    foreach ($nestedColumnData as $nestedKey => $nestedValue) {
                        $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']) . $attr['filterStr'] . "." . $attr['placeholderSuffix'];
                        if (!is_null($filterValue)) {
                            $placeholder = sprintf($placeholderFormat, $filterValue, $value, $nestedValue);
                        } else {
                            $placeholder = sprintf($placeholderFormat, $value, $nestedValue);
                        }

                        $matchData = $this->extractVarSafe($extra['vars'], $placeholder);
                        $matchValue = !empty($matchData) ? current($matchData) : '';

                        $nestedColumnValue = Coordinate::stringFromColumnIndex($columnIndex);
                        $nestedCellCoordinate = $nestedColumnValue . $rowValue;

                        $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, $matchValue, $attr, $extra);
                        $columnIndex++;
                    }
                } else {
                    $placeholderFormat = $this->formatPlaceholder($attr['placeholderPrefix']) . $attr['filterStr'] . "." . $attr['placeholderSuffix'];
                    if (!is_null($filterValue)) {
                        $placeholder = sprintf($placeholderFormat, $filterValue, $value);
                    } else {
                        $placeholder = sprintf($placeholderFormat, $value);
                    }
                    $matchData = $this->extractVarSafe($extra['vars'], $placeholder);
                    $matchValue = !empty($matchData) ? current($matchData) : '';

                    $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                    $cellCoordinate = $columnValue . $rowValue;

                    $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, $matchValue, $attr, $extra);
                    $columnIndex++;
                }
            }

        } else {
            // replace placeholder as blank if data is empty
            $columnValue = $attr['columnValue'];
            $cellCoordinate = $columnValue . $rowValue;
            $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }
    }

    private function nestedMatchRow($nestedRow, $matchFilter, $parentKey, $rowValue, $columnIndex, $objSpreadsheet, $objWorksheet, $objCell, $attr, $extra)
    {
        if (isset($nestedRow['rows'])) {
            $nestedAttr = $nestedRow['rows'];
            $nestedFilter = isset($nestedAttr['filter']) ? $nestedAttr['filter'] : null; // used to filter nested match row data
            $nestedMatchFrom = isset($nestedAttr['matchFrom']) ? $nestedAttr['matchFrom'] : [];
            $nestedMatchTo = isset($nestedAttr['matchTo']) ? $nestedAttr['matchTo'] : [];
            $nestedMergeBy = isset($nestedAttr['mergeBy']) ? $nestedAttr['mergeBy'] : [];
            $secondNestedRow = isset($nestedAttr['children']) ? $nestedAttr['children'] : [];

            $mergeColumns = $attr['mergeColumns'];
            $mergeColumnIndex = $columnIndex + ($mergeColumns - 1);

            $variableMatchFilter = $matchFilter . $this->formatFilter($nestedMatchTo); // used to filter matching results

            $nestedData = [];
            if (!empty($nestedMatchFrom)) {
                if (!is_null($nestedFilter)) {
                    list($placeholderPrefix, $placeholderSuffix) = $this->splitDisplayValue($nestedMatchFrom);
                    $nestedDataFilter = $this->formatFilter($nestedFilter);
                    $placeholderFormat = $this->formatPlaceholder($placeholderPrefix) . $nestedDataFilter . ".";

                    $placeholder = sprintf($placeholderFormat . $placeholderSuffix, $parentKey);
                    $placeholderId = sprintf($placeholderFormat . 'id', $parentKey);
                    $nestedData = $this->combineVarSafe($extra['vars'], $placeholderId, $placeholder);
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
                        $placeholder = $this->formatPlaceholder($attr['placeholderPrefix']) . $printedMatchFilter . "." . $attr['placeholderSuffix'];

                        $matchData = $this->extractVarSafe($extra['vars'], $placeholder);
                        $matchValue = !empty($matchData) ? current($matchData) : '';

                        $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                        $nestedCellCoordinate = $columnValue . $rowValue;

                        $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, $matchValue, $attr, $extra);

                        $mergeRowValue = ($mergeRowCount > 1) ? $rowValue + ($mergeRowCount - 1) : $rowValue;
                        $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $mergeRowValue, $objWorksheet, $attr);

                        $rowValue = $mergeRowValue;
                        $rowValue++;
                    }
                }
            } else {
                $columnValue = Coordinate::stringFromColumnIndex($columnIndex);
                $nestedCellCoordinate = $columnValue . $rowValue;
                $this->renderCell($objSpreadsheet, $objWorksheet, $objCell, $nestedCellCoordinate, "", $attr, $extra);
                $this->mergeRange($columnIndex, $rowValue, $mergeColumnIndex, $rowValue, $objWorksheet, $attr);
                $rowValue++;
            }
        }
        return $rowValue;
    }

    private function countMergeData($mergeAttr, $parentKey, $mergeCount, $extra)
    {
        $mergeFrom = isset($mergeAttr['mergeFrom']) ? $mergeAttr['mergeFrom'] : [];
        $filter = isset($mergeAttr['filter']) ? $mergeAttr['filter'] : null;
        $nestedMergeBy = isset($mergeAttr['mergeBy']) ? $mergeAttr['mergeBy'] : [];

        $data = [];
        if (!empty($mergeFrom)) {
            if (!is_null($filter)) {
                list($placeholderPrefix, $placeholderSuffix) = $this->splitDisplayValue($mergeFrom);
                $formattedFilter = $this->formatFilter($filter);
                $placeholderFormat = $this->formatPlaceholder($placeholderPrefix) . $formattedFilter . ".";

                $placeholder = sprintf($placeholderFormat . $placeholderSuffix, $parentKey);
                $placeholderId = sprintf($placeholderFormat . 'id', $parentKey);
                $data = $this->combineVarSafe($extra['vars'], $placeholderId, $placeholder);
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
        $matchFrom = isset($attr['rows']) ? $attr['rows'] : [];
        $rowData = $this->getPlaceholderData($matchFrom, $extra);

        if (!empty($rowData)) {
            $columnValue = $attr['columnValue'];
            $rowValue = $attr['rowValue'];
            foreach ($rowData as $key => $value) {
                $cellCoordinate = $columnValue . $rowValue;
                $this->renderDropdown($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
                $rowValue++;
            }
        } else {
            $cellCoordinate = $attr['coordinate'];
            $this->renderDropdown($objSpreadsheet, $objWorksheet, $objCell, $cellCoordinate, "", $attr, $extra);
        }
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

    private function image($objSpreadsheet, $objWorksheet, $objCell, $attr, ArrayObject $extra)
    {
        $columnValue = $attr['columnValue'];
        $rowValue = $attr['rowValue'];
        $cellCoordinate = $columnValue . $rowValue;

        $attr['imageWidth'] = isset($attr['imageWidth']) ? $attr['imageWidth'] : 50;
        $attr['imageMarginLeft'] = isset($attr['imageMarginLeft']) ? $attr['imageMarginLeft'] : 0;
        $attr['imageMarginTop'] = isset($attr['imageMarginTop']) ? $attr['imageMarginTop'] : 0;

        $data = $this->extractVarSafe($extra['vars'], $attr['displayValue']);
        $imageContent = current($data);

        //for institution logo
        if ($attr['displayValue'] == 'Institutions.logo_content') {
            if (is_resource($imageContent)) {
                $institutionId = $this->extractVarSafe($extra['vars'], 'Institutions.id');
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

    public function renderImage(
        Spreadsheet       $objSpreadsheet,
        Worksheet         $objWorksheet,
                          $objCell,
        string            $cellCoordinate,
        ?string           $imagePath,
        array             $attr,
        array|ArrayObject $extra = []
    ): void
    {
        $imageWidth = (int)($attr['imageWidth'] ?? 120);
        $imageMarginLeft = (int)($attr['imageMarginLeft'] ?? 0);
        $imageMarginTop = (int)($attr['imageMarginTop'] ?? 0);

        if (!$imagePath || !is_file($imagePath) || !is_readable($imagePath)) {
            Log::warning('renderImage: missing or unreadable image: ' . (string)$imagePath);
            return;
        }

        $drawing = new Drawing();
        $drawing->setPath($imagePath);                 // auto-detects type (png/jpg/gif)
        $drawing->setCoordinates($cellCoordinate);
        $drawing->setOffsetX($imageMarginLeft);
        $drawing->setOffsetY($imageMarginTop);
        $drawing->setWidth($imageWidth);               // keep aspect by default
        $drawing->setWorksheet($objWorksheet);         // use the sheet we were given
    }

    private function getVarSafe(array $vars, string $path)
    {
        if (Hash::get($vars, $path) !== null) {
            return Hash::get($vars, $path);
        }

        // Log the error for debugging
        $errorMessage = "MISSING PLACEHOLDER $path";
        Log::warning($errorMessage);

        // RETURN A HELPFUL MESSAGE IN THE EXCEL CELL (NOT BLANK)
        return '';
    }

    private function extractVarSafe(array $vars, string $path): array|\ArrayAccess
    {
        $result = Hash::extract($vars, $path);

        if (!empty($result)) {
            return $result;
        }

        // Log for debugging
        $errorMessage = "MISSING DATA for extract $path";
        Log::warning($errorMessage);

        // Return placeholder-style array for consistency with normal extract
        return [  ];
    }

    private function combineVarSafe(array $vars, string $keyPath, string $valuePath): array
    {
        $result = Hash::combine($vars, $keyPath, $valuePath);

        if (!empty($result)) {
            return $result;
        }

        // Log for debugging
        $errorMessage = "MISSING DATA for combine($keyPath, $valuePath)";
        Log::warning($errorMessage);

        // Return a placeholder array so repeatRows() still works

        return [ [ '__error' => ' ' ] ];
    }
}
