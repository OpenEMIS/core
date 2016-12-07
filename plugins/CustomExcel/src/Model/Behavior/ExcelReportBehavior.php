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

    private $vars = [];
    private $suppressAutoInsertNewRow = false;
    private $mapParams = [];

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
        $this->vars = $this->getVars($params, $extra);

        pr($this->vars);
        die;
    }

    public function onRenderExcelTemplate(Event $event, ArrayObject $extra)
    {
        $controller = $event->subject();
        $params = $this->getParams($controller);
        $this->vars = $this->getVars($params, $extra);

        // to-do
        $extra['file'] = $this->config('filename') . '_' . date('Ymd') . 'T' . date('His') . '.xlsx';
        $extra['path'] = WWW_ROOT . $this->config('folder') . DS . $this->config('subfolder') . DS;
        $extra['download'] = true;

        $filepath = $extra['path'] . $extra['file'];
        $extra['file_path'] = $extra['path'] . $extra['file'];

        $objPHPExcel = $this->loadExcelTemplate($extra);
        $this->generateExcel($objPHPExcel, $extra);
        $this->saveExcel($objPHPExcel, $filepath);

        if ($extra['download']) {
            $this->downloadExcel($filepath);
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
        $sheet = $objPHPExcel->getSheet(0);
        $cells = $sheet->getCellCollection();

        // Loop through all cell and replace placeholder with variables value
        foreach ($cells as $cellCoordinate) {
            $objCell = $sheet->getCell($cellCoordinate);
            if (is_object($objCell->getValue())) {
                $cellValue = $objCell->getValue()->getPlainText();
            } else {
                $cellValue = $objCell->getValue();
            }

            $this->replaceCell($sheet, $objCell, $cellValue);
        }
        // End loop through all cell and replace placeholder with variables value
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
    
    private function replaceCell($sheet, $objCell, $search)
    {
        $format = '${%s}';

        if (strlen($search) > 0) {
            $rowArray = explode('${repeat-rows:', $search);
            array_shift($rowArray); // first element will not contain the placeholder
            $columnArray = explode('${repeat-columns:', $search);
            array_shift($columnArray); // first element will not contain the placeholder
            $strArray = explode('${', $search);
            array_shift($strArray); // first element will not contain the placeholder

            if (sizeof($rowArray) > 0) {
                $this->replaceRow($sheet, $objCell, $search, $format, $rowArray);
            } else if (sizeof($columnArray) > 0) {
                $this->replaceColumn($sheet, $objCell, $search, $format, $columnArray);
            } else {
                $this->replaceString($sheet, $objCell, $search, $format, $strArray);
            }
        }
    }

    private function replaceRow($sheet, $objCell, $search, $format, $rowArray)
    {
        $str = current($rowArray);
        list($placeholderStr, $filterStr) = $this->splitPlaceholder($str);

        $placeholder = $this->formatPlaceholder($placeholderStr);
        $data = Hash::extract($this->vars, $placeholder);

        $columnValue = $objCell->getColumn();
        $rowValue = $objCell->getRow();
        $cellCoordinate = $objCell->getCoordinate();
        $cellStyle = $objCell->getStyle($cellCoordinate);

        foreach ($data as $key => $value) {
            if (!empty($filterStr)) {
                list($dataType, $attr) = explode(':', $filterStr , 2);
                switch ($dataType) {
                    case 'date':
                        $dataFormat = $attr;
                        $value = $value->format($dataFormat);
                        break;
                }
            }

            $newCellCoordinate = $columnValue.$rowValue;
            $sheet->setCellValue($newCellCoordinate, $value);
            $sheet->duplicateStyle($cellStyle, $newCellCoordinate);
            $rowValue++;
            
            if (!$this->suppressAutoInsertNewRow) {
                $sheet->insertNewRowBefore($rowValue);
            }
        }

        // only insert new row for the first column which have repeat-rows
        if ($this->suppressAutoInsertNewRow == false) {
            $this->suppressAutoInsertNewRow = true;
        }
    }

    private function replaceColumn($sheet, $objCell, $search, $format, $columnArray)
    {
        $str = current($columnArray);
        list($placeholderStr, $filterStr) = $this->splitPlaceholder($str);

        $placeholder = $this->formatPlaceholder($placeholderStr);
        $data = Hash::extract($this->vars, $placeholder);

        // columnIndexFromString(): Column index start from 1
        $columnIndex = $objCell->columnIndexFromString($objCell->getColumn());
        $rowValue = $objCell->getRow();
        $placeholderCellCoordinate = $objCell->getCoordinate();
        $cellStyle = $objCell->getStyle($placeholderCellCoordinate);

        foreach ($data as $key => $value) {
            // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
            $columnValue = $objCell->stringFromColumnIndex($columnIndex-1);

            $cellCoordinate = $columnValue.$rowValue;
            $sheet->setCellValue($cellCoordinate, $value);
            $sheet->duplicateStyle($cellStyle, $cellCoordinate);

            if (!empty($filterStr)) {
                list($dataType, $attr) = explode(':', $filterStr , 2);
                switch ($dataType) {
                    case 'children':
                        $nestedPlaceholderStr = $attr;
                        $nestedPlaceholder = $this->formatPlaceholder($nestedPlaceholderStr);
                        $nestedData = Hash::extract($this->vars, $nestedPlaceholder);

                        $nestedColumnIndex = $columnIndex;
                        $nestedRowValue = $rowValue + 1;
                        foreach ($nestedData as $nestedKey => $nestedValue) {
                            // stringFromColumnIndex(): Column index start from 0, therefore need to minus 1
                            $nestedColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-1);

                            $nestedCellCoordinate = $nestedColumnValue.$nestedRowValue;
                            $sheet->setCellValue($nestedCellCoordinate, $nestedValue);
                            $sheet->duplicateStyle($cellStyle, $nestedCellCoordinate);

                            $nestedColumnIndex++;
                        }

                        if ($nestedColumnIndex > $columnIndex) {
                            $rangeColumnValue = $objCell->stringFromColumnIndex($nestedColumnIndex-2);

                            $mergeRange = $cellCoordinate.":".$rangeColumnValue.$rowValue;
                            $sheet->mergeCells($mergeRange);

                            $columnIndex = $nestedColumnIndex-1;
                        }
                        break;
                }
            }

            $columnIndex++;
        }
    }

    private function replaceString($sheet, $objCell, $search, $format, $strArray)
    {
        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos === false) {
                // closing of placeholder not found
            } else {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);
                $value = Hash::get($this->vars, $placeholder);

                if (!is_null($value)) {
                    $search = str_replace($replace, $value, $search);
                }
            }
        }

        $cellCoordinate = $objCell->getCoordinate();
        $sheet->setCellValue($cellCoordinate, $search);
    }

    private function splitPlaceholder($str)
    {
        $pos = strpos($str, '}');

        $placeholderStr = '';
        $filterStr = '';

        if ($pos === false) {
            // closing of placeholder not found
        } else {
            $placeholderStr = substr($str, 0, $pos);

            $placeholderArray = explode('|', $placeholderStr);
            if (sizeof($placeholderArray) == 1) {
                $placeholderStr = $placeholderArray[0];
            } else if (sizeof($placeholderArray) == 2) {
                $placeholderStr = $placeholderArray[0];
                $filterStr = $placeholderArray[1];
            }
        }

        return [$placeholderStr, $filterStr];
    }

    private function formatPlaceholder($str)
    {
        $placeholderArray = explode('.', $str);
        array_splice($placeholderArray, 1, 0, array('{n}'));
        $placeholder = implode(".", $placeholderArray);

        return $placeholder;
    }
}
