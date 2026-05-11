<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
//POCOR-9349 STARTS
//use PHPExcel_Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Cake\Datasource\EntityInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Cake\Http\Session;
//POCOR-9349 ENDS
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

class ImportInstitutionSurveysTable extends AppTable {
    const RECORD_QUESTION = 2;
    const FIRST_RECORD = 3;

    private $institutionSurveyId = false;
    private $institutionSurvey = false;
    protected $sessionKey;//POCOR-9349

    public function initialize(array $config): void {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'InstitutionSurveys']);

        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionSurveys = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveys');
        $this->InstitutionSurveyAnswers = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveyAnswers');
        $this->InstitutionSurveyTableCells = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveyTableCells');
        $this->SurveyForms = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
        $this->CustomFieldTypes = TableRegistry::getTableLocator()->get('CustomField.CustomFieldTypes');
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
        if (isset($toolbarButtons['back'])) {
            $toolbarButtons['back']['url'] = $this->ControllerAction->url('view');
            $toolbarButtons['back']['url']['action'] = 'Surveys';
            $toolbarButtons['back']['url']['1'] = $this->request->getParam('pass')[1];//POCOR-9349
        }
    }

    public function beforeAction($event) {
        if ($this->action != 'downloadFailed' && $this->action != 'downloadPassed') {
            $session = $this->Session;
            //POCOR-9349 starts changes for V4
            if (!empty($this->request->getParam('pass')) && isset($this->request->getParam('pass')[1])) {
                $this->institutionSurveyId = $this->paramsDecode($this->request->getParam('pass')[1])['id'];
            }//POCOR-9349 ends

            $this->institutionSurvey = $this->InstitutionSurveys
                ->find()
                ->contain([
                    'SurveyForms.CustomFields.CustomFieldOptions', 
                    'SurveyForms.CustomFields.CustomTableRows' => function ($q) {
                            return $q->where(['CustomTableRows.visible' => 1]);
                        },
                    'SurveyForms.CustomFields.CustomTableColumns' => function ($q) {
                            return $q
                                ->where(['CustomTableColumns.visible' => 1]);
                        }
                ])
                ->where([$this->InstitutionSurveys->aliasField('id') => $this->institutionSurveyId])
                ->first()
                ;

            // This is to sort the questions by the order
            $surveyFormQuestions = [];
            foreach ($this->institutionSurvey->survey_form->custom_fields as $question) {
                $order = $question['_joinData']['order'];
                $surveyFormQuestions[$order] = $question;
            }
            ksort($surveyFormQuestions);
            $surveyFormQuestions = array_values($surveyFormQuestions);
            $this->institutionSurvey->survey_form->custom_fields = $surveyFormQuestions;

            $this->fieldTypes = $this->CustomFieldTypes
                ->find('list', ['keyField' => 'code', 'valueField' => 'value'])
                ->toArray()
                ;
            $this->sessionKey = $this->getRegistryAlias().'.Import.data';
            $this->InstitutionSurveyAnswers->ControllerAction = $this->ControllerAction;
        }
    }

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $newEvent = [];
        $newEvent['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $newEvent['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events = array_merge($events, $newEvent);
        return $events;
    }

    // public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona) {
    public function onGetBreadcrumb(EventInterface $event, $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->getAlias());
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys'];
        $Navigation->substituteCrumb($crumbTitle, 'Surveys', $url);
        $Navigation->addCrumb($crumbTitle);
    }

    public function template() {
        $folder = $this->prepareDownload();

        $surveyForm = $this->institutionSurvey->survey_form;
        $header = $this->_generateHeader($surveyForm->custom_fields);
        $dataSheetName = __('Data');

        $excelFile = sprintf('OpenEMIS_Core_Import_Institution_Survey_%s_Template.xlsx', $surveyForm->code);
        $excelPath = $folder . DS . $excelFile;
        //POCOR-9349 starts changes for V4
        // $objPHPExcel = new \PHPExcel();
        // $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $header, '');        
        // $this->setCodesDataTemplate($objPHPExcel);
        // $objPHPExcel->setActiveSheetIndex(0);
        // $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        // $objWriter->save($excelPath);

        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        // Call your custom template setup methods (adapt if needed)
        $this->setImportDataTemplate($spreadsheet, $dataSheetName, $header, '');
        $this->setCodesDataTemplate($spreadsheet);
        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $writer->save($excelPath);  
        //POCOR-9349 ends
        $this->performDownload($excelFile);
        die;
    }

    private function _generateHeader($surveyQuestions) {
        $header = [];
        foreach ($surveyQuestions as $question) {
            if ($question['field_type'] == 'TABLE') {
                $column = [];
                $row = [];
                foreach($question['custom_table_rows'] as $tableRow) {
                    $row[$tableRow['order']] = $tableRow;
                }
                ksort($row);
                $row = array_values($row);
                foreach($question['custom_table_columns'] as $tableCol) {
                    $column[$tableCol['order']] = $tableCol;
                }
                ksort($column);
                $column = array_values($column);

                if (sizeof($row) !=0 && sizeof($column) !=0 ) {
                    for($i = 1; $i < sizeof($column); $i++) {
                        foreach ($row as $r) {
                            $header[] = '(' . $question->code . ') '. $question->name . ' ('.$column[$i]['name'].', '.$r['name'].')';
                        }
                    }
                }
            } else {
                $header[] = trim('(' . $question->code .') '. $question->name);
            }
        }
        return $header;
    }

    public function excelGetCodesData() {
        $survey_form = $this->institutionSurvey->survey_form;
        $questions = $survey_form->custom_fields;
        $data = [];
        $data[0] = [
            'data' => [
                [__('Name'), __('Code')],
                [$survey_form->name, $survey_form->code]
            ],
            'sheetName' => __('Survey Form'),
            'noDropDownList' => true
        ];
        foreach ($questions as $question) {
            if ($question->field_type == 'DROPDOWN' || $question->field_type == 'CHECKBOX') {
                $sheetName = $question->code;
                $columnOrder = $question->_joinData->order;
                $data[$columnOrder] = [
                    'data' => [],
                    'sheetName' => '( '. $question->code .' ) '. $question->name . "\n\n"
                ];
                $data[$columnOrder]['lookupColumn'] = 2;
                $data[$columnOrder]['data'][] = [__('Answer Name'), __('Answer Code')];
                if ($question->field_type == 'DROPDOWN') {
                    $data[$columnOrder]['sheetName'] .= __('(Use only one of the answer codes)');
                    foreach($question->custom_field_options as $key=>$row) {
                        if ($row->visible) {
                            $data[$columnOrder]['data'][] = [$row->name, $row->id];
                        }
                    }
                } elseif ($question->field_type == 'CHECKBOX') {
                    $data[$columnOrder]['sheetName'] .= __('(Multiple codes can be selected and seperated by comma and a space. Example: 1, 2)');
                    $data[$columnOrder]['noDropDownList'] = true;
                    foreach($question->custom_field_options as $key=>$row) {
                        if ($row->visible) {
                            $data[$columnOrder]['data'][] = [$row->name, $row->id];
                        }
                    }
                }
            }
        }
        return $data;
    }

    private function getCellValue($sheet, $columnNumber, $rowNumber) {
        $cell = $sheet->getCellByColumnAndRow($columnNumber, $rowNumber);
        $cellValue = $cell->getValue();
        return $cellValue;
    }

    /**
     * Actual Import business logics reside in this function
     * @param  Event        $event  Event object
     * @param  Entity       $entity Entity object containing the uploaded file parameters 
     * @param  ArrayObject  $data   Event object
     * @return Response             Response object
     */
    public function addBeforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $data) {
        return function ($model, $entity) {
      
            $surveyStatus = $this->institutionSurvey->status_id;
            $WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $workflowStepEntity = $WorkflowSteps
                ->find()
                ->where([$WorkflowSteps->aliasField('id') => $surveyStatus])
                ->first();

            if($workflowStepEntity && $workflowStepEntity->category == WorkflowSteps::DONE) {
                $model->Alert->warning($this->aliasField('restrictImport'), ['reset'=>true]);
                return false;
            }
                
            $errors = $entity->getErrors();
            if (!empty($errors)) {
                return false;
            }

            $systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');
            $systemTimeFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('time_format');
            //POCOR-9349 starts changes for V4
            //$controller = $model->controller;
            //$controller->loadComponent('PhpExcel');
            // $fileObj = $entity->select_file;
            // $uploadedName = $fileObj['name'];
            // $uploaded = $fileObj['tmp_name'];
            //$objPHPExcel = $controller->PhpExcel->loadWorksheet($uploaded);
            //$sheet = $objPHPExcel->getSheet(0);
            $fileObj = $entity->select_file;
            if ($fileObj instanceof \Laminas\Diactoros\UploadedFile) {
                $uploadedName = $fileObj->getClientFilename();
                $uploaded = $fileObj->getStream()->getMetadata('uri'); // temp file path
            } else {
                // fallback if somehow still array (old code)
                $uploadedName = $fileObj['name'] ?? '';
                $uploaded = $fileObj['tmp_name'] ?? '';
            }
            // PhpSpreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploaded);
            $sheet = $spreadsheet->getSheet(0);
            
            $maxRows = 2002;
            $highestRow = $sheet->getHighestRow();
            if ($highestRow > ($maxRows + 2)) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'over_max_rows')], true);
                return false;
            }
            if ($highestRow == self::RECORD_QUESTION) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'no_answers')], true);
                return false;
            }

            $totalImported = 0;
            $totalUpdated = 0;
            $importedUniqueCodes = new ArrayObject;
            $dataFailed = [];
            $dataPassed = [];

            //$references = $objPHPExcel->getSheet(1);
            $references = $spreadsheet->getSheet(1);
            
            // get code from references sheet at it is located at cell B4
            $surveyCode = $references->getCell( "B4" )->getValue();
            if (empty($surveyCode)) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'survey_code_not_found')], true);
                return false;
            }
            //POCOR-9349 Ends
            $survey = $this->SurveyForms
                ->find()
                ->contain([
                    'CustomFields.CustomFieldOptions', 
                    'CustomFields.CustomTableColumns' => function ($q) {
                        return $q->where(['CustomTableColumns.visible' => 1]);
                    }, 
                    'CustomFields.CustomTableRows' => function ($q) {
                        return $q->where(['CustomTableRows.visible' => 1]);
                    }
                ])
                ->where([
                    $this->SurveyForms->aliasField('code') => $surveyCode
                ])
                ->first()
                ;

            if (empty($survey)) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'survey_not_found')]);
                return false;
            }

            $questions = $survey->custom_fields;
            // This is to sort the questions by the order
            $surveyFormQuestions = [];
            foreach ($questions as $question) {
                $order = $question['_joinData']['order'];
                $surveyFormQuestions[$order] = $question;
            }
            ksort($surveyFormQuestions);
            $surveyFormQuestions = array_values($surveyFormQuestions);
            $questions = $surveyFormQuestions;
            $header = $this->_generateHeader($questions);
            $totalColumns = count($header);
            // echo "<pre>"; print_r($questions);
            // die;
            for ($row = 2; $row <= $highestRow; ++$row) {
                if ($row == self::RECORD_QUESTION) { // skip header but check if the uploaded template is correct
                    if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
                        $entity->setError('select_file', [$this->getExcelLabel('Import', 'wrong_template')]);
                        return false;
                    }
                    continue;
                }
                // Check if there is no answers
                if ($row == $highestRow) { // if $row == $highestRow, check if the row cells are really empty, if yes then end the loop
                    if ($this->checkRowCells($sheet, $totalColumns, $row) === false) {
                        $entity->setError('select_file', [$this->getExcelLabel('Import', 'no_answers')]);
                        return false;
                    }
                }

                $colCount = 1;
                $originalRow = new ArrayObject();
                $rowInvalidCodeCols = [];
                $tempRow = [];
                $tempTableRow = [];
                $rowFailed = false;
                // echo "<pre>"; print_r($questions);
                // die;
                foreach ($questions as $k => $question) {//POCOR-9349 add $k key
                    $fieldType = $question->field_type;
                    // Debug: Log the question being processed
                    if ($row == 3) { // Only debug for first data row
                        error_log("Processing question " . ($k + 1) . "/" . count($questions) . ": " . $question->name . " (Field Type: " . $fieldType . ", Column: " . $colCount . ")");
                    }
                    $cellValue = $this->getCellValue($sheet, $colCount, $row);
                    $columnCode = $question->code;
                    
                    // Debug: Log the cell value
                    if ($row == 3) { // Only debug for first data row
                        error_log("Cell value for " . $question->name . ": '" . $cellValue . "'");
                    }
                    
                    if (empty($cellValue) && $question->is_mandatory) {
                        // echo "<pre>"; print_r($question);
                        // die;
                        $rowFailed = true;
                        $rowInvalidCodeCols[$question->name] = __('The answer for this question cannot be empty');
                    } 
                    
                    switch ($fieldType) {
                        case 'DROPDOWN':
                            $originalRow[$colCount - 1] = $cellValue;
                            $questionOptions = $question->custom_field_options;
                            $questionOptions = new Collection($questionOptions);
                            $filtered = $questionOptions->filter(function ($record, $key, $iterator) use ($cellValue) {
                                return $record->id == $cellValue;
                            });
                            $selectedAnswer = $filtered->toArray();
                            if (!empty($selectedAnswer)) {
                                $codeIndex = key($selectedAnswer);
                                $cellValue = $selectedAnswer[$codeIndex]->id;
                            }elseif(empty($selectedAnswer)) { //POCOR-6942
                                $codeIndex = '';
                                $cellValue = '';
                            } else {
                                $rowFailed = true;
                                $rowInvalidCodeCols[$question->name] = $this->getExcelLabel('Import', 'value_not_in_list');
                            }
                            $colCount++;
                            break;
                        case 'CHECKBOX':
                            $originalRow[$colCount - 1] = $cellValue;
                            $questionOptions = $question->custom_field_options;
                            $questionOptions = new Collection($questionOptions);
                            $selections = explode(',', $cellValue);
                            foreach ($selections as $selectionKey => $selection) {
                                $filtered = $questionOptions->filter(function ($record, $key, $iterator) use ($selection) {
                                    return $record->id == trim($selection);
                                });
                                $selectedAnswer = $filtered->toArray();
                                if (!empty($selectedAnswer)) {
                                    $codeIndex = key($selectedAnswer);
                                    $trimmedVal = $selectedAnswer[$codeIndex]->id;
                                } else {
                                    $rowFailed = true;
                                    $rowInvalidCodeCols[$question->name] = $this->getExcelLabel('Import', 'value_not_in_list');
                                }

                                if (!$rowFailed) {
                                    $obj = [
                                        'institution_survey_id' => $this->institutionSurvey->id,
                                        'survey_question_id' => $question->id,
                                        $this->fieldTypes[$fieldType] => $trimmedVal,
                                    ];
                                    $tableEntity = $this->InstitutionSurveyAnswers->newEmptyEntity();
                                    $this->InstitutionSurveyAnswers->patchEntity($tableEntity, $obj);
                                    $tempRow[$columnCode.$selectionKey] = $tableEntity;
                                }
                            }
                            $colCount++;
                            break;

                        case 'NUMBER':
                            $originalRow[$colCount - 1] = $cellValue;
                            if (!empty($cellValue)) {
                                if (!is_numeric($cellValue)) {
                                    $rowFailed = true;
                                    $rowInvalidCodeCols[$question->name] = __('Value should be numerical only');
                                }   
                            }
                            $colCount++;
                            break;

                        case 'DATE':
                            $originalRow[$colCount - 1] = $cellValue;
                            if (empty($cellValue)) {
                                // Skip if not mandatory
                                if ($question->is_mandatory) {
                                    $rowFailed = true;
                                    $rowInvalidCodeCols[$question->name] = __('The answer for this question cannot be empty');
                                }
                            } elseif (is_numeric($cellValue)) {
                                $cellValue = date('Y-m-d', ExcelDate::excelToTimestamp($cellValue));
                                try {
                                    $cellValue = new Date($cellValue);
                                    $originalRow[$colCount - 1] = $cellValue->format($systemDateFormat);
                                } catch (Exception $e) {
                                    $originalRow[$colCount - 1] = $cellValue;
                                }
                            } elseif (strtotime($cellValue) !== false) {
                                // Handle text date (e.g. 22/10/2025)
                                $date = new Date($cellValue);
                                $originalRow[$colCount - 1] = $date->format($systemDateFormat);
                            } else {
                                $rowFailed = true;
                                $rowInvalidCodeCols[$question->name] = __('Wrong date format. It should be DD/MM/YYYY');
                            }
                            $colCount++;
                            break;

                        case 'TIME':
                            $originalRow[$colCount - 1] = $cellValue;
                            // Skip empty time cells properly
                            if (empty($cellValue)) {
                                if ($question->is_mandatory) {
                                    $rowFailed = true;
                                    $rowInvalidCodeCols[$question->name] = __('The answer for this question cannot be empty');
                                }
                                $colCount++;
                                break; // Stop here — don’t check format
                            }

                            if (is_numeric($cellValue)) {
                                // Excel stores time as fraction of a day, so convert manually
                                $secondsInDay = 24 * 60 * 60;
                                $timestamp = (int) round($cellValue * $secondsInDay);

                                // Convert to time string
                                $formattedTime = gmdate('H:i:s', $timestamp); // 24-hour format
                                // or use 12-hour with AM/PM
                                // $formattedTime = gmdate('h:i:s A', $timestamp);

                                try {
                                    $cellValue = new Time($formattedTime);
                                    $originalRow[$colCount - 1] = $cellValue->format($systemTimeFormat);
                                } catch (Exception $e) {
                                    $originalRow[$colCount - 1] = $formattedTime;
                                }
                            } else {
                                // Handle string inputs like "02:11 PM" gracefully
                                $cellValue = trim($cellValue);
                                if (strtotime($cellValue) !== false) {
                                    $time = new Time($cellValue);
                                    $originalRow[$colCount - 1] = $time->format($systemTimeFormat);
                                } else {
                                    $rowFailed = true;
                                    $rowInvalidCodeCols[$question->name] = __('Wrong time format. It should be HH:MM:SS or HH:MM AM/PM');
                                }
                            }
                            $colCount++;
                            break;

                        case 'TABLE':
                            $columns = [];
                            $rows = [];
                            foreach($question->custom_table_rows as $tableRow) {
                                $rows[$tableRow['order']] = $tableRow;
                            }
                            ksort($rows);
                            $rows = array_values($rows);
                            foreach($question->custom_table_columns as $tableCol) {
                                $columns[$tableCol['order']] = $tableCol;
                            }
                            ksort($columns);
                            $columns = array_values($columns);

                            if (sizeof($rows) !=0 && sizeof($columns) !=0 ) {
                                for($i = 1; $i < sizeof($columns); $i++) {
                                    $c = $columns[$i];
                                    foreach ($rows as $r) {
                                        $originalRow[$colCount - 1] = $this->getCellValue($sheet, $colCount, $row);
                                        if (!empty($originalRow[$colCount - 1])) {
                                            $obj = [
                                                'institution_survey_id' => $this->institutionSurvey->id,
                                                'survey_question_id' => $question->id,
                                                'survey_table_row_id' => $r->id,
                                                'survey_table_column_id' => $c->id,
                                                'text_value' => $originalRow[$colCount - 1]
                                            ];
                                            $entityItem = $this->InstitutionSurveyTableCells->newEmptyEntity();
                                            $this->InstitutionSurveyTableCells->patchEntity($entityItem, $obj);
                                            $tempTableRow[] = $entityItem;
                                        } else {
                                            if ($question->is_mandatory) {
                                                $rowFailed = true;
                                                if (!array_key_exists($question->name, $rowInvalidCodeCols)) {
                                                    $rowInvalidCodeCols[$question->name] = __('The answer for this question cannot be empty');
                                                }
                                            }
                                        }
                                        $colCount++;
                                    }
                                }
                            }
                            break;
                        case 'COORDINATE'://POCOR-9349 starts Add case for COORDINATE
                            $originalRow[$colCount - 1] = $cellValue;
                            if (!empty($cellValue)) {
                                // Try to decode JSON
                                $decoded = json_decode($cellValue, true);

                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    $rowFailed = true;
                                    $rowInvalidCodeCols[$question->name] = __('Invalid JSON format for coordinates. Example: {"latitude": 0.0014556, "longitude": 0.12121}');
                                } else {
                                    // Validate required keys
                                    if (!isset($decoded['latitude']) || !isset($decoded['longitude'])) {
                                        $rowFailed = true;
                                        $rowInvalidCodeCols[$question->name] = __('Both latitude and longitude are required.');
                                    } else {
                                        $lat = $decoded['latitude'];
                                        $lng = $decoded['longitude'];

                                        // Validate numeric ranges
                                        if (!is_numeric($lat) || $lat < -90 || $lat > 90) {
                                            $rowFailed = true;
                                            $rowInvalidCodeCols[$question->name] = __('Latitude must be a number between -90 and 90.');
                                        } elseif (!is_numeric($lng) || $lng < -180 || $lng > 180) {
                                            $rowFailed = true;
                                            $rowInvalidCodeCols[$question->name] = __('Longitude must be a number between -180 and 180.');
                                        }
                                    }
                                }
                            } else {
                                if ($question->is_mandatory) {
                                    $rowFailed = true;
                                    $rowInvalidCodeCols[$question->name] = __('The coordinate value cannot be empty.');
                                }
                            }
                            $colCount++;
                            break; //POCOR-9349 ends   
                        default:
                            $originalRow[$colCount - 1] = $cellValue;
                            $colCount++;
                            break;
                    }
                    if ($fieldType != 'CHECKBOX' && $fieldType != 'TABLE') {
                        $obj = [
                            'institution_survey_id' => $this->institutionSurvey->id,
                            'survey_question_id' => $question->id,
                            $this->fieldTypes[$fieldType] => $cellValue,
                        ];
                        $tableEntity = $this->InstitutionSurveyAnswers->newEmptyEntity();
                        $this->InstitutionSurveyAnswers->patchEntity($tableEntity, $obj);
                        $tempRow[$columnCode] = $tableEntity;
                        
                        // Debug: Log the saving process
                        if ($row == 3) { // Only debug for first data row
                            error_log("Saving answer for " . $question->name . ": " . json_encode($obj));
                        }
                    }
                }
                // Debug: Log the final state
                if ($row == 3) { // Only debug for first data row
                    error_log("Final colCount: " . $colCount . ", Total questions: " . count($questions));
                    error_log("TempRow count: " . count($tempRow));
                }
                // echo "<pre>"; print_r($originalRow);
                // die;
                if (empty($rowInvalidCodeCols)) {
                    $dataPassed[] = [
                        'row_number' => $row,
                        'data' => $originalRow
                    ];

                    $this->InstitutionSurveyAnswers->deleteAll(['institution_survey_id' => $this->institutionSurvey->id]);
                    foreach ($tempRow as $entity) {
                        $result = $this->InstitutionSurveyAnswers->save($entity);
                        // Debug: Log the save result
                        if ($row == 3) { // Only debug for first data row
                            error_log("Saved entity: " . json_encode($entity->toArray()) . " - Result: " . ($result ? 'SUCCESS' : 'FAILED'));
                        }
                    }
                    $this->InstitutionSurveyTableCells->deleteAll(['institution_survey_id' => $this->institutionSurvey->id]);
                    foreach ($tempTableRow as $entity) {
                        $this->InstitutionSurveyTableCells->save($entity);
                    }
                    $totalImported++;
                } else {
                    $rowCodeError = '';
                    $rowCodeErrorForExcel = [];

                    foreach ($rowInvalidCodeCols as $questionName => $errMessage) {
                        $rowCodeError .= '<li>' . $questionName . ' => ' . $errMessage . '</li>';
                        $rowCodeErrorForExcel[] = $questionName . ' => ' . $errMessage;
                    }

                    $dataFailed[] = [
                        'row_number' => $row,
                        'error' => '<ul>' . $rowCodeError . '</ul>',
                        'errorForExcel' => implode("\n", $rowCodeErrorForExcel),
                        'data' => $originalRow
                    ];
                    continue;
                }
            } // for ($row = 1; $row <= $highestRow; ++$row)
            
            //$session = $this->Session;
            $session = new \Cake\Http\Session();//POCOR-9349 
            $completedData = [
                'uploadedName' => $uploadedName,
                'dataFailed' => $dataFailed,
                'totalImported' => $totalImported,
                'totalUpdated' => 0,
                'totalRows' => count($dataFailed) + $totalImported,
                'header' => $header,
                'failedExcelFile' => $this->_generateDownloadableFile( $dataFailed, 'failed', $header, $systemDateFormat ),
                'passedExcelFile' => $this->_generateDownloadableFile( $dataPassed, 'passed', $header, $systemDateFormat ),
                'executionTime' => (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])
            ];
            $session->write($this->sessionKey, $completedData);

            $urlRes = $this->ControllerAction->url('results');//POCOR-9349 
            $urlRes[1] = $this->request->getParam('pass')[1];//POCOR-9349 
            return $model->controller->redirect($urlRes);//POCOR-9349 
        };
    }

    private function _generateDownloadableFile( $data, $type, $header, $systemDateFormat ) {
        if (!empty($data)) {
            $downloadFolder = $this->prepareDownload();
            $modelName = $this->getAlias();
            
            $surveyForm = $this->institutionSurvey->survey_form;
            $excelFile = sprintf('OpenEMIS_Core_Import_Institution_Survey_%s_%s_%s.xlsx', $surveyForm->code, ucwords($type), time());
            $excelPath = $downloadFolder . DS . $excelFile;

            $newHeader = $header;
            if ($type == 'failed') {
                $newHeader[] = $this->getExcelLabel('general', 'errors');
            }
            $dataSheetName = __('Data');
            //POCOR-9349 STARTS changes for V4
            //$objPHPExcel = new \PHPExcel();
            //$activeSheet = $objPHPExcel->getActiveSheet();
            // Create Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $this->setImportDataTemplate($spreadsheet, $dataSheetName, $newHeader, '');            
            $activeSheet = $spreadsheet->getActiveSheet();
            //POCOR-9349 ENDS
            foreach($data as $index => $record) {
                if ($type === 'failed') {
                    $values = array_values($record['data']->getArrayCopy());
                    $values[] = $record['errorForExcel'];
                } else {
                    $values = $record['data'];
                }
                $activeSheet->getRowDimension( ($index + 3) )->setRowHeight( 15 );
                foreach ($values as $key => $value) {
                    $alpha = $this->getExcelColumnAlpha($key);
                    $activeSheet->setCellValue( $alpha . ($index + 3), $value);
                    $activeSheet->getColumnDimension( $alpha )->setAutoSize(true);

                    if ($key===(count($values)-1) && $type === 'failed') {
                        $suggestedRowHeight = $this->suggestRowHeight( strlen($value), 15 );
                        $activeSheet->getRowDimension( ($index + 3) )->setRowHeight( $suggestedRowHeight );
                        $activeSheet->getStyle( $alpha . ($index + 3) )->getAlignment()->setWrapText(true);
                    }
                }               
            }

            if ($type === 'failed') {
                //$this->setCodesDataTemplate( $objPHPExcel );//POCOR-9349
                $this->setCodesDataTemplate( $spreadsheet );//POCOR-9349
            }
            //POCOR-9349 STARTS changes for V4
            // $objPHPExcel->setActiveSheetIndex(0);
            // $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            // $objWriter->save($excelPath);
            //POCOR-9349 ENDS
            $spreadsheet->setActiveSheetIndex(0);
            // Save Excel file
            $writer = new Xlsx($spreadsheet);
            $writer->save($excelPath);

            $downloadUrl = $this->ControllerAction->url( 'download' . ucwords($type) );
            $downloadUrl[1] = $excelFile;
            $excelFile = $downloadUrl;
        } else {
            $excelFile = null;
        }

        return $excelFile;
    }

    public function results() {
        //$session = $this->Session;
        $session = new \Cake\Http\Session();//POCOR-9349
        // fallback in case sessionKey is not set
        if (empty($this->sessionKey)) {
            $this->sessionKey = $this->getRegistryAlias().'.Import.data';
        }
        if ($session->check($this->sessionKey)) {
            $completedData = $session->read($this->sessionKey);
            $this->ControllerAction->field('select_file', ['visible' => false]);
            $this->ControllerAction->field('results', [
                'type' => 'element',
                'override' => true,
                'visible' => true,
                'element' => 'Import./results',
                'rowClass' => 'row-reset',
                'results' => $completedData
            ]);
            $session->delete($this->sessionKey);
            if (!empty($completedData['failedExcelFile'])) {
                $message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'failed');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            } else {
                $message = '<i class="fa fa-check-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'success');
                $this->Alert->ok($message, ['type' => 'string', 'reset' => true]);
            }
            // define data as empty entity so that the view file will not throw an undefined notice
            $this->controller->set('data', $this->newEmptyEntity());//POCOR-9349
            $this->ControllerAction->renderView('/ControllerAction/view');
        } else {
            return $this->controller->redirect($this->ControllerAction->url('add'));
        }
    }
}
