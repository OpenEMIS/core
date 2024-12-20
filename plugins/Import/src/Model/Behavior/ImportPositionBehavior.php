<?php

namespace Import\Model\Behavior;

use ArrayObject;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Network\Session;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use ControllerAction\Model\Traits\EventTrait;
use Cake\Log\Log;
// POCOR-7799 start and lot of changes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Laminas\Diactoros\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDate;
// POCOR-7799 end

/**
 * ImportBehavior is to be used with import_mapping table.
 *
 * Depends on ControllerActionComponent.
 * Uses EventTrait.
 * Functions that require ControllerActionComponent events, CakePHP events,
 * and are controller actions functions, resides here.
 * Contains logics to import records through excel sheet.
 * This behavior could not be attached to a table file that loads ExportBehavior as well. Currently, there is a conflict
 * since both ImportBehavior and ExcelBehavior uses EventTrait.
 *
 * Usage:
 * - create a table file in a plugin and define its table as `import_mapping`.
 * - in the table file initialize function, add this behavior using one of the following ways
 *
 * #1
 * `
 * $this->addBehavior('Import.Import');
 * `
 * - ImportBehavior will define the caller's plugin using `$this->_table->registryAlias()`
 * and extract the first word
 * - Caller's model will be defined by pluralizing the plugin name
 *
 * #2
 * `
 * $this->addBehavior('Import.Import', ['plugin'=>'Staff', 'model'=>'Staff']);
 * `
 * - ImportBehavior will acknowledge the plugin name and model name as defined above
 *
 *
 * Default Configuration:
 * - Maximum size of uploaded is set to 512KB as PhpExcel class will not be able to handle files which are too large due to
 * php.ini setting on memory_limit. the size of 512KB will eventually becomes close to tripled when the file was
 * passed to PhpExcel to read it.
 */
class ImportPositionBehavior extends Behavior
{
    use EventTrait;

    const FIELD_OPTION = 1;
    const DIRECT_TABLE = 2;
    const NON_TABLE_LIST = 3;
    const CUSTOM = 4;

    const FIRST_RECORD = 3;

    protected $labels = [];
    protected $directTables = [];

    protected $_defaultConfig = [
        'plugin' => '',
        'model' => '',
        'max_rows' => 2000,
        'max_size' => 524288,
        'backUrl' => [],
        'custom_text' => ''
    ];

    protected $rootFolder = 'import';
    protected $_fileTypesMap = [
        'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
        'zip' => ['application/zip']
    ];

    private $institutionId = false;
    private $recordHeader = '';
    private $customText = '';

    public function initialize(array $config): void
    {
        $fileTypes = $this->getConfig('fileTypes');
        $allowableFileTypes = [];

        if ($fileTypes) {
            foreach ($fileTypes as $value) {
                if (array_key_exists($value, $this->_fileTypesMap)) {
                    $allowableFileTypes[] = $value;
                }
            }
        } else {
            $allowableFileTypes = array_keys($this->_fileTypesMap);
        }

        $this->setConfig('allowable_file_types', $allowableFileTypes);

        $plugin = $this->getConfig('plugin');
        if (empty($plugin)) {
            $exploded = explode('.', $this->_table->getRegistryAlias());
            if (count($exploded) == 2) {
                $this->setConfig('plugin', $exploded[0]);
            }
        }

        $plugin = $this->getConfig('plugin');
        $model = $this->getConfig('model');
        if (empty($model)) {
            $this->setConfig('model', Inflector::pluralize($plugin));
        }

        $this->AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
    }

    private function isCustomText()
    {
        $this->customText = $this->getConfig('custom_text');
        return !empty($this->customText) && strlen($this->customText) > 0;
    }

    public function implementedEvents(): array
    {
        return array_merge(parent::implementedEvents(), [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'ControllerAction.Model.onGetFormButtons' => 'onGetFormButtons',
            'ControllerAction.Model.beforeAction' => 'beforeAction',
            'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
            'ControllerAction.Model.add.beforeSave' => 'addBeforeSave',
        ]);
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $queryString = $this->_table->request->getParam('pass');
        $encodedQueryString = $queryString[1];

        switch ($action) {
            case 'add':
                $backUrl = $toolbarButtons['back']['url'];
                $downloadUrl = $backUrl;
                $downloadUrl[0] = 'template';
                $downloadUrl[2] = $encodedQueryString;
                $this->_table->controller->set('downloadOnClick', "javascript:window.location.href='" . Router::url($downloadUrl) . "'");
                $backUrl['action'] = 'Positions';
                $backUrl['0'] = 'index';
                $backUrl['1'] = $encodedQueryString;
                $toolbarButtons['back']['url'] = $backUrl;
                return;
                break;
            case 'results':
                $backUrl = $toolbarButtons['back']['url'];
                $backUrl['action'] = 'Positions';
                $backUrl['0'] = 'index';
                $backUrl['1'] = $encodedQueryString;
                $toolbarButtons['back']['url'] = $backUrl;
                return;
                break;
        }
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $buttons[0]['name'] = '<i class="fa kd-import"></i> ' . __('Import');
    }

    public function beforeAction($event)
    {
        $session = $this->_table->Session;
        $queryString = $this->_table->getQueryString();
        $this->institutionId = $this->_table->paramsEncode($queryString);

        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }

        $this->sessionKey = $this->getConfig('plugin') . '.' . $this->getConfig('model') . '.Import.data';

        $this->_table->ControllerAction->field('plugin', ['visible' => false]);
        $this->_table->ControllerAction->field('model', ['visible' => false]);
        $this->_table->ControllerAction->field('column_name', ['visible' => false]);
        $this->_table->ControllerAction->field('description', ['visible' => false]);
        $this->_table->ControllerAction->field('lookup_plugin', ['visible' => false]);
        $this->_table->ControllerAction->field('lookup_model', ['visible' => false]);
        $this->_table->ControllerAction->field('lookup_column', ['visible' => false]);
        $this->_table->ControllerAction->field('foreign_key', ['visible' => false]);
        $this->_table->ControllerAction->field('is_optional', ['visible' => false]);

        $comment = '* ' . sprintf(__('Format Supported: %s'), implode(', ', $this->getConfig('allowable_file_types')));
        $comment .= '<br/>';
        $comment .= '* ' . sprintf(__('File size should not be larger than %s.'), $this->bytesToReadableFormat($this->getConfig('max_size')));
        $comment .= '<br/>';
        $comment .= '* ' . sprintf(__('Recommended Maximum Records: %s'), $this->getConfig('max_rows'));

