<?php

namespace Import\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
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
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


class ImportOutcomeResultBehavior extends ImportResultBehavior
{
    use EventTrait;

    /**
     * Actual Import business logics reside in this function
     * @param EventInterface $event Event object
     * @param Entity $entity Entity object containing the uploaded file parameters
     * @param ArrayObject $data Event object
     * @return Response             Response object
     */
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        ini_set('max_execution_time', 180);

        return function ($model, $entity) {

            /* ===========================
             *  COUNTERS & RESULTS
             * =========================== */
            $totalImported = 0;
            $totalUpdated  = 0;
            $dataFailed    = [];
            $dataPassed    = [];
            $rowTracker    = []; // per Excel row tracking

            /* ===========================
             *  BASIC VALIDATION
             * =========================== */
            if (!empty($entity->getErrors())) {
                return false;
            }

            /* ===========================
             *  LOAD EXCEL
             * =========================== */
            $fileObj = $entity->select_file;
            $uploadedName = $fileObj->getClientFilename();
            $uploaded     = $fileObj->getStream()->getMetadata('uri');

            $inputFileType = IOFactory::identify($uploaded);
            $objReader     = IOFactory::createReader($inputFileType);
            $objPHPExcel   = $objReader->load($uploaded);
            $sheet         = $objPHPExcel->getSheet(0);

            /* ===========================
             *  REQUEST DATA
             * =========================== */
            $requestData = $this->_table->request->getData()['ImportOutcomeResults'];
            $queryString = $this->_table->getQueryString();

            $template             = $requestData['outcome_template'];
            $education_subject_id = $requestData['education_subject'];
            $outcome_period_id    = $requestData['outcome_period'];
            $academic_period_id   = $requestData['academic_period'];
            $institution_id       = $queryString['institution_id'];
            /* ===========================
             *  LOAD TABLES
             * =========================== */
            $activeModel = TableRegistry::get(
                $this->getConfig('plugin') . '.' . $this->getConfig('model')
            );

            $UsersTable               = TableRegistry::get('User.Users');
            $outcomeTemplatesTable    = TableRegistry::get('Outcome.OutcomeTemplates');
            $outcomeCriteriasTable    = TableRegistry::get('Outcome.OutcomeCriterias');
            $commentsTable            = TableRegistry::get('Institution.InstitutionOutcomeSubjectComments');

            /* ===========================
             *  EDUCATION GRADE
             * =========================== */
            $educationGradeId = $outcomeTemplatesTable->find()
                ->where(['id' => $template])
                ->extract('education_grade_id')
                ->first();

            /* ===========================
             *  OUTCOME CRITERIA
             * =========================== */
            $criteriaList = $outcomeCriteriasTable->find()
                ->where([
                    'education_subject_id' => $education_subject_id,
                    'outcome_template_id'  => $template
                ])
                ->toArray();

            $totalCriteria = count($criteriaList);

            /* ===========================
             *  COLUMN MAPPING
             * =========================== */
            $openemisColumn  = 1; // Column A (OpenEMIS ID)
            $firstGradeCol   = 3; // Column C
            $commentColumn   = $firstGradeCol + $totalCriteria;

            /* ===========================
             *  ROW LOOP
             * =========================== */
            $highestRow = $sheet->getHighestRow();

            for ($row = 4; $row <= $highestRow; $row++) {

                $rowTracker[$row] = [
                    'success' => false,
                    'errors'  => []
                ];

                /* ---------------------------
                 * STUDENT
                 * --------------------------- */
                $studentOpenEmisId = trim((string)
                    $sheet->getCellByColumnAndRow($openemisColumn, $row)->getCalculatedValue()
                );

                if ($studentOpenEmisId === '') {
                    continue;
                }

                $User = $UsersTable->find()
                    ->select(['id'])
                    ->where(['openemis_no' => $studentOpenEmisId])
                    ->first();

                if (!$User) {
                    $rowTracker[$row]['errors'][] = 'Student not found';
                    continue;
                }

                /* ---------------------------
                 * COMMENT (DELETE + INSERT)
                 * --------------------------- */
                $comment = trim((string)
                    $sheet->getCellByColumnAndRow($commentColumn, $row)->getCalculatedValue()
                );

                if ($comment !== '') {

                    $commentsTable->deleteAll([
                        'student_id'           => $User->id,
                        'outcome_template_id'  => $template,
                        'outcome_period_id'    => $outcome_period_id,
                        'education_grade_id'   => $educationGradeId,
                        'education_subject_id' => $education_subject_id,
                        'institution_id'       => $institution_id,
                        'academic_period_id'   => $academic_period_id,
                    ]);

                    $commentsTable->save(
                        $commentsTable->newEntity([
                            'comments'             => $comment,
                            'student_id'           => $User->id,
                            'outcome_template_id'  => $template,
                            'outcome_period_id'    => $outcome_period_id,
                            'education_grade_id'   => $educationGradeId,
                            'education_subject_id' => $education_subject_id,
                            'institution_id'       => $institution_id,
                            'academic_period_id'   => $academic_period_id,
                        ])
                    );
                }

                /* ---------------------------
                 *      GRADES
                 * --------------------------- */
                $rowHasNew    = false;
                $rowHasUpdate = false;

                for ($i = 0; $i < $totalCriteria; $i++) {

                    $column = $firstGradeCol + $i;

                    $gradeValue = trim((string)
                        $sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue()
                    );

                    if ($gradeValue === '') {
                        continue;
                    }

                    $tempRow            = new ArrayObject;
                    $originalRow        = new ArrayObject;
                    $rowInvalidCodeCols = new ArrayObject;
                    $extra              = new ArrayObject(['entityValidate' => true]);

                    $this->_extractRecord(
                        [
                            'numberColumn' => $column,
                            'sheet'        => $sheet,
                            'row'          => $row,
                            'activeModel'  => $activeModel
                        ],
                        $tempRow,
                        $originalRow,
                        $rowInvalidCodeCols,
                        $extra
                    );

                    if (!$extra['entityValidate']) {
                        $rowTracker[$row]['errors'][] =
                            'Invalid grade: ' . $gradeValue;
                        continue;
                    }

                    $entityData = $tempRow->getArrayCopy() + [
                        'student_id'           => $User->id,
                        'outcome_template_id'  => $template,
                        'outcome_period_id'    => $outcome_period_id,
                        'education_grade_id'   => $educationGradeId,
                        'education_subject_id' => $education_subject_id,
                        'institution_id'       => $institution_id,
                        'academic_period_id'   => $academic_period_id,
                    ];

                    $gradeEntity = $activeModel->newEntity($entityData, ['validate' => false]);

                    $isNew = $gradeEntity->isNew();

                    try {
                            if ($activeModel->save($gradeEntity)) {
                                $rowTracker[$row]['success'] = true;
                                if ($isNew) {
                                    $rowHasNew = true;
                                } else {
                                    $rowHasUpdate = true;
                                }
                            } else {
                                $rowTracker[$row]['errors'][] = 'Failed to save grade';
                            }
                        }catch (Exception $e) {
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
                }

                /* ---------------------------
                 * ROW LEVEL COUNTING (IMPORTANT)
                 * --------------------------- */
                if ($rowHasUpdate) {
                    $totalUpdated++;
                } elseif ($rowHasNew) {
                    $totalImported++;
                }

                /* ---------------------------
                 * FINAL ROW RESULT
                 * --------------------------- */
                if ($rowTracker[$row]['success']) {
                    $dataPassed[] = [
                        'row_number' => (int)$row,
                        'data' => [
                            $studentOpenEmisId
                        ]
                    ];
                } else {
                    $dataFailed[] = [
                        'row_number'    => (int)$row,
                        'error'         => '<ul>' . 'Outcome grade ID could not be assigned. Please verify the OpenEMIS ID and grading value in the Excel file.' . '</ul>',
                        'errorForExcel' => is_array($rowCodeErrorForExcel)
                            ? implode("\n", $rowCodeErrorForExcel)
                            : (string)$rowCodeErrorForExcel,
                        'data' => new ArrayObject([
                            $studentOpenEmisId
                        ])
                    ];
                }
            }

            /* ===========================
             *  STORE RESULT & REDIRECT
             * =========================== */
            $resultHeader = ['Row Number','OpenEMIS ID'];
            $systemDateFormat = TableRegistry::get('Configuration.ConfigItems')
                ->value('date_format');
            $session = $this->_table->Session;

            $session->write($this->sessionKey, [
                'uploadedName'     => $uploadedName,
                'dataFailed'       => $dataFailed,
                'totalImported'    => $totalImported,
                'totalUpdated'     => $totalUpdated,
                'totalRows'        => count($rowTracker),
                'header' => $resultHeader,
                'passedExcelFile' => $this->_generateDownloadableFile($dataPassed, 'passed', $resultHeader, $systemDateFormat),
                'failedExcelFile' => $this->_generateDownloadableFile($dataFailed, 'failed', $resultHeader, $systemDateFormat),
                'executionTime'    => (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])
            ]);


            $url = $this->_table->ControllerAction->url('results'); //POCOR-8343
            $request = $this->_table->request;
            if(empty($this->institutionId) && isset($request->getParam('pass')[1])) {
                $queryString = $this->_table->paramsDecode($request->getParam('pass')[1]);
                $this->institutionId = isset($queryString['institution_id']) ? $queryString['institution_id'] : $this->institutionId ;
            }
            $url[1] =  $this->_table->paramsEncode(['institution_id' => $this->institutionId]);

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
        // Do not lcalize file name as certain non-latin characters might cause issue
        $excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelName);
        $excelPath = $folder . DS . $excelFile;

