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
use PHPExcel_Worksheet;
use PHPExcel_Style_NumberFormat;
use PHPExcel_Shared_Date;
use PHPExcel_IOFactory;
use PHPExcel_Cell;

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
 *
 * @author  hanafi <hanafi.ahmat@kordit.com>
 */
class ImportBehavior extends Behavior
{
    use EventTrait;

    const FIELD_OPTION = 1;
    const DIRECT_TABLE = 2;
    const NON_TABLE_LIST = 3;
    const CUSTOM = 4;

    // const RECORD_HEADER = 2;
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
        // 'csv'    => 'text/plain',
        // 'csv'    => 'text/csv',
        'xls'   => ['application/vnd.ms-excel', 'application/vnd.ms-office'],
        // Use for openoffice .xls format
        'xlsx'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ods'   => ['application/vnd.oasis.opendocument.spreadsheet'],
        'zip'   => ['application/zip']
    ];
    private $institutionId = false;
    private $recordHeader = '';
    private $customText = '';

    public function initialize(array $config)
    {
        $fileTypes = $this->config('fileTypes');
        $allowableFileTypes = [];
        if ($fileTypes) {
            foreach ($fileTypes as $key => $value) {
                if (array_key_exists($value, $this->_fileTypesMap)) {
                    $allowableFileTypes[] = $value;
                }
            }
        } else {
            $allowableFileTypes = array_keys($this->_fileTypesMap);
        }
        $this->config('allowable_file_types', $allowableFileTypes);

        // testing using file size limit set in php.ini settings
        // $this->config('max_size', $this->system_memory_limit());
        // $this->config('max_rows', 50000);
        //

        $plugin = $this->config('plugin');
        if (empty($plugin)) {
            $exploded = explode('.', $this->_table->registryAlias());
            if (count($exploded)==2) {
                $this->config('plugin', $exploded[0]);
            }
        }
        $plugin = $this->config('plugin');
        $model = $this->config('model');
        if (empty($model)) {
            $this->config('model', Inflector::pluralize($plugin));
        }

        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    }

    private function isCustomText()
    {
        $this->customText = $this->config('custom_text');
        if (!empty($this->customText) && strlen($this->customText) > 0) {
            return true;
        } else {
            return false;
        }
    }


