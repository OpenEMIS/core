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

            $competencyItemsTable = TableRegistry::get('Competency.CompetencyItems');
            $competency_item_id = $this->_table->request->query['competency_item'];
            $competencyItemsName = $competencyItemsTable
                    ->find()
                    ->where([$competencyItemsTable->aliasField('id') => $competency_item_id])
                    ->extract('name')
                    ->first();

            // check correct template
            $header = array($competencyItemsName, 'Competency -->');

            //calculate number of student
            $arrayStudent = $this->_table->getStudentArray();

            // calculate competency criterias
            $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();

            $totalCriteria = count($arrayCompetencyCriterias);
            $totalColumns = $totalCriteria + 1;

            //comment will be last after outcomecriterias
            $commentColumn = (count($arrayCompetencyCriterias)*2)+2;

            foreach ($arrayCompetencyCriterias as $key => $value) {
                $headerCriteriaId[] = $value->id;
            }

            $InstitutionCompetencyItemCommentsTable = TableRegistry::get('Institution.InstitutionCompetencyItemComments');

            if (!$this->checkCorrectIdTemplate(2, $headerCriteriaId, $sheet, $totalColumns, 1)) {
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
                    $InstitutionCompetencyItemCommentsData = $InstitutionCompetencyItemCommentsTable->newEntity([
                        'comments' => $comment,
                        'student_id' => $User->id,
                        'competency_template_id' => $this->_table->request->data[$this->_table->alias()]['competency_template'],
                        'competency_period_id' => $this->_table->request->data[$this->_table->alias()]['competency_period'],
                        'competency_item_id' => $this->_table->request->data[$this->_table->alias()]['competency_item'],
                        'institution_id' => $this->_table->request->session()->read('Institution.Institutions.id'),
                        'academic_period_id' => $this->_table->request->data[$this->_table->alias()]['academic_period']
                    ]);

                    $InstitutionCompetencyItemCommentsTable->save($InstitutionCompetencyItemCommentsData);
                }
                // end of save comment

                $i = 0;
                for ($column = 2; $column <= $totalColumns; $column++) {
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
        $modelName = $this->config('model');
        $modelName = str_replace(' ', '_', Inflector::humanize(Inflector::tableize($modelName)));
        // Do not lcalize file name as certain non-latin characters might cause issue
        $excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelName);
        $excelPath = $folder . DS . $excelFile;

        $dataSheetName = $this->getExcelLabel('general', 'data');

        $objPHPExcel = new \PHPExcel();

        $headerRow3 = array("OpenEMIS ID", "Student Name");

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

        $this->beginExcelHeaderStyling($objPHPExcel, $dataSheetName,  __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) .' '. $dataSheetName);
        $competencyItemsTable = TableRegistry::get('Competency.CompetencyItems');       
        $competency_item_id = $this->_table->request->query['competency_item'];
       
        $name = $competencyItemsTable
                ->find()
                ->where([$competencyItemsTable->aliasField('id') => $competency_item_id])
                ->extract('name')
                ->first();
 
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
        $competencyGradingOptionsTable = TableRegistry::get('Competency.CompetencyGradingOptions');

        $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();

        $arrayStudent = $this->_table->getStudentArray();

        //A is 0 in excel column, so 2 is C
        $increase = 0;    
        for ($column = 2; $column < count($arrayCompetencyCriterias)+2; ++$column) {
            $dropdownColumn = $column + $increase;

            $sheet = $objPHPExcel->getSheet(0);
            $cell = $sheet->getCellByColumnAndRow($dropdownColumn, 1);
            $CompetencyId = $cell->getValue();
            $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
            $outcomeGradingTypeId = $outcomeCriteriasTable->find()
            ->where([
                $outcomeCriteriasTable->aliasField('id') => $CompetencyId,
            ])
            ->extract('outcome_grading_type_id')
            ->first();

            $competencyCriteriasTable = TableRegistry::get('Competency.CompetencyCriterias');
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
                $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
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

        $student = $sheet->getCellByColumnAndRow(0, $row);
        $studentValue = $student->getValue();
        $competencyId = $sheet->getCellByColumnAndRow($numberColumn, 1);
        $competencyIdValue = $competencyId->getValue();
        $cell = $sheet->getCellByColumnAndRow($numberColumn, $row);
        $gradeValue = $cell->getValue();
        $Comment = $sheet->getCellByColumnAndRow($numberColumn+1, $row);
        $commentValue = $Comment->getValue();
        $usersTable = TableRegistry::get('User.Users');

        $User = $usersTable->find()
            ->select(['id'])
            ->where([
                $usersTable->aliasField('openemis_no') => $studentValue
            ])
            ->first();

        $competencyCriteriasTable = TableRegistry::get('Competency.CompetencyCriterias');
        $competencyGradingTypeId = $competencyCriteriasTable->find()
          ->where([
              $competencyCriteriasTable->aliasField('id') => $competencyIdValue,
          ])
          ->extract('competency_grading_type_id')
          ->first();

        $competencyGradingOptionsTable = TableRegistry::get('Competency.CompetencyGradingOptions');

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
