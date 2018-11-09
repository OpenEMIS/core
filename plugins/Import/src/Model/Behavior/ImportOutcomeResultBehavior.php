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

class ImportOutcomeResultBehavior extends Behavior
{
    use EventTrait;

    protected $labels = [];

    protected $_defaultConfig = [
        'plugin' => '',
        'model' => '',
        'max_rows' => 2000,
        'max_size' => 524288,
        'backUrl' => [],
        'custom_text' => ''
    ];
    protected $rootFolder = 'import';
    private $_fileTypesMap = [
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
        $model = $this->config('model');
        if (empty($model)) {
            $this->config('model', Inflector::pluralize($plugin));
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
        ini_set('max_execution_time', 180);
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

            $educationSubjectsTable = TableRegistry::get('Education.EducationSubjects');
            $education_subject_id = $this->_table->request->query['education_subject'];
            $subjectName = $educationSubjectsTable->get($education_subject_id)->name;

            // check correct template
            $header = array($subjectName, 'Outcome -->');

            //calculate number of student
            $classId = $this->_table->request->query['class'];
            $institutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
            $studentStatusesTable = TableRegistry::get('Student.StudentStatuses');
            $arrayStudent = $institutionClassStudentsTable->find()
                ->matching('Users')
                ->matching('InstitutionClasses')
                ->matching('EducationGrades')
                ->matching($studentStatusesTable->alias(), function ($q) use ($studentStatusesTable) {
                    return $q->where([$studentStatusesTable->aliasField('code') => 'CURRENT']);
                })
                ->where([
                    $institutionClassStudentsTable->aliasField('institution_class_id') => $classId
                ])
                ->toArray();

            // calculate outcome criterias
            $template = $this->_table->request->query['template'];

            $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
            $aryOutcomeCriteria = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
                $outcomeCriteriasTable->aliasField('outcome_template_id') => $template
            ])
            ->toArray();
            $totalCriteria = count($aryOutcomeCriteria);
            $totalColumns = count($totalCriteria) + 3;

            //comment will be last after outcomecriterias
            $commentColumn = $totalColumns + 1;


            $institutionOutcomeSubjectCommentsTable = TableRegistry::get('Institution.InstitutionOutcomeSubjectComments');
            $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
            $outcomeTemplatesTable = TableRegistry::get('Outcome.OutcomeTemplates');

            $educationGradeId = $outcomeTemplatesTable->find()
                ->where([
                    $outcomeTemplatesTable->aliasField('id') => $template,
                ])
                ->extract('education_grade_id')
                ->first();

            if (!$this->isCorrectTemplate($header, $sheet, 2, 2)) {
                $entity->errors('select_file', [$this->getExcelLabel('Import', 'wrong_template')], true);

                return false;
            }