/******************************************************************************************************************
**
** Events
**
******************************************************************************************************************/
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'ControllerAction.Model.onGetFormButtons' => 'onGetFormButtons',
            'ControllerAction.Model.beforeAction' => 'beforeAction',
            'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
            'ControllerAction.Model.add.beforeSave' => 'addBeforeSave',
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        switch ($action) {
            case 'add':
                $downloadUrl = $toolbarButtons['back']['url'];
                $downloadUrl[0] = 'template';
                if ($buttons['add']['url']['action']=='ImportInstitutionSurveys') {
                    $downloadUrl[1] = $buttons['add']['url'][1];
                }
                $this->_table->controller->set('downloadOnClick', "javascript:window.location.href='". Router::url($downloadUrl) ."'");
                break;
        }

        //back button
        if (!empty($this->config('backUrl'))) {
            $toolbarButtons['back']['url'] = array_merge($toolbarButtons['back']['url'], $this->config('backUrl'));
        } elseif ($toolbarButtons['back']['url']['plugin']=='Directory') { //back button for directory
            $back = [];
            if ($this->_table->request->params['pass'][0] == 'add') {
                $back['action'] = 'Directories';
            } elseif ($this->_table->request->params['pass'][0] == 'results') {
                $back['action'] = $this->_table->alias();
                $back[0] = 'add';
            };
            $toolbarButtons['back']['url'] = array_merge($toolbarButtons['back']['url'], $back);
        } elseif ($this->institutionId && $toolbarButtons['back']['url']['plugin']=='Institution') {
            $back = [];

            if ($this->_table->request->params['pass'][0] == 'add') {
                $back['action'] = str_replace('Import', '', $this->_table->alias());
            } elseif ($this->_table->request->params['pass'][0] == 'results') {
                $back['action'] = $this->_table->alias();
                $back[0] = 'add';
            };

            if (!array_key_exists($back['action'], $this->_table->ControllerAction->models)) {
                $back['action'] = str_replace('Institution', '', $back['action']);
            }
            $toolbarButtons['back']['url'] = array_merge($toolbarButtons['back']['url'], $back);
        } else {
            $toolbarButtons['back']['url']['action'] = 'index';
        }
        unset($toolbarButtons['back']['url'][0]);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $buttons[0]['name'] = '<i class="fa kd-import"></i> ' . __('Import');
    }

    public function beforeAction($event)
    {
        $session = $this->_table->Session;
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        $this->sessionKey = $this->config('plugin').'.'.$this->config('model').'.Import.data';

        $this->_table->ControllerAction->field('plugin', ['visible' => false]);
        $this->_table->ControllerAction->field('model', ['visible' => false]);
        $this->_table->ControllerAction->field('column_name', ['visible' => false]);
        $this->_table->ControllerAction->field('description', ['visible' => false]);
        $this->_table->ControllerAction->field('lookup_plugin', ['visible' => false]);
        $this->_table->ControllerAction->field('lookup_model', ['visible' => false]);
        $this->_table->ControllerAction->field('lookup_column', ['visible' => false]);
        $this->_table->ControllerAction->field('foreign_key', ['visible' => false]);
        $this->_table->ControllerAction->field('is_optional', ['visible' => false]);
        $comment = '* ' . sprintf(__('Format Supported: %s'), implode(', ', $this->config('allowable_file_types')));
        $comment .= '<br/>';
        $comment .= '* ' . sprintf(__('File size should not be larger than %s.'), $this->bytesToReadableFormat($this->config('max_size')));
        $comment .= '<br/>';
        $comment .= '* ' . sprintf(__('Recommended Maximum Records: %s'), $this->config('max_rows'));

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

    public function validationImportFile(Validator $validator)
    {
        $validator = $this->_table->validationDefault($validator);
        $supportedFormats = array_values(Hash::flatten($this->_fileTypesMap));
        $maxSize = $this->config('max_size') < $this->file_upload_max_size() ? $this->config('max_size') : $this->file_upload_max_size();

        return $validator
            ->add('select_file', 'ruleUploadFileError', [
                'rule' => 'uploadError',
                'last' => true,
                'message' => $this->_table->getMessage('Import.upload_error') // will be overwritten in addBeforeSave if message exists
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

    /**
     * addBeforePatch turns off the validation when patching entity with post data, and check the uploaded file size.
     * @param Event       $event   [description]
     * @param Entity      $entity  [description]
     * @param ArrayObject $data    [description]
     * @param ArrayObject $options [description]
     *
     * Refer to phpFileUploadErrors below for the list of file upload errors defination.
     */
    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $options['validate'] = 'importFile';
    }

    /**
     * Actual Import business logics reside in this function
     * @param  Event        $event  Event object
     * @param  Entity       $entity Entity object containing the uploaded file parameters
     * @param  ArrayObject  $data   Event object
     * @return Response             Response object
     */
    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        /**
         * currently, extending the max execution time for individual scripts from the default of 30 seconds to 180 seconds
         * to avoid server timed out issue.
         * to be reviewed...
         */
        ini_set('max_execution_time', 3600);
        /**
         */

        return function ($model, $entity) {
            $errors = $entity->errors();
            if (!empty($errors)) {
                // set error message for php file upload errors
                $fileError = Hash::get($entity->invalid(), 'select_file.error');
                if (!empty($fileError)) {
                    $errorMessage = $model->getMessage("fileUpload.$fileError");
                    if ($errorMessage != '[Message Not Found]') {
                        $entity->errors('select_file', $errorMessage, true);
                    }
                }

                return false;
            }

            $systemDateFormat = TableRegistry::get('Configuration.ConfigItems')->value('date_format');

            $mapping = $this->getMapping();
            $header = $this->getHeader($mapping);
            $columns = $this->getColumns($mapping);
            $totalColumns = count($columns);
            $lookup = $this->getCodesByMapping($mapping);

            $fileObj = $entity->select_file;
            $uploadedName = $fileObj['name'];
            $uploaded = $fileObj['tmp_name'];
            $inputFileType = PHPExcel_IOFactory::identify($uploaded);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($uploaded);

            $totalImported = 0;
            $totalUpdated = 0;
            $importedUniqueCodes = new ArrayObject;
            $dataFailed = [];
            $dataPassed = [];
            $extra = new ArrayObject(['lookup' => [], 'entityValidate' => true]);

            $activeModel = TableRegistry::get($this->config('plugin').'.'.$this->config('model'));
            $activeModel->addBehavior('DefaultValidation');

            $maxRows = $this->config('max_rows');
            $maxRows = $maxRows + 2;
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            if ($highestRow > $maxRows) {
                $entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max_rows')], true);
                return false;
            }

            ($this->isCustomText()) ? $this->recordHeader = 3 : $this->recordHeader = 2;

            if ($highestRow == $this->recordHeader) {
                $entity->errors('select_file', [$this->getExcelLabel('Import', 'no_answers')], true);
                return false;
            }

            ($this->isCustomText()) ? $startCheck = 3 : $startCheck = 2;

            for ($row = $startCheck; $row <= $highestRow; ++$row) {
                if ($row == $this->recordHeader) { // skip header but check if the uploaded template is correct
                    if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
                        $entity->errors('select_file', [$this->getExcelLabel('Import', 'wrong_template')], true);
                        return false;
                    }
                    continue;
                }
                if ($row == $highestRow) { // if $row == $highestRow, check if the row cells are really empty, if yes then end the loop
                    if ($this->checkRowCells($sheet, $totalColumns, $row) === false) {
                        break;
                    }
                }

                // check for unique record
                $tempRow = new ArrayObject;
                $rowInvalidCodeCols = new ArrayObject;
                $params = [$sheet, $row, $columns, $tempRow, $importedUniqueCodes, $rowInvalidCodeCols];
                $this->dispatchEvent($this->_table, $this->eventKey('onImportCheckUnique'), 'onImportCheckUnique', $params);

                // for each columns
                $references = [
                    'sheet'=>$sheet,
                    'mapping'=>$mapping,
                    'columns'=>$columns,
                    'lookup'=>$lookup,
                    'totalColumns'=>$totalColumns,
                    'row'=>$row,
                    'activeModel'=>$activeModel,
                    'systemDateFormat'=>$systemDateFormat,
                ];

                $originalRow = new ArrayObject;
                $checkCustomColumn = new ArrayObject;
                $extra['entityValidate'] = true;
                $rowPass = $this->_extractRecord($references, $tempRow, $originalRow, $rowInvalidCodeCols, $extra);

                if ($rowPass !== NULL && !$rowPass) {
                    $activeModel->setImportValidationFailed();
                }else{
                    $activeModel->setImportValidationPassed();
                }

                $tempRow = $tempRow->getArrayCopy();
                // $tempRow['entity'] must exists!!! should be set in individual model's onImportCheckUnique function
                if (!isset($tempRow['entity'])) {
                    $tableEntity = $activeModel->newEntity();
                } else {
                    $tableEntity = $tempRow['entity'];
                    unset($tempRow['entity']);
                }

                if ($extra['entityValidate'] == true) {
                    // added for POCOR-4577 import staff leave for workflow related record to save the transition record
                    $tempRow['action_type'] = 'imported';
                    $activeModel->patchEntity($tableEntity, $tempRow);
                }

                $errors = $tableEntity->errors();
                $rowInvalidCodeCols = $rowInvalidCodeCols->getArrayCopy();

                // to-do: saving of entity into table with composite primary keys (Exam Results) give wrong isNew value
                $isNew = $tableEntity->isNew();

                if ($extra['entityValidate'] == true) {
                    // POCOR-4258 - shifted saving model before updating errors to implement try-catch to catch database errors
                    try {
                        $newEntity = $activeModel->save($tableEntity);
                    } catch (Exception $e) {
                        $newEntity = false;
                        $message = $e->getMessage();
                        $matches = '';
                        // regex to find values in 2 quotes without the quotes
                        if (preg_match("/(?<=\')(.*?)+(?=\')/", $message, $matches)) {
                            $errorRow = $matches[0];
                        } else {
                            $errorRow = 'row' . $row;
                        }
                        $rowInvalidCodeCols[$errorRow] = $message;
                    }

                    if ($newEntity) {
                        if ($isNew) {
                            $totalImported++;
                        } else {
                            $totalUpdated++;
                        }
                        // update importedUniqueCodes either a single key or composite primary keys
                        $this->dispatchEvent($this->_table, $this->eventKey('onImportUpdateUniqueKeys'), 'onImportUpdateUniqueKeys', [$importedUniqueCodes, $tableEntity]);
                    }
                }

                if (!empty($rowInvalidCodeCols) || $errors) { // row contains error or record is a duplicate based on unique key(s)
                    $rowCodeError = '';
                    $rowCodeErrorForExcel = [];
                    if (!empty($errors)) {
                        foreach ($errors as $field => $arr) {
                            if (in_array($field, $columns)) {
                                $fieldName = $this->getExcelLabel($activeModel->registryAlias(), $field);
                                $rowCodeError .= '<li>' . $fieldName . ' => ' . $arr[key($arr)] . '</li>';
                                $rowCodeErrorForExcel[] = $fieldName . ' => ' . $arr[key($arr)];
                            } else {
                                if (in_array($field, ['student_name', 'staff_name'])) {
                                    $rowCodeError .= '<li>' . $arr[key($arr)] . '</li>';
                                    $rowCodeErrorForExcel[] = $arr[key($arr)];
                                }
                                $model->log('@ImportBehavior line ' . __LINE__ . ': ' . $activeModel->registryAlias() .' -> ' . $field . ' => ' . $arr[key($arr)], 'info');
                            }
                        }
                    }
                    if (!empty($rowInvalidCodeCols)) {
                        foreach ($rowInvalidCodeCols as $field => $errMessage) {
                            $fieldName = $this->getExcelLabel($activeModel->registryAlias(), $field);
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
                    $clonedEntity->virtualProperties([]);

                    $tempPassedRecord = [
                        'row_number' => $row,
                        'data' => $this->_getReorderedEntityArray($clonedEntity, $columns, $originalRow, $systemDateFormat)
                    ];
                    $tempPassedRecord = new ArrayObject($tempPassedRecord);

                    // individual import models can specifically define the passed record values which are to be exported
                    $params = [$clonedEntity, $columns, $tempPassedRecord, $originalRow];
                    $this->dispatchEvent($this->_table, $this->eventKey('onImportSetModelPassedRecord'), 'onImportSetModelPassedRecord', $params);

                    $dataPassed[] = $tempPassedRecord->getArrayCopy();
                }

                // $model->log('ImportBehavior: '.$row.' records imported', 'info');
            } // for ($row = 1; $row <= $highestRow; ++$row)

            $session = $this->_table->Session;
            $completedData = [
                'uploadedName' => $uploadedName,
                'dataFailed' => $dataFailed,
                'totalImported' => $totalImported,
                'totalUpdated' => $totalUpdated,
                'totalRows' => count($dataFailed) + $totalImported + $totalUpdated,
                'header' => $header,
                'failedExcelFile' => $this->_generateDownloadableFile($dataFailed, 'failed', $header, $systemDateFormat),
                'passedExcelFile' => $this->_generateDownloadableFile($dataPassed, 'passed', $header, $systemDateFormat),
                'executionTime' => (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])
            ];
            $session->write($this->sessionKey, $completedData);
            return $model->controller->redirect($this->_table->ControllerAction->url('results'));
        };
    }


/******************************************************************************************************************
**
** Actions
**
******************************************************************************************************************/
    public function template()
    {
        $folder = $this->prepareDownload();
        $modelName = $this->config('model');
        $modelName = str_replace(' ', '_', Inflector::humanize(Inflector::tableize($modelName)));
        // Do not lcalize file name as certain non-latin characters might cause issue
        $excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelName);
        $excelPath = $folder . DS . $excelFile;

        $mapping = $this->getMapping();
        $header = $this->getHeader($mapping);
        $dataSheetName = $this->getExcelLabel('general', 'data');

        $objPHPExcel = new \PHPExcel();

        $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $header, '');

        $this->setCodesDataTemplate($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save($excelPath);

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
            // $session->delete($this->sessionKey);
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
            $this->_table->controller->set('data', $this->_table->newEntity());
            $this->_table->ControllerAction->renderView('/ControllerAction/view');
        } else {
            return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
        }
    }