        $dataSheetName = $this->getExcelLabel('general', 'data');

        $objPHPExcel = new Spreadsheet();

        $headerRow3 = array("OpenEMIS ID", "Student Name", "Outcome Grading Option Id");

        $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $headerRow3, '');

        //$this->setCodesDataTemplate($objPHPExcel);
        //POCOR-9158 start
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('OpenEMIS');
        $drawing->setPath(WWW_ROOT . 'img/openemis_logo.png');
        $drawing->setHeight(100);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($objPHPExcel->getActiveSheet());
        //POCOR-9158 end

        $objPHPExcel->setActiveSheetIndex(0);
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
        $objPHPExcel->setActiveSheetIndex(0);
        $activeSheet = $objPHPExcel->getActiveSheet();
        $passParams = $this->_table->request->getParam('pass');
        $encodedQueryString = $passParams[1] ?? null;
        $data = $this->_table->paramsDecode($encodedQueryString);
        $requestData = $this->_table->request->getData('ImportOutcomeResults');
        $education_subject_id = $requestData['education_subject'];
        $educationSubjectsTable = TableRegistry::get('Education.EducationSubjects');
        $template = $data['outcome_template'];
        $classId = $data['class'];
        $outcome_period_id = $data['outcome_period'];
        $institution_id = $data['institution_id'];
        $academic_period_id = $data['academic_period'];
        $education_subject_id = $data['education_subject']?? $requestData['education_subject'] ?? null;
        $name = $educationSubjectsTable->get($education_subject_id)->name;
        $activeSheet->setCellValue("A2", $name);
        $activeSheet->setCellValue("B2", "Outcome -->");

        //POCOR-9158 headerRow3
        $lastRowToAlign = (int)($lastRowToAlign ?? 3);
        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha((int)$key + 1);
            $cell  = $alpha . $lastRowToAlign;

            $activeSheet->setCellValue($cell, $value);
        } //POCOR-9158

        // POCOR- 7987:start
        $outcomeTemplatesTable = TableRegistry::get('Outcome.OutcomeTemplates');
        // calculate outcome criterias
        $educationGradeId = $outcomeTemplatesTable->find()
            ->where([
                $outcomeTemplatesTable->aliasField('id IS') => $template,
            ])
            ->extract('education_grade_id')
            ->first();
        // POCOR- 7987:end
        $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
        $arrayOutcomeCriterias = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
                $outcomeCriteriasTable->aliasField('outcome_template_id IS') => $template
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
            $column = $key + 3; // This will be 2 (C), 3 (D), 4 (E), etc.
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
       if ($suggestedRowHeight > 15) {
           $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);
       }
       $activeSheet->getRowDimension(1)->setRowHeight(80);
       $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);

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
           ->matching($studentStatusesTable->getAlias(), function ($q) use ($studentStatusesTable) {
               return $q->where([$studentStatusesTable->aliasField('code') => 'CURRENT']);
           })
           ->where([
               $institutionClassStudentsTable->aliasField('institution_class_id IS') => $classId,
               $institutionClassStudentsTable->aliasField('education_grade_id IS') => $educationGradeId //POCOR-7987
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

        // POCOR-9158 start ADD OUTCOME GRADING OPTION DROPDOWNS

        $outcomeGradingOptionsTable = TableRegistry::get('Outcome.OutcomeGradingOptions');

        $gradingOptions = $outcomeGradingOptionsTable->find()->toArray();

        // Create / use a hidden reference sheet
        $referenceSheet = $objPHPExcel->getSheetByName('References');
        if (!$referenceSheet) {
            $referenceSheet = $objPHPExcel->createSheet();
            $referenceSheet->setTitle('References');
        }

        // Fill grading options
        $row = 1;
        foreach ($gradingOptions as $option) {
            $referenceSheet->setCellValue('A' . $row, $option->name);
            $row++;
        }

        // Hide reference sheet
        $referenceSheet->setSheetState(
            \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN
        );

        // Apply dropdown to each outcome criteria column
        $startRow = 4;
        $endRow   = count($arrayStudent) + 3;

        foreach ($arrayOutcomeCriterias as $key => $criteria) {

            // Criteria start from column C (index 3)
            $columnIndex = $key + 3;
            $alpha = $this->getExcelColumnAlpha($columnIndex);

            for ($row = $startRow; $row <= $endRow; $row++) {

                $validation = $activeSheet
                    ->getCell($alpha . $row)
                    ->getDataValidation();

                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1(
                    "='References'!\$A\$1:\$A\$" . count($gradingOptions)
                );
            }
        }        //POCOR-9158 end

        //  -1 to start from A, +2 is for education subject and outcome-->, -1+2=+1
        $arrayLastAlpha = $this->getExcelColumnAlpha(count($arrayOutcomeCriterias) + 1);
        //$activeSheet->mergeCells('C3:' . $arrayLastAlpha . '3');
        $startAlpha = $this->getExcelColumnAlpha(3);

        // last criteria column
        $endAlpha = $this->getExcelColumnAlpha(count($arrayOutcomeCriterias) + 1);

        // convert to numeric indexes
        $startIndex = Coordinate::columnIndexFromString($startAlpha);
        $endIndex   = Coordinate::columnIndexFromString($endAlpha);

        // prevent invalid reverse range
        if ($endIndex >= $startIndex) {
            $activeSheet->mergeCells($startAlpha . '3:' . $endAlpha . '3');
        }
        // -1 to start from A, +2 is for education subject and outcome-->, +1 comment after criteria name, -1+2+1=+2
        $countCriterias = count($arrayOutcomeCriterias);
        $Comment = $this->getExcelColumnAlpha($countCriterias + 3);
        $activeSheet->setCellValue($Comment . '3', __("Comment"));
        $activeSheet->getColumnDimension($Comment)->setAutoSize(true);

    }

    public function setCodesDataTemplate($objPHPExcel)
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
                //$alpha = $this->getExcelColumnAlpha($columnOrder); // This 'alpha' is tricky. It determines the column on sheet 0.

                $alpha = $this->getExcelColumnAlpha($columnOrder + 3);
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

    //POCOR-9158
    protected function _extractRecord($references,ArrayObject $tempRow,ArrayObject $originalRow,ArrayObject $rowInvalidCodeCols,ArrayObject $extra)
    {
        $sheet            = $references['sheet'];
        $row              = $references['row'];
        $numberColumn     = $references['numberColumn']; 
        $rowPass          = true;

        /**
         * =========================
         * 1. STUDENT (Column A)
         * =========================
         */
        $studentValue = trim((string) $sheet
            ->getCellByColumnAndRow(1, $row)
            ->getFormattedValue()
        );

        if ($studentValue === '') {
            $rowInvalidCodeCols['student_id'] = __('Student OpenEMIS ID missing');
            $extra['entityValidate'] = false;
            return false;
        }

        /**
         * =========================
         * 2. OUTCOME CRITERIA ID (Row 1)
         * =========================
         */
        $outcomeIdValue = (int) trim((string) $sheet
            ->getCellByColumnAndRow($numberColumn, 1)
            ->getValue()
        );

        if ($outcomeIdValue <= 0) {
            $rowInvalidCodeCols['outcome_criteria_id'] = __('Invalid Outcome Criteria');
            $extra['entityValidate'] = false;
            return false;
        }

        /**
         * =========================
         * 3. GRADE VALUE (Student Row)
         * =========================
         */
        $gradeValue = trim((string) $sheet
            ->getCellByColumnAndRow($numberColumn, $row)
            ->getFormattedValue()
        );

        /**
         * =========================
         * 4. FIND USER
         * =========================
         */
        $usersTable = TableRegistry::get('User.Users');

        $User = $usersTable->find()
            ->select(['id'])
            ->where([
                $usersTable->aliasField('openemis_no') => $studentValue
            ])
            ->first();

        if (empty($User)) {
            $rowInvalidCodeCols['student_id'] = __('Student not found');
            $extra['entityValidate'] = false;
            return false;
        }

        /**
         * =========================
         * 5. OUTCOME GRADING TYPE
         * =========================
         */
        $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');

        $outcomeGradingTypeId = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('id') => $outcomeIdValue
            ])
            ->select(['outcome_grading_type_id'])
            ->first()
            ->outcome_grading_type_id ?? null;

        /**
         * =========================
         * 6. FIND GRADING OPTION
         * =========================
         */
        $outcomeGradingOptionsTable = TableRegistry::get('Outcome.OutcomeGradingOptions');

        $Grading = $outcomeGradingOptionsTable->find()
            ->select(['id'])
            ->where([
                $outcomeGradingOptionsTable->aliasField('name') => $gradeValue,
                $outcomeGradingOptionsTable->aliasField('outcome_grading_type_id') => $outcomeGradingTypeId
            ])
            ->first();

        if (empty($Grading)) {
            $rowInvalidCodeCols['outcome_grading_option_id'] = __('Wrong Grade Option');
            $extra['entityValidate'] = false;
            return false;
        }

        /**
         * =========================
         * 7. MAP DATA
         * =========================
         */
        $tempRow['outcome_criteria_id']        = $outcomeIdValue;
        $tempRow['student_id']                 = $User->id;
        $tempRow['outcome_grading_option_id']  = $Grading->id;

        $originalRow[] = $outcomeIdValue;
        $originalRow[] = $studentValue;
        $originalRow[] = $gradeValue;

        return $rowPass;
    }

    public function addBeforeSavebkp(EventInterface $event, Entity $entity, ArrayObject $data)
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
            $errors = $entity->getErrors();
            if (!empty($errors)) {
                // set error message for php file upload errors
                $fileError = Hash::get($entity->getInvalid(), 'select_file.error');
                if (!empty($fileError)) {
                    $errorMessage = $model->getMessage("fileUpload.$fileError");
                    if ($errorMessage != '[Message Not Found]') {
                        $entity->getErrors('select_file', $errorMessage, true);
                    }
                }

                return false;
            }

            $systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');

            $fileObj = $entity->select_file;
            //$uploadedName = $fileObj['name'];
           // $uploaded = $fileObj['tmp_name'];
            $uploadedName = $fileObj->getClientFilename();   // instead of ['name']
            $uploaded     = $fileObj->getStream()->getMetadata('uri'); // instead of ['tmp_name']
            $inputFileType = IOFactory::identify($uploaded); 
            $objReader = IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($uploaded);

            $totalImported = 0;
            $totalUpdated = 0;
            $importedUniqueCodes = new ArrayObject;
            $dataFailed = [];
            $dataPassed = [];
            $extra = new ArrayObject(['lookup' => [], 'entityValidate' => true]);

            $activeModel = TableRegistry::getTableLocator()->get($this->config('plugin') . '.' . $this->config('model'));
            $activeModel->addBehavior('DefaultValidation');

            $maxRows = $this->getConfig('max_rows');
            $maxRows = $maxRows + 2;
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            if ($highestRow > $maxRows) {
                $entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max_rows')], true);
                return false;
            }

            $educationSubjectsTable = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
            $education_subject_id = $this->_table->request->query['education_subject'];
            $subjectName = $educationSubjectsTable->get($education_subject_id)->name;

            // check correct template
            $header = array($subjectName, 'Outcome -->');
            // POCOR- 7987 moved up
            $outcomeTemplatesTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeTemplates');
            // calculate outcome criterias


            $educationGradeId = $outcomeTemplatesTable->find()
                ->where([
                    $outcomeTemplatesTable->aliasField('id') => $template,
                ])
                ->extract('education_grade_id')
                ->first();

            //calculate number of student
            $classId = $this->_table->request->query['class'];
            $institutionClassStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
            $studentStatusesTable = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
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

            $outcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
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

            $institutionOutcomeSubjectCommentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeSubjectComments');

            if (!$this->checkCorrectTemplate(
                3,
                $headerCriteriaId,
                $sheet,
                3 + count($headerCriteriaId) - 1,
                1 // ✅ correct row
            )) {
                $entity->setErrors([
                    'select_file' => [
                        '_error' => $this->getExcelLabel('Import', 'wrong_template')
                    ]
                ]);
                return false;
            }

            /*if (!$this->checkCorrectTemplate(
                    0,
                    ['OpenEMIS ID', 'Student Name'],
                    $sheet,
                    1,
                    3
                )) {
                $entity->setErrors([
                    'select_file' => [
                        '_error' => $this->getExcelLabel('Import', 'wrong_template')
                    ]
                ]);
                return false;
            }*/

            $numberOfStudents = count($arrayStudent);
            for ($row = 4; $row < $numberOfStudents + 4; $row++) {

                // do the save for the comment
                $student = $sheet->getCellByColumnAndRow(0, $row);
                $studentOpenEmisId = $student->getValue();
                $UsersTable = TableRegistry::getTableLocator()->get('User.Users');

                $User = $UsersTable->find()
                    ->select(['id'])
                    ->where([
                        $UsersTable->aliasField('openemis_no IS') => $studentOpenEmisId
                    ])
                    ->first();
                $institutionOutcomeSubjectCommentsTable =
                    TableRegistry::get('Institution.InstitutionOutcomeSubjectComments');

                $comment = trim((string) $sheet
                    ->getCellByColumnAndRow($commentColumn, $row)
                    ->getCalculatedValue()
                );
                
                if ($comment !== '') {

                    // 1️⃣ Delete existing comment (if any)
                    $institutionOutcomeSubjectCommentsTable->deleteAll([
                        'student_id' => $User->id,
                        'outcome_template_id' => $template,
                        'outcome_period_id' => $outcome_period_id,
                        'education_grade_id' => $educationGradeId,
                        'education_subject_id' => $education_subject_id,
                        'institution_id' => $institution_id,
                        'academic_period_id' => $academic_period_id,
                    ]);

                    // 2️⃣ Insert fresh comment
                    $commentEntity = $institutionOutcomeSubjectCommentsTable->newEntity([
                        'comments' => $comment,
                        'student_id' => $User->id,
                        'outcome_template_id' => $template,
                        'outcome_period_id' => $outcome_period_id,
                        'education_grade_id' => $educationGradeId,
                        'education_subject_id' => $education_subject_id,
                        'institution_id' => $institution_id,
                        'academic_period_id' => $academic_period_id,
                        //'created_user_id' => $this->getCurrentUserId(),
                       // 'created' => FrozenTime::now(),
                    ]);

                    $institutionOutcomeSubjectCommentsTable->save($commentEntity);
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
                        $tableEntity = $activeModel->newEmptyEntity();
                    } else {
                        $tableEntity = $tempRow['entity'];
                        unset($tempRow['entity']);
                    }

                    /*if ($extra['entityValidate'] == true) {
                        // added for POCOR-4577 import staff leave for workflow related record to save the transition record
                        $tempRow['action_type'] = 'imported';
                        $activeModel->patchEntity($tableEntity, $tempRow,
                            [ 'validate' => false] //POCOR-7977
                        );
                    }*/

                    if ($extra['entityValidate'] === true) {

                        //  COMPLETE COMPOSITE PRIMARY KEY (MANDATORY)
                        $tempRow['student_id']           = $User->id;
                        $tempRow['outcome_template_id']  = $template;
                        $tempRow['outcome_period_id']    = $outcome_period_id;
                        $tempRow['education_grade_id']   = $educationGradeId;
                        $tempRow['education_subject_id'] = $education_subject_id;
                        $tempRow['academic_period_id']   = $academic_period_id;
                        $tempRow['institution_id']       = $institution_id;

                        // from _extractRecord()
                        // $tempRow['outcome_criteria_id']
                        // $tempRow['outcome_grading_option_id']

                        $tempRow['action_type'] = 'imported';

                        $activeModel->patchEntity(
                            $tableEntity,
                            $tempRow,
                            ['validate' => false]
                        );
                    }


                    $errors = $tableEntity->getErrors();
//                    if ($errors) { //POCOR-7977
//                        $model->log('@ImportOutcomeBehavior line ' . __LINE__, 'debug');
//                        $model->log($errors, 'debug');
//                    }
                    $rowInvalidCodeCols = $rowInvalidCodeCols->getArrayCopy();

                    // to-do: saving of entity into table with composite primary keys (Exam Results) give wrong isNew value
                    $isNew = $tableEntity->isNew();
                    //echo "<pre>"; print_r($tableEntity); die;
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
                                    $fieldName = $this->getExcelLabel($activeModel->getRegistryAlias(), $field);
                                    $rowCodeError .= '<li>' . $fieldName . ' => ' . $arr[key($arr)] . '</li>';
                                    $rowCodeErrorForExcel[] = $fieldName . ' => ' . $arr[key($arr)];
                                } else {
                                    if (in_array($field, ['student_name', 'staff_name'])) {
                                        $rowCodeError .= '<li>' . $arr[key($arr)] . '</li>';
                                        $rowCodeErrorForExcel[] = $arr[key($arr)];
                                    }
                                    $model->log('@ImportOutcomeBehavior line ' . __LINE__ . ': ' . $activeModel->getRegistryAlias() . ' -> ' . $field . ' => ' . $arr[key($arr)], 'info'); //POCOR-7977
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
                        $columns = [
                            'outcome_criteria_id',
                            'student_id',
                            'outcome_grading_option_id'
                        ];

                        $tempPassedRecord = [
                            'row_number' => $row,
                            'data' => $this->_getReorderedEntityArray(
                                $clonedEntity,
                                $columns,
                                $originalRow,
                                $systemDateFormat
                            )
                        ];

                        $dataPassed[] = $tempPassedRecord;

                    }

                }
                //echo "<pre>"; print_r($params); die;
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
            $url = $this->_table->ControllerAction->url('results'); //POCOR-8343
            $request = $this->_table->request;
            if(empty($this->institutionId) && isset($request->getParam('pass')[1])) {
                $queryString = $this->_table->paramsDecode($request->getParam('pass')[1]);
                $this->institutionId = isset($queryString['institution_id']) ? $queryString['institution_id'] : $this->institutionId ;
            }
            $url[1] =  $this->_table->paramsEncode(['institution_id' => $this->institutionId]);

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


    /******************************************************************************************************************
     **
     ** Import Functions
     **
     ******************************************************************************************************************/

    public function setImportDataTemplate($objPHPExcel, $dataSheetName, $header, $type)
    {
        $objPHPExcel->setActiveSheetIndex(0);
        $activeSheet = $objPHPExcel->getActiveSheet();

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName, __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) . ' ' . $dataSheetName);

        $educationSubjectsTable = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
        $education_subject_id = $this->_table->request->query['education_subject'];
        $name = $educationSubjectsTable->get($education_subject_id)->name;

        $activeSheet->setCellValue("A2", $name);
        $activeSheet->setCellValue("B2", "Outcome -->");

        //headerRow3
        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 3, $value);
        }

        $template = $this->_table->request->query['outcome_template'];
        // POCOR- 7987:start
        $outcomeTemplatesTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeTemplates');
        // calculate outcome criterias

        $educationGradeId = $outcomeTemplatesTable->find()
            ->where([
                $outcomeTemplatesTable->aliasField('id') => $template,
            ])
            ->extract('education_grade_id')
            ->first();
        // POCOR- 7987:end
        $outcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
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
            $activeSheet->getColumnDimension($alpha)->setWidth(35);
        }
        $activeSheet->getRowDimension(1)->setRowHeight(80);
        $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);

        $classId = $this->_table->request->query['class'];
        $institutionClassStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $studentStatusesTable = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
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
                $institutionClassStudentsTable->aliasField('institution_class_id') => $classId,
                $institutionClassStudentsTable->aliasField('education_grade_id') => $educationGradeId // POCOR- 7987
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
        $arrayLastAlpha = $this->getExcelColumnAlpha(count($arrayOutcomeCriterias) + 1);
        $activeSheet->mergeCells('C3:' . $arrayLastAlpha . '3');
        // -1 to start from A, +2 is for education subject and outcome-->, +1 comment after criteria name, -1+2+1=+2
        $Comment = $this->getExcelColumnAlpha(count($arrayOutcomeCriterias) + 2);
        $activeSheet->setCellValue($Comment . '3', "Comment");
        $activeSheet->getColumnDimension($Comment)->setAutoSize(true);

    }

    public function setCodesDataTemplate($objPHPExcel)
    {
        $outcomeGradingOptionsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeGradingOptions');
        $education_subject_id = $this->_table->request->query['education_subject'];
        $template = $this->_table->request->query['outcome_template'];

        $outcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
        $outcomeCriteriasArray = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('education_subject_id') => $education_subject_id,
                $outcomeCriteriasTable->aliasField('outcome_template_id') => $template
            ])
            ->toArray();

        $classId = $this->_table->request->query['class'];
        $institutionClassStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $studentStatusesTable = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
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
        for ($column = 2; $column < count($outcomeCriteriasArray) + 2; ++$column) {
            $sheet = $objPHPExcel->getSheet(0);
            $cell = $sheet->getCellByColumnAndRow($column, 1);
            $outcomeId = $cell->getValue();
            $outcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
            $outcomeGradingTypeId = $outcomeCriteriasTable->find()
                ->where([
                    $outcomeCriteriasTable->aliasField('id') => $outcomeId,
                ])
                ->extract('outcome_grading_type_id')
                ->first();

            $gradeOptionArray = $outcomeGradingOptionsTable->find()
                ->select(['name'])
                ->where([$outcomeGradingOptionsTable->aliasField('outcome_grading_type_id') => $outcomeGradingTypeId])
                ->toArray();

            $dropDownList = '';
            foreach ($gradeOptionArray as $singleGradeOptionArray) {
                if ($singleGradeOptionArray->name == end($gradeOptionArray)->name) {
                    $dropDownList .= $singleGradeOptionArray->name;
                } else {
                    $dropDownList .= $singleGradeOptionArray->name . ', ';
                }
            }

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
                $objValidation->setFormula1('"' . $dropDownList . '"');
            }
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
    protected function _extractRecordbkp($references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols, ArrayObject $extra)
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

        $outcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
        $outcomeGradingTypeId = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('id IS') => $outcomeIdValue,
            ])
            ->extract('outcome_grading_type_id')
            ->first();

        $usersTable = TableRegistry::getTableLocator()->get('User.Users');

        $User = $usersTable->find()
            ->select(['id'])
            ->where([
                $usersTable->aliasField('openemis_no IS') => $studentValue
            ])
            ->first();

        $outcomeGradingOptionsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeGradingOptions');

        $Grading = $outcomeGradingOptionsTable->find()
            ->select(['id'])
            ->where([
                $outcomeGradingOptionsTable->aliasField('name IS') => $gradeValue,
                $outcomeGradingOptionsTable->aliasField('outcome_grading_type_id IS') => $outcomeGradingTypeId
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
            $rowPass = $rowPassEvent->getResult();
        }


        return $rowPass;
    }

    
}