        $this->_table->ControllerAction->field('select_file', [
            'type' => 'binary',
            'visible' => true,
            'attr' => [
                'label' => __('Select File To Import')
            ],
            'null' => false,
            'comment' => $comment,
            'startWithOneLeftButton' => 'download'
        ]);
    }

    public function validationImportFile(Validator $validator): Validator
    {
        $validator = $this->_table->validationDefault($validator);
        $supportedFormats = array_values(Hash::flatten($this->_fileTypesMap));
        $maxSize = min($this->getConfig('max_size'), $this->file_upload_max_size());

        return $validator
            ->add('select_file', 'ruleUploadFileError', [
                'rule' => 'uploadError',
                'last' => true,
                'message' => $this->_table->getMessage('Import.upload_error')
            ])
            ->add('select_file', 'ruleInvalidFileType', [
                'rule' => ['mimeType', $supportedFormats],
                'message' => $this->_table->getMessage('Import.not_supported_format'),
                'last' => true
            ])
            ->add('select_file', 'ruleInvalidFileSize', [
                'rule' => ['fileSize', '<=', $maxSize],
                'message' => $this->_table->getMessage('Import.over_max')
            ]);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $options['validate'] = 'importFile';
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        ini_set('max_execution_time', 3600);

        return function ($model, $entity) {

            $errors = $entity->getErrors();

            if (!empty($errors)) {
                $fileError = Hash::get($entity->getInvalid(), 'select_file.error');

                if (!empty($fileError)) {
                    $errorMessage = $model->getMessage("fileUpload.$fileError");
                    if ($errorMessage != '[Message Not Found]') {
                        $entity->setError('select_file', $errorMessage, true);
                    }
                }

                return false;
            }

            $systemDateFormat = self::getDynamicTableInstance('Configuration.ConfigItems')->value('date_format');
            $mapping = $this->getMapping();
            $header = $this->getHeader($mapping);
            $columns = $this->getColumns($mapping);
            $totalColumns = count($columns);
            $lookup = $this->getCodesByMapping($mapping);

            /** @var UploadedFile $fileObj */
            $fileObj = $entity->select_file;
            $uploadedName = $fileObj->getClientFilename();
            $uploadedStream = $fileObj->getStream()->getMetadata('uri');

            $spreadsheet = IOFactory::load($uploadedStream);
            $sheet = $spreadsheet->getActiveSheet();

            $totalImported = 0;
            $totalUpdated = 0;
            $importedUniqueCodes = new ArrayObject();
            $dataFailed = [];
            $dataPassed = [];
            $extra = new ArrayObject(['lookup' => [], 'entityValidate' => true]);

            $activeModel = self::getDynamicTableInstance($this->getConfig('plugin') . '.' . $this->getConfig('model'));
            $activeModel->addBehavior('DefaultValidation');

            $maxRows = $this->getConfig('max_rows') + 2;
            $highestRow = $sheet->getHighestRow();

            if ($highestRow > $maxRows) {
                $entity->setError('select_file',
                    [$this->getExcelLabel('Import', 'over_max_rows', __FUNCTION__)], true);
                return false;
            }

            $this->recordHeader = $this->isCustomText() ? 3 : 2;

            if ($highestRow == $this->recordHeader) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'no_answers', __FUNCTION__)], true);
                return false;
            }

            $startCheck = $this->isCustomText() ? 3 : 2;
            $jk = 0;

            for ($row = $startCheck; $row <= $highestRow; ++$row) {
                if ($row == $this->recordHeader) {
                    if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
                        $entity->setError('select_file', [$this->getExcelLabel('Import', 'wrong_template', __FUNCTION__)], true);
                        return false;
                    }
                    continue;
                }

                if ($row == $highestRow && !$this->checkRowCells($sheet, $totalColumns, $row)) {
                    break;
                }

                $tempRow = new ArrayObject();
                $rowInvalidCodeCols = new ArrayObject();
                $params = [$sheet, $row, $columns, $tempRow, $importedUniqueCodes, $rowInvalidCodeCols];
                $this->dispatchEvent($this->_table, $this->eventKey('onImportCheckUnique'), 'onImportCheckUnique', $params);

                $references = [
                    'sheet' => $sheet,
                    'mapping' => $mapping,
                    'columns' => $columns,
                    'lookup' => $lookup,
                    'totalColumns' => $totalColumns,
                    'row' => $row,
                    'activeModel' => $activeModel,
                    'systemDateFormat' => $systemDateFormat,
                ];

                $originalRow = new ArrayObject();
                $extra['entityValidate'] = true;
                $institutionID = $this->_table->getQueryString('institution_id');
                $this->institutionId = $institutionID;
                if (empty($tempRow['institution_id'])) {
                    $tempRow['institution_id'] = $institutionID;
                }
                if (empty($tempRow['assignee_id'])) {
                    $session = $this->_table->Session;
                    $userId = $session->read('Auth.User.id');
                    $tempRow['assignee_id'] = $userId;
                }

                $rowPass = $this->_extractRecord($references, $tempRow, $originalRow, $rowInvalidCodeCols, $extra);

                if ($rowPass !== NULL && !$rowPass) {
                    $activeModel->setImportValidationFailed();
                } else {
                    $activeModel->setImportValidationPassed();
                }

                $tempRow = $tempRow->getArrayCopy();
