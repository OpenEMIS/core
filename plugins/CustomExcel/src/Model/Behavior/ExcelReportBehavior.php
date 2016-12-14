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

    private function jsonToArray($str)
    {
        $pos = strpos($str, '$');
        $json = substr($str, $pos+1, strlen($str));
        $jsonArray = json_decode($json, true);

        return $jsonArray;
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
                    $this->$function($objWorksheet, $objCell, $cellValue, $extra);
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

    private function row($objWorksheet, $objCell, $cellValue, $extra)
    {
        $jsonArray = $this->jsonToArray($cellValue);
        $rowArray = array_key_exists('repeatRows', $jsonArray) ? $jsonArray['repeatRows'] : [];
        $placeholderStr = isset($rowArray['displayValue']) ? $rowArray['displayValue'] : '';
        $displayType = isset($rowArray['type']) ? $rowArray['type'] : null;
        $displayFormat = isset($rowArray['format']) ? $rowArray['format'] : null;

        $placeholder = $this->formatPlaceholder($placeholderStr);
        $data = !empty($placeholder) ? Hash::extract($extra['vars'], $placeholder) : [];

        $cellColumnValue = $objCell->getColumn();
        $cellRowValue = $objCell->getRow();
        $cellCoordinate = $objCell->getCoordinate();
        $cellStyle = $objCell->getStyle($cellCoordinate);

        $rowValue = $cellRowValue;
        foreach ($data as $key => $value) {
            if (!$this->suppressAutoInsertNewRow && $rowValue != $cellRowValue) {
                $objWorksheet->insertNewRowBefore($rowValue);
            }

            $displayValue = $value;
            switch ($displayType) {
                case 'date':
                    $displayValue = !is_null($displayFormat) ? $displayValue->format($displayFormat) : $displayValue;
                    break;
            }

            $newCellCoordinate = $cellColumnValue.$rowValue;
            $objWorksheet->setCellValue($newCellCoordinate, $displayValue);
            $objWorksheet->duplicateStyle($cellStyle, $newCellCoordinate);
            $rowValue++;
        }

        // only insert new row for the first column which have repeat-rows
        if ($this->suppressAutoInsertNewRow == false) {
            $this->suppressAutoInsertNewRow = true;
        }
    }

    private function column($objWorksheet, $objCell, $cellValue, $extra)
    {
        $jsonArray = $this->jsonToArray($cellValue);
        $columnArray = array_key_exists('repeatColumns', $jsonArray) ? $jsonArray['repeatColumns'] : [];
        $placeholderStr = isset($columnArray['displayValue']) ? $columnArray['displayValue'] : '';
        $nestedColumn = isset($columnArray['children']) ? $columnArray['children'] : [];

        $placeholder = $this->formatPlaceholder($placeholderStr);
        $data = !empty($placeholder) ? Hash::extract($extra['vars'], $placeholder) : [];

        // columnIndexFromString(): Column index start from 1
        $cellColumnValue = $objCell->getColumn();
        $cellColumnIndex = $objCell->columnIndexFromString($cellColumnValue);
        $cellColumnWidth = $objWorksheet->getColumnDimension($cellColumnValue)->getWidth();
        $cellRowValue = $objCell->getRow();
        $cellCoordinate = $objCell->getCoordinate();
        $cellStyle = $objCell->getStyle($cellCoordinate);

        $columnIndex = $cellColumnIndex;
        foreach ($data as $key => $value) {
            // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
            $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);
            $newCellCoordinate = $columnValue.$cellRowValue;

            $objWorksheet->setCellValue($newCellCoordinate, $value);
            $objWorksheet->duplicateStyle($cellStyle, $newCellCoordinate);
            // set column width to follow placeholder
            $objWorksheet->getColumnDimension($columnValue)->setAutoSize(false);
            $objWorksheet->getColumnDimension($columnValue)->setWidth($cellColumnWidth);

            if (!empty($nestedColumn)) {
                $nestedColumnArray = array_key_exists('repeatColumns', $nestedColumn) ? $nestedColumn['repeatColumns'] : [];
                $nestedPlaceholderStr = isset($nestedColumnArray['displayValue']) ? $nestedColumnArray['displayValue'] : '';

                $nestedPlaceholder = $this->formatPlaceholder($nestedPlaceholderStr);
                $nestedData = !empty($nestedPlaceholder) ? Hash::extract($extra['vars'], $nestedPlaceholder) : [];

                $nestedColumnIndex = $columnIndex;
                // always output children to the immediate next row
                $nestedRowValue = $cellRowValue + 1;
                foreach ($nestedData as $nestedKey => $nestedValue) {
                    // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                    $nestedColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-1);

                    $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
                    $objWorksheet->setCellValue($nestedCellCoordinate, $nestedValue);
                    $objWorksheet->duplicateStyle($cellStyle, $nestedCellCoordinate);
                    // set nested column width to follow placeholder
                    $objWorksheet->getColumnDimension($nestedColumnValue)->setAutoSize(false);
                    $objWorksheet->getColumnDimension($nestedColumnValue)->setWidth($cellColumnWidth);

                    $nestedColumnIndex++;
                }

                // merge parent cell following number of columns occupied by children
                if ($nestedColumnIndex > $columnIndex) {
                    $rangeColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-2);

                    $mergeRange = $newCellCoordinate.":".$rangeColumnValue.$cellRowValue;
                    $objWorksheet->mergeCells($mergeRange);

                    $columnIndex = $nestedColumnIndex-1;
                }
            }
            $columnIndex++;
        }
    }

    private function match($objWorksheet, $objCell, $cellValue, $extra)
    {
        $jsonArray = $this->jsonToArray($cellValue);
        $matchArray = array_key_exists('match', $jsonArray) ? $jsonArray['match'] : [];
        $rowsArray = array_key_exists('rows', $matchArray) ? $matchArray['rows'] : [];

        $rowPlaceholderStr = array_key_exists('matchFrom', $rowsArray) ? $rowsArray['matchFrom'] : '';
        $rowFilterStr = array_key_exists('matchTo', $rowsArray) ? $rowsArray['matchTo'] : '';
        $rowPlaceholder = $this->formatPlaceholder($rowPlaceholderStr);
        $rowData = Hash::extract($extra['vars'], $rowPlaceholder);

        // columnIndexFromString(): Column index start from 1
        $cellColumnValue = $objCell->getColumn();
        $cellColumnIndex = $objCell->columnIndexFromString($cellColumnValue);
        $cellRowValue = $objCell->getRow();
        $cellCellCoordinate = $objCell->getCoordinate();
        $cellStyle = $objCell->getStyle($cellCellCoordinate);

        if (!empty($rowsArray)) {
            foreach ($rowData as $rowKey => $rowValue) {
                $columnIndex = $cellColumnIndex;
                $filterStr = $this->formatFilter($rowFilterStr, $rowValue);
                $this->matchColumns($objWorksheet, $objCell, $matchArray, $columnIndex, $cellRowValue, $cellStyle, $filterStr, $extra);

                $cellRowValue++;
            }
        } else {
            $columnIndex = $cellColumnIndex;
            $this->matchColumns($objWorksheet, $objCell, $matchArray, $columnIndex, $cellRowValue, $cellStyle, null, $extra);
        }
    }

    private function matchColumns($objWorksheet, $objCell, $matchArray, &$columnIndex, &$cellRowValue, $cellStyle, $filter=null, ArrayObject $extra)
    {
        $displayType = isset($matchArray['type']) ? $matchArray['type'] : null;
        $displayFormat = isset($matchArray['format']) ? $matchArray['format'] : null;

        $columnsArray = array_key_exists('columns', $matchArray) ? $matchArray['columns'] : [];
        $nestedColumnsArray = isset($columnsArray['children']['columns']) ? $columnsArray['children']['columns'] : [];

        $columnPlaceholderStr = array_key_exists('matchFrom', $columnsArray) ? $columnsArray['matchFrom'] : '';
        $columnFilterStr = array_key_exists('matchTo', $columnsArray) ? $columnsArray['matchTo'] : '';
        $columnPlaceholder = $this->formatPlaceholder($columnPlaceholderStr);
        $columnData = Hash::extract($extra['vars'], $columnPlaceholder);

        $nestedPlaceholderStr = array_key_exists('matchFrom', $nestedColumnsArray) ? $nestedColumnsArray['matchFrom'] : '';
        $nestedFilterStr = array_key_exists('matchTo', $nestedColumnsArray) ? $nestedColumnsArray['matchTo'] : '';
        $nestedPlaceholder = $this->formatPlaceholder($nestedPlaceholderStr);
        $nestedData = Hash::extract($extra['vars'], $nestedPlaceholder);

        foreach ($columnData as $columnKey => $columnValue) {
            $columnFilter = $this->formatFilter($columnFilterStr, $columnValue);

            foreach ($nestedData as $nestedKey => $nestedValue) {
                $nestedFilter = $this->formatFilter($nestedFilterStr, $nestedValue);

                // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                $nestedColumnValue = $objCell->stringFromColumnIndex($columnIndex-1);
                $nestedCellCoordinate = $nestedColumnValue.$cellRowValue;

                $displayValue = isset($matchArray['displayValue']) ? $matchArray['displayValue'] : '';
                list($placeholderPrefix, $placeholderSuffix) = $this->splitDisplayValue($displayValue);

                $matchPlaceholder = $this->formatPlaceholder($placeholderPrefix);
                if (!is_null($filter)) {
                    $matchPlaceholder .= $filter;
                }
                $matchPlaceholder .= $columnFilter;
                $matchPlaceholder .= $nestedFilter;
                $matchPlaceholder .= ".".$placeholderSuffix;

                $matchData = Hash::extract($extra['vars'], $matchPlaceholder);
                $matchValue = !empty($matchData) ? current($matchData) : '';

                switch ($displayType) {
                    case 'number':
                        // set to two decimal places
                        if (!is_null($displayFormat)) {
                            $formatting = number_format(0, $displayFormat);
                            $objWorksheet->getStyle($nestedCellCoordinate)->getNumberFormat()->setFormatCode($formatting);
                        }
                        break;
                }

                $objWorksheet->setCellValue($nestedCellCoordinate, $matchValue);
                $objWorksheet->duplicateStyle($cellStyle, $nestedCellCoordinate);

                $columnIndex++;
            }
        }
    }
}
