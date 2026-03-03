<?php

namespace Import\Model\Behavior;

use ArrayObject;
use Cake\Routing\Router;
use Cake\Http\Session;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

use Import\Model\Behavior\ImportBehavior;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportResultBehavior extends ImportBehavior
{
    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        switch ($action) {
            case 'add':
                $downloadUrl = $toolbarButtons['back']['url'];
                $downloadUrl[0] = 'template';
                if ($buttons['add']['url']['action'] == 'ImportInstitutionSurveys') {
                    $downloadUrl[1] = $buttons['add']['url'][1];
                } else if ($buttons['add']['url']['action'] == 'ImportOutcomeResults') {
                    $data = $this->_table->request->getData('ImportOutcomeResults');
                    unset($data['id']);
                    unset($data['select_file']);
                    $data['institution_id'] = $this->institutionId;
                    $downloadUrl[1] = $this->_table->paramsEncode($data);
                } else {
                    $downloadUrl[1] = $buttons['add']['url'][1];
                    //POCOR-9584: start - carry query params set by addAfterAction (class, competency_item, academic_period, etc.)
                    //   to the template download URL so getStudentArray() and getCompetencyCriteriasArray() can read them
                    $queryParams = array_filter(
                        $this->_table->request->getQueryParams(),
                        fn($v) => $v !== null && $v !== '' && $v !== '0'
                    );
                    if (!empty($queryParams)) {
                        $downloadUrl['?'] = $queryParams;
                    }
                    //POCOR-9584: end
                }
                $this->_table->controller->set('downloadOnClick', "javascript:window.location.href='" . Router::url($downloadUrl) . "'");
                break;
        }

        //back button
        if (!empty($this->getConfig('backUrl'))) {
            //POCOR-9158 start
            if($buttons['add']['url']['action'] == 'ImportOutcomeResults'){
                $pass = $this->_table->request->getParam('pass');
                $encodedParams = $pass[1] ?? null;
                $toolbarButtons['back']['url'] = array_merge(
                    $toolbarButtons['back']['url'],
                    $this->getConfig('backUrl'),
                    [
                        '0' => 'index',
                        $encodedParams
                    ]
                );
            } //POCOR-9158 end
            $toolbarButtons['back']['url'] = array_merge($toolbarButtons['back']['url'], $this->getConfig('backUrl'));
        } elseif ($this->institutionId && $toolbarButtons['back']['url']['plugin'] == 'Institution') {
            $back = [];

            if ($this->_table->request->getAttribute('params')['pass'][0] == 'add') {
                $back['action'] = str_replace('Import', '', $this->_table->getAlias());
            } elseif ($this->_table->request->getAttribute('params')['pass'][0] == 'results') {
                $back['action'] = $this->_table->getAlias();
                $back[0] = 'add';
            };

            $models = $this->_table->ControllerAction->models;
            if (!isset($models[$back['action']])) {
                $back['action'] = str_replace('Institution', '', $back['action']);
            }
            $toolbarButtons['back']['url'] = array_merge($toolbarButtons['back']['url'], $back);
        } else {
            $toolbarButtons['back']['url']['action'] = 'index';
        }
        unset($toolbarButtons['back']['url'][0]);
    }

    /******************************************************************************************************************
     **
     ** Actions
     **
     ******************************************************************************************************************/

    public function results()
    {
        $session = $this->_table->request->getSession();
        if ($session->check($this->sessionKey)) {
            $completedData = $session->read($this->sessionKey);
            $this->_table->ControllerAction->field('select_file', ['visible' => false]);
            $this->_table->ControllerAction->field('results', [
                'type' => 'element',
                'override' => true,
                'visible' => true,
                'element' => 'Import./criterias_results',
                'rowClass' => 'row-reset',
                'results' => $completedData
            ]);
            if (!empty($completedData['failedExcelFile'])) {
                if (!empty($completedData['passedExcelFile'])) {
                    $message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'partial_failed');
                } else {
                    $message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'failed');
                }
                $this->_table->Alert->error($message, ['type' => 'string', 'reset' => true]);
            } else {
                $message = '<i class="fa fa-check-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'success');
                $this->_table->Alert->ok($message, ['type' => 'string', 'reset' => true]);
            }
            // define data as empty entity so that the view file will not throw an undefined notice
            $this->_table->controller->set('data', $this->_table->newEntity([]));
            $this->_table->ControllerAction->renderView('/ControllerAction/view');
            return ;
        } else {
            return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
        }
        return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
    }

    /******************************************************************************************************************
     **
     ** Import Functions
     **
     *****************************************************************************************************************
     * @throws Exception
     */
    public function beginExcelHeaderStyling($objPHPExcel, $dataSheetName, $defaultTitle = ''): void
    {
        //set the image
        $activeSheet = $objPHPExcel->getActiveSheet();
        $activeSheet->setTitle($dataSheetName);
        if (function_exists('imagecreatefromjpeg')) {
            //POCOR-7477-HINDOL - in case that imagecreatefromjpeg is not available
            $gdImage = imagecreatefromjpeg(ROOT . DS . 'plugins' . DS . 'Import' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.jpg');
            $objDrawing = new MemoryDrawing();
            $objDrawing->setName('OpenEMIS Logo');
            $objDrawing->setDescription('OpenEMIS Logo');
            $objDrawing->setImageResource($gdImage);
            $objDrawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
            $objDrawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
            $objDrawing->setHeight(100);
            $objDrawing->setCoordinates('A1');
            $objDrawing->setWorksheet($activeSheet);
        }
        $activeSheet->getRowDimension(1)->setRowHeight(75);
        $activeSheet->getRowDimension(2)->setRowHeight(25);
    }

    public function beginExcelTopTittle($objPHPExcel, $title = '')
    {
        $activeSheet = $objPHPExcel->getActiveSheet();
        $activeSheet->setCellValue("C1", $title);
    }

    public function endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, $lastRowToAlign = 2, $applyFillFontSetting = [], $applyCellBorder = [])
    {
        if (empty($applyFillFontSetting)) {
            $applyFillFontSetting = ['s' => 2, 'e' => 2];
        }

        if (empty($applyCellBorder)) {
            $applyCellBorder = ['s' => 2, 'e' => 2];
        }

        $activeSheet = $objPHPExcel->getActiveSheet();

        // merging should start from cell C1 instead of A1 since the title is already set in cell C1 in beginExcelHeaderStyling()
        if (!in_array($headerLastAlpha, ['A', 'B', 'C'])) {
            $activeSheet->mergeCells('C1:' . $headerLastAlpha . '1');
        }

        $activeSheet->getStyle("A1:" . $headerLastAlpha . "1")->getFont()->setBold(true)->setSize(16);
        $style = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        $activeSheet->getStyle("A1:" . $headerLastAlpha . $lastRowToAlign)->applyFromArray($style)->getFont()->setBold(true);
        $activeSheet->getStyle("A" . $applyFillFontSetting['s'] . ":" . $headerLastAlpha . $applyFillFontSetting['e'])->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $activeSheet->getStyle("A" . $applyFillFontSetting['s'] . ":" . $headerLastAlpha . $applyFillFontSetting['e'])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('6699CC'); // OpenEMIS Core product color
        $activeSheet->getStyle("A" . $applyCellBorder['s'] . ":" . $headerLastAlpha . $applyCellBorder['e'])->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    public function setResultDataTemplate($objPHPExcel, $dataSheetName, $header, $type)
    {
        $objPHPExcel->setActiveSheetIndex(0);

        $activeSheet = $objPHPExcel->getActiveSheet();

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName,  __(Inflector::humanize(Inflector::tableize($this->_table->getAlias()))) . ' ' . $dataSheetName);
        $this->beginExcelTopTittle($objPHPExcel, __(Inflector::humanize(Inflector::tableize($this->_table->getAlias()))) . ' ' . $dataSheetName);
        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 2, $value);
        }

        $headerLastAlpha = $this->getExcelColumnAlpha(count($header) - 1);
        $lastRowToAlign = 2;
        $this->endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, $lastRowToAlign);
    }

    protected function _generateDownloadableFile($data, $type, $header, $systemDateFormat)
    {
        if (!empty($data)) {
            $downloadFolder = $this->prepareDownload();
            // Do not lcalize file name as certain non-latin characters might cause issue
            $excelFile = sprintf('OpenEMIS_Core_Import_%s_%s_%s.xlsx', $this->getConfig('model'), ucwords($type), time());
            $excelPath = $downloadFolder . DS . $excelFile;

            $newHeader = $header;
            if ($type == 'failed') {
                $newHeader[] = $this->getExcelLabel('general', 'errors');
            }
            $dataSheetName = $this->getExcelLabel('general', 'data');

            $objPHPExcel = new Spreadsheet();

            ($this->isCustomText()) ? $rowData = 4 : $rowData = 3;

            $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $newHeader, $type);
            $activeSheet = $objPHPExcel->getActiveSheet();
            foreach ($data as $index => $record) {
                if ($type == 'failed') {
                    $values = array_values($record['data']->getArrayCopy());
                    $values[] = $record['errorForExcel'];
                } else {
                    $values = $record['data'];
                }
                $activeSheet->getRowDimension(($index + $rowData))->setRowHeight(15);
                foreach ($values as $key => $value) {
                    $alpha = $this->getExcelColumnAlpha($key + 1);
                    $activeSheet->setCellValue($alpha . ($index + $rowData), $value);
                    $activeSheet->getColumnDimension($alpha)->setAutoSize(true);

                    if ($key == (count($values)) && $type == 'failed') {
                        $suggestedRowHeight = $this->suggestRowHeight(strlen($value), 15);
                        $activeSheet->getRowDimension(($index + $rowData))->setRowHeight($suggestedRowHeight);
                        $activeSheet->getStyle($alpha . ($index + $rowData))->getAlignment()->setWrapText(true);
                    }
                }
            }

            if ($type == 'failed') {
                $this->setCodesDataTemplate($objPHPExcel);
            }

            $objPHPExcel->setActiveSheetIndex(0);
            $objWriter = new Xlsx($objPHPExcel); // Changed class name //POCOR-8082
            $objWriter->save($excelPath);

            $downloadUrl = $this->_table->ControllerAction->url('download' . ucwords($type));
            $downloadUrl[] = $excelFile;
            $excelFile = $downloadUrl;
        } else {
            $excelFile = null;
        }

        return $excelFile;
    }

    public function checkCorrectTemplate($col, $header, $sheet, $totalColumns, $row)
    {
        $cellsValue = [];
        for ($col; $col <= $totalColumns; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $cellsValue[] = $cell->getValue();
        }

        return $header == $cellsValue;
    }
}