//                dd($tempRow);
                if (!isset($tempRow['entity'])) {
                    $tableEntity = $activeModel->newEntity([]);
                } else {
                    $tableEntity = $tempRow;
//                    unset($tempRow['entity']);
                }
                if (!isset($tempRow['position_no'])) {
                    $tempRow['position_no'] = $this->_table->InstitutionPositions->getUniquePositionNo($institutionID);
                }

                if ($extra['entityValidate']) {
                    $tempRow['action_type'] = 'imported';
//                    dd([$tableEntity, $tempRow])
                    $activeModel->patchEntity($tableEntity, $tempRow);
                }

                $errors = $tableEntity->getErrors();
                $rowInvalidCodeCols = $rowInvalidCodeCols->getArrayCopy();
                $isNew = $tableEntity->isNew();

                if ($extra['entityValidate']) {

                    $InstitutionShifts = self::getDynamicTableInstance('Institution.InstitutionShifts');
                    $selectedPeriod = $this->AcademicPeriods->getCurrent();
                    $shiftArr = $InstitutionShifts->find('all', ['fields' => ['id', 'shift_option_id'], 'conditions' => ['academic_period_id' => $selectedPeriod,
                        'location_institution_id' => $tempRow['institution_id']]])
                        ->disableHydration()
                        ->toArray();

                    $arr = array_column($shiftArr, 'shift_option_id');

                    if(empty($tempRow['institution_id'])){
                        $tempRow['institution_id'] = $institutionID;
                    }
                    try {
                        if (!empty($tempRow['institution_id'])) {
                            if (!in_array($tempRow['shift_id'], $arr)) {
                                $rowInvalidCodeCols['shift_id'] = "Selected value is not in the list";

                            } else {
                                $saved = $activeModel->save($tableEntity);
                                Log::debug(print_r($saved, true));
                                if ($saved) {
                                    $jk++;
                                    $newEntity = true;
//                                    dd($tableEntity);
                                }
                            }
                        }

                    } catch (Exception $e) {
                        $newEntity = false;
                        $message = $e->getMessage();
                        $matches = '';

                        if (preg_match("/(?<=\')(.*?)+(?=\')/", $message, $matches)) {
                            $errorRow = $matches[0];
                        } else {
                            $errorRow = 'row' . $row;
                        }
                        $rowInvalidCodeCols[$errorRow] = $message;
//                        dd($rowInvalidCodeCols);
                    }

                    if ($newEntity) {
                        if ($isNew) {
                            $totalImported++;
                        } else {
                            $totalUpdated++;
                        }
                        $this->dispatchEvent($this->_table, $this->eventKey('onImportUpdateUniqueKeys'), 'onImportUpdateUniqueKeys', [$importedUniqueCodes, $tableEntity]);
                    }
                }

                if (!empty($rowInvalidCodeCols) || $errors) {
//                    dd([$rowInvalidCodeCols, $errors]);
                    $rowCodeError = '';
                    $rowCodeErrorForExcel = [];

                    if (!empty($errors)) {
                        foreach ($errors as $field => $arr) {
                            if (in_array($field, $columns)) {
                                $fieldName = $this->getExcelLabel($activeModel->getRegistryAlias(), $field, __FUNCTION__);
                                $rowCodeError .= '<li>' . $fieldName . ' => ' . $arr[key($arr)] . '</li>';
                                $rowCodeErrorForExcel[] = $fieldName . ' => ' . $arr[key($arr)];
                            } else {
                                if (in_array($field, ['student_name', 'staff_name'])) {
                                    $rowCodeError .= '<li>' . $arr[key($arr)] . '</li>';
                                    $rowCodeErrorForExcel[] = $arr[key($arr)];
                                }
                                $model->log('@ImportBehavior line ' . __LINE__ . ': ' . $activeModel->getRegistryAlias() . ' -> ' . $field . ' => ' . $arr[key($arr)], 'info');
                            }
                        }
                    }

                    if (!empty($rowInvalidCodeCols)) {
                        foreach ($rowInvalidCodeCols as $field => $errMessage) {
                            $fieldName = $this->getExcelLabel($activeModel->getRegistryAlias(), $field, __FUNCTION__);
                            if (!isset($errors[$field])) {
                                $rowCodeError .= '<li>' . $fieldName . ' => ' . $errMessage . '</li>';
                                $rowCodeErrorForExcel[] = $fieldName . ' => ' . $errMessage;
                            }
                        }
                    }

                    $dataFailed[] = [
                        'row_number' => $row,
                        'error' => '<ul>' . $rowCodeError . '</ul>',
                        'errorForExcel' => implode("\n", $rowCodeErrorForExcel),
                        'data' => $originalRow
                    ];

                    continue;
                } else {
                    $clonedEntity = clone $tableEntity;
                    $clonedEntity->setVirtual([]);

                    $tempPassedRecord = [
                        'row_number' => $row,
                        'data' => $this->_getReorderedEntityArray($clonedEntity, $columns, $originalRow, $systemDateFormat)
                    ];

                    $tempPassedRecord = new ArrayObject($tempPassedRecord);
                    $params = [$clonedEntity, $columns, $tempPassedRecord, $originalRow];
                    $this->dispatchEvent($this->_table, $this->eventKey('onImportSetModelPassedRecord'), 'onImportSetModelPassedRecord', $params);

                    $dataPassed[] = $tempPassedRecord->getArrayCopy();
                }
            }

            $session = $this->_table->Session;
            $completedData = [
                'uploadedName' => $uploadedName,
                'dataFailed' => $dataFailed,
                'totalImported' => $jk,
                'totalUpdated' => $jk,
                'totalRows' => count($dataFailed) + $jk,
                'header' => $header,
                'failedExcelFile' => $this->_generateDownloadableFile($dataFailed, 'failed', $header, $systemDateFormat),
                'passedExcelFile' => $this->_generateDownloadableFile($dataPassed, 'passed', $header, $systemDateFormat),
                'executionTime' => (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])
            ];

            $session->write($this->sessionKey, $completedData);
            $url = $this->_table->ControllerAction->url('results');
            $queryString = $this->_table->ControllerAction->getQueryString();
            if (empty($queryString)) {
                $queryString = $this->_table->getQueryString();
            }
            $encodedQueryString = $this->_table->ControllerAction->paramsEncode($queryString);
            $url[1] = $encodedQueryString;
            return $model->controller->redirect($url);
        };
    }

    public function template()
    {
        $folder = $this->prepareDownload();
        $modelName = $this->getConfig('model');
        $modelName = str_replace(' ', '_', Inflector::humanize(Inflector::tableize($modelName)));
        $excelFile = sprintf('OpenEMIS_Core4_Import_%s_Template.xlsx', $modelName);

        $excelPath = $folder . DS . $excelFile;
        $mapping = $this->getMapping();
        $header = $this->getHeader($mapping);

        $dataSheetName = $this->getExcelLabel('general', 'data', __FUNCTION__);
        $spreadsheet = new Spreadsheet();

        $this->setImportDataTemplate($spreadsheet, $dataSheetName, $header, '');
        $this->setCodesDataTemplate($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        try {
            $writer->save($excelPath);
        } catch (\Exception $e) {
            dd($e);
        }
        $this->performDownload($excelFile);
        die;
    }

    public function downloadFailed($excelFile)
    {
        $this->performDownload($excelFile);
        die;
    }

    public function downloadPassed($excelFile)
    {
        $this->performDownload($excelFile);
        die;
    }

    public function results()
    {
        $session = $this->_table->Session;
        if ($session->check($this->sessionKey)) {
            $completedData = $session->read($this->sessionKey);
            $this->_table->ControllerAction->field('select_file', ['visible' => false]);
            $this->_table->ControllerAction->field('results', [
                'type' => 'element',
                'override' => true,
                'visible' => true,
                'element' => 'Import./results',
                'rowClass' => 'row-reset',
                'results' => $completedData
            ]);

            if (!empty($completedData['failedExcelFile'])) {
                if (!empty($completedData['passedExcelFile'])) {
                    $message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file', __FUNCTION__) . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'partial_failed');
                } else {
                    $message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file', __FUNCTION__) . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'failed');
                }
                $this->_table->Alert->error($message, ['type' => 'string', 'reset' => true]);
            } else {
                $message = '<i class="fa fa-check-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file', __FUNCTION__) . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'success', __FUNCTION__);
                $this->_table->Alert->ok($message, ['type' => 'string', 'reset' => true]);
            }

            $this->_table->controller->set('data', $this->_table->newEntity([]));
            $this->_table->ControllerAction->renderView('/ControllerAction/view');
        } else {
            return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
        }
    }

    public function beginExcelHeaderStyling($spreadsheet, $dataSheetName, $title = '')
    {
        if (empty($title)) {
            $title = $dataSheetName;
        } else {
            if ($title == 'Import Training Session Trainee Results Data') {
                $title = 'Import Training Results Data';
            }
        }

        $activeSheet = $spreadsheet->getActiveSheet();
        $activeSheet->setTitle($dataSheetName);

        if (function_exists('imagecreatefromjpeg')) {
            $gdImage = imagecreatefromjpeg(ROOT . DS . 'plugins' . DS . 'Import' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.jpg');
            $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing();
            $objDrawing->setName('OpenEMIS Logo');
            $objDrawing->setDescription('OpenEMIS Logo');
            $objDrawing->setImageResource($gdImage);
            $objDrawing->setRenderingFunction(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::RENDERING_JPEG);
            $objDrawing->setMimeType(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_DEFAULT);
            $objDrawing->setHeight(100);
            $objDrawing->setCoordinates('A1');
            $objDrawing->setWorksheet($activeSheet);
        }

        $activeSheet->getRowDimension(1)->setRowHeight(75);
        $activeSheet->getRowDimension(2)->setRowHeight(25);

        if ($this->isCustomText()) {
            $activeSheet->getRowDimension(3)->setRowHeight(25);
        }

        $activeSheet->setCellValue("C1", $title);
    }

    public function endExcelHeaderStyling($spreadsheet, $headerLastAlpha, $lastRowToAlign = 2, $applyFillFontSetting = [], $applyCellBorder = [])
    {
        if (empty($applyFillFontSetting)) {
            $applyFillFontSetting = $this->isCustomText() ? ['s' => 3, 'e' => 3] : ['s' => 2, 'e' => 2];
        }

        if (empty($applyCellBorder)) {
            $applyCellBorder = $this->isCustomText() ? ['s' => 3, 'e' => 3] : ['s' => 2, 'e' => 2];
        }

        $activeSheet = $spreadsheet->getActiveSheet();

        if (!in_array($headerLastAlpha, ['A', 'B', 'C'])) {
            $activeSheet->mergeCells('C1:' . $headerLastAlpha . '1');
        }

        $activeSheet->getStyle("A1:" . $headerLastAlpha . "1")->getFont()->setBold(true)->setSize(16);
        $style = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ];
        $activeSheet->getStyle("A1:" . $headerLastAlpha . $lastRowToAlign)->applyFromArray($style)->getFont()->setBold(true);
        $activeSheet->getStyle("A" . $applyFillFontSetting['s'] . ":" . $headerLastAlpha . $applyFillFontSetting['e'])->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $activeSheet->getStyle("A" . $applyFillFontSetting['s'] . ":" . $headerLastAlpha . $applyFillFontSetting['e'])->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('6699CC');
        $activeSheet->getStyle("A" . $applyCellBorder['s'] . ":" . $headerLastAlpha . $applyCellBorder['e'])->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    }

    public function setImportDataTemplate($spreadsheet, $dataSheetName, $header, $type)
    {
        $spreadsheet->setActiveSheetIndex(0);

        $description = ' ( DD/MM/YYYY )';
        $dateHeader = [
            __('Date Closed') . $description,
            __('Date Opened') . $description,
            __('Start Date') . $description,
            __('End Date') . $description,
            __('Date Of Birth') . $description,
            __('Salary Date') . $description,
            __('Expiry Date') . $description,
        ];

        $lastRowToAlign = $this->isCustomText() ? 3 : 2;
        $activeSheet = $spreadsheet->getActiveSheet();

        if ($this->isCustomText()) {
            if (!empty($type) && $type == 'failed') {
                $activeSheet->mergeCells('A2:D2');
            } else if (empty($type) || $type != 'failed') {
                $activeSheet->mergeCells('A2:C2');
            }

            $activeSheet->setCellValue("A2", $this->customText);
        }

        $this->beginExcelHeaderStyling($spreadsheet, $dataSheetName, __(Inflector::humanize(Inflector::tableize($this->_table->getAlias()))) . ' ' . $dataSheetName);

        $currentRowHeight = $activeSheet->getRowDimension($lastRowToAlign)->getRowHeight();

        foreach ($header as $key => $value) {
            $alpha = Coordinate::stringFromColumnIndex($key + 1);
            $activeSheet->setCellValue($alpha . $lastRowToAlign, $value);
            $activeSheet->getColumnDimension($alpha)->setAutoSize(true);
            if (strlen($value) < 50) {
                if (in_array($value, $dateHeader)) {
                    $activeSheet->getStyle($alpha)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            } else {
                $currentRowHeight = $this->suggestRowHeight(strlen($value), $currentRowHeight);
                $activeSheet->getRowDimension($lastRowToAlign)->setRowHeight($currentRowHeight);
                $activeSheet->getStyle($alpha . $lastRowToAlign)->getAlignment()->setWrapText(true);
            }
        }

        $headerLastAlpha = Coordinate::stringFromColumnIndex(count($header));
        $this->endExcelHeaderStyling($spreadsheet, $headerLastAlpha, $lastRowToAlign);
    }

    public function suggestRowHeight($stringLen, $currentRowHeight)
    {
        $multiplier = $stringLen >= 50 ? $stringLen % 50 : 0;
        $rowHeight = (3 * $multiplier) + 25;
        return $rowHeight > $currentRowHeight && $rowHeight <= 250 ? $rowHeight : $currentRowHeight;
    }

    public function setCodesDataTemplate($spreadsheet)
    {

        $sheetName = __('References');
        $spreadsheet->createSheet(1);
        $spreadsheet->setActiveSheetIndex(1);

        $this->beginExcelHeaderStyling($spreadsheet, $sheetName);
        $spreadsheet->getActiveSheet()->getRowDimension(3)->setRowHeight(25);

        if (method_exists($this->_table, 'excelGetCodesData')) {
            $codesData = $this->_table->excelGetCodesData();
        } else {
            $codesData = $this->excelGetCodesData($this->_table);
        }

        $lastColumn = 0;
        $currentRowHeight = $spreadsheet->getActiveSheet()->getRowDimension(2)->getRowHeight();

        foreach ($codesData as $columnOrder => $modelArr) {
            $modelData = $modelArr['data'];
            $firstColumn = $lastColumn + 1;
            $lastColumn = $firstColumn + count($modelArr['data'][0]) - 1;

            $strCoordinate = $this->getValidRange($firstColumn, 2, $lastColumn, 2);
//            dd($strCoordinate);
            $spreadsheet->getActiveSheet()->mergeCells($strCoordinate);
            $spreadsheet->getActiveSheet()->setCellValue(Coordinate::stringFromColumnIndex($firstColumn) . "2", $modelArr['sheetName']);

            if (strlen($modelArr['sheetName']) < 50) {
                $spreadsheet->getActiveSheet()->getColumnDimension(Coordinate::stringFromColumnIndex($firstColumn))->setAutoSize(true);
            } else {
                $currentRowHeight = $this->suggestRowHeight(strlen($modelArr['sheetName']), $currentRowHeight);
                $spreadsheet->getActiveSheet()->getRowDimension(2)->setRowHeight($currentRowHeight);
                $spreadsheet->getActiveSheet()->getStyle(Coordinate::stringFromColumnIndex($firstColumn) . "2")->getAlignment()->setWrapText(true);
            }

            foreach ($modelData as $index => $sets) {
                $i = 0;
                foreach ($sets as $key => $value) {
                    $alpha = Coordinate::stringFromColumnIndex($key + $firstColumn);
                    $cellAddress = $alpha . ($index + 3);
                    if($i == 1){
                        $startCell = $alpha . ($index + 4);
                        $modelData[$index]['lookupStartAddress'] = $startCell;
                    }
                    $i++;
                    // Set cell value
                    $spreadsheet->getActiveSheet()->setCellValue($cellAddress, $value);
                    $spreadsheet->getActiveSheet()->getColumnDimension($alpha)->setAutoSize(true);
                    // Always update the end address for lookup
                }
                $modelData[$index]['lookupEndAddress'] = $cellAddress;

            }

            if (count($modelData) > 1 && !isset($modelArr['noDropDownList'])) {
                $lookupColumn = $firstColumn + intval($modelArr['lookupColumn']) - 1;
                $alpha = Coordinate::stringFromColumnIndex($columnOrder);
                $lookupColumnAlpha = Coordinate::stringFromColumnIndex($lookupColumn);
                $lookupStart = $this->isCustomText() ? 4 : 3;

                // Use the stored start and end addresses for the list range
                $startAddress = $modelData[0]['lookupStartAddress']; // Example: 'C4'
                $endAddress = $modelData[count($modelData) - 1]['lookupEndAddress']; // Example: 'C72'
                $listLocation = "'References'!$" . $lookupColumnAlpha . "$" . substr($startAddress, 1) . ":$" . $lookupColumnAlpha . "$" . substr($endAddress, 1);
                $spreadsheet->setActiveSheetIndex(0);
                for ($i = $lookupStart; $i < 103; $i++) {

                    $objValidation = $spreadsheet->getActiveSheet()->getCell($alpha . $i)->getDataValidation();
                    $objValidation->setType(DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);

                    // Apply the list location formula
                    $objValidation->setFormula1($listLocation);
                }

                $spreadsheet->setActiveSheetIndex(1);
            }

        }

        if ($lastColumn > -1) {
            $headerLastAlpha = Coordinate::stringFromColumnIndex($lastColumn + 1);
            $spreadsheet->getActiveSheet()->getStyle("A2:" . $headerLastAlpha . "2")->getFont()->setBold(true)->setSize(12);
            $this->endExcelHeaderStyling($spreadsheet, $headerLastAlpha, 3, ['s' => 3, 'e' => 3], ['s' => 2, 'e' => 3]);
        }
    }

    protected function _getReorderedEntityArray(Entity $entity, array $columns, ArrayObject $originalRow, $systemDateFormat)
    {
        $array = [];
        foreach ($columns as $col => $property) {
            $value = $originalRow[$col];
            $array[] = $value;
        }
        return $array;
    }

    private function _generateDownloadableFile($data, $type, $header, $systemDateFormat)
    {
        if (!empty($data)) {
            $downloadFolder = $this->prepareDownload();
            $excelFile = sprintf('OpenEMIS_Core_Import_%s_%s_%s.xlsx', $this->getConfig('model'), ucwords($type), time());
            $excelPath = $downloadFolder . DS . $excelFile;

            $newHeader = $header;
            if ($type == 'failed') {
                $newHeader[] = $this->getExcelLabel('general', 'errors', __FUNCTION__);
            }

            $dataSheetName = $this->getExcelLabel('general', 'data', __FUNCTION__);
            $spreadsheet = new Spreadsheet();
            $rowData = $this->isCustomText() ? 4 : 3;

            $this->setImportDataTemplate($spreadsheet, $dataSheetName, $newHeader, $type);
            $activeSheet = $spreadsheet->getActiveSheet();

            foreach ($data as $index => $record) {
                if ($type == 'failed') {
                    $values = array_values($record['data']->getArrayCopy());
                    $values[] = $record['errorForExcel'];
                } else {
                    $values = $record['data'];
                }

                $activeSheet->getRowDimension(($index + $rowData))->setRowHeight(15);
                foreach ($values as $key => $value) {
                    $alpha = Coordinate::stringFromColumnIndex($key + 1);
                    $activeSheet->setCellValue($alpha . ($index + $rowData), $value);
                    $activeSheet->getColumnDimension($alpha)->setAutoSize(true);

                    if ($key == (count($values) - 1) && $type == 'failed') {
                        $suggestedRowHeight = $this->suggestRowHeight(strlen($value), 15);
                        $activeSheet->getRowDimension(($index + $rowData))->setRowHeight($suggestedRowHeight);
                        $activeSheet->getStyle($alpha . ($index + $rowData))->getAlignment()->setWrapText(true);
                    }
                }
            }

            if ($type == 'failed') {
                $this->setCodesDataTemplate($spreadsheet);
            }

            $spreadsheet->setActiveSheetIndex(0);
            $writer = new Xlsx($spreadsheet);
            $writer->save($excelPath);

            $downloadUrl = $this->_table->ControllerAction->url('download' . ucwords($type));
            $downloadUrl[] = $excelFile;
            $excelFile = $downloadUrl;
        } else {
            $excelFile = null;
        }

        return $excelFile;
    }

    public function getExcelColumnAlpha($column_number)
    {
        return Coordinate::stringFromColumnIndex($column_number + 1);
    }

    public function checkRowCells($sheet, $totalColumns, $row)
    {
        $cellsState = [];
        for ($col = 0; $col < $totalColumns; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, $row);
            $value = $cell->getValue();
            $cellsState[] = !empty($value);
        }
        return in_array(true, $cellsState);
    }

    public function isCorrectTemplate($header, $sheet, $totalColumns, $row)
    {
        $model = $this->_table;
        if ($model->getAlias() == 'ImportTrainingSessionTraineeResults') {
            $newheader = [];
            foreach ($header as $key => $value) {
                if ($value == 'Training Session') {
                    $value = 'Training Session Code';
                } else if ($value == 'Training Result Type') {
                    $value = 'Result Types';
                }
                $newheader[$key] = $value;
            }
            $header = $newheader;
        }

        $cellsValue = [];
        for ($col = 0; $col < $totalColumns; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, $row);
            $cellsValue[] = $cell->getValue();
        }
        return $header === $cellsValue;
    }

    public function getMapping()
    {
        return $this->_table->find('all')
            ->where([
                $this->_table->aliasField('model') => $this->getConfig('plugin') . '.' . $this->getConfig('model')
            ])
            ->order($this->_table->aliasField('order'))
            ->toArray();
    }

    protected function getHeader($mapping = [])
    {
        if (empty($mapping)) {
            $mapping = $this->getMapping($this->_table);
        }

        $header = [];
        foreach ($mapping as $value) {
            if ($value->foreign_key == self::CUSTOM) {
                $customDataSource = $value->lookup_column;
                $customHeaderData = new ArrayObject();
                $params = [$customDataSource, $customHeaderData];
                $this->dispatchEvent($this->_table, $this->eventKey('onImportCustomHeader'), 'onImportCustomHeader', $params);

                $label = $customHeaderData[1];
                if ($customHeaderData[0]) {
                    $label .= ' ' . __($value->description);
                }
            } else {
//                if (!$value->column_name) {

//                }
                $column = $value->column_name;
                $lookup_model = $value->lookup_model;
                if (!empty($lookup_model)) {
                    $label = $this->getExcelLabel('Imports', $value->lookup_model, __FUNCTION__);
                }
                if (empty($lookup_model)) {
                    $label = $this->getExcelLabel($value->model, $column);
                }
//                dd($label);
                if (($value->lookup_model == 'Users') && ($value->lookup_column == 'openemis_no')) {
                    $label = '';
                }

                if ($value->lookup_model == 'AreaAdministratives') {
                    $label = $this->getExcelLabel($value->model, $column);
                }

                if (!empty($value->description)) {
                    $label .= ' ' . __($value->description);
                }
            }

            $header[] = __($label);
        }

        return $header;
    }

    protected function getColumns($mapping = [])
    {
        $columns = [];
        if (empty($mapping)) {
            $mapping = $this->getMapping($this->_table);
        }

        foreach ($mapping as $value) {
            $columns[] = $value->column_name;
        }

        return $columns;
    }

    protected function getCodesByMapping($mapping)
    {
        $lookup = [];
        foreach ($mapping as $key => $obj) {
            $mappingRow = $obj;
            if ($mappingRow->foreign_key == self::FIELD_OPTION) {
                $lookupPlugin = $mappingRow->lookup_plugin;
                $lookupModel = $mappingRow->lookup_model;
                $lookupColumn = $mappingRow->lookup_column;
                $lookupModelObj = TableRegistry::getTableLocator()->get($lookupModel, ['className' => $lookupPlugin . '.' . $lookupModel]);

                $lookupValues = $lookupModelObj->getList($lookupModelObj->find());
                $emptyCodeRecords = $lookupValues;
                $emptyCodeRecords = $emptyCodeRecords->stopWhen(function ($record, $index) {
                    return !empty($record->national_code);
                })->toArray();

                $lookupValues = $lookupValues->toArray();
                $lookup[$key] = [];
                if (!empty($lookupValues)) {
                    foreach ($lookupValues as $record) {
                        if (count($emptyCodeRecords) < 1) {
                            $lookup[$key][$record->national_code] = [
                                'id' => $record->id,
                                'name' => $record->name
                            ];
                        } else {
                            $lookup[$key][$record->id] = [
                                'id' => $record->id,
                                'name' => $record->name
                            ];
                        }
                    }
                }
            }
        }

        return $lookup;
    }

    public function excelGetCodesData(Table $model)
    {
        $mapping = $model->find('all')
            ->where([
                $model->aliasField('model') => $this->getConfig('plugin') . '.' . $this->getConfig('model'),
                $model->aliasField('foreign_key') . ' IN' => [self::FIELD_OPTION, self::DIRECT_TABLE, self::NON_TABLE_LIST]
            ])
            ->order($model->aliasField('order'))
            ->toArray();

        $data = new ArrayObject();
        foreach ($mapping as $row) {
            $foreignKey = $row->foreign_key;
            $lookupPlugin = $row->lookup_plugin;
            $lookupModel = $row->lookup_model;
            $lookupColumn = $row->lookup_column;
            $mappingModel = $row->model;

            $translatedCol = $this->getExcelLabel($model, $lookupColumn);

            $sheetName = trim($this->getExcelLabel($row->model, $row->column_name));
            $data[$row->order] = [
                'data' => [],
                'sheetName' => $sheetName
            ];
            $modelData = [];

            if ($foreignKey == self::FIELD_OPTION) {
                $relatedModel = TableRegistry::getTableLocator()->get($lookupModel, ['className' => $lookupPlugin . '.' . $lookupModel]);
                $modelData = $relatedModel->getList($relatedModel->find());
                $emptyCodeRecords = $modelData;
                $emptyCodeRecords = $emptyCodeRecords->stopWhen(function ($record, $key) {
                    return !empty($record->national_code);
                })->toArray();

                $modelData = $modelData->toArray();
                $data[$row->order]['lookupColumn'] = 2;
                $data[$row->order]['data'][] = [__('Name'), $translatedCol];

                if (!empty($modelData)) {
                    foreach ($modelData as $record) {
                        if (count($emptyCodeRecords) < 1) {
                            $data[$row->order]['data'][] = [$record->name, $record->national_code];
                        } else {
                            $data[$row->order]['data'][] = [$record->name, $record->id];
                        }
                    }
                }
            } elseif ($foreignKey == self::DIRECT_TABLE || $foreignKey == self::NON_TABLE_LIST) {
                $params = [$lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, $data, $row->order];
                $this->dispatchEvent($this->_table, $this->eventKey('onImportPopulate' . $lookupModel . 'Data'), 'onImportPopulate' . $lookupModel . 'Data', $params);
            }
        }

        return $data;
    }

    public function prepareDownload()
    {
        $folder = WWW_ROOT . $this->rootFolder;
        if (!file_exists($folder)) {
            umask(0);
            mkdir($folder, 0777);
        } else {
            $fileList = array_diff(scandir($folder), array('..', '.'));
            $now = new DateTime();
            $now->sub(new DateInterval('PT1H'));

            foreach ($fileList as $file) {
                $path = $folder . DS . $file;
                $timestamp = filectime($path);
                $date = new DateTime();
                $date->setTimestamp($timestamp);

                if ($now > $date) {
                    if (!unlink($path)) {
                        $this->_table->log('Unable to delete ' . $path, 'export');
                    }
                }
            }
        }

        return $folder;
    }

    public function performDownload($excelFile)
    {
        $folder = WWW_ROOT . $this->rootFolder;
        $excelPath = $folder . DS . $excelFile;
        $filename = basename($excelPath);

        header("Pragma: public", true);
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($excelPath));
        echo file_get_contents($excelPath);
    }

    public function getExcelLabel($module, $columnName, $function = 'No!')
    {

        if ($module instanceof Table) {
            $module = $module->getAlias();
        }

        $dotPos = strpos($module, '.');
        if ($dotPos > -1) {
            $module = substr($module, $dotPos + 1);
        }

        if (!empty($this->labels) && isset($this->labels[$module]) && isset($this->labels[$module][$columnName])) {
            return $this->labels[$module][$columnName];
        }

        if ($module == 'Import') {
            $translatedCol = $this->_table->getMessage($module . '.' . $columnName);
        } else {
            $language = '';
            $translatedCol = $translatedCol ?? $columnName;

            if (empty($translatedCol) || ($translatedCol == $columnName && $columnName != 'FTE')) {
                $translatedCol = Inflector::humanize(Inflector::singularize(Inflector::tableize($columnName)));
            }
        }

        $this->labels[$module][$columnName] = $translatedCol;
        return __($translatedCol);
    }

    protected function _extractRecord($references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols, ArrayObject $extra)
    {
        $sheet = $references['sheet'];
        $mapping = $references['mapping'];
        $columns = $references['columns'];
        $lookup = $references['lookup'];
        $totalColumns = $references['totalColumns'];
        $row = $references['row'];
        $activeModel = $references['activeModel'];
        $systemDateFormat = $references['systemDateFormat'];
        $references = null;

        $rowPass = true;
        $customColumnCounter = 0;

        for ($col = 0; $col < $totalColumns; ++$col) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, $row);

            if (self::timeTwelvehoursValidator($cell->getFormattedValue())) {
                $cell->getStyle()->getNumberFormat()->setFormatCode('h:mm:ss');
                $originalValue = $cell->getFormattedValue();
            } else if (SharedDate::isDateTime($cell)) {
                $cell->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $originalValue = $cell->getFormattedValue();
            } else {
                $originalValue = $cell->getValue();
            }

            $cellValue = $originalValue;
            if (gettype($cellValue) == 'double' || gettype($cellValue) == 'boolean') {
                $cellValue = (string)$cellValue;
            }

            $excelMappingObj = $mapping[$col];
            $foreignKey = $excelMappingObj->foreign_key;
            $lookupPlugin = $excelMappingObj->lookup_plugin;
            $lookupModel = $excelMappingObj->lookup_model;
            $lookupColumn = $excelMappingObj->lookup_column;
            $lookupColumnName = $excelMappingObj->column_name;
            $mappingModel = $excelMappingObj->model;

                $columnName = $columns[$col];
                $originalRow[$col] = $originalValue;


            $val = $cellValue;
            $datePattern = "/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}/";

            if (isset($tempRow[$columnName]) && !empty($tempRow[$columnName]) && $tempRow[$columnName] !== 0) {
                continue;
            }
            if (!empty($val)) {
                $columnAttr = $activeModel->getSchema()->getColumn($columnName);
                if ($columnAttr['type'] == 'date') {
                    $originalRow[$col] = $val;

                    if (!empty($val) && preg_match($datePattern, $val)) {
                        $val = trim($val);
                        $split = explode('/', $val);
                        $dateObject = new Date();
                        $dateObject->setDate($split[2], $split[1], $split[0]);

                        if ($val != $dateObject->format('d/m/Y')) {
                            $rowInvalidCodeCols[$columnName] = __('You have entered an invalid date');
                            $rowPass = false;
                            $extra['entityValidate'] = false;
                        } else {
                            $originalRow[$col] = $dateObject->format('d/m/Y');
                        }
                    } else {
                        $rowInvalidCodeCols[$columnName] = __('You have entered an invalid date');
                        $rowPass = false;
                        $extra['entityValidate'] = false;
                    }
                }
            }

            $translatedCol = $this->getExcelLabel($activeModel->getAlias(), $columnName, __FUNCTION__);
            $columnDescription = strtolower($mapping[$col]->description);
            $isOptional = $mapping[$col]->is_optional;
            if (!$isOptional) {
                $isOptional = substr_count($columnDescription, 'not required');
            }

            if ($foreignKey == self::FIELD_OPTION) {
                if (!empty($cellValue)) {
                    if (array_key_exists($cellValue, $lookup[$col])) {
                        $val = $lookup[$col][$cellValue]['id'];
                    } else {
                        $rowPass = false;
                        $rowInvalidCodeCols[$columnName] = $this->getExcelLabel('Import', 'value_not_in_list', __FUNCTION__);
                    }
                } else {
                    if (!$isOptional) {
                        $rowPass = false;
                        $rowInvalidCodeCols[$columnName] = __('This field cannot be left empty');
                    }
                }
            } elseif ($foreignKey == self::DIRECT_TABLE) {
                $registryAlias = $lookupPlugin . '.' . $lookupModel;
                if (!empty($this->directTables) && isset($this->directTables[$registryAlias])) {
                    $excelLookupModel = $this->directTables[$registryAlias]['excelLookupModel'];
                } else {
                    $excelLookupModel = TableRegistry::getTableLocator()->get($registryAlias);
                    $this->directTables[$registryAlias] = ['excelLookupModel' => $excelLookupModel];
                }
                $excludeValidation = false;
                if (!empty($cellValue)) {
                    if (isset($extra['lookup'][$excelLookupModel->getAlias()][$cellValue])) {
                        $record = $extra['lookup'][$excelLookupModel->getAlias()][$cellValue];
                    } else {
                        if ($mappingModel == 'Student.StudentGuardians' && $lookupColumnName == 'guardian_id') {
                            if ($securityUser) {
                                $cellValue = $securityUser->openemis_id;
                            } else {
                                $cellValue = $originalValue;
                            }

                            if ($mappingModel == 'Student.StudentGuardians' && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number') {
                                $lookupColumn = 'openemis_no';
                            }
                        }

                        $lookupQuery = $excelLookupModel->find()->where([$excelLookupModel->aliasField($lookupColumn) => $cellValue]);
                        $record = $lookupQuery->first();
                        $extra['lookup'][$excelLookupModel->getAlias()][$cellValue] = $record;
                    }
                } else {
                    $columnAttr = $activeModel->getSchema()->getColumn($columnName);
                    if ($columnAttr && !$columnAttr['null']) {
                        if (isset($columnAttr['default']) && strlen($columnAttr['default']) > 0) {
                            $cellValue = $columnAttr['default'];
                            $excludeValidation = true;
                        } else {
                            $record = '';
                        }
                    } else {
                        $excludeValidation = true;
                    }
                }
                if (!$excludeValidation) {
                    if (!empty($record)) {
                        if ($mappingModel == 'Institution.InstitutionMealStudents' && $lookupColumnName == 'OpenEMIS_ID' && $lookupColumn == 'openemis_no') {
                            $val = $record->openemis_no;
                        } else {
                            $val = $record->id;
                        }
                        $this->directTables[$registryAlias][$val] = $record->name;
                    } else {
                        if (!empty($cellValue)) {
                            $rowPass = false;
                            if (!$rowInvalidCodeCols->offsetExists($columnName)) {
                                $rowInvalidCodeCols[$columnName] = $this->getExcelLabel('Import', 'value_not_in_list', __FUNCTION__);
                            }
                        } else {
                            if ($mappingModel == 'Student.StudentGuardians' && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number' && empty($originalValue)) {
                                if ($i == 0) {
                                    $columnName = 'guardian_id';
                                    $rowInvalidCodeCols[$columnName] = __('Please enter either OpenEMIS ID or Identity number for guardian');
                                    $rowPass = false;
                                    $extra['entityValidate'] = false;
                                }
                            } else {
                                $rowPass = false;
                                $rowInvalidCodeCols[$columnName] = __('This field cannot be left empty');
                            }
                        }
                    }
                } else {
                    $val = $cellValue;
                }
            } elseif ($foreignKey == self::NON_TABLE_LIST) {
                if (strlen($cellValue) > 0) {
                    $getIdEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportGet' . $excelMappingObj->lookup_model . 'Id'), 'onImportGet' . $excelMappingObj->lookup_model . 'Id', [$cellValue]);
                    $recordId = $getIdEvent->getResult();
                    if (strlen($recordId) > 0) {
                        $val = $recordId;
                    } else {
                        $rowPass = false;
                        $rowInvalidCodeCols[$columnName] = $this->getExcelLabel('Import', 'value_not_in_list', __FUNCTION__);
                    }
                } else {
                    if (!$isOptional) {
                        $rowPass = false;
                        $rowInvalidCodeCols[$columnName] = __('This field cannot be left empty');
                    }
                }
            } elseif ($foreignKey == self::CUSTOM) {
                $params = [$tempRow, $cellValue];
                $event = $this->dispatchEvent($this->_table, $this->eventKey('onImportCheck' . ucfirst($excelMappingObj->column_name) . 'Config'), 'onImportCheck' . $excelMappingObj->column_name . 'Config', $params);

                if ($event->getResult() !== true) {
                    $rowInvalidCodeCols[$columnName] = __($event->getResult());
                    $rowPass = false;
                } else {
                    if (!isset($tempRow['customColumns'])) {
                        $tempRow['customColumns'] = [];
                    }
                    $tempRow['customColumns'][$columnName] = $val;
                }
            }

            if (!$isOptional || ($isOptional && strlen($val) > 0)) {
                $tempRow[$columnName] = $val;
            }
        }

        $plugin = $this->getConfig('plugin');
        $model = $this->getConfig('model');
        if ($plugin == 'Institution' && $model == 'Institutions') {
            $session = $this->_table->Session;
            $userId = $session->read('Auth.User.id');
            $superAdmin = $session->read('Auth.User.super_admin');

            $tempRow['userId'] = $userId;
            $tempRow['superAdmin'] = $superAdmin;
        }
        if ($rowPass) {
            $rowPassEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportModelSpecificValidation'), 'onImportModelSpecificValidation', [$references, $tempRow, $originalRow, $rowInvalidCodeCols]);
            $rowPass = $rowPassEvent->getResult();
        }

        return $rowPass;
    }

    private static function timeTwelvehoursValidator($time)
    {
        $regex = '/^(1[012]|[1-9]):[0-5][0-9]:[0-5][0-9]\s*[ap]m$/i';
        if (preg_match($regex, $time)) {
            return true;
        }

        $regex = '/^(1[012]|[1-9]):[0-5][0-9]\s*[ap]m$/i';
        if (preg_match($regex, $time)) {
            return true;
        }

        $regex = '/^([0-1][0-9]):[0-5][0-9]\s*[ap]m$/i';
        if (preg_match($regex, $time)) {
            return true;
        }

        $regex = '/^([0-1][0-9]):[0-5][0-9]:[0-5][0-9]\s*[ap]m$/i';
        if (preg_match($regex, $time)) {
            return true;
        }

        return false;
    }

    public function getAcademicPeriodByStartDate($date)
    {
        if (empty($date)) {
            return false;
        }

        if ($date instanceof DateTime) {
            $date = $date->format('Y-m-d');
        }

        return $this->AcademicPeriods
            ->find()
            ->where([
                "date(start_date) <= date '" . $date . "'",
                "date(end_date) >= date '" . $date . "'",
                'parent_id <> 0',
                'visible = 1'
            ])
            ->toArray();
    }

    public function getAcademicPeriodLevel($academicPeriodId)
    {
        if (empty($academicPeriodId)) {
            return false;
        }

        return $this->AcademicPeriods
            ->find()
            ->select(['academic_period_level_id'])
            ->where(["id = " . $academicPeriodId])
            ->toArray();
    }

    protected function eventKey($key)
    {
        return 'Model.import.' . $key;
    }

    protected function file_upload_max_size()
    {
        static $max_size = -1;

        if ($max_size < 0) {
            $max_size = $this->post_upload_max_size();

            $upload_max = $this->upload_max_filesize();
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    function getValidRange($firstColumn, $firstRow, $lastColumn, $lastRow) {
        // Ensure firstColumn is less than or equal to lastColumn and firstRow is less than or equal to lastRow
        $startCell = Coordinate::stringFromColumnIndex($firstColumn) . $firstRow;
        $endCell = Coordinate::stringFromColumnIndex($lastColumn) . $lastRow;

        if ($firstColumn <= $lastColumn && $firstRow <= $lastRow) {
            return "$startCell:$endCell";
        } else {
            return "$endCell:$startCell";
        }
    }

    protected function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return round($size);
    }

    protected function post_upload_max_size()
    {
        $max_size = $this->parse_size(ini_get('post_max_size'));
        $system_limit = $this->system_memory_limit();

        if ($max_size == 0) {
            $max_size = $system_limit;
        }
        return $max_size;
    }

    protected function system_memory_limit()
    {
        return $this->parse_size(ini_get('memory_limit'));
    }

    protected function upload_max_filesize()
    {
        return $this->parse_size(ini_get('upload_max_filesize'));
    }

    protected function bytesToReadableFormat($bytes)
    {
        $KILO = 1024;
        $MEGA = $KILO * 1024;
        $GIGA = $MEGA * 1024;
        $TERA = $GIGA * 1024;

        if ($bytes < $KILO) {
            return $bytes . 'B';
        }
        if ($bytes < $MEGA) {
            return round($bytes / $KILO, 2) . 'KB';
        }
        if ($bytes < $GIGA) {
            return round($bytes / $MEGA, 2) . 'MB';
        }
        if ($bytes < $TERA) {
            return round($bytes / $GIGA, 2) . 'GB';
        }
        return round($bytes / $TERA, 2) . 'TB';
    }

    protected $phpFileUploadErrors = [
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    ];

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

}
