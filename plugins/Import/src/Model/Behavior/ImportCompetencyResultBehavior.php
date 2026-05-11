<?php
namespace Import\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\Session; //POCOR-9584: Cake\Network\Session → Cake\Http\Session in CakePHP4+
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
            $errors = $entity->getErrors(); //POCOR-9584: errors() → getErrors()
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave entity_errors=' . json_encode($errors)); //[TEMP-LOG]
            if (!empty($errors)) {
                // set error message for php file upload errors
                $fileError = Hash::get($entity->getInvalid(), 'select_file.error'); //POCOR-9584: invalid() → getInvalid()
                //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave fileError=' . json_encode($fileError)); //[TEMP-LOG]
                if (!empty($fileError)) {
                    $errorMessage = $model->getMessage("fileUpload.$fileError");
                    if ($errorMessage != '[Message Not Found]') {
                        $entity->setError('select_file', [$errorMessage]); //POCOR-9584: errors(f,m,true) → setError()
                    }
                }

                return false;
            }

            $systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');

            $fileObj = $entity->select_file;
            //POCOR-9584: start - CakePHP5 UploadedFile object (Laminas\Diactoros) — ['name']/['tmp_name'] array access removed
            $uploadedName = $fileObj->getClientFilename();
            $uploaded = $fileObj->getStream()->getMetadata('uri');
            //POCOR-9584: end
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave uploadedName=' . json_encode($uploadedName)); //[TEMP-LOG]
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
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave highestRow=' . $highestRow . ' maxRows=' . $maxRows); //[TEMP-LOG]
            if ($highestRow > $maxRows) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'over_max_rows')]); //POCOR-9584: errors() → setError()
                return false;
            }

            $competencyItemsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyItems');
            //POCOR-9584: start - request->query[] → getQuery(); guard against null (CakePHP5 null WHERE)
            //   addOnInitialize clears query params before addBeforeSave runs on POST — fall back to POST data
            $postDataAlias = $this->_table->request->getData()[$this->_table->getAlias()] ?? [];
            $competency_item_id = $this->_table->request->getQuery('competency_item_id') //POCOR-9584: renamed competency_item → competency_item_id
                ?? ($postDataAlias['competency_item_id'] ?? null); //POCOR-9584: POST fallback for addOnInitialize clear
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave allQueryParams=' . json_encode($this->_table->request->getQueryParams()) . ' allPostKeys=' . json_encode(array_keys($postDataAlias))); //[TEMP-LOG]
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave competency_item_id=' . json_encode($competency_item_id)); //[TEMP-LOG]
            $competencyItemsName = null;
            if (!empty($competency_item_id)) {
                $competencyItemsName = $competencyItemsTable
                    ->find()
                    ->where([$competencyItemsTable->aliasField('id') => $competency_item_id])
                    ->extract('name')
                    ->first();
            }
            //POCOR-9584: end
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave competencyItemsName=' . json_encode($competencyItemsName)); //[TEMP-LOG]

            // check correct template
            $header = array($competencyItemsName, 'Competency -->');

            //calculate number of student
            $arrayStudent = $this->_table->getStudentArray();
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave studentCount=' . count($arrayStudent)); //[TEMP-LOG]

            // calculate competency criterias
            $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave criteriaCount=' . count($arrayCompetencyCriterias) . ' criteriaIds=' . json_encode(array_column((array)$arrayCompetencyCriterias, 'id'))); //[TEMP-LOG]

            $totalCriteria = count($arrayCompetencyCriterias);
            $totalColumns = $totalCriteria + 1;

            //POCOR-9584: start - PhpSpreadsheet getCellByColumnAndRow is 1-indexed; $commentColumn must
            //   match the 1-indexed column where "Overall Comment" is written in setImportDataTemplate.
            //   setImportDataTemplate uses getExcelColumnAlpha((N*2)+2) = stringFromColumnIndex((N*2)+3),
            //   so PhpSpreadsheet column index for "Overall Comment" is (N*2)+3, not (N*2)+2.
            $commentColumn = (count($arrayCompetencyCriterias)*2)+3;
            //POCOR-9584: end
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave totalCriteria=' . $totalCriteria . ' totalColumns=' . $totalColumns . ' commentColumn=' . $commentColumn); //[TEMP-LOG]

            foreach ($arrayCompetencyCriterias as $key => $value) {
                $headerCriteriaId[] = $value->id;
            }
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave headerCriteriaId=' . json_encode($headerCriteriaId ?? [])); //[TEMP-LOG]

            $InstitutionCompetencyItemCommentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionCompetencyItemComments');

            //POCOR-9584: start - criteria IDs in row 1 start at PhpSpreadsheet column 3 ("C") not 2 ("B");
            //   totalColumns shifts +1 so the loop in checkCorrectIdTemplate covers the last criteria column
            $checkIdResult = $this->checkCorrectIdTemplate(3, $headerCriteriaId ?? [], $sheet, $totalColumns + 1, 1);
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave checkCorrectIdTemplate result=' . json_encode($checkIdResult) . ' startCol=3 endCol=' . ($totalColumns + 1)); //[TEMP-LOG]
            if (!$checkIdResult) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'wrong_template')]); //POCOR-9584: errors() → setError()
                return false;
            }

            //POCOR-9584: start - competency name is at A2 (col 1) and "Competency -->" is at B2 (col 2);
            //   old code passed col=0 which is invalid in PhpSpreadsheet 1-indexed
            $checkTemplateResult = $this->checkCorrectTemplate(1, $header, $sheet, 2, 2);
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave checkCorrectTemplate result=' . json_encode($checkTemplateResult) . ' header=' . json_encode($header)); //[TEMP-LOG]
            if (!$checkTemplateResult) {
                $entity->setError('select_file', [$this->getExcelLabel('Import', 'wrong_template')]); //POCOR-9584: errors() → setError()
                return false;
            }
            //POCOR-9584: end

            $numberOfStudents = count($arrayStudent);
            //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave starting row loop numberOfStudents=' . $numberOfStudents . ' rows=4..' . ($numberOfStudents + 3)); //[TEMP-LOG]
            for ($row = 4; $row < $numberOfStudents + 4; $row++) {

                // do the save for the comment
                //POCOR-9584: getCellByColumnAndRow is 1-indexed in PhpSpreadsheet; column A = 1 (not 0)
                $student = $sheet->getCellByColumnAndRow(1, $row);
                $studentOpenEmisId = trim((string)$student->getValue());
                //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave row=' . $row . ' studentOpenEmisId=' . json_encode($studentOpenEmisId)); //[TEMP-LOG]

                $comment = $sheet->getCellByColumnAndRow($commentColumn, $row)->getValue();
                //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave row=' . $row . ' overallComment(col' . $commentColumn . ')=' . json_encode($comment)); //[TEMP-LOG]

                if (!empty($comment)) {
                    $User = null;
                    if ($studentOpenEmisId !== '') {
                        $UsersTable = TableRegistry::getTableLocator()->get('User.Users');
                        $User = $UsersTable->find()
                            ->select(['id'])
                            ->where([
                                $UsersTable->aliasField('openemis_no') => $studentOpenEmisId
                            ])
                            ->first();
                    }
                    if ($User) {
                        //POCOR-9584: start - CakePHP3 request->data[], ->alias(), session institution_id → CakePHP5 equivalents
                        $alias = $this->_table->getAlias(); //POCOR-9584: alias() → getAlias()
                        $reqData = $this->_table->request->getData()[$alias] ?? []; //POCOR-9584: request->data[] → getData()
                        //// Log::debug('@ImportCompetencyResultBehavior::addBeforeSave row=' . $row . ' reqData=' . json_encode($reqData)); //[TEMP-LOG]
                        $InstitutionCompetencyItemCommentsData = $InstitutionCompetencyItemCommentsTable->newEntity([
                            'comments' => $comment,
                            'student_id' => $User->id,
                            'competency_template_id' => $reqData['competency_template_id'] ?? null, //POCOR-9584: old key competency_template → competency_template_id
                            'competency_period_id' => $reqData['competency_period_id'] ?? null,     //POCOR-9584: old key competency_period → competency_period_id
                            'competency_item_id' => $reqData['competency_item_id'] ?? null,         //POCOR-9584: old key competency_item → competency_item_id
                            'institution_id' => $this->_table->getInstitutionID(), //POCOR-9584: session read → getInstitutionID()
                            'academic_period_id' => $reqData['academic_period_id'] ?? null          //POCOR-9584: old key academic_period → academic_period_id
                        ]);
                        //POCOR-9584: end

                        $InstitutionCompetencyItemCommentsTable->save($InstitutionCompetencyItemCommentsData);
                    }
                }
                // end of save comment

                $i = 0;
                //POCOR-9584: start - Grade columns start at PhpSpreadsheet col 3 ("C") not 2 ("B");
                //   upper bound shifts +1 so loop runs N times (one per criterion)
                for ($column = 3; $column <= $totalColumns + 1; $column++) {
                //POCOR-9584: end
                    $gradeColumn = $column + $i;
                    $i++;
                    $cell = $sheet->getCellByColumnAndRow($gradeColumn, $row);
                    $gradeValue = $cell->getValue();
                    $comment = $sheet->getCellByColumnAndRow($gradeColumn+1, $row);
                    $commentValue = $comment->getValue();
                    // Log::debug('@ImportCompetencyResultBehavior::addBeforeSave row=' . $row . ' column=' . $column . ' gradeCol=' . $gradeColumn . ' gradeValue=' . json_encode($gradeValue) . ' commentValue=' . json_encode($commentValue)); //[TEMP-LOG]

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
                        $tableEntity = $activeModel->newEntity([]); //POCOR-9584: CakePHP5 — newEntity() requires array argument
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
                    //POCOR-9584: define $columns before error-reporting block (was only defined in else branch at line ~315, causing undefined var warning on line ~281)
                    $columns = ['competency_criteria_id', 'student_id', 'competency_grading_option_id', 'comments'];
                    // Log::debug('@ImportCompetencyResultBehavior::addBeforeSave row=' . $row . ' col=' . $column . ' tableEntityErrors=' . json_encode($errors) . ' rowInvalidCodeCols=' . json_encode($rowInvalidCodeCols)); //[TEMP-LOG]

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
                        // Log::debug('@ImportCompetencyResultBehavior::addBeforeSave row=' . $row . ' col=' . $column . ' saveResult=' . json_encode((bool)($newEntity ?? false)) . ' isNew=' . json_encode($isNew) . ' totalImported=' . $totalImported . ' totalUpdated=' . $totalUpdated); //[TEMP-LOG]
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
                        $clonedEntity->setVirtual([]); //POCOR-9584: CakePHP5 — virtualProperties() removed; use setVirtual()

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

            // Log::debug('@ImportCompetencyResultBehavior::addBeforeSave DONE totalImported=' . $totalImported . ' totalUpdated=' . $totalUpdated . ' failedRows=' . count($dataFailed)); //[TEMP-LOG]

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

            //POCOR-9584: start - carry encoded pass[1] (institution_id) to results redirect URL;
            //   bare url('results') loses pass[1] and carries stale ?period query param
            $resultsUrl = $this->_table->ControllerAction->url('results');
            $encodedParam = $this->_table->request->getParam('pass')[1] ?? null;
            if ($encodedParam) {
                $resultsUrl[1] = $encodedParam;
            } else {
                $resultsUrl[1] = $this->_table->paramsEncode(['institution_id' => $this->institutionId]);
            }
            unset($resultsUrl['?']); // strip stale query params (e.g. period=34) from add page URL
            //POCOR-9584: end

            return $model->controller->redirect($resultsUrl);
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
        // Log::debug('@ImportCompetencyResultBehavior::template modelName=' . $modelName . ' excelPath=' . $excelPath); //[TEMP-LOG]

        $dataSheetName = $this->getExcelLabel('general', 'data');

        $objPHPExcel = new Spreadsheet(); //POCOR-9584: new \PHPExcel() → new Spreadsheet()

        $headerRow3 = array("OpenEMIS ID", "Student Name");

        $this->setImportDataTemplate($objPHPExcel, $dataSheetName, $headerRow3, '');

        $this->setCodesDataTemplate($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = new Xlsx($objPHPExcel); //POCOR-9584: PHPExcel_Writer_Excel2007 → Xlsx
        $objWriter->save($excelPath);
        // Log::debug('@ImportCompetencyResultBehavior::template saved to excelPath=' . $excelPath); //[TEMP-LOG]

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
        //POCOR-9584: start - request->query[] → getQuery(); guard null for CakePHP5 null WHERE
        $competency_item_id = $this->_table->request->getQuery('competency_item_id'); //POCOR-9584: renamed competency_item → competency_item_id
        // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate competency_item_id=' . json_encode($competency_item_id)); //[TEMP-LOG]

        $name = null;
        if (!empty($competency_item_id)) {
            $name = $competencyItemsTable
                ->find()
                ->where([$competencyItemsTable->aliasField('id') => $competency_item_id])
                ->extract('name')
                ->first();
        }
        //POCOR-9584: end
        // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate competencyItemName=' . json_encode($name)); //[TEMP-LOG]

        $activeSheet->setCellValue("A2", $name);
        $activeSheet->setCellValue("B2", "Competency -->");

        //headerRow3
        // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate headerRow3 count=' . count($header)); //[TEMP-LOG]
        foreach ($header as $key => $value) {
            //POCOR-9584: getExcelColumnAlpha is 1-indexed at runtime (pass 1 for "A"); key starts at 0 → pass key+1
            $alpha = $this->getExcelColumnAlpha($key + 1);
            // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate headerRow3 key=' . $key . ' alpha=' . $alpha . ' value=' . json_encode($value)); //[TEMP-LOG]
            $activeSheet->setCellValue($alpha . 3, $value);
        }

        $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();
        // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate criteriaCount=' . count($arrayCompetencyCriterias)); //[TEMP-LOG]

        $suggestedRowHeight = 0;
        $i = 0;
        foreach ($arrayCompetencyCriterias as $key => $value) {
            //POCOR-9584: criteria start at col "C" (1-indexed 3); offset is key+3+i (not key+2+i)
            $key = $key + 3 + $i;
            $alpha = $this->getExcelColumnAlpha($key);
            $commentKey = $key + 1;
            $commentAlpha = $this->getExcelColumnAlpha($commentKey);
            // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate criteria id=' . $value->id . ' gradeAlpha=' . $alpha . '(key=' . $key . ') commentAlpha=' . $commentAlpha); //[TEMP-LOG]
            $activeSheet->setCellValue($alpha . 1, $value->id);
            //POCOR-9584: hide criteria ID in row 1 (white text) — it's only for template validation, not user-visible
            $activeSheet->getStyle($alpha . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
            $activeSheet->setCellValue($alpha . 2, $value->name);
            $activeSheet->setCellValue($alpha . 3, 'Grade');
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
        // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate studentCount=' . count($arrayStudent)); //[TEMP-LOG]

        $i = 4;
        foreach ($arrayStudent as $key => $value) {
            // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate student row=' . $i . ' openemis_no=' . json_encode($value->_matchingData['Users']->openemis_no) . ' name=' . json_encode($value->_matchingData['Users']->name)); //[TEMP-LOG]
            $activeSheet->setCellValue('A' . $i, $value->_matchingData['Users']->openemis_no);
            $activeSheet->setCellValue('B' . $i, $value->_matchingData['Users']->name);
            $i++;
            $activeSheet->getColumnDimension('A')->setAutoSize(true);
            $activeSheet->getColumnDimension('B')->setAutoSize(true);
        }

        //POCOR-9584: overall comment column = after N criteria pairs (grade+comment = 2 cols each) starting at col 3
        //   → col = 2 + 2*N + 1 = 2N+3; getExcelColumnAlpha is 1-indexed so pass 2N+3
        $overallCommentKey = (count($arrayCompetencyCriterias)*2)+3;
        $arrayLastAlpha = $this->getExcelColumnAlpha($overallCommentKey);
        // Log::debug('@ImportCompetencyResultBehavior::setImportDataTemplate overallCommentKey(1-based)=' . $overallCommentKey . ' overallCommentAlpha=' . $arrayLastAlpha); //[TEMP-LOG]
        $activeSheet->setCellValue($arrayLastAlpha . '3', "Overall Comment");
        $activeSheet->getColumnDimension($arrayLastAlpha)->setAutoSize(true);
    }

    public function setCodesDataTemplate($objPHPExcel)
    {
        $competencyGradingOptionsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyGradingOptions');

        $arrayCompetencyCriterias = $this->_table->getCompetencyCriteriasArray();

        $arrayStudent = $this->_table->getStudentArray();
        // Log::debug('@ImportCompetencyResultBehavior::setCodesDataTemplate criteriaCount=' . count($arrayCompetencyCriterias) . ' studentCount=' . count($arrayStudent)); //[TEMP-LOG]

        // $dropdownColumn is 0-indexed (matches getExcelColumnAlpha which adds +1 internally);
        // getCellByColumnAndRow is raw PhpSpreadsheet (1-indexed), so pass $dropdownColumn+1 to read row 1
        $increase = 0;
        for ($column = 2; $column < count($arrayCompetencyCriterias)+2; ++$column) {
            $dropdownColumn = $column + $increase;

            $sheet = $objPHPExcel->getSheet(0);
            $cell = $sheet->getCellByColumnAndRow($dropdownColumn + 1, 1); //POCOR-9584: +1 for PhpSpreadsheet 1-indexed
            $CompetencyId = $cell->getValue();
            // Log::debug('@ImportCompetencyResultBehavior::setCodesDataTemplate column=' . $column . ' dropdownColumn=' . $dropdownColumn . ' reading PhpSS col=' . ($dropdownColumn + 1) . ' CompetencyId=' . json_encode($CompetencyId)); //[TEMP-LOG]
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

            //POCOR-9584: getExcelColumnAlpha is 1-indexed; dropdownColumn starts at 2 → pass +1 to match "C"
            $alpha = $this->getExcelColumnAlpha($dropdownColumn + 1);
            // Log::debug('@ImportCompetencyResultBehavior::setCodesDataTemplate dropdownColumn=' . $dropdownColumn . ' alpha=' . $alpha . ' gradingTypeId=' . json_encode($competencyGradingTypeId) . ' optionCount=' . count($gradeOptionArray) . ' dropDownList=' . json_encode($dropDownList)); //[TEMP-LOG]

            for ($i = 4; $i < count($arrayStudent) + 4; $i++) {
                $objPHPExcel->setActiveSheetIndex(0);
                $objValidation = $objPHPExcel->getActiveSheet()->getCell($alpha . $i)->getDataValidation();
                $objValidation->setType(DataValidation::TYPE_LIST); //POCOR-9584: \PHPExcel_Cell_DataValidation → DataValidation
                $objValidation->setErrorStyle(DataValidation::STYLE_INFORMATION); //POCOR-9584: \PHPExcel_Cell_DataValidation → DataValidation
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
        // Log::debug('@ImportCompetencyResultBehavior::checkCorrectIdTemplate header=' . json_encode($header) . ' cellsValue=' . json_encode($cellsValue) . ' match=' . json_encode($header == $cellsValue)); //[TEMP-LOG]
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

        //POCOR-9584: getCellByColumnAndRow is 1-indexed in PhpSpreadsheet; column A = 1 (not 0)
        $student = $sheet->getCellByColumnAndRow(1, $row);
        $studentValue = $student->getValue();
        $competencyId = $sheet->getCellByColumnAndRow($numberColumn, 1);
        $competencyIdValue = $competencyId->getValue();
        $cell = $sheet->getCellByColumnAndRow($numberColumn, $row);
        $gradeValue = $cell->getValue();
        $Comment = $sheet->getCellByColumnAndRow($numberColumn+1, $row);
        $commentValue = $Comment->getValue();
        // Log::debug('@ImportCompetencyResultBehavior::_extractRecord numberColumn=' . $numberColumn . ' row=' . $row . ' studentValue=' . json_encode($studentValue) . ' competencyIdValue=' . json_encode($competencyIdValue) . ' gradeValue=' . json_encode($gradeValue) . ' commentValue=' . json_encode($commentValue)); //[TEMP-LOG]
        $usersTable = TableRegistry::getTableLocator()->get('User.Users');

        $User = $usersTable->find()
            ->select(['id'])
            ->where([
                $usersTable->aliasField('openemis_no') => $studentValue
            ])
            ->first();
        // Log::debug('@ImportCompetencyResultBehavior::_extractRecord userId=' . json_encode($User ? $User->id : null)); //[TEMP-LOG]

        $competencyCriteriasTable = TableRegistry::getTableLocator()->get('Competency.CompetencyCriterias');
        $competencyGradingTypeId = $competencyCriteriasTable->find()
          ->where([
              $competencyCriteriasTable->aliasField('id') => $competencyIdValue,
          ])
          ->extract('competency_grading_type_id')
          ->first();
        // Log::debug('@ImportCompetencyResultBehavior::_extractRecord competencyGradingTypeId=' . json_encode($competencyGradingTypeId)); //[TEMP-LOG]

        $competencyGradingOptionsTable = TableRegistry::getTableLocator()->get('Competency.CompetencyGradingOptions');

        if (!empty($gradeValue)) {
            $Grading = $competencyGradingOptionsTable->find()
                ->select(['id'])
                ->where([
                    $competencyGradingOptionsTable->aliasField('name') => $gradeValue,
                    $competencyGradingOptionsTable->aliasField('competency_grading_type_id') => $competencyGradingTypeId
                ])
                ->first();
            // Log::debug('@ImportCompetencyResultBehavior::_extractRecord Grading=' . json_encode($Grading ? $Grading->id : null)); //[TEMP-LOG]
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
        } elseif (empty($gradeValue)) {
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
        // Log::debug('@ImportCompetencyResultBehavior::_extractRecord rowPass=' . json_encode($rowPass) . ' tempRow=' . json_encode($tempRow->getArrayCopy())); //[TEMP-LOG]

        if ($rowPass) {
            $rowPassEvent = $this->dispatchEvent($this->_table, $this->eventKey('onImportModelSpecificValidation'), 'onImportModelSpecificValidation', [$references, $tempRow, $originalRow, $rowInvalidCodeCols]);
            $rowPass = $rowPassEvent->getResult(); //POCOR-9584: CakePHP5 — $event->result protected; use getResult()
        }

        return $rowPass;
    }

}
