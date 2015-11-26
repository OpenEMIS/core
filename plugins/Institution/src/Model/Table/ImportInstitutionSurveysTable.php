<?php
namespace Institution\Model\Table;

use DateTime;
use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportInstitutionSurveysTable extends AppTable {
	const RECORD_QUESTION = 1;
	const FIRST_RECORD = 2;

	public $institutionSurveyId = false;
	public $institutionSurvey = false;
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'InstitutionSiteSurveys']);

	    $this->Institutions = TableRegistry::get('Institution.Institutions');
	    $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
	    $this->InstitutionSurveys = TableRegistry::get('Institution.InstitutionSurveys');
	    $this->InstitutionSurveyAnswers = TableRegistry::get('Institution.InstitutionSurveyAnswers');
	    $this->InstitutionSurveyTableCells = TableRegistry::get('Institution.InstitutionSurveyTableCells');
	    $this->SurveyForms = TableRegistry::get('Survey.SurveyForms');
		$this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
	}

	public function beforeAction($event) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$this->institutionId = $session->read('Institution.Institutions.id');
		} else {
			$this->institutionId = false;
		}
		if (!empty($this->request->pass) && isset($this->request->pass[1])) {
			$this->institutionSurveyId = $this->request->pass[1];
		}
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
		$this->sessionKey = $this->registryAlias().'.Import.data';
		$this->InstitutionSurveyAnswers->ControllerAction = $this->ControllerAction;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function template() {
		$folder = $this->prepareDownload();
		$modelName = $this->alias();
		$excelFile = sprintf('%s_%s_%s_%s.xlsx', 'Import', 'Institution', $modelName, 'Template');

		$excelPath = $folder . DS . $excelFile;

		$writer = new \XLSXWriter();
		
		$surveyForm = $this->institutionSurvey->survey_form;
		$header = $this->generateHeader($surveyForm->custom_fields);

		$surveySheetName = Text::truncate('(' . $surveyForm->code .') '.$surveyForm->name, 31, ['ellipsis' => '']);
		$writer->writeSheetRow($surveySheetName, array_values($header));
		
		$codesData = $this->excelGetCodesData($this);
		foreach($codesData as $modelName => $modelArr) {
			foreach($modelArr as $row) {
				$writer->writeSheetRow($modelName, array_values($row));
			}
		}
		
		$writer->writeToFile($excelPath);
		$this->performDownload($excelFile);
		die;
	}

	private function generateHeader($surveyQuestions) {
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

				if (sizeof($row) !=0 || sizeof($column) !=0 ) {
					for($i = 1; $i < sizeof($column); $i++) {
						foreach ($row as $r) {
							$header[] = '(' . $question->code . ') '. $question->name . ' ('.$column[$i]['name'].', '.$r['name'].')';
						}
					}
				}
			} else {
				$header[] = '(' . $question->code .') '. $question->name;
			}
		}
		return $header;
	}

	public function excelGetCodesData(Table $model) {
		$questions = $this->institutionSurvey->survey_form->custom_fields;
		$data = [];
		foreach ($questions as $question) {
			$sheetName = $question->code;
			if ($question->field_type == 'DROPDOWN') {
				$data[$sheetName][] = [__('Answer Code'), __('Answer Name'), '', __('Question')];
				foreach($question->custom_field_options as $key=>$row) {
					if ($row->visible) {
						$data[$sheetName][] = [$row->id, $row->name];
					}
				}
				$data[$sheetName][1][4] = '';
				$data[$sheetName][1][5] = $question->name;
				$data[$sheetName][2][4] = '';
				$data[$sheetName][2][5] = __('(Use only one of the answer codes)');
			} elseif ($question->field_type == 'CHECKBOX') {
				$data[$sheetName][] = [__('Answer Code'), __('Answer Name'), '', __('Question')];
				foreach($question->custom_field_options as $key=>$row) {
					if ($row->visible) {
						$data[$sheetName][] = [$row->id, $row->name];
					}
				}
				$data[$sheetName][1][4] = '';
				$data[$sheetName][1][5] = $question->name;
				$data[$sheetName][2][4] = '';
				$data[$sheetName][2][5] = __('(Multiple codes can be selected and seperated by comma)');
			}
		}
		return $data;
	}

	private function getCellValue($sheet, $columnNumber, $rowNumber) {
		$cell = $sheet->getCellByColumnAndRow($columnNumber, $rowNumber);
		$cellValue = $cell->getValue();
		return $cellValue;
	}

	private function getTotalColumns($surveyFormQuestions) {
		$count = 0;
		foreach ($surveyFormQuestions as $question) {
			if ($question['field_type'] == 'TABLE') {

				// First row is a description of the first column
				$rowCount = count($question['custom_table_rows']) - 1;
				$colCount = count($question['custom_table_columns']);

				// Multiplication of both count will determine the number of columns for this table type
				$count += ($rowCount * $colCount);
			} else {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Actual Import business logics reside in this function
	 * @param  Event  		$event  Event object
	 * @param  Entity 		$entity Entity object containing the uploaded file parameters 
	 * @param  ArrayObject  $data  	Event object
	 * @return Response       		Response object
	 */
	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		return function ($model, $entity) {
			$errors = $entity->errors();
			if (!empty($errors)) {
				return false;
			}

			$systemDateFormat = TableRegistry::get('ConfigItems')->value('date_format');
			$systemTimeFormat = TableRegistry::get('ConfigItems')->value('time_format');

			$controller = $model->controller;
			$controller->loadComponent('PhpExcel');

			$surveyForm = $this->institutionSurvey->survey_form;
			$header = $this->generateHeader($surveyForm->custom_fields);

			$fileObj = $entity->select_file;
			$uploadedName = $fileObj['name'];
			$uploaded = $fileObj['tmp_name'];
			$objPHPExcel = $controller->PhpExcel->loadWorksheet($uploaded);
			$sheet = $objPHPExcel->getSheet(0);
			$maxRows = 2002;
			$highestRow = $sheet->getHighestRow();
			if ($highestRow > $maxRows) {
				$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max_rows')], true);
				return false;
			}

			$totalImported = 0;
			$totalUpdated = 0;
			$importedUniqueCodes = new ArrayObject;
			$dataFailed = [];

			$sheetName = $sheet->getTitle();
			// get code from sheetname which is within a pair of brackets "()".
			// sheet name should have only one pair of brackets "()".
			// preg_match("/(\([0-9a-zA-Z]{0,50}\))/", $sheetName, $output);
			preg_match("/^\((\w{0,50})\)/", $sheetName, $output);
			if (count($output)!=2) {
				$entity->errors('select_file', [$this->getExcelLabel('Import', 'survey_not_found')], true);
				return false;
			}

			$surveyCode = (isset($output[1])) ? $output[1] : str_replace(')', '', str_replace('(', '', $output[0]));
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
			$totalColumns = count($questions);

			$totalColumns = $this->getTotalColumns($questions);
			pr($totalColumns);die;

			for ($row = 1; $row <= $highestRow; ++$row) {
				if ($row == self::RECORD_QUESTION) { // skip header but check if the uploaded template is correct
					// if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
					// 	$entity->errors('select_file', [$this->getExcelLabel('Import', 'wrong_template')]);
					// 	return false;
					// }
					continue;
				}
				if ($row == $highestRow) { // if $row == $highestRow, check if the row cells are really empty, if yes then end the loop
					if ($this->checkRowCells($sheet, $totalColumns, $row) === false) {
						break;
					}
				}
				
				foreach ($questions as $question) {
					$fieldType = $question->field_type;
					switch ($fieldType) {
						case 'DROPDOWN':

							break;
						case 'CHECKBOX':
							break;

						case 'NUMBER':
							break;

						case 'DATE':
						case 'TIME':
							break;

						case 'TABLE':
							break;
					}

					if ($fieldType != 'CHECKBOX') {

					}
				}

				$tempRow = [];
				$rowInvalidCodeCols = [];
				$originalRow = new ArrayObject;
				$rowFailed = false;
				for ($col = 0; $col < $totalColumns; ++$col) {
					$cellValue = $this->getCellValue($sheet, $col, $row);

					$columnCode = $questions[$col]->code;
					$questionOptions = $questions[$col]->custom_field_options;
					$originalRow[$col] = $cellValue;
					if (empty($cellValue) && $questions[$col]->is_mandatory) {
						$rowFailed = true;
						$rowInvalidCodeCols[] = $columnCode;
					} else if (empty($cellValue) && !$questions[$col]->is_mandatory) {
						continue;
					}
					switch ($questions[$col]->field_type) {
						case 'DROPDOWN':
							$questionOptions = new Collection($questionOptions);
							$filtered = $questionOptions->filter(function ($record, $key, $iterator) use ($cellValue) {
							    return $record->id == $cellValue;
							});
							$selectedAnswer = $filtered->toArray();
							if (!empty($selectedAnswer)) {
								$codeIndex = key($selectedAnswer);
								$cellValue = $selectedAnswer[$codeIndex]->id;
							} else {
								$rowFailed = true;
								$rowInvalidCodeCols[] = $columnCode;
							}
							break;
						
						case 'CHECKBOX':
							$questionOptions = new Collection($questionOptions);
							$selections = explode(',', $cellValue);
							foreach ($selections as $selectionKey=>$selection) {

								$filtered = $questionOptions->filter(function ($record, $key, $iterator) use ($selection) {
								    return $record->id == trim($selection);
								});
								$selectedAnswer = $filtered->toArray();

								if (!empty($selectedAnswer)) {
									$codeIndex = key($selectedAnswer);
									$trimmedVal = $selectedAnswer[$codeIndex]->id;
								} else {
									$rowFailed = true;
									$rowInvalidCodeCols[] = $columnCode;
								}

								if (!$rowFailed) {
									$obj = [
										'institution_site_survey_id' => $this->institutionSurvey->id,
										'survey_question_id' => $questions[$col]->id,
										$this->fieldTypes[$questions[$col]->field_type] => $trimmedVal,
									];
									$tableEntity = $this->InstitutionSurveyAnswers->newEntity();
									$this->InstitutionSurveyAnswers->patchEntity($tableEntity, $obj);
									$tempRow[$columnCode.$selectionKey] = $tableEntity;
								}

							}
							break;

						case 'NUMBER':
							if (!is_numeric($cellValue)) {
								$rowFailed = true;
								$rowInvalidCodeCols[] = $columnCode;
							}
							break;

						case 'DATE':case 'TIME':
							if (is_numeric($cellValue)) {
								$cellValue = date('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($cellValue));
								// converts val to Time object so that this field will pass 'validDate' check since
								// different model has different date format checking. Example; user->date_of_birth is using dmY while others using Y-m-d,
								// so it is best to convert the date here instead of adjusting individual model's date validation format
								try {
									$cellValue = new Time($cellValue);
									if ($questions[$col]->field_type == 'DATE') {
										$originalRow[$col] = $cellValue->format($systemDateFormat);
									} else {
										$originalRow[$col] = $cellValue->format($systemTimeFormat);
									}
								} catch (Exception $e) {
								    $originalRow[$col] = $cellValue;
								}
							} else {
								$rowFailed = true;
								$rowInvalidCodeCols[] = $columnCode;
							}
							break;

						case 'TABLE':
							pr($this->getCellValue($sheet, $col++, $row));
							pr($this->getCellValue($sheet, $col++, $row));
							pr($questions[$col]);die;
							break;
					}

					if ($questions[$col]->field_type != 'CHECKBOX') {
						$obj = [
							'institution_site_survey_id' => $this->institutionSurvey->id,
							'survey_question_id' => $questions[$col]->id,
							$this->fieldTypes[$questions[$col]->field_type] => $cellValue,
						];
						$tableEntity = $this->InstitutionSurveyAnswers->newEntity();
						$this->InstitutionSurveyAnswers->patchEntity($tableEntity, $obj);
						$tempRow[$columnCode] = $tableEntity;
					}
				}

				if (!$rowFailed) {
					foreach ($tempRow as $entity) {
						$this->InstitutionSurveyAnswers->save($entity);
					}
					$totalImported++;
				} else {
					$rowCodeError = $this->getExcelLabel('Import', 'invalid_code').': ';
					$rowCodeError .= implode(', ', $rowInvalidCodeCols);
					$dataFailed[] = array(
						'row_number' => $row,
						'error' => $rowCodeError,
						'data' => $originalRow
					);
					$model->log('ImportBehavior @ line '.__LINE__, 'debug');
					$model->log($rowCodeError, 'debug');
					continue;
				}

			} // for ($row = 1; $row <= $highestRow; ++$row)

			if (!empty($dataFailed)) {
				$downloadFolder = $this->prepareDownload();
				$modelName = $this->alias();
				$excelFile = sprintf('%s_%s_%s_%s_%s.xlsx', 'Import', 'Institution', $modelName, 'Failed', time());
				$excelPath = $downloadFolder . DS . $excelFile;
				
				$writer = new \XLSXWriter();
				$newHeader = $header;
				$newHeader[] = $this->getExcelLabel('general', 'errors');
				$dataSheetName = $this->getExcelLabel('general', 'data');
				$writer->writeSheetRow($dataSheetName, array_values($newHeader));
				foreach($dataFailed as $record) {
					$record['data'][] = $record['error'];
					$writer->writeSheetRow($dataSheetName, array_values($record['data']->getArrayCopy()));
				}
				
				$codesData = $this->excelGetCodesData($this);
				foreach($codesData as $modelName => $modelArr) {
					foreach($modelArr as $row) {
						$writer->writeSheetRow($modelName, array_values($row));
					}
				}
				
				$writer->writeToFile($excelPath);
				$downloadUrl = $this->ControllerAction->url('downloadFailed');
				$downloadUrl[1] = $excelFile;
				$excelFile = $downloadUrl;
			} else {
				$excelFile = null;
			}

			$session = $model->controller->request->session();
			$completedData = [
				'uploadedName' => $uploadedName,
				'dataFailed' => $dataFailed,
				'totalImported' => $totalImported,
				'totalUpdated' => 0,
				'totalRows' => count($dataFailed) + $totalImported,
				'header' => $header,
				'excelFile' => $excelFile,
				'executionTime' => (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])
			];
			$session->write($this->sessionKey, $completedData);
			return $model->controller->redirect($this->ControllerAction->url('results'));
		};
	}

	public function results() {
		$session = $this->controller->request->session();
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
			if (!empty($completedData['excelFile'])) {
				$message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'failed');
				$this->Alert->error($message, ['type' => 'string', 'reset' => true]);
			} else {
				$message = '<i class="fa fa-check-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'success');
				$this->Alert->ok($message, ['type' => 'string', 'reset' => true]);
			}
			// define data as empty entity so that the view file will not throw an undefined notice
			$this->controller->set('data', $this->newEntity());
			$this->ControllerAction->renderView('/ControllerAction/view');
		} else {
			return $this->controller->redirect($this->ControllerAction->url('add'));
		}
	}
}
