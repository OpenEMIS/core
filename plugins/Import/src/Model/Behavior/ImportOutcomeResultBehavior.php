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

class ImportOutcomeResultBehavior extends ImportResultBehavior
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
            $template = $this->_table->request->query['outcome_template'];

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
            $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
            $outcomeTemplatesTable = TableRegistry::get('Outcome.OutcomeTemplates');

            $educationGradeId = $outcomeTemplatesTable->find()
                ->where([
                    $outcomeTemplatesTable->aliasField('id') => $template,
                ])
                ->extract('education_grade_id')
                ->first();

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

        $template = $this->_table->request->query['outcome_template'];

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

    public function setCodesDataTemplate($objPHPExcel)
    {
        $outcomeGradingOptionsTable = TableRegistry::get('Outcome.OutcomeGradingOptions');
        $education_subject_id = $this->_table->request->query['education_subject'];
        $template = $this->_table->request->query['outcome_template'];

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
            $sheet = $objPHPExcel->getSheet(0);
            $cell = $sheet->getCellByColumnAndRow($column, 1);
            $outcomeId = $cell->getValue();
            $outcomeCriteriasTable = TableRegistry::get('Outcome.OutcomeCriterias');
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
                $objValidation->setFormula1('"'.$dropDownList.'"');
            }
        }
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
        }else {
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
