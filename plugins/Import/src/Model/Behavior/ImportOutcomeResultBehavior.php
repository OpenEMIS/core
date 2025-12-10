<?php

namespace Import\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Session;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use ControllerAction\Model\Traits\EventTrait;
use Cake\Log\Log;
use PHPExcel_IOFactory;

use Import\Model\Behavior\ImportResultBehavior;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ImportOutcomeResultBehavior extends ImportResultBehavior
{
    use EventTrait;

    /**
     * Actual Import business logics reside in this function
     * @param Event $event Event object
     * @param Entity $entity Entity object containing the uploaded file parameters
     * @param ArrayObject $data Event object
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

        $closureFunction = function ($model, $entity) {
            $errors = $entity->getErrors();
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

            $activeModel = TableRegistry::get($this->config('plugin') . '.' . $this->config('model'));
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
            $data = $this->_table->getQueryString();
            dd([__FUNCTION__ => $data]);
            $template = $data['outcome_template'];
            $education_subject_id = $data['education_subject_id'];
            $classId = $data['class'];
            $outcome_period_id = $data['outcome_period'];
            $institution_id = $data['institution_id'];
            $academic_period_id = $data['academic_period'];
            $subjectName = $educationSubjectsTable->get($education_subject_id)->name;

            // check correct template
            $header = array($subjectName, 'Outcome -->');
            // POCOR- 7987 moved up
            $outcomeTemplatesTable = TableRegistry::get('Outcome.OutcomeTemplates');
            // calculate outcome criterias


            $educationGradeId = $outcomeTemplatesTable->find()
                ->where([
                    $outcomeTemplatesTable->aliasField('id') => $template,
                ])
                ->extract('education_grade_id')
                ->first();

            //calculate number of student
            $institutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
            $studentStatusesTable = TableRegistry::get('Student.StudentStatuses');
            $arrayStudent = $institutionClassStudentsTable->find()
                ->matching('Users')
                ->matching('InstitutionClasses')
                ->matching('EducationGrades')
                ->matching($studentStatusesTable->getAlias(), function ($q) use ($studentStatusesTable) {
                    return $q->where([$studentStatusesTable->aliasField('code') => 'CURRENT']);
                })
                ->where([
                    $institutionClassStudentsTable->aliasField('institution_class_id') => $classId,
                    $institutionClassStudentsTable->aliasField('education_grade_id') => $educationGradeId // POCOR- 7987
                ])
                ->toArray();

            $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
            $aryOutcomeCriteria = $outcomeCriteriasTable->find()
                ->where([
                    $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
                    $outcomeCriteriasTable->aliasField('outcome_template_id') => $template
                ])
                ->toArray();
            $totalCriteria = count($aryOutcomeCriteria);
            $totalColumns = $totalCriteria + 1;

            //comment will be last after outcomecriterias
            $commentColumn = $totalColumns + 1;

            foreach ($aryOutcomeCriteria as $key => $value) {
                $headerCriteriaId[] = $value->id;
            }

            $institutionOutcomeSubjectCommentsTable = TableRegistry::get('Institution.InstitutionOutcomeSubjectComments');

            if (!$this->checkCorrectTemplate(2, $headerCriteriaId, $sheet, $totalColumns, 1)) {
                $entity->errors('select_file', [$this->getExcelLabel('Import', 'wrong_template')], true);

                return false;
            }

            if (!$this->checkCorrectTemplate(0, $header, $sheet, 1, 2)) {
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
                        'outcome_period_id' => $outcome_period_id,
                        'education_grade_id' => $educationGradeId,
                        'education_subject_id' => $education_subject_id,
                        'institution_id' => $institution_id,
                        'academic_period_id' => $academic_period_id
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
                        'commentColumn' => $commentColumn,
                        'numberColumn' => $column,
                        'sheet' => $sheet,
                        'totalColumns' => $totalCriteria,
                        'row' => $row,
                        'activeModel' => $activeModel,
                        'systemDateFormat' => $systemDateFormat,
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
                        $activeModel->patchEntity($tableEntity, $tempRow,
                            [ 'validate' => false] //POCOR-7977
                        );
                    }

                    $errors = $tableEntity->errors();
//                    if ($errors) { //POCOR-7977
//                        $model->log('@ImportOutcomeBehavior line ' . __LINE__, 'debug');
//                        $model->log($errors, 'debug');
//                    }
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
//                            if ($errors) { //POCOR-7977
//                                $model->log('@ImportOutcomeBehavior line ' . __LINE__, 'debug');
//                                $model->log($message, 'debug');
//                            }
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
                                    $model->log('@ImportOutcomeBehavior line ' . __LINE__ . ': ' . $activeModel->registryAlias() . ' -> ' . $field . ' => ' . $arr[key($arr)], 'info'); //POCOR-7977
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

        return $closureFunction;
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
        // Do not lcalize file name as certain non-latin characters might cause issue
        $excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelName);
        $excelPath = $folder . DS . $excelFile;

        $dataSheetName = $this->getExcelLabel('general', 'data');

        $objPHPExcel = new Spreadsheet();

        $headerRow3 = array("OpenEMIS ID", "Student Name", "Outcome Grading Option Id");

        $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $headerRow3, '');

        $this->setCodesDataTemplate($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
//        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter = new Xlsx($objPHPExcel);;
        try {
            $objWriter->save($excelPath);
        } catch (\Throwable $th) {

        }

        $this->performDownload($excelFile);
        die;
    }


    /******************************************************************************************************************
     **
     ** Import Functions
     **
     ******************************************************************************************************************/

    public function setImportDataTemplate($objPHPExcel, $dataSheetName, $header, $type)
    {
        parent::setImportDataTemplate($objPHPExcel, $dataSheetName, $header, $type);
        $objPHPExcel->setActiveSheetIndex(0);
        $activeSheet = $objPHPExcel->getActiveSheet();

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName, __(Inflector::humanize(Inflector::tableize($this->_table->getAlias()))) . ' ' . $dataSheetName);

        $educationSubjectsTable = TableRegistry::get('Education.EducationSubjects');
        $data = $this->_table->getQueryString();
//        dd($data);
        $template = $data['outcome_template'];
        $education_subject_id = $data['education_subject'];
        $classId = $data['class'];
        $outcome_period_id = $data['outcome_period'];
        $institution_id = $data['institution_id'];
        $academic_period_id = $data['academic_period'];
        $name = $educationSubjectsTable->get($education_subject_id)->name;

        $activeSheet->setCellValue("A2", $name);
        $activeSheet->setCellValue("B2", "Outcome -->");

        //headerRow3
        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 3, $value);
        }

        // POCOR- 7987:start
        $outcomeTemplatesTable = TableRegistry::get('Outcome.OutcomeTemplates');
        // calculate outcome criterias

        $educationGradeId = $outcomeTemplatesTable->find()
            ->where([
                $outcomeTemplatesTable->aliasField('id') => $template,
            ])
            ->extract('education_grade_id')
            ->first();
        // POCOR- 7987:end
        $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
        $arrayOutcomeCriterias = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
                $outcomeCriteriasTable->aliasField('outcome_template_id') => $template
            ])
            ->toArray();

        // Initialize suggestedRowHeight *before* the loop, ideally to a reasonable default
        $defaultRowHeight = $activeSheet->getRowDimension(2)->getRowHeight();

