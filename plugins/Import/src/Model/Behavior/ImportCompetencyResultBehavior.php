<?php
namespace Import\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\Session; //POCOR-9584: Cake\Network\Session was moved to Cake\Http\Session in CakePHP4+
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use ControllerAction\Model\Traits\EventTrait;
use Cake\Log\Log;
//POCOR-9584: start - replace legacy PHPExcel with PhpSpreadsheet 3.x (matches ImportBehavior.php)
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
//POCOR-9584: end

use Import\Model\Behavior\ImportResultBehavior;

class ImportCompetencyResultBehavior extends ImportResultBehavior
{
    use EventTrait;

    /**
     * Actual Import business logics reside in this function
     * @param  Event        $event  Event object
     * @param  Entity       $entity Entity object containing the uploaded file parameters
     * @param  ArrayObject  $data   Event object
     * @return Response             Response object
     */
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
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
            $errors = $entity->getErrors(); //POCOR-9584: errors() removed in CakePHP5, use getErrors()
            if (!empty($errors)) {
                // set error message for php file upload errors
                $fileError = Hash::get($entity->getInvalid(), 'select_file.error'); //POCOR-9584: invalid() → getInvalid()
                if (!empty($fileError)) {
                    $errorMessage = $model->getMessage("fileUpload.$fileError");
                    if ($errorMessage != '[Message Not Found]') {
                        $entity->setError('select_file', [$errorMessage]); //POCOR-9584: errors(field,msg,true) → setError()
                    }
                }

                return false;
            }

            $systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');

            $fileObj = $entity->select_file;
            $uploadedName = $fileObj['name'];
            $uploaded = $fileObj['tmp_name'];
            $inputFileType = IOFactory::identify($uploaded); //POCOR-9584: PHPExcel_IOFactory → IOFactory
            $objReader = IOFactory::createReader($inputFileType); //POCOR-9584: PHPExcel_IOFactory → IOFactory
            $objPHPExcel = $objReader->load($uploaded);

            $totalImported = 0;
            $totalUpdated = 0;
            $importedUniqueCodes = new ArrayObject;
            $dataFailed = [];
            $dataPassed = [];
            $extra = new ArrayObject(['lookup' => [], 'entityValidate' => true]);

            $activeModel = TableRegistry::getTableLocator()->get($this->getConfig('plugin').'.'.$this->getConfig('model')); //POCOR-9584: config() → getConfig()
            $activeModel->addBehavior('DefaultValidation');

