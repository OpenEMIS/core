<?php

namespace Import\Model\Behavior;

use ArrayObject;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Http\Session;
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
//POCOR-8343
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing; // POCOR-8683
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use function PHPUnit\Framework\isEmpty;
use PhpOffice\PhpSpreadsheet\Style\Alignment; // POCOR-9364
use PhpOffice\PhpSpreadsheet\Cell\DataType; // POCOR-9364

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
        'custom_text' => '', //POCOR-8683
        'row_heights' => [75,25], //POCOR-8683
    ];
    protected $type = ''; //POCOR-8683
    protected $rootFolder = 'import';
    protected $_fileTypesMap = [
        // 'csv'    => 'text/plain',
        // 'csv'    => 'text/csv',
        'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office'],
        // Use for openoffice .xls format
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
        'zip' => ['application/zip']
    ];
    protected $institutionId = false;
    private $recordHeader = '';
    private $customText = '';

    public function initialize(array $config): void
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        //// Log::debug('@ImportBehavior::initialize START table=' . $this->_table->getAlias() . ' config_plugin=' . json_encode($config['plugin'] ?? null) . ' config_model=' . json_encode($config['model'] ?? null)); //[TEMP-LOG]
        //POCOR-9584: end
        $fileTypes = $this->getConfig('fileTypes');
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
        $this->setConfig('allowable_file_types', $allowableFileTypes);

        // testing using file size limit set in php.ini settings
        // $this->config('max_size', $this->system_memory_limit());
        // $this->config('max_rows', 50000);
        //

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

    protected function isCustomText()
    {
        // POCOR-8683 start
        $headings = $this->getConfig('headings') ?? [];

        foreach ($headings as $heading) {
            if (!empty($heading['subtitle'])) {
                return true; // At least one subheading exists
            }
        }
        $this->customText = $this->getConfig('custom_text');
        if (!empty($this->customText) && strlen($this->customText) > 0) {
            $row_heights = $this->getConfig('row_heights');
            if(!isset($row_heights[2])){
                $row_heights[2] = 25;
            }
            $this->getConfig('row_heights', $row_heights);
            return true;
        } else {
            return false;
        }
        // POCOR-8683 end
    }

    /******************************************************************************************************************
     **
     ** Events
     **
     ******************************************************************************************************************/
    public function implementedEvents(): array
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

    // POCOR-9080 start
    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $this->setupDownloadUrlIfAddAction($action, $toolbarButtons, $buttons);
        $this->setupBackButtonUrl($toolbarButtons);
    }

    private function setupDownloadUrlIfAddAction($action, &$toolbarButtons, $buttons)
    {
        // Log::debug('@ImportBehavior::setupDownloadUrlIfAddAction action=' . json_encode($action) . ' institutionId=' . json_encode($this->institutionId)); //[TEMP-LOG]
        if ($action !== 'add') {
            return;
        }

        $downloadUrl = $toolbarButtons['back']['url'];

        $downloadUrl[0] = 'template';

        if ($buttons['add']['url']['action'] === 'ImportInstitutionSurveys') {
            $downloadUrl[1] = $buttons['add']['url'][1];
        } else {
            //POCOR-9584: start - always carry full pass[1] from current request so all context params
            //   (class_id, academic_period_id, competency_template_id, etc.) survive to the template URL.
            //   Fall back to encoding only institution_id if pass[1] is absent.
            $fullEncodedParam = $this->_table->request->getParam('pass')[1] ?? null;
            if ($fullEncodedParam) {
                $downloadUrl[1] = $fullEncodedParam;
            } elseif ($this->institutionId) {
                $downloadUrl[1] = $this->_table->paramsEncode(['institution_id' => $this->institutionId]);
            }
            //POCOR-9584: end
        }

        //POCOR-9584: start - carry any non-empty query params (set by addAfterAction withQueryParams) to the template URL
        //   so methods like getCompetencyCriteriasArray() can read competency_item, competency_period, etc. on the GET request
        $allQueryParams = $this->_table->request->getQueryParams();
        // Log::debug('@ImportBehavior::setupDownloadUrlIfAddAction allQueryParams before filter=' . json_encode($allQueryParams)); //[TEMP-LOG]
        $queryParams = array_filter($allQueryParams, fn($v) => $v !== null && $v !== '' && $v !== '0');
        // Log::debug('@ImportBehavior::setupDownloadUrlIfAddAction queryParams after filter=' . json_encode($queryParams)); //[TEMP-LOG]
        if (!empty($queryParams)) {
            $downloadUrl['?'] = $queryParams;
        }
        //POCOR-9584: end

        $url = Router::url($downloadUrl);
        // Log::debug('@ImportBehavior::setupDownloadUrlIfAddAction final url=' . json_encode($url)); //[TEMP-LOG]
        $this->_table->controller->set('downloadOnClick', "javascript:window.location.href='{$url}'");
    }

    private function setupBackButtonUrl(&$toolbarButtons)
    {
        // Log::debug('@ImportBehavior::setupBackButtonUrl start backUrl=' . json_encode($toolbarButtons['back']['url'] ?? null) . ' institutionId=' . json_encode($this->institutionId) . ' pass=' . json_encode($this->_table->request->getParam('pass'))); //[TEMP-LOG]
        if (!empty($this->getConfig('backUrl'))) {
            $toolbarButtons['back']['url'] = array_merge($toolbarButtons['back']['url'], $this->getConfig('backUrl'));
            //POCOR-9584: start - only add encoded [1] when institutionId is set; otherwise clear stale pass params
            if ($this->institutionId) {
                $toolbarButtons['back']['url'][1] = $this->_table->paramsEncode(['institution_id' => $this->institutionId]);
            } else {
                unset($toolbarButtons['back']['url'][0]);
                unset($toolbarButtons['back']['url'][1]);
            }
            //POCOR-9584: end
            return;
        }

        $plugin = $toolbarButtons['back']['url']['plugin'] ?? null;
        $firstParam = $this->_table->request->getParam('pass')[0] ?? null;

        if ($plugin === 'Directory') {
            $toolbarButtons['back']['url'] = $this->generateDirectoryBackUrl($toolbarButtons['back']['url'], $firstParam);
        } elseif ($plugin === 'Institution' && $this->institutionId) {
            $toolbarButtons['back']['url'] = $this->generateInstitutionBackUrl($toolbarButtons['back']['url'], $firstParam);
        } else {
            //POCOR-9584: start - Staff context
            //   - For 'results': flip [0] from 'results' to 'index'; keep action (model alias) unchanged
            //   - For other sub-actions (add/index): do NOT touch action or [0] here —
            //     ImportStaffQualificationsTable::onUpdateToolbarButtons owns those (fires before this)
            //     and unsetting [0] here would undo its work regardless of firing order
            //   - Always carry the full encoded param from pass[1] so staff_id/user_id are preserved
            $fullEncodedParam = $this->_table->request->getParam('pass')[1] ?? null;
            if ($firstParam === 'results') {
                $toolbarButtons['back']['url'][0] = 'index';
            }
            if ($fullEncodedParam) {
                $toolbarButtons['back']['url'][1] = $fullEncodedParam;
            }
            //POCOR-9584: end
        }
        // Log::debug('@ImportBehavior::setupBackButtonUrl end backUrl=' . json_encode($toolbarButtons['back']['url'] ?? null)); //[TEMP-LOG]
    }

    private function generateDirectoryBackUrl(array $url, $firstParam): array
    {
        $back = [];

        if ($firstParam === 'add') {
            $back['action'] = 'Directories';
        } elseif ($firstParam === 'results') {
            $back['action'] = $this->_table->getAlias();
            $back[0] = 'add';
        }

        $url = array_merge($url, $back);
        unset($url[0]);

        return $url;
    }

    private function generateInstitutionBackUrl(array $url, $firstParam): array
    {
        $back = [];

        if ($firstParam === 'add') {
            $back['action'] = str_replace('Import', '', $this->_table->getAlias());
            $back[0] = 'index';
        } elseif ($firstParam === 'results') {
            $back['action'] = str_replace('Import', '', $this->_table->getAlias());
            $back[0] = 'index';
        }

        if (!array_key_exists($back['action'], $this->_table->ControllerAction->models)) {
            $back['action'] = str_replace('Institution', '', $back['action']);
        }
        $back[1] = $this->_table->paramsEncode(['institution_id' => $this->institutionId]);
        $back['plugin'] = $this->getConfig('plugin');
        $url = array_merge($url, $back);

//        unset($url[0]);
        return $url;
    }
    // POCOR-9080 end

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $buttons[0]['name'] = '<i class="fa kd-import"></i> ' . __('Import');
    }

    public function beforeAction($event)
    {
        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportBehavior::beforeAction START table=' . $this->_table->getAlias()); //[TEMP-LOG]
        // Log::debug('@ImportBehavior::beforeAction passParams=' . json_encode($this->_table->request->getParam('pass'))); //[TEMP-LOG]
        // Log::debug('@ImportBehavior::beforeAction routeAction=' . json_encode($this->_table->request->getParam('action'))); //[TEMP-LOG]
        //POCOR-9584: end

        $session = $this->_table->Session;
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        $request = $this->_table->request;
        if(empty($this->institutionId) && $request->getParam('pass')[0] != 'downloadFailed'
            && $request->getParam('pass')[0] != 'downloadPassed'
            && isset($request->getParam('pass')[1])) {
            $queryString = $this->_table->paramsDecode($request->getParam('pass')[1]);
            $this->institutionId = isset($queryString['institution_id']) ? $queryString['institution_id'] : $this->institutionId ;
        }
        $this->sessionKey = $this->getConfig('plugin') . '.' . $this->getConfig('model') . '.Import.data';

        //POCOR-9584: start - debug logging for ImportOutcomeResults/add black screen
        // Log::debug('@ImportBehavior::beforeAction institutionId=' . json_encode($this->institutionId) . ' sessionKey=' . $this->sessionKey); //[TEMP-LOG]
        //POCOR-9584: end

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

    public function validationImportFile(Validator $validator)
    {
        $validator = $this->_table->validationDefault($validator);
        $supportedFormats = array_values(Hash::flatten($this->_fileTypesMap));
        $maxSize = $this->getConfig('max_size') < $this->file_upload_max_size() ? $this->getConfig('max_size') : $this->file_upload_max_size();

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
     * @param EventInterface $event [description]
     * @param Entity $entity [description]
     * @param ArrayObject $data [description]
     * @param ArrayObject $options [description]
     *
     * Refer to phpFileUploadErrors below for the list of file upload errors defination.
     */
    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $options['validate'] = 'importFile';
    }

    /**
     * Actual Import business logics reside in this function
     * @param EventInterface $event Event object
     * @param Entity $entity Entity object containing the uploaded file parameters
     * @param ArrayObject $data Event object
     * @return Response             Response object
     */
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
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
            $errors = $entity->getErrors();
            if (!empty($errors)) {
                $fileObj = $entity->get('select_file');
                if (!$fileObj || $fileObj->getError() === UPLOAD_ERR_NO_FILE) {
                    $entity->setError('select_file', __('Please select a file to upload.'));
                    return false;
                }

                return false;
            }

            $systemDateFormat = self::getDynamicTableInstance('Configuration.ConfigItems')->value('date_format');

            $mapping = $this->getMapping();
            $header = $this->getHeader($mapping);
            $columns = $this->getColumns($mapping);
            $totalColumns = count($columns);
            $lookup = $this->getCodesByMapping($mapping);

            $fileObj = $entity->select_file;
            //$uploadedName = $fileObj['name']; //POCOR-8343 START
            // $uploaded = $fileObj['tmp_name'];
            // $inputFileType = PHPExcel_IOFactory::identify($uploaded);
            // $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $uploadedName = $fileObj->getClientFilename();
            $uploaded = $fileObj->getStream()->getMetadata('uri');
            // Log::debug('@ImportBehavior::processImport uploaded_name=' . json_encode($uploadedName) . ', path=' . json_encode($uploaded)); //[TEMP-LOG]

            try {
                $inputFileType = IOFactory::identify($uploaded);
                // Log::debug('@ImportBehavior::processImport inputFileType=' . json_encode($inputFileType)); //[TEMP-LOG]
                $objReader = IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($uploaded);
                // Log::debug('@ImportBehavior::processImport file loaded successfully'); //[TEMP-LOG]
            } catch (\Exception $e) {
                throw new NotFoundException(__('Error loading file: ') . $e->getMessage());
            }
            //POCOR-8343  End
            $totalImported = 0;
            $totalUpdated = 0;
            $importedUniqueCodes = new ArrayObject;
            $dataFailed = [];
            $dataPassed = [];
            $extra = new ArrayObject(['lookup' => [], 'entityValidate' => true]);

            $activeModel = TableRegistry::getTableLocator()->get($this->getConfig('plugin') . '.' . $this->getConfig('model'));
            // Log::debug('@ImportBehavior::processImport activeModel_alias=' . json_encode($activeModel->getAlias()) . ', plugin=' . json_encode($this->getConfig('plugin')) . ', model=' . json_encode($this->getConfig('model'))); //[TEMP-LOG]
            $activeModel->addBehavior('DefaultValidation');

            $maxRows = $this->getConfig('max_rows');
            $maxRows = $maxRows + 2;
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            ($this->isCustomText()) ? $this->recordHeader = 3 : $this->recordHeader = 2;
            // Log::debug('@ImportBehavior::processImport highestRow=' . $highestRow . ', maxRows=' . $maxRows . ', recordHeader=' . $this->recordHeader . ', isCustomText=' . json_encode($this->isCustomText())); //[TEMP-LOG]
            if ($highestRow > $maxRows) {
                // Log::debug('@ImportBehavior::processImport EXIT: over_max_rows'); //[TEMP-LOG]
                $entity->getErrors('select_file', [$this->getExcelLabel('Import', 'over_max_rows')], true);
                return false;
            }

            if ($highestRow == $this->recordHeader) {
                // Log::debug('@ImportBehavior::processImport EXIT: no_answers (file is empty/header only)'); //[TEMP-LOG]
                $entity->getErrors('select_file', [$this->getExcelLabel('Import', 'no_answers')], true);
                return false;
            }

            ($this->isCustomText()) ? $startCheck = 3 : $startCheck = 2;

            for ($row = $startCheck; $row <= $highestRow; ++$row) {
                if ($row == $this->recordHeader) { // skip header but check if the uploaded template is correct
                    // Log::debug('@ImportBehavior::processImport checking template on row=' . $row . ', header=' . json_encode($header) . ', totalColumns=' . $totalColumns); //[TEMP-LOG]
                    $templateOk = $this->isCorrectTemplate($header, $sheet, $totalColumns, $row);
                    // Log::debug('@ImportBehavior::processImport isCorrectTemplate=' . json_encode($templateOk)); //[TEMP-LOG]
                    if (!$templateOk) {
                        //POCOR-9584: start - compute column mismatches for meaningful error
                        $cellsValue = [];
                        for ($col = 1; $col <= $totalColumns; $col++) {
                            $cellsValue[] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                        }
                        $mismatches = [];
                        foreach ($header as $i => $expected) {
                            $actual = isset($cellsValue[$i]) ? $cellsValue[$i] : '(missing)';
                            if ($expected !== $actual) {
                                $mismatches[] = 'col ' . ($i + 1) . ': expected "' . $expected . '", got "' . $actual . '"';
                            }
                        }
                        if (count($cellsValue) > count($header)) {
                            for ($i = count($header); $i < count($cellsValue); $i++) {
                                $mismatches[] = 'col ' . ($i + 1) . ': unexpected column "' . $cellsValue[$i] . '"';
                            }
                        }
                        $mismatchDetail = implode('; ', $mismatches);
                        Log::error('@ImportBehavior::processImport wrong_template - ' . $mismatchDetail);
                        // Log::debug('@ImportBehavior::processImport EXIT: wrong_template - ' . $mismatchDetail); //[TEMP-LOG]
                        $this->_table->Alert->error(__('Wrong template: please re-download the template. Column mismatch: ') . $mismatchDetail, ['type' => 'string', 'reset' => true]); //POCOR-9584
                        //POCOR-9584: end
                        return false; //POCOR-8343
                    }
                    continue;
                }
//                if ($row == $highestRow) { // check if the row cells are really empty, if yes then end the loop
                    if ($this->checkRowCells($sheet, $totalColumns, $row) === false) {
                        // Log::debug('@ImportBehavior::processImport row=' . $row . ' SKIPPED by checkRowCells (appears empty)'); //[TEMP-LOG]
                        continue;
                    }
//                }

                // check for unique record
                $tempRow = new ArrayObject;
                $rowInvalidCodeCols = new ArrayObject;
                $params = [$sheet, $row, $columns, $tempRow, $importedUniqueCodes, $rowInvalidCodeCols];

                $this->dispatchEvent($this->_table, $this->eventKey('onImportCheckUnique'), 'onImportCheckUnique', $params);

                // for each columns
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

                $originalRow = new ArrayObject;
                $checkCustomColumn = new ArrayObject;
                $extra['entityValidate'] = true;
                $rowPass = $this->_extractRecord($references, $tempRow, $originalRow, $rowInvalidCodeCols, $extra);

                if ($rowPass !== NULL && !$rowPass) {
                    $activeModel->setImportValidationFailed();
                } else {
                    $activeModel->setImportValidationPassed();
                }

                // Log::debug('@ImportBehavior::processImport row=' . json_encode($row) . ', tempRow=' . json_encode($tempRow->getArrayCopy())); //[TEMP-LOG]
                $tempRow = $tempRow->getArrayCopy();

                // $tempRow['entity'] must exists!!! should be set in individual model's onImportCheckUnique function
                if (!isset($tempRow['entity'])) {
                    $tableEntity = $activeModel->newEntity([]);
                } else {
                    if(!isset($tempRow['institution_class_id']) && $activeModel->getAlias() == 'StudentAdmission') {
                        $tempRow['entity']['institution_class_id'] = NULL;
                    }
                    $tableEntity = $tempRow['entity'];
                    unset($tempRow['entity']);
                }
                $feature = $this->_table->request->getData()['ImportStaff']['feature'] ?? null;
                if ($extra['entityValidate'] == true) {
                    //POCOR-9394[START]
                    //POCOR-9417[START]
                    $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                    $academic_period_id = $AcademicPeriods->getCurrent();
                    if (isset($tempRow['academic_period_id'])) { //POCOR-9417
                        $academic_period_id = $tempRow['academic_period_id'];
                    } else {
                        $tempRow['academic_period_id'] = $academic_period_id;
                    }
                    //POCOR-9532 start
                    if ($feature === 'Institution.Institutions.ImportStaff') {
                        // CASE 1: XLSX has end_date → KEEP IT
                        if (!empty($tempRow['end_date'])) {
                            // normalize end_year
                            $tempRow['end_year'] = date(
                                'Y',
                                strtotime(str_replace('/', '-', $tempRow['end_date']))
                            );

                        }
                        // CASE 2: XLSX end_date EMPTY → KEEP NULL
                        else {
                            $tempRow['end_date'] = null;
                            $tempRow['end_year'] = null;
                        }
                        //POCOR-9532 end
                    }elseif($academic_period_id)
                    {
                        $AcademicPeriodsData = $AcademicPeriods
                            ->find('all')
                            ->select([$AcademicPeriods->aliasField('end_date')])
                            ->where([$AcademicPeriods->aliasField('id') => $academic_period_id])
                            ->first();
                        $tempRow['end_date'] = $AcademicPeriodsData->end_date->format('d/m/Y');
                    } //POCOR-9417[END]
                    //POCOR-9394[END]

                    // added for POCOR-4577 import staff leave for workflow related record to save the transition record
                    $tempRow['action_type'] = 'imported';
                    $tempRow['student_id'] = (int) $tempRow['student_id'];
                    // Log::debug('@ImportBehavior::processImport patchEntity with tempRow=' . json_encode($tempRow)); //[TEMP-LOG]
                    //$activeModel->patchEntity($tableEntity, $tempRow);
                    $tableEntity = $activeModel->patchEntity($tableEntity, $tempRow);
                }

                $errors = $tableEntity->getErrors();
                // Log::debug('@ImportBehavior::processImport errors_after_patchEntity=' . json_encode($errors)); //[TEMP-LOG]
                $rowInvalidCodeCols = $rowInvalidCodeCols->getArrayCopy();

                // to-do: saving of entity into table with composite primary keys (Exam Results) give wrong isNew value
                $isNew = $tableEntity->isNew();

                if ($extra['entityValidate'] == true) {
                    // POCOR-4258 - shifted saving model before updating errors to implement try-catch to catch database errors
                    try {
                        //POCOR-9294[START]
                        // $checkRequest = $this->_table->request->getData()['ImportStudentAdmission']['feature'];
                        // if($checkRequest == 'Institution.Institutions.ImportStudentAdmission'){
                        //     $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                        //     $AcademicPeriodsData = $AcademicPeriods
                        //             ->find('all')
                        //             ->select([$AcademicPeriods->aliasField('start_year'), $AcademicPeriods->aliasField('end_year')])
                        //             ->where([$AcademicPeriods->aliasField('id') => $tableEntity['academic_period_id']])
                        //             ->first();

                        //     $tableEntity['student_status_id'] = 1;
                        //     $tableEntity['start_year'] = $AcademicPeriodsData->start_year;
                        //     $tableEntity['end_year'] = $AcademicPeriodsData->end_year;
                        //     $activeModel = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
                        //     $supprtiveModel = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
                        //     $newEntity = $activeModel->save($tableEntity);
                        // }else{
                        //     $newEntity = $activeModel->save($tableEntity); // Initial code
                        // }
                        //POCOR-9294[END]
                        // Log::debug('@ImportBehavior::processImport attempting save with entity=' . json_encode($tableEntity->toArray())); //[TEMP-LOG]
                        //$model->log('@ImportBehavior pre-save errors=' . json_encode($errors), 'debug');
                        //POCOR-9583 Start
                        if($activeModel->getAlias() == 'AssessmentItemResults' && isset($tableEntity->institution_class_id)) {
                            $tableEntity->set('institution_classes_id', $tableEntity->institution_class_id);
                        } 
                        //POCOR-9583 End
                        $newEntity = $activeModel->save($tableEntity);
                        // Log::debug('@ImportBehavior::processImport save result newEntity=' . json_encode($newEntity ? 'saved' : 'failed')); //[TEMP-LOG]
                        //POCOR-9584: merge errors set during beforeSave (not captured before save() runs)
                        if (!$newEntity) {
                            $afterSaveErrors = $tableEntity->getErrors();
                            // Log::debug('@ImportBehavior::processImport afterSaveErrors=' . json_encode($afterSaveErrors)); //[TEMP-LOG]
                            //$model->log('@ImportBehavior post-save errors=' . json_encode($afterSaveErrors), 'debug');
                            $errors = array_merge($errors, $afterSaveErrors);
                            // Log::debug('@ImportBehavior::processImport merged_errors=' . json_encode($errors)); //[TEMP-LOG]
                            //$model->log('@ImportBehavior merged errors=' . json_encode($errors), 'debug');
                        }
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
                            $arr = array_reverse($arr, true);
                            if (in_array($field, $columns)) {
                                $fieldName = $this->getExcelLabel($activeModel->getRegistryAlias(), $field);
                                $rowCodeError .= '<li>' . $fieldName . ' => ' . $arr[key($arr)] . '</li>';
                                $rowCodeErrorForExcel[] = $fieldName . ' => ' . $arr[key($arr)];
                            } else {
                                if (in_array($field, ['student_name', 'staff_name'])) {
                                    $rowCodeError .= '<li>' . $arr[key($arr)] . '</li>';
                                    $rowCodeErrorForExcel[] = $arr[key($arr)];
                                }
                                else{
                                    $rowCodeError .= '<li>' . $field . '</li>';
                                    $rowCodeErrorForExcel[] = $arr[key($arr)];
                                }
                                $model->log('@ImportBehavior line ' . __LINE__ . ': ' . $activeModel->getRegistryAlias() . ' -> ' . $field . ' => ' . $arr[key($arr)], 'info');
                            }
                        }
                    }
                    if (!empty($rowInvalidCodeCols)) {
                        foreach ($rowInvalidCodeCols as $field => $errMessage) {
                            $fieldName = $this->getExcelLabel($activeModel->getRegistryAlias(), $field);
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
                    //$clonedEntity->virtualProperties([]);
                    $clonedEntity->setVirtual([]);

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
            $url = $this->_table->ControllerAction->url('results'); //POCOR-8343
            $request = $this->_table->request;
            if(empty($this->institutionId) && isset($request->getParam('pass')[1])) {
                $queryString = $this->_table->paramsDecode($request->getParam('pass')[1]);
                $this->institutionId = isset($queryString['institution_id']) ? $queryString['institution_id'] : $this->institutionId ;
            }
            //POCOR-9584: start - carry full encoded params to results redirect; use direct key assignment
            //   (array_merge renumbers integer keys, breaking pass param order)
            $fullEncodedParam = $request->getParam('pass')[1] ?? null;
            // Log::debug('@ImportBehavior::processImport url_before=' . json_encode($url) . ' pass=' . json_encode($request->getParam('pass')) . ' fullEncodedParam=' . json_encode($fullEncodedParam)); //[TEMP-LOG]
            if ($fullEncodedParam) {
                $url[1] = $fullEncodedParam;
            } else {
                $url[1] = $this->_table->paramsEncode(['institution_id' => $this->institutionId]);
            }
            // Strip stale query params from results redirect URL (e.g. period=34 carried from add page)
            unset($url['?']);
            // Log::debug('@ImportBehavior::processImport url_after=' . json_encode($url)); //[TEMP-LOG]
            //POCOR-9584: end

            return $model->controller->redirect($url);
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
        $modelName = $this->getConfig('model');
        $modelName = str_replace(' ', '_', Inflector::humanize(Inflector::tableize($modelName)));
        //5695 starts
        if ($modelName == 'Training_Session_Trainee_Results') {
            $modelNameforTemplate = 'Training_Results';
            $excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelNameforTemplate);
        } else {
            // Do not lcalize file name as certain non-latin characters might cause issue
            $excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelName);
        }//5695 ends
        $excelPath = $folder . DS . $excelFile;

        $mapping = $this->getMapping();
        $header = $this->getHeader($mapping);
        //5695 starts
        if ($modelName == 'Training_Session_Trainee_Results') {
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
        }//5695 ends
        $dataSheetName = $this->getExcelLabel('general', 'data');
        //$objPHPExcel = new \PHPExcel(); //POCOR-8343
        $objPHPExcel = new Spreadsheet();

        $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $header, '');

        $this->setCodesDataTemplate($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
        //$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter = new Xlsx($objPHPExcel);
        try {
            $objWriter->save($excelPath);
        } catch (\Throwable $th) {
            Log::debug(print_r([__FUNCTION__ => $th->getMessage()], true));
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
            $this->_table->controller->set('data', $this->_table->newEntity([]));
            $this->_table->ControllerAction->renderView('/ControllerAction/view');
        } else {
            return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
        }
    }


    /******************************************************************************************************************
     **
     ** Import Functions
     ** POCOR-8683 refactured
     *****************************************************************************************************************
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function beginExcelHeaderStyling($objPHPExcel, $dataSheetName, $defaultTitle = ''): void
    {

        // Set default title if not provided
        if (empty($defaultTitle)) {
            $defaultTitle = $dataSheetName;
        } else { // 5695 starts
            if ($defaultTitle == 'Import Training Session Trainee Results Data') {
                $defaultTitle = 'Import Training Results Data';
            } // 5695 ends
        }

// Set up the sheet
        $activeSheet = $objPHPExcel->getActiveSheet();
        $activeSheetIndex = $objPHPExcel->getIndex($activeSheet); // Get current sheet index
        $activeSheet->setTitle($dataSheetName);
        $this->addLogo($activeSheet);
// Logic for the first sheet
        if ($activeSheetIndex === 0) {
            // Add a logo if the function is available


            // Get titles and subtitles from config
            $headings = $this->getConfig('headings') ?? [
                [
                    'title' => $defaultTitle,
                    'title_range' => 'C1:R1',
                ]
            ];
            $rowHeights = $this->getConfig('row_heights') ?? [75, 25]; // Default heights for rows
            $headerFontSize = $this->getConfig('header_font_size') ?? 16;

            // Set default row heights
            foreach ($rowHeights as $index => $height) {
                $activeSheet->getRowDimension($index + 1)->setRowHeight($height);
            }
            $activeSheet->getRowDimension(3)->setRowHeight(25);

            // Process each title and subtitle
            $titleindex = 1; // Start at the first row
            foreach ($headings as $index => $heading) {
                $this->applyHeadingToSheet($activeSheet, $heading, $titleindex, $headerFontSize);

                // Add custom text for headings other than the first/default heading
                if ($index > 0 && $this->customText != "") {
                    $customTextColumn = explode(':', $heading['title_range'])[0]; // Start column of the title range
                    $customTextCell = $customTextColumn . "3"; // Custom text in the third row of the title's start column
                    $activeSheet->setCellValue($customTextCell, $this->customText);
                }

                $titleindex++;
            }

            // Add custom text only for subheaders
            if ($this->isCustomText() && $this->customText != "") {
                $activeSheet->setCellValue("A3", $this->customText);
            }
        } else {
            // Logic for subsequent sheets
            $activeSheet->getRowDimension(1)->setRowHeight(75); // Default row height for header
            $activeSheet->setCellValue("C1", "Resources"); // Add "Resources" as the only header
            $activeSheet->getStyle("C1")->getFont()->setBold(true)->setSize(16);
        }


    }

    /**
     * POCOR-8683 refactured
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function addLogo($activeSheet): void
    {
        $imagePath = ROOT . DS . 'plugins' . DS . 'Import' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.jpg';
        if (file_exists($imagePath)) {
            $drawing = new Drawing();
            $drawing->setName('OpenEMIS Logo');
            $drawing->setDescription('OpenEMIS Logo');
            $drawing->setPath($imagePath); // Set the path to the image file
            $drawing->setHeight(100); // Set the height of the image
            $drawing->setCoordinates('A1'); // Position the image
            $drawing->setWorksheet($activeSheet); // Add the image to the active sheet
        }
    }

    /*
     * POCOR-8683 refactured
     */
    private function applyHeadingToSheet($activeSheet, $heading, $titleindex, $fontSize): void
    {
        $title = $heading['title'] ?? '';
        $titleRange = $heading['title_range'] ?? '';
        $subtitle = $heading['subtitle'] ?? [];
        $subtitleRange = $heading['subtitle_range'] ?? '';
        // Apply title if it exists
        $type = $this->type;
        if (!empty($title) && !empty($titleRange)) {
            $this->applyCellStyle($activeSheet, $titleRange, $fontSize, true);
            if ($titleindex > 1) {
                $activeSheet->mergeCells($titleRange);
            }
            $activeSheet->setCellValue(explode(':', $titleRange)[0], $title); // Set title in the first cell of the range
        }

        // Apply subtitle if it exists
        if (!empty($subtitle) && !empty($subtitleRange)) {
//            $this->applyCellStyle($activeSheet, $subtitleRange, $fontSize, false);
            $subtitleCells = explode(':', $subtitleRange);
            $startColumn = $subtitleCells[0];
            $cell = $startColumn; // Subtitle row is after the title
            if (empty($type) || $type != 'failed' ) {
                $activeSheet->mergeCells($subtitleRange);
            }
            $activeSheet->setCellValue($cell, $subtitle);
        }
    }

    /*
     * POCOR-8683 refactured
     */
    private function applyCellStyle($activeSheet, $range, $fontSize, $bold)
    {
        $style = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => $bold,
                'size' => $fontSize,
            ],
        ];

        $activeSheet->getStyle($range)->applyFromArray($style);
    }

    /*
     * POCOR-8683 refactured
     */
    public function endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, $lastRowToAlign = 2, $applyFillFontSetting = [], $applyCellBorder = [])
    {
        if (empty($applyFillFontSetting)) {
            ($this->isCustomText()) ? $applyFillFontSetting = ['s' => 3, 'e' => 3] : $applyFillFontSetting = ['s' => 2, 'e' => 2];
        }

        if (empty($applyCellBorder)) {
            ($this->isCustomText()) ? $applyCellBorder = ['s' => 3, 'e' => 3] : $applyCellBorder = ['s' => 2, 'e' => 2];
        }

        $activeSheet = $objPHPExcel->getActiveSheet();
        if ($this->getConfig('headings')) {
            // Get the last column of the first title range from config
            $headings = $this->getConfig('headings');
            if (!empty($headings[0]['title_range'])) {
                $headerLastAlphaOne = explode(':', $headings[0]['title_range'])[1][0]; // Extract column from "R1"
            } else {
                $headerLastAlphaOne = $headerLastAlpha; // Fallback to default
            }
        } else {
            $headerLastAlphaOne = $headerLastAlpha; // Fallback to default
        }
        // merging should start from cell C1 instead of A1 since the title is already set in cell C1 in beginExcelHeaderStyling()
        if (!in_array($headerLastAlpha, ['A', 'B', 'C'])) {
            $activeSheet->mergeCells('C1:' . $headerLastAlphaOne . '1');
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
        $activeSheet->getStyle("A" . $applyFillFontSetting['s'] . ":" . $headerLastAlpha . $applyFillFontSetting['e'])->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('6699CC'); // OpenEMIS Core product color
        $activeSheet->getStyle("A" . $applyCellBorder['s'] . ":" . $headerLastAlpha . $applyCellBorder['e'])->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
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
        // POCOR-8683 start
        if($type == 'failed') { //if failed, then need to merge 4 columns instead of 3
            $this->type = 'failed';
        }
        // POCOR-8683 end
        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName, __(Inflector::humanize(Inflector::tableize($this->_table->getAlias()))) . ' ' . $dataSheetName);

        $currentRowHeight = $activeSheet->getRowDimension($lastRowToAlign)->getRowHeight();

        foreach ($header as $key => $value) {
            //echo "<pre>"; print_r($key); die;
            $alpha = $this->getExcelColumnAlpha((string)((int)$key + 1));// PhpSpreadsheet, rows and columns are typically 1-indexed
            $activeSheet->setCellValue($alpha . $lastRowToAlign, $value);
            $activeSheet->getColumnDimension($alpha)->setAutoSize(true);
            if (strlen($value) < 50) {
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
        $headerLastAlpha = $this->getExcelColumnAlpha(count($header));
        $this->endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, $lastRowToAlign);
    }

    public function suggestRowHeight($stringLen, $currentRowHeight)
    {
        if ($stringLen >= 50) {
            $multiplier = $stringLen % 50;
        } else {
            $multiplier = 0;
        }
        $rowHeight = (3 * $multiplier) + 25;
        if ($rowHeight > $currentRowHeight && $rowHeight <= 250) {
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
            $firstColumn = $lastColumn == -1 ? 1 : 1 + $lastColumn ;  //POCOR-8343
            $modelDataCount = is_array($modelArr['data'][0]) ?  count($modelArr['data'][0])  : 0; //POCOR-8343
            $lastColumn = $firstColumn + $modelDataCount - 1;
            $objPHPExcel->getActiveSheet()->mergeCells($this->getExcelColumnAlpha($firstColumn) . "2:" . $this->getExcelColumnAlpha($lastColumn) . "2");
            $objPHPExcel->getActiveSheet()->setCellValue($this->getExcelColumnAlpha($firstColumn) . "2", $modelArr['sheetName']);
            if (strlen($modelArr['sheetName']) < 50) {
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

            if (count($modelData) > 1 && !isset($modelArr['noDropDownList'])) {
                $lookupColumn = $firstColumn + intval($modelArr['lookupColumn']) - 1;
                $alpha = $this->getExcelColumnAlpha($columnOrder);
                $lookupColumnAlpha = $this->getExcelColumnAlpha($lookupColumn);
                ($this->isCustomText()) ? $lookupStart = 4 : $lookupStart = 3;
                for ($i = $lookupStart; $i < 103; $i++) {
                    $objPHPExcel->setActiveSheetIndex(0);
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($alpha . $i)->getDataValidation();
                    $objValidation->setType(DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $listLocation = "'" . $sheetName . "'!$" . $lookupColumnAlpha . "$4:$" . $lookupColumnAlpha . "$" . (count($modelData) + 2);
                    $objValidation->setFormula1($listLocation);
                }
                $objPHPExcel->setActiveSheetIndex(1);
            }
        }

        if ($lastColumn > -1) { //if got no reference data.
            $headerLastAlpha = $this->getExcelColumnAlpha($lastColumn);
            $objPHPExcel->getActiveSheet()->getStyle("A2:" . $headerLastAlpha . "2")->getFont()->setBold(true)->setSize(12);
            $this->endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, 3, ['s' => 3, 'e' => 3], ['s' => 2, 'e' => 3]);
        }
    }

    /**
     * Set a record columns value based on what is being saved in the table.
     * @param Entity $entity Cloned entity. The actual entity is not saved yet but already validated but we are using a cloned entity in case it might be messed up.
     * @param Array $columns Target Model columns defined in import_mapping table.
     * @param string $systemDateFormat System Date Format which varies across deployed environments.
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
            $excelFile = sprintf('OpenEMIS_Core_Import_%s_%s_%s.xlsx', $this->getConfig('model'), ucwords($type), time());
            $excelPath = $downloadFolder . DS . $excelFile;

            $newHeader = $header;
            if ($type == 'failed') {
                $newHeader[] = $this->getExcelLabel('general', 'errors');
            }
            $dataSheetName = $this->getExcelLabel('general', 'data');

            //$objPHPExcel = new \PHPExcel();
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
                $rowNumber = $index + $rowData;  // POCOR-9364
                $activeSheet->getRowDimension(($index + $rowData))->setRowHeight(15);
                foreach ($values as $key => $value) {
                    // POCOR-9364 start
                    $alpha = $this->getExcelColumnAlpha((string)((int)$key + 1));
                    $cell  = $alpha . $rowNumber;

                    // Write explicitly as string to keep "\n" literal
                    $activeSheet->setCellValueExplicit($cell, (string)$value, DataType::TYPE_STRING);
                    $activeSheet->getColumnDimension($alpha)->setAutoSize(true);

                    // If this cell contains a newline, enable wrap and bump row height
                    if (is_string($value) && strpos($value, "\n") !== false) {
                        $activeSheet->getStyle($cell)->getAlignment()
                            ->setWrapText(true)
                            ->setVertical(Alignment::VERTICAL_TOP)
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                        // Simple height heuristic: 15px per line (tweak as needed)
                        $lines = substr_count($value, "\n") + 1;
                        $activeSheet->getRowDimension($rowNumber)
                            ->setRowHeight(max(15, 15 * $lines));
                        // POCOR-9364 end
                    }
                    if ($key == (count($values) - 1) && $type == 'failed') {
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
            //$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $objWriter = new Xlsx($objPHPExcel);
            try {
                $objWriter->save($excelPath);
            } catch (\Throwable $th) {
                Log::debug(print_r([__FUNCTION__ => $th->getMessage()], true));

            }

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
     * @param mixed $column_number either an integer or a string named as "last"
     * @return string               the string representation of a column based on excel grid
     * @todo  the alpha string array values should be auto-generated instead of hard-coded
     */
    public function getExcelColumnAlpha($column_number)
    {
        return Coordinate::stringFromColumnIndex($column_number);
    }

    /**
     * Check if all the columns in the row is not empty
     * @param WorkSheet $sheet The worksheet object
     * @param integer $totalColumns Total number of columns to be checked
     * @param integer $row Row number
     * @return boolean               the result to be return as true or false
     */
    public function checkRowCells($sheet, $totalColumns, $row): bool
    {

        $cellsState = [];
        for ($col = 0; $col < $totalColumns; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $value = $cell->getValue();
            if(is_string($value)){
                $value = trim($value);
            }
            // Consider both null and empty string ("") as empty
            $cellState = !($value === null || $value === "" || empty($value));
            $cellsState[] = $cellState;
        }

        // Return true if at least one cell is non-empty
        $rowState = in_array(true, $cellsState, true);
        return $rowState;
    }
    /**
     * Check if the uploaded file is the correct template by comparing the headers extracted from mapping table
     * and first row of the uploaded file record
     * @param array $header The headers extracted from mapping table according to active model
     * @param WorkSheet $sheet The worksheet object
     * @param integer $totalColumns Total number of columns to be checked
     * @param integer $row Row number
     * @return boolean                      the result to be return as true or false
     */
    public function isCorrectTemplate($header, $sheet, $totalColumns, $row)
    {
        //5695 starts
        $model = $this->_table;
        if ($model->alias == 'ImportTrainingSessionTraineeResults') {
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
        }//5695 ends
        $cellsValue = [];
        for ($col = 1; $col <= $totalColumns; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $cellsValue[] = $cell->getValue();
        }
        return $header === $cellsValue;
    }

    //POCOR-9236 Start
    /**
     * Model name as stored in import_mapping.model (may differ from ORM table model name).
     */
    protected function getImportMappingModelName(): string
    {
        $modelName = (string)$this->getConfig('model');
        if ($modelName === 'ExaminationStudentSubjectResults') {
            return 'ExaminationItemResults';
        }

        return $modelName;
    }

    /**
     * Full import_mapping.model value, e.g. Examination.ExaminationItemResults
     */
    protected function getImportMappingModelKey(): string
    {
        return $this->getConfig('plugin') . '.' . $this->getImportMappingModelName();
    }
    //POCOR-9236 End

    public function getMapping()
    {
        $model = $this->_table;
        $mapping = $model->find('all')
            ->where([
                $model->aliasField('model') => $this->getImportMappingModelKey(), //POCOR-9236 
            ])
            ->order($model->aliasField('order'))
            ->toArray();
        return $mapping;
    }

    protected function getHeader($mapping = [])
    {
        if (empty($mapping)) {
            $mapping = $this->getMapping(); // POCOR-8683
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
                    if ($value->model == 'Student.StudentGuardians') {
                        $label = __($value->description);
                    } else {
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
            $mapping = $this->getMapping(); // POCOR-8683
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
                $model->aliasField('model') => $this->getImportMappingModelKey(),//POCOR-9236 
                $model->aliasField('foreign_key') . ' IN' => [self::FIELD_OPTION, self::DIRECT_TABLE, self::NON_TABLE_LIST]
            ])
            ->order($model->aliasField('order'))
            ->toArray();
        $data = new ArrayObject;
        foreach ($mapping as $row) {
            //POCOR-9236 Marks must be free entry; dropdown lists belong on examination_grading_option_id only.
            if ($row->column_name === 'marks' && preg_match(
                '/^Examination\\.(ExaminationItemResults|ExaminationStudentSubjectResults)$/',
                (string)$row->model
            )) {
                continue;
            }
            //POCOR-9236 End
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
                if (TableRegistry::exists($lookupModel)) {
                    $relatedModel = TableRegistry::getTableLocator()->get($lookupModel);
                } elseif ($mappingModel == 'Student.Extracurriculars' && $lookupModel == 'Users') {
                    $institutionId = 0;
                    $session = $this->_table->Session;
                    if ($session->check('Institution.Institutions.id')) {
                        $institutionId = $session->read('Institution.Institutions.id');
                    }

                    $relatedModel = TableRegistry::getTableLocator()->get($lookupModel, ['className' => $lookupPlugin . '\Model\Table\\' . $lookupModel . 'Table'])->findStudents($institutionId);
                } else {
                    $relatedModel = TableRegistry::getTableLocator()->get($lookupModel, ['className' => $lookupPlugin . '\Model\Table\\' . $lookupModel . 'Table']);
                }

                if ($mappingModel == 'Student.Extracurriculars' && $lookupModel == 'Users') {
                    $emptyCodeRecords = $relatedModel;
                    $modelData = $relatedModel;
                } else {
                    // POCOR-8683 start
                    $relatedQuery = $relatedModel->find();

// Check if the 'order' field exists in the model's schema
                    if ($relatedModel->getSchema()->hasColumn('order')) {
                        $relatedQuery->order($relatedModel->aliasField('order'));
                    }

                    $modelData = $relatedModel->getList($relatedQuery);
                    // POCOR-8683 end
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
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($excelPath));
        echo file_get_contents($excelPath);
    }

    public function getExcelLabel($module, $columnName)
    {
        if (empty($module) || empty($columnName)) { // POCOR-9080 in case somebody forget to add field name
            return '';
        }
        $translatedCol = '';
        if ($module instanceof Table) {
            $module = $module->getAlias();
        }
        $dotPost = strpos($module, '.');
        if ($dotPost > -1) {
            $module = substr($module, ($dotPost + 1));
        }
        if (!empty($this->labels) && isset($this->labels[$module]) && isset($this->labels[$module][$columnName])) {
            $translatedCol = $this->labels[$module][$columnName];
        } else {
            if ($module == 'Import') {
                $translatedCol = $this->_table->getMessage($module . '.' . $columnName);
            } else {
                /**
                 * $language should provide the current selected locale language
                 */
                $language = '';
                $eventName = 'onGetFieldLabel';
                $translatedCol = $this->_table->onGetFieldLabel(new Event($eventName, $this), $module, $columnName, $language);
                if (empty($translatedCol) || ($translatedCol == $columnName && $columnName != 'FTE')) { // checking for column name FTE should not be hard-coded here, do revisit this in the future
                    //$translatedCol = Inflector::humanize(Inflector::singularize(Inflector::tableize($columnName)));

                    if ($columnName !== null) {
                        $translatedCol = Inflector::humanize(Inflector::singularize(Inflector::tableize($columnName)));
                    }
                }
            }
            // saves label in runtime array to avoid multiple calls to the db or cache
            $this->labels[$module][$columnName] = $translatedCol;
        }
        return __($translatedCol);
    }

    /**
     * Extract the values in every columns
     * @param array $references the variables/arrays in this array are for references
     * @param ArrayObject $tempRow for holding converted values extracted from the excel sheet on a per row basis
     * @param ArrayObject $originalRow for holding the original value extracted from the excel sheet on a per row basis
     * @param ArrayObject $rowInvalidCodeCols for holding error messages found on option field columns
     * @return boolean                          returns whether the row being checked pass option field columns check
     */
    protected function _extractRecord($references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols, ArrayObject $extra)
    {

        [
            'sheet' => $sheet,
            'mapping' => $mapping,
            'columns' => $columns,
            'lookup' => $lookup,
            'totalColumns' => $totalColumns,
            'row' => $row,
            'activeModel' => $activeModel,
            'systemDateFormat' => $systemDateFormat,
        ] = $references;
        $references = null;

        $rowPass = true;
        $customColumnCounter = 0;

        for ($col = 0; $col < $totalColumns; ++$col) {
            $colm = $col + 1; //POCOR-8343
            $cell = $sheet->getCellByColumnAndRow($colm, $row);

            $originalValue = $this->getFormattedCellValue($cell);
            $cellValue = $this->castValue($originalValue);

            $excelMappingObj = $mapping[$col];
            $foreignKey = $excelMappingObj->foreign_key;
            $lookupPlugin = $excelMappingObj->lookup_plugin;
            $lookupModel = $excelMappingObj->lookup_model;
            $lookupColumn = $excelMappingObj->lookup_column;
            $lookupColumnName = $excelMappingObj->column_name;
            $mappingModel = $excelMappingObj->model;

            if ($mappingModel == 'Student.Extracurriculars'
                && $lookupColumnName == 'openemis_no') {
                $columnName = 'security_user_id';
                $securityUserID = $this->getSecurityUserIDbyOpenemisNO($originalValue);
                if($securityUserID){
                    $originalRow[$col] = $securityUserID;
                    $cellValue = $securityUserID;
                }
                if (!$securityUserID) {
                    $rowInvalidCodeCols[$columnName] = __('OpenEMIS ID is not found');
                    $rowPass = false;
                    $extra['entityValidate'] = false;
                }
            } else if ($mappingModel == 'Training.TrainingSessionTraineeResults'
                && $lookupColumnName == 'OpenEMIS_ID') { //POCOR-5695 starts
                $columnName = 'OpenEMIS_ID';
                $securityUserID = $this->getSecurityUserIDbyOpenemisNO($originalValue);
                if (!$securityUserID) {
                    $rowInvalidCodeCols[$columnName] = __('OpenEMIS ID is not found');
                    $rowPass = false;
                    $extra['entityValidate'] = false;
                }
                $originalRow[$col] = $originalValue;
                $cellValue = $originalValue;//POCOR-5695 ends
            } else if ($mappingModel == 'Student.StudentGuardians'
                && $lookupColumnName == 'guardian_id'
                && $lookupColumn == 'openemis_no'
                && !empty($originalValue)) { //POCOR-5913 starts
                $i = 1;
                $columnName = 'guardian_id';
                $userIdentities = self::getDynamicTableInstance('User.Identities');
                $identityTypes = self::getDynamicTableInstance('identity_types');
                $User = self::getDynamicTableInstance('security_users');
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
                        [$userIdentities->getAlias() => $userIdentities->getTable()],
                        [$userIdentities->aliasField('security_user_id = ') . $User->aliasField('id')]
                    )
                    ->leftJoin(
                        [$identityTypes->getAlias() => $identityTypes->getTable()],
                        [$identityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')]
                    )
                    ->where([
                        'OR' => [
                            $User->aliasField('openemis_no') => $originalValue,
                            'AND' => [
                                $userIdentities->aliasField('number') => $originalValue,
                                $identityTypes->aliasField('default') => 1
                            ]
                        ]

                    ])
                    ->first();
                if (!$securityUser) {
                    $rowInvalidCodeCols[$columnName] = __('OpenEMIS ID is not valid');
                    $rowPass = false;
                    $extra['entityValidate'] = false;

                    $originalRow[$col] = $originalValue;
                    $cellValue = $originalValue;
                } else {
                    $originalRow[$col] = $securityUser->id;
                    $cellValue = $securityUser->id;
                }

            } else if ($mappingModel == 'Student.StudentGuardians'
                && $lookupColumnName == 'guardian_id'
                && $lookupColumn == 'number'
                && !empty($originalValue)) {
                if ($i == 1) {
                    break;
                }
                $k = 1;
                $columnName = 'guardian_id';
                $userIdentities = self::getDynamicTableInstance('User.Identities');
                $identityTypes = self::getDynamicTableInstance('identity_types');
                $User = self::getDynamicTableInstance('security_users');
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
                        [$userIdentities->getAlias() => $userIdentities->getTable()],
                        [$userIdentities->aliasField('security_user_id = ') . $User->aliasField('id')]
                    )
                    ->leftJoin(
                        [$identityTypes->getAlias() => $identityTypes->getTable()],
                        [$identityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')]
                    )
                    ->where([
                        'OR' => [
                            $User->aliasField('openemis_no') => $originalValue,
                            'AND' => [
                                $userIdentities->aliasField('number') => $originalValue,
                                $identityTypes->aliasField('default') => 1
                            ]
                        ]

                    ])
                    ->first();
                if (!$securityUser) {
                    $rowInvalidCodeCols[$columnName] = __('Identity number is not valid');
                    $rowPass = false;
                    $extra['entityValidate'] = false;

                    $originalRow[$col] = $originalValue;
                    $cellValue = $originalValue;
                } else {
                    $originalRow[$col] = $securityUser->id;
                    $cellValue = $securityUser->id;
                }
                //POCOR-5913 ends
            } else {
                $columnName = $columns[$col];
                $originalRow[$col] = $originalValue;
            }

            //POCOR-5913 starts
            if ($mappingModel == 'Student.StudentGuardians'
                && $lookupColumnName == 'guardian_id'
                && $lookupColumn == 'openemis_no'
                && empty($originalValue)) {
                $i = 0;
                continue;
            } else if ($mappingModel == 'Student.StudentGuardians'
                && $lookupColumnName == 'guardian_id'
                && $lookupColumn == 'number'
                && empty($originalValue)) {
                if ($i == 0) {

                } else {
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
            if (isset($tempRow[$columnName]) && !empty($tempRow[$columnName]) && $tempRow[$columnName] !== 0) {
                continue;
            }
            if (!empty($val)) {
                $columnAttr = $activeModel->getSchema()->getColumn($columnName);
                if ($columnAttr['type'] == 'date') { // checking the main table schema data type
                    $originalRow[$col] = $val;

                    if (!empty($val) && preg_match($datePattern, $val)) {
                        $val = trim($val); // POCOR-4251 trim the whitespace on the date
                        $split = explode('/', $val);
                        $dateObject = new Date();
                        $dateObject->setDate((int)$split[2], (int)$split[1], (int)$split[0]);

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
            $translatedCol = $this->getExcelLabel($activeModel->getAlias(), $columnName);
            $columnDescription = strtolower($mapping[$col]->description);
            $isOptional = $mapping[$col]->is_optional;
            if (!$isOptional) {
                $isOptional = substr_count($columnDescription, 'not required');
            }

            if ($foreignKey == self::FIELD_OPTION) {
                if (!empty($cellValue)) {
                    if (isset($lookup[$col][$cellValue])) {
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

                    if ($registryAlias == '.InstitutionSubjects' && $mappingModel == 'Institution.StudentAbsencesPeriodDetails'){
                        $registryAlias = 'Institution.InstitutionSubjects';
                        $excelLookupModel = TableRegistry::getTableLocator()->get($registryAlias);
                        $this->directTables[$registryAlias] = ['excelLookupModel' => $excelLookupModel];
                    }else{
                        $excelLookupModel = TableRegistry::getTableLocator()->get($registryAlias);
                        $this->directTables[$registryAlias] = ['excelLookupModel' => $excelLookupModel];
                    }

                }
                $excludeValidation = false;
                if (!empty($cellValue)) {
                    if (isset($extra['lookup'][$excelLookupModel->getAlias()][$cellValue])) {
                        $record = $extra['lookup'][$excelLookupModel->getAlias()][$cellValue];
                    } else {
                        //POCOR-5913 starts
                        if ($mappingModel == 'Student.StudentGuardians' && $lookupColumnName == 'guardian_id') {
                            if ($securityUser) {
                                $cellValue = $securityUser->openemis_id;
                            } else {
                                $cellValue = $originalValue;
                            }

                            if ($mappingModel == 'Student.StudentGuardians' && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number') {
                                $lookupColumn = 'openemis_no';
                            }
                        }//POCOR-5913 ends

                        $lookupQuery = $excelLookupModel->find()->where([$excelLookupModel->aliasField($lookupColumn) => $cellValue]);
                        $record = $lookupQuery->first();
                        $extra['lookup'][$excelLookupModel->getAlias()][$cellValue] = $record;
                    }
                } else {
                    $columnAttr =$activeModel->getSchema()->getColumn($columnName);
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
                        //POCOR-6681 starts
                        if ($mappingModel == 'Institution.InstitutionMealStudents' && $lookupColumnName == 'OpenEMIS_ID' && $lookupColumn == 'openemis_no') {
                            $val = $record->openemis_no;//POCOR-6681 end
                        } else {
                            $val = $record->id;
                        }
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
                            if ($mappingModel == 'Student.StudentGuardians' && $lookupColumnName == 'guardian_id' && $lookupColumn == 'number' && empty($originalValue)) {
                                if ($i == 0) {
                                    $columnName = 'guardian_id';
                                    $rowInvalidCodeCols[$columnName] = __('Please enter either OpenEMIS ID or Identity number for guardian');
                                    $rowPass = false;
                                    $extra['entityValidate'] = false;
                                }
                                //POCOR-5913 ends
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
        // add condition to check if its importing institutions
        $plugin = $this->getConfig('plugin');
        $model = $this->getConfig('model');
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
            $rowPass = $rowPassEvent->getResult();
        }

        return $rowPass;
    }



    protected function getFormattedCellValue($cell)
    {
        if (self::timeTwelvehoursValidator($cell->getFormattedValue()) == 1) {
            $cell->getStyle()->getNumberFormat()->setFormatCode('h:mm:ss');
            return $cell->getFormattedValue();
        } elseif (SpreadsheetDate::isDateTime($cell)) {
            $cell->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            return $cell->getFormattedValue();
        }
        return $cell->getValue();
    }

    protected function castValue($value)
    {
        return is_double($value) || is_bool($value) ? (string)$value : $value;
    }

    private static function timeTwelvehoursValidator($time)
    {


        $regex = '/^(1[012]|[1-9])\:[0-5][0-9]\:[0-5][0-9]\s*[ap]m$/i';

        if (preg_match($regex, $time)) {
            return true;
        }

        $regex = '/^(1[012]|[1-9])\:[0-5][0-9]\s*[ap]m$/i';

        if (preg_match($regex, $time)) {
            return true;
        }

        $regex = '/^([0-1][0-9])\:[0-5][0-9]\s*[ap]m$/i';

        if (preg_match($regex, $time)) {
            return true;
        }

        $regex = '/^([0-1][0-9])\:[0-5][0-9]\:[0-5][0-9]\s*[ap]m$/i';

        if (preg_match($regex, $time)) {
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
                "date(start_date) <= date '" . $date . "'",
                "date(end_date) >= date '" . $date . "'",
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
                "id = " . $academicPeriodId
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

    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

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


    private function getSecurityUserIDbyOpenemisNO($originalValue)
    {
        $securityUserID = false;
        $openemis_no = $originalValue ?? false;
        if(!$openemis_no){
            return $securityUserID;
        }
        $User = self::getDynamicTableInstance('security_users');

        $securityUser = $User
            ->find()
            ->select(['id' => $User->aliasField('id')])
            ->where(['openemis_no' => $openemis_no])
            ->first();
        if($securityUser){
            $securityUserID = $securityUser->id;
        }
        return $securityUserID;
    }

}