// If the defaultRowHeight is -1 (auto-height) or not explicitly set,
// initialize suggestedRowHeight to a sensible default like 15 points.
        if ($defaultRowHeight === -1 || $defaultRowHeight === null) {
            $suggestedRowHeight = 15; // A common default height in points
        } else {
            $suggestedRowHeight = $defaultRowHeight;
        }

// --- Get the source style from Column A (assuming A2 for the header row) ---
        $sourceStyle = $activeSheet->getStyle('A2');

// Extract specific style properties
        $sourceFill = $sourceStyle->getFill();
        $sourceFont = $sourceStyle->getFont();
        $sourceAlignment = $sourceStyle->getAlignment(); // <--- Get the Alignment object

        $sourceBackgroundColor = $sourceFill->getStartColor()->getArgb();
        $sourceFontColor = $sourceFont->getColor()->getArgb();
        $sourceFontSize = $sourceFont->getSize();
        $sourceFontBold = $sourceFont->getBold();

// Extract alignment properties
        $sourceHorizontalAlignment = $sourceAlignment->getHorizontal(); // <--- Get horizontal alignment
        $sourceVerticalAlignment = $sourceAlignment->getVertical();     // <--- Get vertical alignment


        foreach ($arrayOutcomeCriterias as $key => $value) {
            $column = $key + 2; // This will be 2 (C), 3 (D), 4 (E), etc.
            $alpha = $this->getExcelColumnAlpha($column);

            $activeSheet->setCellValue($alpha . 1, $value->id);

            // Clean up line breaks in $value->name
            $cleanedName = str_replace(["\r\n", "\r"], "\n", $value->name);

            // Set value for the name cell with cleaned line breaks
            $activeSheet->setCellValue($alpha . 2, $cleanedName);

            // --- Apply consistent styling for column headers (Row 2) ---
            $cellStyle = $activeSheet->getStyle($alpha . 2);

            // Apply extracted background color
            $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($sourceBackgroundColor);

            // Apply extracted font color, size, and boldness
            $cellStyle->getFont()->getColor()->setARGB($sourceFontColor);
            $cellStyle->getFont()->setSize($sourceFontSize);
            $cellStyle->getFont()->setBold($sourceFontBold);

            // Apply extracted alignment properties <--- NEW
            $cellStyle->getAlignment()->setHorizontal($sourceHorizontalAlignment);
            $cellStyle->getAlignment()->setVertical($sourceVerticalAlignment);

            // Set wrap text (as per your previous requirement)
            $cellStyle->getAlignment()->setWrapText(true);

            // Set column width (as per your previous requirement)
            $activeSheet->getColumnDimension($alpha)->setWidth(35);

            // Recalculate suggested row height based on the cleaned name
            $currentCellHeight = $this->suggestRowHeight(strlen($cleanedName), 15);
            if ($currentCellHeight > $suggestedRowHeight) {
                $suggestedRowHeight = $currentCellHeight;
            }
        }