/******************************************************************************************************************
**
** Import Functions
**
******************************************************************************************************************/
    public function beginExcelHeaderStyling($objPHPExcel, $dataSheetName, $title = '')
    {
        if (empty($title)) {
            $title = $dataSheetName;
        }
        $activeSheet = $objPHPExcel->getActiveSheet();
        $activeSheet->setTitle($dataSheetName);

        $gdImage = imagecreatefromjpeg(ROOT . DS . 'plugins' . DS . 'Import' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.jpg');
        $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
        $objDrawing->setName('OpenEMIS Logo');
        $objDrawing->setDescription('OpenEMIS Logo');
        $objDrawing->setImageResource($gdImage);
        $objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
        $objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
        $objDrawing->setHeight(100);
        $objDrawing->setCoordinates('A1');
        $objDrawing->setWorksheet($activeSheet);

        $activeSheet->getRowDimension(1)->setRowHeight(75);
        $activeSheet->getRowDimension(2)->setRowHeight(25);

        ($this->isCustomText()) ? $activeSheet->getRowDimension(3)->setRowHeight(25) : '';

        $activeSheet->setCellValue("C1", $title);
    }

    public function endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, $lastRowToAlign = 2, $applyFillFontSetting = [], $applyCellBorder = [])
    {
        if (empty($applyFillFontSetting)) {
            ($this->isCustomText()) ? $applyFillFontSetting = ['s'=>3, 'e'=>3] : $applyFillFontSetting = ['s'=>2, 'e'=>2];
        }

        if (empty($applyCellBorder)) {
            ($this->isCustomText()) ? $applyCellBorder = ['s'=>3, 'e'=>3] : $applyCellBorder = ['s'=>2, 'e'=>2];
        }

        $activeSheet = $objPHPExcel->getActiveSheet();

        // merging should start from cell C1 instead of A1 since the title is already set in cell C1 in beginExcelHeaderStyling()
        if (!in_array($headerLastAlpha, ['A','B','C'])) {
            $activeSheet->mergeCells('C1:'. $headerLastAlpha .'1');
        }

        $activeSheet->getStyle("A1:" . $headerLastAlpha . "1")->getFont()->setBold(true)->setSize(16);
        $style = [
            'alignment' => [
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];
        $activeSheet->getStyle("A1:". $headerLastAlpha . $lastRowToAlign)->applyFromArray($style)->getFont()->setBold(true);
        $activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('6699CC'); // OpenEMIS Core product color
        $activeSheet->getStyle("A". $applyCellBorder['s'] .":". $headerLastAlpha . $applyCellBorder['e'])->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
    }

    public function setImportDataTemplate($objPHPExcel, $dataSheetName, $header, $type)
    {
        $objPHPExcel->setActiveSheetIndex(0);
        // column_name in import_mapping that have date format, after the humanize
        // to compare, to know that the column are date format.
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

        ($this->isCustomText()) ? $lastRowToAlign = 3 : $lastRowToAlign = 2;

        $activeSheet = $objPHPExcel->getActiveSheet();

        if ($this->isCustomText()) {
            if (!empty($type) && $type == 'failed') { //if failed, then need to merge 4 columns instead of 3
                $activeSheet->mergeCells('A2:D2');
            } else if (empty($type) || $type != 'failed') {
                $activeSheet->mergeCells('A2:C2');
            }

            $activeSheet->setCellValue("A2", $this->customText);
        }

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName,  __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) .' '. $dataSheetName);

        $currentRowHeight = $activeSheet->getRowDimension($lastRowToAlign)->getRowHeight();

        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . $lastRowToAlign, $value);
            $activeSheet->getColumnDimension($alpha)->setAutoSize(true);
            if (strlen($value)<50) {
                // if the $value is in $dateHeader array, it is a date format.
                if (in_array($value, $dateHeader)) {
                    $activeSheet->getStyle($alpha)
                        ->getNumberFormat()
                        ->setFormatCode('dd/mm/yyyy');
                }                
            } else {
                $currentRowHeight = $this->suggestRowHeight(strlen($value), $currentRowHeight);
                $activeSheet->getRowDimension($lastRowToAlign)->setRowHeight($currentRowHeight);
                $activeSheet->getStyle($alpha . $lastRowToAlign)->getAlignment()->setWrapText(true);
            }
        }
        $headerLastAlpha = $this->getExcelColumnAlpha(count($header)-1);

        $this->endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, $lastRowToAlign);
    }

    public function suggestRowHeight($stringLen, $currentRowHeight)
    {
        if ($stringLen>=50) {
            $multiplier = $stringLen % 50;
        } else {
            $multiplier = 0;
        }
        $rowHeight = (3 * $multiplier) + 25;
        if ($rowHeight > $currentRowHeight && $rowHeight<=250) {
            $currentRowHeight = $rowHeight;
        }
        return $currentRowHeight;
    }

    public function setCodesDataTemplate($objPHPExcel)
    {
        $sheetName = __('References');
        $objPHPExcel->createSheet(1);
        $objPHPExcel->setActiveSheetIndex(1);

        $this->beginExcelHeaderStyling($objPHPExcel, $sheetName);

        $objPHPExcel->getActiveSheet()->getRowDimension(3)->setRowHeight(25);

        if (method_exists($this->_table, 'excelGetCodesData')) {
            $codesData = $this->_table->excelGetCodesData();
        } else {
            $codesData = $this->excelGetCodesData($this->_table);
        }
        $lastColumn = -1;
        $currentRowHeight = $objPHPExcel->getActiveSheet()->getRowDimension(2)->getRowHeight();
        foreach ($codesData as $columnOrder => $modelArr) {
            $modelData = $modelArr['data'];
            $firstColumn = $lastColumn + 1;
            $lastColumn = $firstColumn + count($modelArr['data'][0]) - 1;

            $objPHPExcel->getActiveSheet()->mergeCells($this->getExcelColumnAlpha($firstColumn) ."2:". $this->getExcelColumnAlpha($lastColumn) ."2");
            $objPHPExcel->getActiveSheet()->setCellValue($this->getExcelColumnAlpha($firstColumn) ."2", $modelArr['sheetName']);
            if (strlen($modelArr['sheetName'])<50) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($this->getExcelColumnAlpha($firstColumn))->setAutoSize(true);
            } else {
                // $objPHPExcel->getActiveSheet()->getColumnDimension( $this->getExcelColumnAlpha($firstColumn) )->setWidth(35);
                $currentRowHeight = $this->suggestRowHeight(strlen($modelArr['sheetName']), $currentRowHeight);
                $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight($currentRowHeight);
                $objPHPExcel->getActiveSheet()->getStyle($this->getExcelColumnAlpha($firstColumn) . "2")->getAlignment()->setWrapText(true);
            }

            foreach ($modelData as $index => $sets) {
                foreach ($sets as $key => $value) {
                    $alpha = $this->getExcelColumnAlpha(($key + $firstColumn));
                    $objPHPExcel->getActiveSheet()->setCellValue($alpha . ($index + 3), $value);
                    $objPHPExcel->getActiveSheet()->getColumnDimension($alpha)->setAutoSize(true);
                }
            }

            if (count($modelData)>1 && !array_key_exists('noDropDownList', $modelArr)) {
                $lookupColumn = $firstColumn + intval($modelArr['lookupColumn']) - 1;
                $alpha = $this->getExcelColumnAlpha($columnOrder - 1);
                $lookupColumnAlpha = $this->getExcelColumnAlpha($lookupColumn);
                ($this->isCustomText()) ? $lookupStart = 4 : $lookupStart = 3;
                for ($i=$lookupStart; $i < 103; $i++) {
                    $objPHPExcel->setActiveSheetIndex(0);
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($alpha . $i)->getDataValidation();
                    $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $listLocation = "'". $sheetName ."'!$". $lookupColumnAlpha ."$4:$". $lookupColumnAlpha ."$". (count($modelData)+2);
                    $objValidation->setFormula1($listLocation);
                }
                $objPHPExcel->setActiveSheetIndex(1);
            }
        }

        if ($lastColumn > -1) { //if got no reference data.
            $headerLastAlpha = $this->getExcelColumnAlpha($lastColumn);
            $objPHPExcel->getActiveSheet()->getStyle( "A2:" . $headerLastAlpha . "2" )->getFont()->setBold(true)->setSize(12);
            $this->endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, 3, ['s'=>3, 'e'=>3], ['s'=>2, 'e'=>3] );
        }
    }

    /**
     * Set a record columns value based on what is being saved in the table.
     * @param  Entity $entity           Cloned entity. The actual entity is not saved yet but already validated but we are using a cloned entity in case it might be messed up.
     * @param  Array  $columns          Target Model columns defined in import_mapping table.
     * @param  string $systemDateFormat System Date Format which varies across deployed environments.
     * @return Array                    The columns value that will be written to a downloadable excel file.
     */
    protected function _getReorderedEntityArray(Entity $entity, array $columns, ArrayObject $originalRow, $systemDateFormat)
    {
        $array = [];
        foreach ($columns as $col => $property) {
            /*
            //if value in datetime format, then format it according to the systemDateFormat
            $value = ( $entity->{$property} instanceof DateTimeInterface ) ? $entity->{$property}->format( $systemDateFormat ) : $originalRow[$col];
            */
            $value = $originalRow[$col];
            $array[] = $value;
        }
        return $array;
    }

    private function _generateDownloadableFile($data, $type, $header, $systemDateFormat)
    {
        if (!empty($data)) {
            $downloadFolder = $this->prepareDownload();
            // Do not lcalize file name as certain non-latin characters might cause issue
            $excelFile = sprintf('OpenEMIS_Core_Import_%s_%s_%s.xlsx', $this->config('model'), ucwords($type), time());
            $excelPath = $downloadFolder . DS . $excelFile;

            $newHeader = $header;
            if ($type == 'failed') {
                $newHeader[] = $this->getExcelLabel('general', 'errors');
            }
            $dataSheetName = $this->getExcelLabel('general', 'data');

            $objPHPExcel = new \PHPExcel();

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
                    $alpha = $this->getExcelColumnAlpha($key);
                    $activeSheet->setCellValue($alpha . ($index + $rowData), $value);
                    $activeSheet->getColumnDimension($alpha)->setAutoSize(true);

                    if ($key==(count($values)-1) && $type == 'failed') {
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
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $objWriter->save($excelPath);

            $downloadUrl = $this->_table->ControllerAction->url('download' . ucwords($type));
            $downloadUrl[] = $excelFile;
            $excelFile = $downloadUrl;
        } else {
            $excelFile = null;
        }

        return $excelFile;
    }

    /**
     * Get the string representation of a column based on excel grid
     * @param  mixed $column_number either an integer or a string named as "last"
     * @return string               the string representation of a column based on excel grid
     * @todo  the alpha string array values should be auto-generated instead of hard-coded
     */
    public function getExcelColumnAlpha($column_number)
    {
        return PHPExcel_Cell::stringFromColumnIndex($column_number);
    }

    /**
     * Check if all the columns in the row is not empty
     * @param  WorkSheet $sheet      The worksheet object
     * @param  integer $totalColumns Total number of columns to be checked
     * @param  integer $row          Row number
     * @return boolean               the result to be return as true or false
     */
    public function checkRowCells($sheet, $totalColumns, $row)
    {
        $cellsState = [];
        for ($col=0; $col < $totalColumns; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $value = $cell->getValue();
            if (empty($value)) {
                $cellsState[] = false;
            } else {
                $cellsState[] = true;
            }
        }
        return in_array(true, $cellsState);
    }

    /**
     * Check if the uploaded file is the correct template by comparing the headers extracted from mapping table
     * and first row of the uploaded file record
     * @param  array        $header         The headers extracted from mapping table according to active model
     * @param  WorkSheet    $sheet          The worksheet object
     * @param  integer      $totalColumns   Total number of columns to be checked
     * @param  integer      $row            Row number
     * @return boolean                      the result to be return as true or false
     */
    public function isCorrectTemplate($header, $sheet, $totalColumns, $row)
    {
        $cellsValue = [];
        for ($col=0; $col < $totalColumns; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $cellsValue[] = $cell->getValue();
        }
        return $header === $cellsValue;
    }

    public function getMapping()
    {
        $model = $this->_table;
        $mapping = $model->find('all')
            ->where([
                $model->aliasField('model') => $this->config('plugin').'.'.$this->config('model')
            ])
            ->order($model->aliasField('order'))
            ->toArray();
        return $mapping;
    }

    protected function getHeader($mapping = [])
    {
        $model = $this->_table;
        if (empty($mapping)) {
            $mapping = $this->getMapping($model);
        }

        $header = [];
        foreach ($mapping as $key => $value) {
            if ($value->foreign_key == self::CUSTOM) { //custom then need check the default value.

                $customDataSource = $value->lookup_column;
                $customHeaderData = new ArrayObject;

                $params = [$customDataSource, $customHeaderData];
                $this->dispatchEvent($this->_table, $this->eventKey('onImportCustomHeader'), 'onImportCustomHeader', $params);

                $label = $customHeaderData[1]; //column name

                if ($customHeaderData[0]) { //show description or not
                    $label .= ' ' . __($value->description);
                }
            } else {
                $column = $value->column_name;

                $label = $this->getExcelLabel('Imports', $value->lookup_model);

                if (empty($label)) {
                    $label = $this->getExcelLabel($value->model, $column);
                }

                //to remove "lookup_model" from included into header (POCOR-3256)
                if (($value->lookup_model == 'Users') && ($value->lookup_column == 'openemis_no')) {
                    $label = '';
                }

                // POCOR-3916 directories > import user showed 2 area administrative code, due to showing the lookup model.
                if ($value->lookup_model == 'AreaAdministratives') {
                    $label = $this->getExcelLabel($value->model, $column);
                }
                // end POCOR-3916

                if (!empty($value->description)) {
                    //POCOR-5913 starts 
                    if($value->model == 'Student.StudentGuardians') {
                        $label =  __($value->description);   
                    }else{
                        $label .= ' ' . __($value->description);     
                    }
                    //POCOR-5913 ends 
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
            $mapping = $this->getMapping($model);
        }

        foreach ($mapping as $key => $value) {
            $column = $value->column_name;
            $columns[] = $column;
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
                $lookupModelObj = TableRegistry::get($lookupModel, ['className' => $lookupPlugin . '.' . $lookupModel]);

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
                $model->aliasField('model') => $this->config('plugin').'.'.$this->config('model'),
                $model->aliasField('foreign_key') . ' IN' => [self::FIELD_OPTION, self::DIRECT_TABLE, self::NON_TABLE_LIST]
            ])
            ->order($model->aliasField('order'))
            ->toArray()
            ;

        $data = new ArrayObject;
        foreach ($mapping as $row) {
            $foreignKey = $row->foreign_key;
            $lookupPlugin = $row->lookup_plugin;
            $lookupModel = $row->lookup_model;
            $lookupColumn = $row->lookup_column;
            $mappingModel = $row->model;

            $translatedCol = $this->getExcelLabel($model, $lookupColumn);

            $sheetName = trim($this->getExcelLabel($row->model, $row->column_name));
            $data[$row->order] = [
                'data'=>[],
                'sheetName'=>$sheetName
            ];
            $modelData = [];
            if ($foreignKey == self::FIELD_OPTION) {
                if (TableRegistry::exists($lookupModel)) {
                    $relatedModel = TableRegistry::get($lookupModel);
                } elseif($mappingModel == 'Student.Extracurriculars' && $lookupModel == 'Users') {
                    $institutionId = 0;
                    $session = $this->_table->Session;
                    if ($session->check('Institution.Institutions.id')) {
                        $institutionId = $session->read('Institution.Institutions.id');
                    }
                    
                    $relatedModel = TableRegistry::get($lookupModel, ['className' => $lookupPlugin . '\Model\Table\\' . $lookupModel.'Table'])->findStudents($institutionId);
                }else{
                    $relatedModel = TableRegistry::get($lookupModel, ['className' => $lookupPlugin . '\Model\Table\\' . $lookupModel.'Table']);
                }
                
                if($mappingModel == 'Student.Extracurriculars' && $lookupModel == 'Users') {
                    
                    $emptyCodeRecords = $relatedModel;
                    $modelData = $relatedModel;
                }else{
                    $modelData = $relatedModel->getList($relatedModel->find());
                    $emptyCodeRecords = $modelData;
                    $emptyCodeRecords = $emptyCodeRecords->stopWhen(function ($record, $key) {
                        return !empty($record->national_code);
                    })->toArray();
                    
                    $modelData = $modelData->toArray();
                }
                
                $data[$row->order]['lookupColumn'] = 2;
                $data[$row->order]['data'][] = [__('Name'), $translatedCol];
                
                if (!empty($modelData)) {
                    foreach ($modelData as $record) {
                        if (count($emptyCodeRecords)<1) {
                            $data[$row->order]['data'][] = [$record->name, $record->national_code];
                        } else {
                            $data[$row->order]['data'][] = [$record->name, $record->id];
                        }
                    }
                }
            } elseif ($foreignKey == self::DIRECT_TABLE || $foreignKey == self::NON_TABLE_LIST) {
                $params = [$lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, $data, $row->order];
                $this->dispatchEvent($this->_table, $this->eventKey('onImportPopulate'.$lookupModel.'Data'), 'onImportPopulate'.$lookupModel.'Data', $params);
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
            // delete all old files that are more than one hour old
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
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($excelPath));
        echo file_get_contents($excelPath);
    }

    public function getExcelLabel($module, $columnName)
    {
        $translatedCol = '';
        if ($module instanceof Table) {
            $module = $module->alias();
        }
        $dotPost = strpos($module, '.');
        if ($dotPost > -1) {
            $module = substr($module, ($dotPost + 1));
        }
        if (!empty($this->labels) && isset($this->labels[$module]) && isset($this->labels[$module][$columnName])) {
            $translatedCol = $this->labels[$module][$columnName];
        } else {
            if ($module=='Import') {
                $translatedCol = $this->_table->getMessage($module.'.'.$columnName);
            } else {
                /**
                 * $language should provide the current selected locale language
                 */
                $language = '';
                $translatedCol = $this->_table->onGetFieldLabel(new Event($this), $module, $columnName, $language);
                if (empty($translatedCol) || ($translatedCol==$columnName && $columnName!='FTE')) { // checking for column name FTE should not be hard-coded here, do revisit this in the future
                    $translatedCol = Inflector::humanize(Inflector::singularize(Inflector::tableize($columnName)));
                }
            }
            // saves label in runtime array to avoid multiple calls to the db or cache
            $this->labels[$module][$columnName] = $translatedCol;
        }
        return __($translatedCol);
    }

    /**
     * Extract the values in every columns
     * @param  array        $references         the variables/arrays in this array are for references
     * @param  ArrayObject  $tempRow            for holding converted values extracted from the excel sheet on a per row basis
     * @param  ArrayObject  $originalRow        for holding the original value extracted from the excel sheet on a per row basis
     * @param  ArrayObject  $rowInvalidCodeCols for holding error messages found on option field columns
     * @return boolean                          returns whether the row being checked pass option field columns check
     */
    protected function _extractRecord($references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols, ArrayObject $extra)
    {
        
        // $references = [$sheet, $mapping, $columns, $lookup, $totalColumns, $row, $activeModel, $systemDateFormat];
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
            $cell = $sheet->getCellByColumnAndRow($col, $row); 

            if (self::timeTwelvehoursValidator($cell->getFormattedValue()) == 1) {
                $cell->getStyle()->getNumberFormat()->setFormatCode('h:mm:ss');
                $originalValue = $cell->getFormattedValue();
            } else if (PHPExcel_Shared_Date::isDateTime($cell)) {
                $cell->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $originalValue = $cell->getFormattedValue();
            } else {
                $originalValue = $cell->getValue();
            }
            
            $cellValue = $originalValue;
            // need to understand this check
            // @hanafi - this might be for type casting a double or boolean value to a string to avoid data loss when assigning
            // them to $val. Example: the value of latitude, "1.05647" might become "1" if not casted as a string type.
            if (gettype($cellValue) == 'double' || gettype($cellValue) == 'boolean') {
                $cellValue = (string) $cellValue;
            }
            // need to understand the above check

            $excelMappingObj = $mapping[$col];
            $foreignKey = $excelMappingObj->foreign_key;
            $lookupPlugin = $excelMappingObj->lookup_plugin;
            $lookupModel = $excelMappingObj->lookup_model;
            $lookupColumn = $excelMappingObj->lookup_column;
            $lookupColumnName = $excelMappingObj->column_name;
            $mappingModel = $excelMappingObj->model;

            if($mappingModel == 'Student.Extracurriculars'  && $lookupColumnName == 'openemis_no'){
                $columnName = 'security_user_id';
                $securityUser = TableRegistry::get('User.Users')->find()->where(['openemis_no' => $originalValue])->first();
                if(!$securityUser) {
                    $rowInvalidCodeCols[$columnName] = __('OpenEMIS ID is not valid');
                    $rowPass = false;
                    $extra['entityValidate'] = false;
                }
                $originalRow[$col] = $securityUser->id;
                $cellValue = $securityUser->id;
            }else if($mappingModel == 'Student.StudentGuardians'  && $lookupColumnName == 'guardian_id' && $lookupColumn == 'openemis_no' && !empty($originalValue)){ //POCOR-5913 starts
                $i=1;
                $columnName = 'guardian_id';
                $userIdentities = TableRegistry::get('user_identities');
                $identityTypes = TableRegistry::get('identity_types');
                $User = TableRegistry::get('security_users');
                $securityUser = $User
                                    ->find()
                                    ->select([
                                        'id' => $User->aliasField('id'), 
                                        'openemis_id' => $User->aliasField('openemis_no'),
                                        'user_identities_id' => $userIdentities->aliasField('id'),
                                        'identity_type_id' => $userIdentities->aliasField('identity_type_id'),
                                        'number' => $userIdentities->aliasField('number'),
                                        'security_user_id' => $userIdentities->aliasField('security_user_id'),
                                        'identityTypes_id' => $identityTypes->aliasField('id'),
                                        'default' => $identityTypes->aliasField('default')
                                    ])
                                    ->leftJoin(
                                        [$userIdentities->alias() => $userIdentities->table()],
                                        [$userIdentities->aliasField('security_user_id = ') .$User->aliasField('id')]
                                    )
                                    ->leftJoin(
                                        [$identityTypes->alias() => $identityTypes->table()],
                                        [$identityTypes->aliasField('id =') .$userIdentities->aliasField('identity_type_id')]
                                    )
                                    ->where([
                                        'OR'=>[
                                            $User->aliasField('openemis_no') => $originalValue,
                                            'AND'=>[
                                                $userIdentities->aliasField('number') => $originalValue,
                                                $identityTypes->aliasField('default') => 1
                                            ]
                                        ]

                                    ])
                                    ->first();
                if(!$securityUser) {
                    $rowInvalidCodeCols[$columnName] = __('OpenEMIS ID is not valid');
                    $rowPass = false;
                    $extra['entityValidate'] = false;

                    $originalRow[$col] = $originalValue;
                    $cellValue = $originalValue;
                }else{
                    $originalRow[$col] = $securityUser->id;
                    $cellValue = $securityUser->id;
                }
            }else if($mappingModel == 'Student.StudentGuardians'  && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number' && !empty($originalValue)){ 
                if($i == 1){
                    break;
                }
                $k = 1;
                $columnName = 'guardian_id';
                $userIdentities = TableRegistry::get('user_identities');
                $identityTypes = TableRegistry::get('identity_types');
                $User = TableRegistry::get('security_users');
                $securityUser = $User
                                    ->find()
                                    ->select([
                                        'id' => $User->aliasField('id'), 
                                        'openemis_id' => $User->aliasField('openemis_no'),
                                        'user_identities_id' => $userIdentities->aliasField('id'),
                                        'identity_type_id' => $userIdentities->aliasField('identity_type_id'),
                                        'number' => $userIdentities->aliasField('number'),
                                        'security_user_id' => $userIdentities->aliasField('security_user_id'),
                                        'identityTypes_id' => $identityTypes->aliasField('id'),
                                        'default' => $identityTypes->aliasField('default')
                                    ])
                                    ->leftJoin(
                                        [$userIdentities->alias() => $userIdentities->table()],
                                        [$userIdentities->aliasField('security_user_id = ') .$User->aliasField('id')]
                                    )
                                    ->leftJoin(
                                        [$identityTypes->alias() => $identityTypes->table()],
                                        [$identityTypes->aliasField('id =') .$userIdentities->aliasField('identity_type_id')]
                                    )
                                    ->where([
                                        'OR'=>[
                                            $User->aliasField('openemis_no') => $originalValue,
                                            'AND'=>[
                                                $userIdentities->aliasField('number') => $originalValue,
                                                $identityTypes->aliasField('default') => 1
                                            ]
                                        ]

                                    ])
                                    ->first();
                if(!$securityUser) {
                    $rowInvalidCodeCols[$columnName] = __('Identity number is not valid');
                    $rowPass = false;
                    $extra['entityValidate'] = false;

                    $originalRow[$col] = $originalValue;
                    $cellValue = $originalValue;
                }else{
                    $originalRow[$col] = $securityUser->id;
                    $cellValue = $securityUser->id;
                }
                //POCOR-5913 ends
            }else{
                $columnName = $columns[$col];
                $originalRow[$col] = $originalValue;
            }

            //POCOR-5913 starts
            if($mappingModel == 'Student.StudentGuardians'  && $lookupColumnName == 'guardian_id' && $lookupColumn == 'openemis_no' && empty($originalValue)){
                $i=0;
                continue;
            }else if($mappingModel == 'Student.StudentGuardians'  && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number' && empty($originalValue)){
                if($i==0){
                    /*$columnName = 'guardian_id';
                    $rowInvalidCodeCols[$columnName] = __('Please enter either OpenEMIS ID or Identity number for guardian');
                    $rowPass = false;
                    $extra['entityValidate'] = false;*/
                }else{
                    continue;
                }
            }
            //POCOR-5913 ends
            $val = $cellValue;

            $datePattern = "/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}/"; // dd/mm/yyyy

            // skip a record column which has value defined earlier before this function is called
            // example; openemis_no
            // but if the value is 0, it will still proceed
            // example; class for importing students into an institution default value is 0
            if (isset($tempRow[$columnName]) && !empty($tempRow[$columnName]) && $tempRow[$columnName]!==0) {
                continue;
            }
            if (!empty($val)) {
                $columnAttr = $activeModel->schema()->column($columnName);
                if ($columnAttr['type'] == 'date') { // checking the main table schema data type
                    $originalRow[$col] = $val;

                    if (!empty($val) && preg_match($datePattern, $val)) {
                        $val = trim($val); // POCOR-4251 trim the whitespace on the date
                        $split = explode('/', $val);
                        $dateObject = new Date();
                        $dateObject->setDate($split[2], $split[1], $split[0]);

                        // compare the date input and new formatted date to cater (31/02/2016 changed to 02/03/2016)
                        if ($val != $dateObject->format('d/m/Y')) {
                            $rowInvalidCodeCols[$columnName] = __('You have entered an invalid date');
                            $rowPass = false;
                            $extra['entityValidate'] = false;
                        } else {
                            $originalRow[$col] = $dateObject->format('d/m/Y');
                        }
                    } else {
                        // string input without the correct format (not dd/mm/yyyy)
                        $rowInvalidCodeCols[$columnName] = __('You have entered an invalid date');
                        $rowPass = false;
                        $extra['entityValidate'] = false;
                    }
                }
            }
            $translatedCol = $this->getExcelLabel($activeModel->alias(), $columnName);
            $columnDescription = strtolower($mapping[$col]->description);
            $isOptional = $mapping[$col]->is_optional;
            if (!$isOptional) {
                $isOptional = substr_count($columnDescription, 'not required');
            }


            if ($foreignKey == self::FIELD_OPTION) {
                if (!empty($cellValue)) {
                    if (array_key_exists($cellValue, $lookup[$col])) {
                        $val = $lookup[$col][$cellValue]['id'];
                    } else { // if the cell value not found in lookup
                        $rowPass = false;
                        $rowInvalidCodeCols[$columnName] = $this->getExcelLabel('Import', 'value_not_in_list');
                    }
                } else { // if cell is empty
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
                    $excelLookupModel = TableRegistry::get($registryAlias);
                    $this->directTables[$registryAlias] = ['excelLookupModel' => $excelLookupModel];
                }
                $excludeValidation = false;
                if (!empty($cellValue)) {
                    if (isset($extra['lookup'][$excelLookupModel->alias()][$cellValue])) {
                        $record = $extra['lookup'][$excelLookupModel->alias()][$cellValue];
                    } else {
                        //POCOR-5913 starts
                        if($mappingModel == 'Student.StudentGuardians'  && $lookupColumnName == 'guardian_id'){
                            if($securityUser){
                                $cellValue = $securityUser->openemis_id;
                            }else{
                                $cellValue = $originalValue;
                            }

                            if($mappingModel == 'Student.StudentGuardians'  && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number'){
                                $lookupColumn = 'openemis_no';
                            }
                        }//POCOR-5913 ends

                        $lookupQuery = $excelLookupModel->find()->where([$excelLookupModel->aliasField($lookupColumn) => $cellValue]);
                        $record = $lookupQuery->first();
                        $extra['lookup'][$excelLookupModel->alias()][$cellValue] = $record;
                    }
                } else {
                    $columnAttr = $activeModel->schema()->column($columnName);
                    // when blank and the field is not nullable, set cell value as default value setup in database
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
                        $val = $record->id;
                        $this->directTables[$registryAlias][$val] = $record->name;
                    } else {
                        if (!empty($cellValue)) {
                            $rowPass = false;
                            // allow to overwrite from lookup before query event
                            if (!$rowInvalidCodeCols->offsetExists($columnName)) {
                                $rowInvalidCodeCols[$columnName] = $this->getExcelLabel('Import', 'value_not_in_list');
                            }
                        } else {
                            //POCOR-5913 starts
                            if($mappingModel == 'Student.StudentGuardians'  && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number' && empty($originalValue)){
                                if($i==0){
                                    $columnName = 'guardian_id';
                                    $rowInvalidCodeCols[$columnName] = __('Please enter either OpenEMIS ID or Identity number for guardian');
                                    $rowPass = false;
                                    $extra['entityValidate'] = false;
                                }
                            //POCOR-5913 ends
                            }else{
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
                    $getIdEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportGet'.$excelMappingObj->lookup_model.'Id'), 'onImportGet'.$excelMappingObj->lookup_model.'Id', [$cellValue]);
                    $recordId = $getIdEvent->result;
                    if (strlen($recordId) > 0) {
                        $val = $recordId;
                    } else {
                        $rowPass = false;
                        $rowInvalidCodeCols[$columnName] = $this->getExcelLabel('Import', 'value_not_in_list');
                    }
                } else {
                    if (!$isOptional) {
                        $rowPass = false;
                        $rowInvalidCodeCols[$columnName] = __('This field cannot be left empty');
                    }
                }
            } elseif ($foreignKey == self::CUSTOM) { //foreign_key = 4

                $params = [$tempRow, $cellValue];
                $event = $this->dispatchEvent($this->_table, $this->eventKey('onImportCheck'.ucfirst($excelMappingObj->column_name).'Config'), 'onImportCheck'.$excelMappingObj->column_name.'Config', $params);

                if ($event->result !== true) {
                    $rowInvalidCodeCols[$columnName] = __($event->result);
                    $rowPass = false;
                } else {
                    if (!array_key_exists('customColumns', $tempRow)) {
                        $tempRow['customColumns'] = [];
                    }
                    $tempRow['customColumns'][$columnName] = $val;
                }
            }
            if (!$isOptional || ($isOptional && strlen($val) > 0)) {
                $tempRow[$columnName] = $val;
            }
        }
        
        // add condition to check if its importing institutions
        $plugin = $this->config('plugin');
        $model = $this->config('model');

        if ($plugin == 'Institution' && $model == 'Institutions') {
            // if its importing institution will get the userId and super_admin from the session and add the userId and Super_admin to the extracted data.
            $session = $this->_table->Session;
            $userId = $session->read('Auth.User.id');
            $superAdmin = $session->read('Auth.User.super_admin');

            $tempRow['userId'] = $userId;
            $tempRow['superAdmin'] = $superAdmin;
        }
       
        if ($rowPass) {
            $rowPassEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportModelSpecificValidation'), 'onImportModelSpecificValidation', [$references, $tempRow, $originalRow, $rowInvalidCodeCols]);
            $rowPass = $rowPassEvent->result;
        }

        return $rowPass;
    }

   private static function timeTwelvehoursValidator( $time ) {

      
     $regex = '/^(1[012]|[1-9])\:[0-5][0-9]\:[0-5][0-9]\s*[ap]m$/i';
       
      if ( preg_match($regex, $time) ) {
        return true;
      }

      $regex = '/^(1[012]|[1-9])\:[0-5][0-9]\s*[ap]m$/i';
       
      if ( preg_match($regex, $time) ) {
        return true;
      }
        
       $regex = '/^([0-1][0-9])\:[0-5][0-9]\s*[ap]m$/i';
       
      if ( preg_match($regex, $time) ) {
        return true;
      }
      
      $regex = '/^([0-1][0-9])\:[0-5][0-9]\:[0-5][0-9]\s*[ap]m$/i';
       
      if ( preg_match($regex, $time) ) {
        return true;
      }
      
      return false;
}


/******************************************************************************************************************
**
** Miscelleneous Functions
**
******************************************************************************************************************/
    public function getAcademicPeriodByStartDate($date)
    {
        if (empty($date)) {
            // die('date is empty');
            return false;
        }

        if ($date instanceof DateTime) {
            $date = $date->format('Y-m-d');
        }
        $period = $this->AcademicPeriods
                    ->find()
                    ->where([
                        "date(start_date) <= date '".$date."'",
                        "date(end_date) >= date '".$date."'",
                        'parent_id <> 0',
                        'visible = 1'
                    ]);
        return $period->toArray();
    }

    public function getAcademicPeriodLevel($academicPeriodId)
    {
        if (empty($academicPeriodId)) {
            return false;
        }
        $period = $this->AcademicPeriods
                    ->find()
                    ->select([
                        'academic_period_level_id'
                    ])
                    ->where([
                        "id = ".$academicPeriodId
                    ]);
        return $period->toArray();
    }

    protected function eventKey($key)
    {
        return 'Model.import.' . $key;
    }

    /**
     * @link("PHP get actual maximum upload size", http://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size)
     */
    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    protected function file_upload_max_size()
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $max_size = $this->post_upload_max_size();

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = $this->upload_max_filesize();

            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    protected function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }
    /**
     *
     */

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

    /**
     * http://codereview.stackexchange.com/questions/6476/quick-way-to-convert-bytes-to-a-more-readable-format
     * @param  [type] $bytes [description]
     * @return [type]        [description]
     */
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

    /**
     * @link("Upload errors defination", http://php.net/manual/en/features.file-upload.errors.php#115746)
     * For reference.
     */
    protected $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );
}