            $maxRows = $this->getConfig('max_rows'); //POCOR-9584: config() → getConfig()
            $maxRows = $maxRows + 2;
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            if ($highestRow > $maxRows) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'over_max_rows')]); //POCOR-9584: errors() → setError()
                return false;
            }

            $competencyItemsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyItems');
            $competency_item_id = $this->_table->request->getQuery('competency_item'); //POCOR-9584: request->query[] → getQuery()
            //POCOR-9584: guard against null $competency_item_id — CakePHP5 requires IS operator for null
            $competencyItemsName = null;
            if (!empty($competency_item_id)) {
                $competencyItemsName = $competencyItemsTable
                    ->find()
                    ->where([$competencyItemsTable->aliasField('id') => $competency_item_id])
                    ->extract('name')
                    ->first();
            }

            // check correct template
            $header = array($competencyItemsName, 'Competency -->');

            //calculate number of student
            $arrayStudent = $this->_table->getStudentArray();

            // calculate competency criterias
            $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();

            $totalCriteria = count($arrayCompetencyCriterias);
            $totalColumns = $totalCriteria + 1;

            //comment will be last after outcomecriterias
            //POCOR-9584: start - PhpSpreadsheet getCellByColumnAndRow is 1-indexed; $commentColumn must
            //   match the actual 1-indexed column where "Overall Comment" was written in setImportDataTemplate.
            //   setImportDataTemplate uses getExcelColumnAlpha((N*2)+2) which calls stringFromColumnIndex((N*2)+3),
            //   so the PhpSpreadsheet column index for "Overall Comment" is (N*2)+3 not (N*2)+2.
            $commentColumn = (count($arrayCompetencyCriterias)*2)+3;
            //POCOR-9584: end

            foreach ($arrayCompetencyCriterias as $key => $value) {
                $headerCriteriaId[] = $value->id;
            }

            $InstitutionCompetencyItemCommentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionCompetencyItemComments');

            //POCOR-9584: start - criteria IDs start at PhpSpreadsheet column 3 ("C") not 2 ("B");
            //   totalColumns must also shift +1 to cover the last criteria column (1-indexed)
            if (!$this->checkCorrectIdTemplate(3, $headerCriteriaId, $sheet, $totalColumns + 1, 1)) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'wrong_template')]); //POCOR-9584: errors() → setError()

                return false;
            }

            //POCOR-9584: start - competency name is at A2 (col 1) and "Competency -->" is at B2 (col 2);
            //   old code passed col=0 (invalid in PhpSpreadsheet 1-indexed); start at 1, end at 2
            if (!$this->checkCorrectTemplate(1, $header, $sheet, 2, 2)) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'wrong_template')]); //POCOR-9584: errors() → setError()

                return false;
            }
            //POCOR-9584: end

            $numberOfStudents = count($arrayStudent);
            for ($row = 4; $row < $numberOfStudents + 4; $row++) {

                // do the save for the comment
                //POCOR-9584: getCellByColumnAndRow is 1-indexed in PhpSpreadsheet; column A = 1 (not 0)
                $student = $sheet->getCellByColumnAndRow(1, $row);
                $studentOpenEmisId = $student->getValue();
                $UsersTable = TableRegistry::getTableLocator()->get('User.Users');

                $User = $UsersTable->find()
                    ->select(['id'])
                    ->where([
                        $UsersTable->aliasField('openemis_no') => $studentOpenEmisId
                    ])
                    ->first();

                $comment = $sheet->getCellByColumnAndRow($commentColumn, $row)->getValue();

                if (!empty($comment)) {
                    //POCOR-9584: start - CakePHP3 request->data[], ->alias(), session institution_id replaced with CakePHP5 equivalents
                    $alias = $this->_table->getAlias();
                    $reqData = $this->_table->request->getData()[$alias] ?? [];
                    $InstitutionCompetencyItemCommentsData = $InstitutionCompetencyItemCommentsTable->newEntity([
                        'comments' => $comment,
                        'student_id' => $User->id,
                        'competency_template_id' => $reqData['competency_template'] ?? null,
                        'competency_period_id' => $reqData['competency_period'] ?? null,
                        'competency_item_id' => $reqData['competency_item'] ?? null,
                        'institution_id' => $this->_table->getInstitutionID(), //POCOR-9584: use getInstitutionID() (reads from encoded queryString) instead of session
                        'academic_period_id' => $reqData['academic_period'] ?? null
                    ]);
                    //POCOR-9584: end

                    $InstitutionCompetencyItemCommentsTable->save($InstitutionCompetencyItemCommentsData);
                }
                // end of save comment

                $i = 0;
                //POCOR-9584: start - Grade columns start at PhpSpreadsheet col 3 ("C") not 2 ("B");
                //   upper bound shifts by +1 so loop still runs N times (one per criterion)
                for ($column = 3; $column <= $totalColumns + 1; $column++) {
                //POCOR-9584: end
                    $gradeColumn = $column + $i;
                    $i++;
                    $cell = $sheet->getCellByColumnAndRow($gradeColumn, $row);
                    $gradeValue = $cell->getValue();
                    $comment = $sheet->getCellByColumnAndRow($gradeColumn+1, $row);
                    $commentValue = $comment->getValue();

                    // if there is no any data, just skip
                    if (empty($gradeValue) && empty($commentValue)) {
                        continue;
                    }

                    $tempRow = new ArrayObject;
                    $rowInvalidCodeCols = new ArrayObject;

                    // for each columns
                    $references = [
                        'numberColumn'=>$gradeColumn,
                        'sheet'=>$sheet,
                        'row'=>$row,
                    ];

                    $originalRow = new ArrayObject;
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

                    $errors = $tableEntity->getErrors(); //POCOR-9584: errors() → getErrors()
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

                        $columns = array("outcome_criteria_id", "student_id", "competency_grading_option_id", "comment");
                        $tempPassedRecord = [
                            'row_number' => $row,
                            'data' => $this->_getReorderedEntityArray($clonedEntity, $columns, $originalRow, $systemDateFormat)
                        ];

                        $tempPassedRecord = new ArrayObject($tempPassedRecord);

                        $dataPassed[] = $tempPassedRecord->getArrayCopy();
                    }
                }
            }

            $resultHeader = array('Outcome Criteria Id', 'OpenEMIS ID', 'Competency Grading Option', 'Competency Comment');

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
        $modelName = $this->getConfig('model'); //POCOR-9584: config() → getConfig()
        $modelName = str_replace(' ', '_', Inflector::humanize(Inflector::tableize($modelName)));
        // Do not lcalize file name as certain non-latin characters might cause issue
        $excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelName);
        $excelPath = $folder . DS . $excelFile;

        $dataSheetName = $this->getExcelLabel('general', 'data');

        $objPHPExcel = new Spreadsheet(); //POCOR-9584: new \PHPExcel() → new Spreadsheet()

        $headerRow3 = array("OpenEMIS ID", "Student Name");

        $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $headerRow3, '');

        $this->setCodesDataTemplate($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = new Xlsx($objPHPExcel); //POCOR-9584: PHPExcel_Writer_Excel2007 → Xlsx
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

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName, __(Inflector::humanize(Inflector::tableize($this->_table->getAlias()))) .' '. $dataSheetName); //POCOR-9584: alias() → getAlias()
        $competencyItemsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyItems');
        $competency_item_id = $this->_table->request->getQuery('competency_item'); //POCOR-9584: request->query[] → getQuery()

        //POCOR-9584: CakePHP5 requires 'id IS' operator when value is null; guard with empty check to avoid InvalidArgumentException
        $name = null;
        if (!empty($competency_item_id)) {
            $name = $competencyItemsTable
                ->find()
                ->where([$competencyItemsTable->aliasField('id') => $competency_item_id])
                ->extract('name')
                ->first();
        }
 
        $activeSheet->setCellValue("A2", $name);
        $activeSheet->setCellValue("B2", "Competency -->");

        //headerRow3
        foreach ($header as $key => $value) {
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 3, $value);
        }

        $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();

        $suggestedRowHeight = 0;
        $i = 0;
        foreach ($arrayCompetencyCriterias as $key => $value) {
            $key = $key + 2 + $i ;
            $alpha = $this->getExcelColumnAlpha($key);
            $activeSheet->setCellValue($alpha . 1, $value->id);
            $activeSheet->setCellValue($alpha . 2, $value->name);
            $activeSheet->setCellValue($alpha . 3, 'Grade');
            $commentKey = $key + 1;           
            $commentAlpha = $this->getExcelColumnAlpha($commentKey);
            $activeSheet->setCellValue($commentAlpha . 3, 'Comment');
            $i++;          
            if ($this->suggestRowHeight(strlen($value->name), 15) > $suggestedRowHeight) {
                $suggestedRowHeight = $this->suggestRowHeight(strlen($value->name), 15);
            }
            $activeSheet->getColumnDimension( $alpha )->setWidth(20);
            $activeSheet->getColumnDimension( $commentAlpha )->setWidth(20);          
            $activeSheet->mergeCells($alpha.'1:'. $commentAlpha.'1');
            $activeSheet->mergeCells($alpha.'2:'. $commentAlpha.'2');
        }

        $activeSheet->getRowDimension(1)->setRowHeight(80);
        $activeSheet->getRowDimension(2)->setRowHeight($suggestedRowHeight);

        $arrayStudent = $this->_table->getStudentArray();

        $i = 4;
        foreach ($arrayStudent as $key => $value) {
            $activeSheet->setCellValue('A' . $i, $value->_matchingData['Users']->openemis_no);
            $activeSheet->setCellValue('B' . $i, $value->_matchingData['Users']->name);
            $i++;
            $activeSheet->getColumnDimension('A')->setAutoSize(true);
            $activeSheet->getColumnDimension('B')->setAutoSize(true);

        }

        $arrayLastAlpha = $this->getExcelColumnAlpha((count($arrayCompetencyCriterias)*2)+2);
        $activeSheet->setCellValue($arrayLastAlpha . '3', "Overall Comment");
        $activeSheet->getColumnDimension($arrayLastAlpha)->setAutoSize(true);
    }

    public function setCodesDataTemplate($objPHPExcel)
    {
        $competencyGradingOptionsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyGradingOptions');

        $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();

        $arrayStudent = $this->_table->getStudentArray();

        // $dropdownColumn is 0-indexed (getExcelColumnAlpha adds +1 internally);
        // but getCellByColumnAndRow is raw PhpSpreadsheet (1-indexed), so pass $dropdownColumn+1
        $increase = 0;
        for ($column = 2; $column < count($arrayCompetencyCriterias)+2; ++$column) {
            $dropdownColumn = $column + $increase;

            $sheet = $objPHPExcel->getSheet(0);
            $cell = $sheet->getCellByColumnAndRow($dropdownColumn + 1, 1); //POCOR-9584: +1 for PhpSpreadsheet 1-indexed
            $CompetencyId = $cell->getValue();
            $outcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
            $outcomeGradingTypeId = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('id') => $CompetencyId,
            ])
            ->extract('outcome_grading_type_id')
            ->first();

            $competencyCriteriasTable = TableRegistry::getTableLocator()->get('Competency.CompetencyCriterias');
            $competencyGradingTypeId = $competencyCriteriasTable->find()
              ->where([
                  $competencyCriteriasTable->aliasField('id') => $CompetencyId,
              ])
              ->extract('competency_grading_type_id')
              ->first();

            $gradeOptionArray = $competencyGradingOptionsTable->find()
                ->select(['name'])
                ->where([$competencyGradingOptionsTable->aliasField('competency_grading_type_id') => $competencyGradingTypeId])
                ->toArray();

            $dropDownList = '';
            foreach ($gradeOptionArray as $singleGradeOptionArray) {
                if ($singleGradeOptionArray->name == end($gradeOptionArray)->name) {
                    $dropDownList .= $singleGradeOptionArray->name;
                } else {
                    $dropDownList .= $singleGradeOptionArray->name . ', ';
                }
            }

            $alpha = $this->getExcelColumnAlpha($dropdownColumn);

            for ($i = 4; $i < count($arrayStudent) + 4; $i++) {
                $objPHPExcel->setActiveSheetIndex(0);
                $objValidation = $objPHPExcel->getActiveSheet()->getCell($alpha . $i)->getDataValidation();
                $objValidation->setType(DataValidation::TYPE_LIST); //POCOR-9584: PHPExcel_Cell_DataValidation → DataValidation
                $objValidation->setErrorStyle(DataValidation::STYLE_INFORMATION); //POCOR-9584: PHPExcel_Cell_DataValidation → DataValidation
                $objValidation->setAllowBlank(false);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setFormula1('"'.$dropDownList.'"');
            }
            $increase++;
        }
   
    }

    private function checkCorrectIdTemplate($col, $header, $sheet, $totalColumns, $row)
    {
        $cellsValue = [];
        $i = 0;
        for ($col; $col <= $totalColumns; $col++) {
            $correctCol = $col;
            $correctCol = $correctCol + $i;
            $cell = $sheet->getCellByColumnAndRow($correctCol, $row);
            $cellsValue[] = $cell->getValue();
            $i++;
        }
        return $header == $cellsValue;
    }

    // /**
    //  * Extract the values in every columns
    //  * @param  array        $references         the variables/arrays in this array are for references
    //  * @param  ArrayObject  $tempRow            for holding converted values extracted from the excel sheet on a per row basis
    //  * @param  ArrayObject  $originalRow        for holding the original value extracted from the excel sheet on a per row basis
    //  * @param  ArrayObject  $rowInvalidCodeCols for holding error messages found on option field columns
    //  * @return boolean                          returns whether the row being checked pass option field columns check
    //  */
    protected function _extractRecord($references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols, ArrayObject $extra)
    {
        $numberColumn = $references['numberColumn'];
        $sheet = $references['sheet'];
        $row = $references['row'];
        $references = null;

        $rowPass = true;

        //POCOR-9584: getCellByColumnAndRow is 1-indexed in PhpSpreadsheet; OpenEMIS ID is column A = 1
        $student = $sheet->getCellByColumnAndRow(1, $row);
        $studentValue = $student->getValue();
        $competencyId = $sheet->getCellByColumnAndRow($numberColumn, 1);
        $competencyIdValue = $competencyId->getValue();
        $cell = $sheet->getCellByColumnAndRow($numberColumn, $row);
        $gradeValue = $cell->getValue();
        $Comment = $sheet->getCellByColumnAndRow($numberColumn+1, $row);
        $commentValue = $Comment->getValue();
        $usersTable = TableRegistry::getTableLocator()->get('User.Users');

        $User = $usersTable->find()
            ->select(['id'])
            ->where([
                $usersTable->aliasField('openemis_no') => $studentValue
            ])
            ->first();

        $competencyCriteriasTable = TableRegistry::getTableLocator()->get('Competency.CompetencyCriterias');
        $competencyGradingTypeId = $competencyCriteriasTable->find()
          ->where([
              $competencyCriteriasTable->aliasField('id') => $competencyIdValue,
          ])
          ->extract('competency_grading_type_id')
          ->first();

        $competencyGradingOptionsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyGradingOptions');

        if (!empty($gradeValue)) {
            $Grading = $competencyGradingOptionsTable->find()
                ->select(['id'])
                ->where([
                    $competencyGradingOptionsTable->aliasField('name') => $gradeValue,
                    $competencyGradingOptionsTable->aliasField('competency_grading_type_id') => $competencyGradingTypeId
                ])
                ->first();
        }

        if (!empty($gradeValue) && !empty($commentValue)) {
            if (empty($Grading)) {  
                $rowPass = false;
            } else {
                $tempRow['competency_grading_option_id'] = $Grading->id;
                $tempRow['comments'] = $commentValue;                
            }
        } elseif (empty($commentValue)) {
            if (empty($Grading)) {  
                $rowPass = false;
            } else {
                $tempRow['competency_grading_option_id'] = $Grading->id;
            } 
        }elseif (empty($gradeValue)) {
            $tempRow['comments'] = $commentValue;
        }

        if ($rowPass == false) {
            $rowInvalidCodeCols['competency_grading_option_id'] = __('Wrong Grade Option');
            $extra['entityValidate'] = false;
        }

        $tempRow['competency_criteria_id'] = $competencyIdValue;
        $tempRow['student_id'] = $User->id;
        $originalRow[] = $competencyIdValue;
        $originalRow[] = $studentValue;
        $originalRow[] = $gradeValue;
        $originalRow[] = $commentValue;

        if ($rowPass) {
            $rowPassEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportModelSpecificValidation'), 'onImportModelSpecificValidation', [$references, $tempRow, $originalRow, $rowInvalidCodeCols]);
            $rowPass = $rowPassEvent->result;
        }

        return $rowPass;
    }

}