// --- Apply the maximum calculated row height to the entire row AFTER the loop ---
        if ($suggestedRowHeight > $activeSheet->getRowDimension(2)->getRowHeight()) { // Only apply if it's actually larger than default
            $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);
        }

        // After the loop, apply the suggested row height to the entire row if needed
//        if ($suggestedRowHeight > 15) {
//            $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);
//        }
//        $activeSheet->getRowDimension(1)->setRowHeight(80);
//        $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);

//        $institutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
//        $studentStatusesTable = TableRegistry::get('Student.StudentStatuses');
//        $arrayStudent = $institutionClassStudentsTable->find()
//            ->select([
//                $institutionClassStudentsTable->Users->aliasField('openemis_no'),
//                $institutionClassStudentsTable->Users->aliasField('first_name'),
//                $institutionClassStudentsTable->Users->aliasField('middle_name'),
//                $institutionClassStudentsTable->Users->aliasField('third_name'),
//                $institutionClassStudentsTable->Users->aliasField('last_name'),
//                $institutionClassStudentsTable->Users->aliasField('preferred_name'),
//            ])
//            ->matching('Users')
//            ->matching('InstitutionClasses')
//            ->matching('EducationGrades')
//            ->matching($studentStatusesTable->getAlias(), function ($q) use ($studentStatusesTable) {
//                return $q->where([$studentStatusesTable->aliasField('code') => 'CURRENT']);
//            })
//            ->where([
//                $institutionClassStudentsTable->aliasField('institution_class_id') => $classId,
//                $institutionClassStudentsTable->aliasField('education_grade_id') => $educationGradeId // POCOR- 7987
//            ])
//            ->order([
//                $institutionClassStudentsTable->Users->aliasField('first_name'),
//                $institutionClassStudentsTable->Users->aliasField('last_name')
//            ])
//            ->toArray();
//
//        $i = 4;
//        foreach ($arrayStudent as $key => $value) {
//            $activeSheet->setCellValue('A' . $i, $value->_matchingData['Users']->openemis_no);
//            $activeSheet->setCellValue('B' . $i, $value->_matchingData['Users']->name);
//            $i++;
//            $activeSheet->getColumnDimension('A')->setAutoSize(true);
//            $activeSheet->getColumnDimension('B')->setAutoSize(true);
//
//        }
        // -1 to start from A, +2 is for education subject and outcome-->, -1+2=+1
        $arrayLastAlpha = $this->getExcelColumnAlpha(count($arrayOutcomeCriterias) + 1);
        $activeSheet->mergeCells('C3:' . $arrayLastAlpha . '3');
        // -1 to start from A, +2 is for education subject and outcome-->, +1 comment after criteria name, -1+2+1=+2
        $countCriterias = count($arrayOutcomeCriterias);
        $Comment = $this->getExcelColumnAlpha($countCriterias + 2);
        $activeSheet->setCellValue($Comment . '3', __("Comment"));
        $activeSheet->getColumnDimension($Comment)->setAutoSize(true);

    }

    public function __setCodesDataTemplate($objPHPExcel)
    {
        $sheetName = __('References');
        $objPHPExcel->createSheet(1);
        $objPHPExcel->setActiveSheetIndex(1); // Activate the new sheet

        $this->beginExcelHeaderStyling($objPHPExcel, $sheetName);

        $objPHPExcel->getActiveSheet()->getRowDimension(3)->setRowHeight(25);

        // --- Start: Data Fetching Logic (ported from __setCodesDataTemplate concept) ---
        // You'll need to pass 'outcome_template', 'education_subject', and 'class'
        // to this function or fetch them here if they're available from another source.
        // For demonstration, let's assume you have access to a way to get these.
        // For now, I'll use placeholders. You need to replace these with actual values.

        $data = $this->_table->getQueryString(); // Assuming this fetches the necessary data
        $template = $data['outcome_template'] ?? null; // Replace with actual template ID
        $education_subject_id = $data['education_subject'] ?? null; // Replace with actual subject ID
        $classId = $data['class'] ?? null; // Replace with actual class ID

        // Initialize an array to hold the structured data for the "References" sheet
        $codesData = [];

        // Fetch Outcome Criterias (similar to __setCodesDataTemplate)
        if ($education_subject_id && $template) {
            $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias'); // Use getTable for newer CakePHP
            $outcomeCriteriasArray = $outcomeCriteriasTable->find()
                ->where([
                    $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
                    $outcomeCriteriasTable->aliasField('outcome_template_id') => $template
                ])
                ->toArray();

            // Prepare data for the first column block: Outcome Criterias (if you want them listed)
            if (!empty($outcomeCriteriasArray)) {
                $outcomeCriteriaNames = [];
                foreach($outcomeCriteriasArray as $criteria) {
                    // Assuming 'name' or 'title' is the displayable field
                    $outcomeCriteriaNames[$criteria->id] = [$criteria->title ?? $criteria->name]; // Make sure it's an array of array for cells
                }
                $codesData[] = [
                    'sheetName' => __('Outcome Criterias'),
                    'data' => $outcomeCriteriaNames,
                    'noDropDownList' => true // Don't make dropdowns for these
                ];
            }
        }


        // Fetch Grading Options based on Outcome Criterias
        if (!empty($outcomeCriteriasArray)) {
            $outcomeGradingOptionsTable = TableRegistry::get('Outcome.OutcomeGradingOptions'); // Use getTable for newer CakePHP
            $processedGradingTypeIds = []; // To avoid duplicate columns for the same grading type

            foreach ($outcomeCriteriasArray as $criteria) {
                $outcomeGradingTypeId = $criteria->outcome_grading_type_id;

                // Only fetch and add grading options once per grading type
                // if (!in_array($outcomeGradingTypeId, $processedGradingTypeIds)) {
                    $gradeOptionArray = $outcomeGradingOptionsTable->find()
                        ->select(['name'])
                        ->where([$outcomeGradingOptionsTable->aliasField('outcome_grading_type_id') => $outcomeGradingTypeId])
                        ->toArray();

                    if (!empty($gradeOptionArray)) {
                        $gradeOptionsForSheet = [];
                        foreach ($gradeOptionArray as $singleGradeOption) {
                            $gradeOptionsForSheet[] = [$singleGradeOption->name]; // Each option as an array for the cell
                        }

                        $codesData[] = [
                            'sheetName' => 'Grades for Type ' . $outcomeGradingTypeId, // Dynamic name
                            'data' => $gradeOptionsForSheet,
                            // You might want a lookupColumn here if you intend to use it for dropdowns later
                            'lookupColumn' => 1 // The column where these values start within this block (always 1 if it's the only value)
                        ];
                        $processedGradingTypeIds[$criteria->outcome_grading_type_id] = $outcomeGradingTypeId;
                    }
                // }
            }
        }

        // --- End: Data Fetching Logic ---


        $lastColumn = -1;
        $currentRowHeight = $objPHPExcel->getActiveSheet()->getRowDimension(2)->getRowHeight();
        foreach ($codesData as $columnOrder => $modelArr) {
            $modelData = $modelArr['data']; // This is now like [['A+'], ['Pass'], ['Fail']]
            $firstColumn = $lastColumn == -1 ? 1 : 1 + $lastColumn ;
            // POCOR-8343: modelDataCount is typically 1 for single columns like grades,
            // but it iterates through each row of the first item in modelData
            $modelDataCount = !empty($modelArr['data'][0]) ? count($modelArr['data'][0]) : 0;
            $lastColumn = $firstColumn + $modelDataCount - 1;

            $objPHPExcel->getActiveSheet()->mergeCells($this->getExcelColumnAlpha($firstColumn) . "2:" . $this->getExcelColumnAlpha($lastColumn) . "2");
            $objPHPExcel->getActiveSheet()->setCellValue($this->getExcelColumnAlpha($firstColumn) . "2", $modelArr['sheetName']);
            if (strlen($modelArr['sheetName']) < 50) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($this->getExcelColumnAlpha($firstColumn))->setAutoSize(true);
            } else {
                $currentRowHeight = $this->suggestRowHeight(strlen($modelArr['sheetName']), $currentRowHeight);
                $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight($currentRowHeight);
                $objPHPExcel->getActiveSheet()->getStyle($this->getExcelColumnAlpha($firstColumn) . "2")->getAlignment()->setWrapText(true);
            }

            // This loop populates the cells
            foreach ($modelData as $index => $sets) { // $sets will be like ['A+'] or ['Pass']
                foreach ($sets as $key => $value) { // $key will be 0, $value will be 'A+'
                    $alpha = $this->getExcelColumnAlpha(($key + $firstColumn)); // $key + $firstColumn will simply be $firstColumn
                    $objPHPExcel->getActiveSheet()->setCellValue($alpha . ($index + 3), $value); // Populate A3, A4, A5 etc.
                    $objPHPExcel->getActiveSheet()->getColumnDimension($alpha)->setAutoSize(true);
                }
            }

            // This part applies data validation (dropdowns) to the *first* sheet (index 0)
            // based on the data in the *current* sheet (index 1, 'References')
            if (count($modelData) > 1 && !isset($modelArr['noDropDownList'])) {
                $lookupColumn = $firstColumn + intval($modelArr['lookupColumn']) - 1;
                $alpha = $this->getExcelColumnAlpha($columnOrder); // This 'alpha' is tricky. It determines the column on sheet 0.
                $lookupColumnAlpha = $this->getExcelColumnAlpha($lookupColumn); // Column on sheet 1 for dropdown source
                ($this->isCustomText()) ? $lookupStart = 4 : $lookupStart = 3; // Starting row for dropdown list on sheet 1

                for ($i = $lookupStart; $i < 103; $i++) { // Loop for rows on sheet 0
                    $objPHPExcel->setActiveSheetIndex(0); // Switch to the main sheet
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($alpha . $i)->getDataValidation();
                    $objValidation->setType(DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    // The formula to reference the data on the 'References' sheet
                    $listLocation = "'" . $sheetName . "'!$" . $lookupColumnAlpha . "$4:$" . $lookupColumnAlpha . "$" . (count($modelData) + 2);
                    $objValidation->setFormula1($listLocation);
                }
                $objPHPExcel->setActiveSheetIndex(1); // Switch back to the 'References' sheet
            }
        }

        if ($lastColumn > -1) { //if got no reference data.
            $headerLastAlpha = $this->getExcelColumnAlpha($lastColumn);
            $objPHPExcel->getActiveSheet()->getStyle("A2:" . $headerLastAlpha . "2")->getFont()->setBold(true)->setSize(12);
            $this->endExcelHeaderStyling($objPHPExcel, $headerLastAlpha, 3, ['s' => 3, 'e' => 3], ['s' => 2, 'e' => 3]);
        }
    }

    /**
     * Extract the values in every column
     * @param array $references the variables/arrays in this array are for references
     * @param ArrayObject $tempRow for holding converted values extracted from the excel sheet on a per row basis
     * @param ArrayObject $originalRow for holding the original value extracted from the excel sheet on a per row basis
     * @param ArrayObject $rowInvalidCodeCols for holding error messages found on option field columns
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

        $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
        $outcomeGradingTypeId = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('id') => $outcomeIdValue,
            ])
            ->extract('outcome_grading_type_id')
            ->first();

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
                $outcomeGradingOptionsTable->aliasField('name') => $gradeValue,
                $outcomeGradingOptionsTable->aliasField('outcome_grading_type_id') => $outcomeGradingTypeId
            ])
            ->first();

        if (empty($Grading)) {
            $rowPass = false;
            $rowInvalidCodeCols['outcome_grading_option_id'] = __('Wrong Grade Option');
            $extra['entityValidate'] = false;
        } else {
            $tempRow['outcome_criteria_id'] = $outcomeIdValue;
            $tempRow['student_id'] = $User->id;
            $tempRow['outcome_grading_option_id'] = $Grading->id;
        }
        $originalRow[] = $outcomeIdValue;
        $originalRow[] = $studentValue;
        $originalRow[] = $gradeValue;

        if ($rowPass) {
            $rowPassEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportModelSpecificValidation'), 'onImportModelSpecificValidation', [$references, $tempRow, $originalRow, $rowInvalidCodeCols]);
            $rowPass = $rowPassEvent->result;
        }


        return $rowPass;
    }
}