            $numberOfStudents = count($arrayStudent);
            for ($row = 4; $row < $numberOfStudents + 4; $row++) {

                // do the save for the comment
                $student = $sheet->getCellByColumnAndRow(0, $row);
                $studentOpenEmisId = $student->getValue();
                $UsersTable = TableRegistry::get('User.Users');

                $User = $UsersTable->find()
                    ->select(['id'])
                    ->where([
                        $UsersTable->aliasField('openemis_no') => $studentOpenEmisId
                    ])
                    ->first();

                $comment = $sheet->getCellByColumnAndRow($commentColumn, $row)->getValue();

                if (!empty($comment)) {
                    $institutionOutcomeSubjectCommentsData = $institutionOutcomeSubjectCommentsTable->newEntity([
                        'comments' => $comment,
                        'student_id' => $User->id,
                        'outcome_template_id' => $template,
                        'outcome_period_id' => $this->_table->request->data['ImportOutcomeResults']['outcome_period'],
                        'education_grade_id' => $educationGradeId,
                        'education_subject_id' => $education_subject_id,
                        'institution_id' => $this->_table->request->session()->read('Institution.Institutions.id'),
                        'academic_period_id' => $this->_table->request->data['ImportOutcomeResults']['academic_period']
                    ]);

                    $institutionOutcomeSubjectCommentsTable->save($institutionOutcomeSubjectCommentsData);
                }
                // end of save comment

                for ($column = 2; $column <= $totalColumns; $column++) {
                    $cell = $sheet->getCellByColumnAndRow($column, $row);
                    $gradeValue = $cell->getValue();

                    // if there is no any data, just skip
                    if (empty($gradeValue)) {
                        continue;
                    }

                    $tempRow = new ArrayObject;
                    $rowInvalidCodeCols = new ArrayObject;

                    // for each columns
                    $references = [
                        'commentColumn'=>$commentColumn,
                        'numberColumn'=>$column,
                        'sheet'=>$sheet,
                        'totalColumns'=>$totalCriteria,
                        'row'=>$row,
                        'activeModel'=>$activeModel,
                        'systemDateFormat'=>$systemDateFormat,
                    ];

                    $originalRow = new ArrayObject;
                    $checkCustomColumn = new ArrayObject;
                    $extra['entityValidate'] = true;
                    $this->_extractRecord($references, $tempRow, $originalRow, $rowInvalidCodeCols, $extra);

                    $tempRow = $tempRow->getArrayCopy();
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

                        $columns = array("outcome_criteria_id", "student_id", "outcome_grading_option_id");
                        $tempPassedRecord = [
                            'row_number' => $row,
                            'data' => $this->_getReorderedEntityArray($clonedEntity, $columns, $originalRow, $systemDateFormat)
                        ];

                        $tempPassedRecord = new ArrayObject($tempPassedRecord);

                        $dataPassed[] = $tempPassedRecord->getArrayCopy();
                    }

                }
            }

            $resultHeader = array('Outcome Criteria Id', 'OpenEMIS ID', 'Outcome Grading Option');

            $session = $this->_table->Session;
            $completedData = [
                'uploadedName' => $uploadedName,
                'dataFailed' => $dataFailed,
                'totalImported' => $totalImported,
                'totalUpdated' => $totalUpdated,
                'totalRows' => count($dataFailed) + $totalImported + $totalUpdated,
                'header' => $resultHeader,
                'failedExcelFile' => $this->_generateDownloadableFile($dataFailed, 'failed', $resultHeader, $systemDateFormat),
                'passedExcelFile' => $this->_generateDownloadableFile($dataPassed, 'passed', $resultHeader, $systemDateFormat),
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

        $dataSheetName = $this->getExcelLabel('general', 'data');

        $objPHPExcel = new \PHPExcel();

        $headerRow3 = array("OpenEMIS ID", "Student Name", "Outcome Grading Option Id");

        $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $headerRow3, '');

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
                'element' => 'Import./outcome_results',
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
        //set the image
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
    }

    public function beginExcelTopTittle($objPHPExcel, $title = '')
    {
        $activeSheet = $objPHPExcel->getActiveSheet();
        $activeSheet->setCellValue("C1", $title);
    }

    public function endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, $lastRowToAlign = 2, $applyFillFontSetting = [], $applyCellBorder = [])
    {
        if (empty($applyFillFontSetting)) {
            $applyFillFontSetting = ['s'=>2, 'e'=>2];
        }

        if (empty($applyCellBorder)) {
            $applyCellBorder = ['s'=>2, 'e'=>2];
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
        $activeSheet = $objPHPExcel->getActiveSheet();

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName,  __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) .' '. $dataSheetName);

        $educationSubjectsTable = TableRegistry::get('Education.EducationSubjects');
        $education_subject_id = $this->_table->request->query['education_subject'];
        $name = $educationSubjectsTable->get($education_subject_id)->name;

        $activeSheet->setCellValue("A2", $name);
        $activeSheet->setCellValue("B2", "Outcome -->");

        //headerRow3
        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 3, $value);
        }

        $template = $this->_table->request->query['template'];

        $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
        $arrayOutcomeCriterias = $outcomeCriteriasTable->find()
        ->where([
            $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
            $outcomeCriteriasTable->aliasField('outcome_template_id') => $template
        ])
        ->toArray();

        $suggestedRowHeight = 0;
        foreach ($arrayOutcomeCriterias as $key => $value) {
            $key = $key + 2;
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 1, $value->id);
            $activeSheet->setCellValue($alpha . 2, $value->name);
            if ($this->suggestRowHeight(strlen($value->name), 15) > $suggestedRowHeight) {
                $suggestedRowHeight = $this->suggestRowHeight(strlen($value->name), 15);
            }
            $activeSheet->getColumnDimension( $alpha )->setWidth(35);
        }
        $activeSheet->getRowDimension(1)->setRowHeight(80);
        $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);

        $classId = $this->_table->request->query['class'];
        $institutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        $studentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $arrayStudent = $institutionClassStudentsTable->find()
            ->select([
                $institutionClassStudentsTable->Users->aliasField('openemis_no'),
                $institutionClassStudentsTable->Users->aliasField('first_name'),
                $institutionClassStudentsTable->Users->aliasField('middle_name'),
                $institutionClassStudentsTable->Users->aliasField('third_name'),
                $institutionClassStudentsTable->Users->aliasField('last_name'),
                $institutionClassStudentsTable->Users->aliasField('preferred_name'),
            ])
            ->matching('Users')
            ->matching('InstitutionClasses')
            ->matching('EducationGrades')
            ->matching($studentStatusesTable->alias(), function ($q) use ($studentStatusesTable) {
                return $q->where([$studentStatusesTable->aliasField('code') => 'CURRENT']);
            })
            ->where([
                $institutionClassStudentsTable->aliasField('institution_class_id') => $classId
            ])
            ->order([
                $institutionClassStudentsTable->Users->aliasField('first_name'),
                $institutionClassStudentsTable->Users->aliasField('last_name')
            ])
            ->toArray();

        $i = 4;
        foreach ($arrayStudent as $key => $value) {
            $activeSheet->setCellValue('A' . $i, $value->_matchingData['Users']->openemis_no);
            $activeSheet->setCellValue('B' . $i, $value->_matchingData['Users']->name);
            $i++;
            $activeSheet->getColumnDimension('A')->setAutoSize(true);
            $activeSheet->getColumnDimension('B')->setAutoSize(true);

        }
        // -1 to start from A, +2 is for education subject and outcome-->, -1+2=+1
        $arrayLastAlpha = $this->getExcelColumnAlpha(count($arrayOutcomeCriterias)+1);
        $activeSheet->mergeCells('C3:'. $arrayLastAlpha.'3');
        // -1 to start from A, +2 is for education subject and outcome-->, +1 comment after criteria name, -1+2+1=+2
        $Comment = $this->getExcelColumnAlpha(count($arrayOutcomeCriterias)+2);
        $activeSheet->setCellValue($Comment . '3', "Comment");
        $activeSheet->getColumnDimension($Comment)->setAutoSize(true);

    }

    public function setResultDataTemplate($objPHPExcel, $dataSheetName, $header, $type)
    {
        $objPHPExcel->setActiveSheetIndex(0);

        $activeSheet = $objPHPExcel->getActiveSheet();

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName,  __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) .' '. $dataSheetName);
        $this->beginExcelTopTittle($objPHPExcel, __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) .' '. $dataSheetName);
        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 2, $value);
        }

        $headerLastAlpha = $this->getExcelColumnAlpha(count($header)-1);
        $lastRowToAlign = 2;
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
        $outcomeGradingOptionsTable = TableRegistry::get('Outcome.OutcomeGradingOptions');

        $gradeOptionArray = $outcomeGradingOptionsTable->find()
            ->select(['name'])
            ->toArray();

        $dropDownList = '';
        foreach ($gradeOptionArray as $singleGradeOptionArray) {
            if ($singleGradeOptionArray->name == end($gradeOptionArray)->name) {
                $dropDownList .= $singleGradeOptionArray->name;
            } else {
                $dropDownList .= $singleGradeOptionArray->name . ', ';
            }
        }

        $education_subject_id = $this->_table->request->query['education_subject'];
        $template = $this->_table->request->query['template'];

        $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
        $outcomeCriteriasArray = $outcomeCriteriasTable->find()
        ->where([
            $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
            $outcomeCriteriasTable->aliasField('outcome_template_id') => $template
        ])
        ->toArray();

        $classId = $this->_table->request->query['class'];
        $institutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        $studentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $studentArray = $institutionClassStudentsTable->find()
            ->matching('Users')
            ->matching('InstitutionClasses')
            ->matching('EducationGrades')
            ->matching($studentStatusesTable->alias(), function ($q) use ($studentStatusesTable) {
                return $q->where([$studentStatusesTable->aliasField('code') => 'CURRENT']);
            })
            ->where([
                $institutionClassStudentsTable->aliasField('institution_class_id') => $classId
            ])
            ->toArray();
        //A is 0 in excel column, so 2 is C
        for ($column = 2; $column < count($outcomeCriteriasArray)+2; ++$column) {
            $alpha = $this->getExcelColumnAlpha($column);
            for ($i = 4; $i < count($studentArray) + 4; $i++) {
                $objPHPExcel->setActiveSheetIndex(0);
                $objValidation = $objPHPExcel->getActiveSheet()->getCell($alpha . $i)->getDataValidation();
                $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                $objValidation->setAllowBlank(false);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setFormula1('"'.$dropDownList.'"');
            }
        }
    }

    /**
     * Set a record columns value based on what is being saved in the table.
     * @param  Entity $entity           Cloned entity. The actual entity is not saved yet but already validated but we are using a cloned entity in case it might be messed up.
     * @param  Array  $columns          Target Model columns defined in import_mapping table.
     * @param  string $systemDateFormat System Date Format which varies across deployed environments.
     * @return Array                    The columns value that will be written to a downloadable excel file.
     */
    private function _getReorderedEntityArray(Entity $entity, array $columns, ArrayObject $originalRow, $systemDateFormat)
    {
        $array = [];
        foreach ($columns as $col => $property) {
            /*
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

            $rowData = 3;

            $this->setResultDataTemplate($objPHPExcel, $dataSheetName, $newHeader, $type);

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
        $numberColumn = $references['numberColumn'];
        $sheet = $references['sheet'];
        $totalColumns = $references['totalColumns'];
        $row = $references['row'];
        $activeModel = $references['activeModel'];
        $systemDateFormat = $references['systemDateFormat'];
        $references = null;

        $rowPass = true;

        $student = $sheet->getCellByColumnAndRow(0, $row);
        $studentValue = $student->getValue();
        $outcomeId = $sheet->getCellByColumnAndRow($numberColumn, 1);
        $outcomeIdValue = $outcomeId->getValue();
        $cell = $sheet->getCellByColumnAndRow($numberColumn, $row);
        $gradeValue = $cell->getValue();

        $usersTable = TableRegistry::get('User.Users');

        $User = $usersTable->find()
            ->select(['id'])
            ->where([
                $usersTable->aliasField('openemis_no') => $studentValue
            ])
            ->first();

        $outcomeGradingOptionsTable = TableRegistry::get('Outcome.OutcomeGradingOptions');

        $Grading = $outcomeGradingOptionsTable->find()
            ->select(['id'])
            ->where([
                $outcomeGradingOptionsTable->aliasField('name') => $gradeValue
            ])
            ->first();

        if (empty($Grading)) {
            $rowPass = false;
            $rowInvalidCodeCols['outcome_grading_option_id'] = __('Wrong Grade Option');
            $extra['entityValidate'] = false;
        }else {
            $tempRow['outcome_criteria_id'] = $outcomeIdValue;
            $tempRow['student_id'] = $User->id;
            $tempRow['outcome_grading_option_id'] = $Grading->id;
        }
        $originalRow[] = $outcomeIdValue;
        $originalRow[] = $studentValue;
        $originalRow[] = $gradeValue;

        // add condition to check if its importing institutions
        $plugin = $this->config('plugin');
        $model = $this->config('model');

        if ($rowPass) {
            $rowPassEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportModelSpecificValidation'), 'onImportModelSpecificValidation', [$references, $tempRow, $originalRow, $rowInvalidCodeCols]);
            $rowPass = $rowPassEvent->result;
        }


        return $rowPass;
    }


/******************************************************************************************************************
**
** Miscelleneous Functions
**
******************************************************************************************************************/
    private function eventKey($key)
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
