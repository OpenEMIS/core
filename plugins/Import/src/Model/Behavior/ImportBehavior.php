<?php
namespace Import\Model\Behavior;

use DateTime;
use DateInterval;
use ArrayObject;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Network\Session;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Utility\Inflector;

/**
 * Depends on ControllerActionComponent
 */
class ImportBehavior extends Behavior {
	protected $_defaultConfig = [
		'plugin' => '',
		'model' => '',
	];
	protected $rootFolder = 'import';
	private $_fileTypesMap = [
		// 'csv' 	=> 'text/csv',
		'xls' 	=> 'application/vnd.ms-excel',
		'xlsx' 	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		// 'zip' 	=> 'application/zip',
	];

	public function initialize(array $config) {
		$fileTypes = $this->config('fileTypes');
		$allowableFileTypes = [];
		if ($fileTypes) {
			foreach ($fileTypes as $key=>$value) {
				if (array_key_exists($value, $this->_fileTypesMap)) {
					$allowableFileTypes[] = $value;
				}
			}
		} else {
			$allowableFileTypes = array_keys($this->_fileTypesMap);
		}
		$this->config('allowable_file_types', $allowableFileTypes);

		if (empty($this->config('plugin'))) {
			$exploded = explode('.', $this->_table->registryAlias());
			if (count($exploded)==2) {
				$this->config('plugin', $exploded[0]);
			}
		}
		if (empty($this->config('model'))) {
			$this->config('model', Inflector::pluralize($this->config('plugin')));
		}
	}
	

/******************************************************************************************************************
**
** Events
**
******************************************************************************************************************/
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
			'ControllerAction.Model.onGetFormButtons' => 'onGetFormButtons', // called to add/remove form buttons
			'ControllerAction.Model.beforeAction' => 'beforeAction',
			'ControllerAction.Model.add.onNew' => 'beforeSave',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'add':
				$toolbarButtons['import'] = $toolbarButtons['back'];
				$toolbarButtons['import']['url'][0] = 'template';
				$toolbarButtons['import']['attr']['title'] = __('Download Template');
				$toolbarButtons['import']['label'] = '<i class="fa kd-download"></i>';

				$toolbarButtons['back']['url']['action'] = 'index';
				unset($toolbarButtons['back']['url'][0]);
				break;

			case 'view':
				unset($toolbarButtons['back']['url']['action']);
				unset($toolbarButtons['back']['url'][0]);
				break;

		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$buttons[0]['name'] = '<i class="fa kd-import"></i> ' . __('Import');
	}

	public function beforeAction($event) {
		$this->sessionKey = $this->config('plugin').'.'.$this->config('plugin').'.Import.data';
		if (strtolower($this->_table->action) == 'index') {
			return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
		}

		$this->_table->ControllerAction->field('plugin', ['visible' => false]);
		$this->_table->ControllerAction->field('model', ['visible' => false]);
		$this->_table->ControllerAction->field('column_name', ['visible' => false]);
		$this->_table->ControllerAction->field('description', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_plugin', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_model', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_alias', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_column', ['visible' => false]);
		$this->_table->ControllerAction->field('foreign_key', ['visible' => false]);

		// $comment = '* File size should not be larger than ' . $this->config('size');
		$comment = '* Format Supported: ' . implode(', ', $this->config('allowable_file_types'));
		$comment .= '<br/>* Recommended Maximum Records: 3000';

		$this->_table->ControllerAction->field('select_file', [
			'type' => 'binary',
			'visible' => true,
			'attr' => ['label' => 'Select File To Import'],
			'null' => false,
			'comment' => $comment
		]);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$fileObj = $event->subject()->request->data[$this->_table->alias()]['select_file'];
		if ($fileObj['error'] == 0) {
			$options['validate'] = false;
		}
	}

	/**
	 * Actual Import business logics reside in this function
	 * @param  Event  $event  [description]
	 * @param  Entity $entity [description]
	 * @return [type]         [description]
	 */
	public function beforeSave(Event $event, Entity $entity) {
		$fileObj = $entity->select_file;
		if ($fileObj['error'] > 0) {
			$entity->errors('select_file', [__('File is required.')]);
		} else {
			$supportedFormats = $this->_fileTypesMap;
			$uploadedName = $fileObj['name'];
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$fileFormat = finfo_file($finfo, $fileObj['tmp_name']);
			finfo_close($finfo);

			if (!in_array($fileFormat, $supportedFormats)) {

				$entity->errors('select_file', [__('File format not supported.')]);					
			
			} else {

				$controller = $event->subject()->controller;
				$controller->loadComponent('PhpExcel');

				$header = $this->getHeader($this->_table);
				$columns = $this->getColumns($this->_table);
				$mapping = $this->getMapping($this->_table);
				$totalColumns = count($columns);

				$lookup = $this->getCodesByMapping($mapping);

				$uploaded = $fileObj['tmp_name'];

				$objPHPExcel = $controller->PhpExcel->loadWorksheet($uploaded);
				$worksheets = $objPHPExcel->getWorksheetIterator();
				$firstSheetOnly = false;

				$totalImported = 0;
				$totalUpdated = 0;
				$importedCodes = [];
				$dataFailed = [];
				foreach ($worksheets as $sheet) {
					if ($firstSheetOnly) {break;}

					$activeModel = TableRegistry::get($this->config('plugin').'.'.$this->config('model'));
					$highestRow = $sheet->getHighestRow();
					$totalRows = $highestRow - 1;
					for ($row = 1; $row <= $highestRow; ++$row) {
						$tempRow = [];
						$originalRow = [];
						$rowPass = true;
						$rowInvalidCodeCols = [];
						for ($col = 0; $col < $totalColumns; ++$col) {
							$cell = $sheet->getCellByColumnAndRow($col, $row);
							$originalValue = $cell->getValue();
							$cellValue = $originalValue;
							if(gettype($cellValue) == 'double' || gettype($cellValue) == 'boolean') {
								$cellValue = (string) $cellValue;
							}
							$excelMappingObj = $mapping[$col];
							$foreignKey = $excelMappingObj->foreign_key;
							$columnName = $columns[$col];
							$originalRow[$col] = $originalValue;
							$val = $cellValue;
							
							if ($row > 1) {
								if (!empty($val)) {
									if($columnName == 'date_opened' || $columnName == 'date_closed') {
										$val = date('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($val));
										$originalRow[$col] = $val;
									}
								}
								
								$translatedCol = $this->getExcelLabel($this->config('model'), $columnName);

								if ($foreignKey == 1) {
									if (!empty($cellValue)) {
										if (array_key_exists($cellValue, $lookup[$col])) {
											$val = $cellValue;
										} else {
											if($row !== 1) {
												$rowPass = false;
												$rowInvalidCodeCols[] = $translatedCol;
											}
										}
									} else {
										if($row !== 1) {
											$rowPass = false;
											$rowInvalidCodeCols[] = $translatedCol;
										}
									}
								} else if ($foreignKey == 2) {
									$excelLookupModel = TableRegistry::get($excelMappingObj->lookup_plugin . '.' . $excelMappingObj->lookup_model);
									if (!empty($cellValue)) {
										$recordId = $excelLookupModel->find()->where([$excelLookupModel->aliasField($excelMappingObj->lookup_column) => $cellValue])->first();
									} else {
										$recordId = '';
									}
									if (!empty($recordId)) {
										$val = $recordId->id;
									} else {
										if($row !== 1) {
											$rowPass = false;
											$rowInvalidCodeCols[] = $translatedCol;
										}
									}
								}
							}
							
							$tempRow[$columnName] = $val;
						}

						if (!$rowPass) {
							$rowCodeError = $this->getExcelLabel('Import', 'invalid_code');
							$colCount = 1;
							foreach($rowInvalidCodeCols as $codeCol){
								if ($colCount == 1) {
									$rowCodeError .= ': ' . $codeCol;
								} else {
									$rowCodeError .= ', ' . $codeCol;
								}
								$colCount ++;
							}
							
							$dataFailed[] = array(
								'row_number' => $row,
								'error' => $rowCodeError,
								'data' => $originalRow
							);
							continue;
						}
						
						if ($row === 1) {
							$header = $tempRow;
							$dataFailed = [];
							continue;
						}

						$tableEntity = $activeModel->newEntity($tempRow);
						if (empty($tableEntity->errors())) {
							if ($activeModel->save($tableEntity)) {
								$totalImported++;
								$importedCodes[] = $tableEntity->code;
							}
						} else {
							$validationErrors = $tableEntity->errors();
							if (array_key_exists('code', $validationErrors) && count($validationErrors) == 1) {
								if (!in_array($tempRow['code'], $importedCodes)) {
									$existingRecord = $activeModel->find()->where(['code' => $tempRow['code']])->first();
									$tempRow['id'] = $existingRecord->id;
									$activeModel->patchEntity($tableEntity, $tempRow, ['validate'=>false]);
									if ($activeModel->save($tableEntity)) {
										$totalUpdated++;
										$importedCodes[] = $tempRow['code'];
									} else {
										$dataFailed[] = [
											'row_number' => $row,
											'error' => $this->getExcelLabel('Import', 'saving_failed'),
											'data' => $originalRow
										];
									}
								} else {
									$dataFailed[] = [
										'row_number' => $row,
										'error' => $this->getExcelLabel('Import', 'duplicate_code'),
										'data' => $originalRow
									];
								}
							} else {
								$errorStr = $this->getExcelLabel('Import', 'validation_failed');
								$count = 1;
								foreach($validationErrors as $field => $arr) {
									$fieldName = $this->getExcelLabel($this->config('model'), $field);
									if (empty($fieldName)) {
										$fieldName = __($field);
									}
									if ($count === 1) {
										$errorStr .= ': ' . $fieldName;
									} else {
										$errorStr .= ', ' . $fieldName;
									}
									$count ++;
								}
								$dataFailed[] = [
									'row_number' => $row,
									'error' => $errorStr,
									'data' => $originalRow
								];
								$this->_table->log($validationErrors, 'debug');
							}
						}
					}

					$firstSheetOnly = true;
				}

				if(!empty($dataFailed)){
					$downloadFolder = $this->prepareDownload();
					$excelFile = sprintf('%s_%s_%s_%s_%s.xlsx', 
							$this->getExcelLabel('general', 'import'), 
							$this->getExcelLabel('general',  $this->config('plugin')), 
							$this->getExcelLabel('general',  $this->config('model')), 
							$this->getExcelLabel('general', 'failed'),
							time()
					);
					$excelPath = $downloadFolder . DS . $excelFile;

					$writer = new \XLSXWriter();
					$newHeader = $header;
					$newHeader[] = $this->getExcelLabel('general', 'errors');
					$dataSheetName = $this->getExcelLabel('general', 'data');
					$writer->writeSheetRow($dataSheetName, array_values($newHeader));
					foreach($dataFailed as $record) {
						$record['data'][] = $record['error'];
						$writer->writeSheetRow($dataSheetName, array_values($record['data']));
					}
					
					$codesData = $this->excelGetCodesData($this->_table);
					foreach($codesData as $modelName => $modelArr) {
						foreach($modelArr as $row) {
							$writer->writeSheetRow($modelName, array_values($row));
						}
					}
					
					$writer->writeToFile($excelPath);
					$this->_table->Alert->error('general.add.failed');

				} else {
					$excelFile = null;
					$this->_table->Alert->success('general.add.success');
				}

				$session = $this->_table->controller->request->session();
				$completedData = [
					'uploadedName' => $uploadedName,
					'totalRows' => $totalRows,
					'dataFailed' => $dataFailed,
					'totalImported' => $totalImported,
					'totalUpdated' => $totalUpdated,
					'header' => $header,
					'excelFile' => $excelFile,
				];
				$session->write($this->sessionKey, $completedData);
				return $this->_table->controller->redirect($this->_table->ControllerAction->url('view'));
			}

		}
	}

	

/******************************************************************************************************************
**
** Import methods
**
******************************************************************************************************************/
	protected function getMapping(Table $model) {
		$mapping = $model->find('all')
			->where([
				$model->aliasField('plugin') => $this->config('plugin'),
				$model->aliasField('model') => $this->config('model')
			])
			->order($model->aliasField('order'))
			->toArray();
		return $mapping;
	}
	
	protected function getHeader(Table $model) {
		$header = array();
		$mapping = $this->getMapping($model);
		
		foreach($mapping as $key => $value) {
			$column = $value->column_name;
			$label = $this->getExcelLabel($value->model, $column);
			// if($column == 'openemis_no') {
			// 	$headerCol = $this->getExcelLabel($model, sprintf('%s.%s', 'Import', $column));
			// } else if(!empty($label)) {
			// 	$headerCol = $label;
			// } else {
			// 	$headerCol = __(Inflector::humanize($column));
			// }
			if (empty($label)) {
				$headerCol = __(Inflector::humanize($column));
			} else {
				$headerCol = $label;
			}
			
			if(!empty($value->description)){
				$headerCol .= ' ' . $value->description;
			}
			
			$header[] = $headerCol;
		}

		return $header;
	}
	
	protected function getColumns(Table $model) {
		$columns = [];
		$mapping = $this->getMapping($model);
		
		foreach($mapping as $key => $value) {
			$column = $value->column_name;
			$columns[] = $column;
		}

		return $columns;
	}
	
	protected function getCodesByMapping($mapping) {
		$lookup = [];
		foreach ($mapping as $key => $obj) {
			$mappingRow = $obj;
			if ($mappingRow->foreign_key == 1) {
				$lookupPlugin = $mappingRow->lookup_plugin;
				$lookupModel = $mappingRow->lookup_model;
				$lookupAlias = $mappingRow->lookup_alias;
				$lookupColumn = $mappingRow->lookup_column;
				$lookupModelObj = TableRegistry::get($lookupAlias, ['className' => $lookupPlugin . '.' . $lookupModel]);
				$lookupValues = $lookupModelObj->getList()->toArray();
				$lookup[$key] = [];
				foreach ($lookupValues as $valId => $valObj) {
					$lookup[$key][$valId] = $valObj;
				}
			}
		}
		
		return $lookup;
	}

	protected function excelGetCodesData(Table $model) {
		$mapping = $model->find('all')
			->where([
				$model->aliasField('model') => $this->config('model'),
				$model->aliasField('foreign_key') . ' IN' => [1, 2]
			])
			->order($model->aliasField('order'))
			->toArray()
			;
		
		$data = [];
		foreach($mapping as $row) {
			$foreignKey = $row->foreign_key;
			$lookupPlugin = $row->lookup_plugin;
			$lookupModel = $row->lookup_model;
			$lookupAlias = $row->lookup_alias;
			$lookupColumn = $row->lookup_column;
			
			$translatedCol = $this->getExcelLabel($model, $lookupColumn);

			$sheetName = $this->getExcelLabel($row->model, $row->column_name);
			$data[$sheetName] = [];
			$modelData = [];
			if ($foreignKey == 1) {
				if (TableRegistry::exists($lookupAlias)) {
					$relatedModel = TableRegistry::get($lookupAlias);
				} else {
					$relatedModel = TableRegistry::get($lookupAlias, ['className' => $lookupPlugin . '\Model\Table\\' . $lookupModel.'Table']);
				}
				$modelData = $relatedModel->getList()->toArray();
				$data[$sheetName][] = [__('Name'), $translatedCol];
				if (!empty($modelData)) {
					foreach($modelData as $key=>$row) {
						$data[$sheetName][] = [$row, $key];
					}
				}
			} else if ($foreignKey == 2) {
				if ($lookupModel == 'Areas') {
					$order = [$lookupModel.'.area_level_id', $lookupModel.'.order'];
				} else if ($lookupModel == 'AreaAdministratives') {
					$order = [$lookupModel.'.area_administrative_level_id', $lookupModel.'.order'];
				} else {
					$order = [$lookupModel.'.order'];
				}
				$query = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
				$modelData = $query->find('all')
					->select(['name', $lookupColumn])
					->order($order)
					->toArray()
					;
				$data[$sheetName][] = [__('Name'), $translatedCol];
				if (!empty($modelData)) {
					foreach($modelData as $row) {
						$data[$sheetName][] = [$row->name, $row->$lookupColumn];
					}
				}
			}
		}
		
		return $data;
	}
	
	protected function prepareDownload(Table $model) {
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
	
	protected function performDownload(Table $model, $excelFile) {
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

	protected function getExcelLabel($module, $columnName) {
		$translatedCol = '';
		if ($module instanceof Table) {
			$module = $module->alias();
		}
		/**
		 * $language should provide the current selected locale language
		 */
		$language = '';
		$translatedCol = $this->_table->onGetFieldLabel(new Event($this), $module, $columnName, $language);

		return __($translatedCol);
	}
	

/******************************************************************************************************************
**
** Actions
**
******************************************************************************************************************/
	public function template() {
		$folder = $this->prepareDownload($this->_table);
		$excelFile = sprintf('%s_%s_%s_%s.xlsx', __('Import'), __($this->config('plugin')), __($this->config('model')), __('Template'));
		$excelPath = $folder . DS . $excelFile;

		$writer = new \XLSXWriter();
		
		$header = $this->getHeader($this->_table);
		$writer->writeSheetRow(__('Data'), array_values($header));
		
		$codesData = $this->excelGetCodesData($this->_table);
		foreach($codesData as $modelName => $modelArr) {
			foreach($modelArr as $row){
				$writer->writeSheetRow($modelName, array_values($row));
			}
		}
		
		$writer->writeToFile($excelPath);
		$this->performDownload($this->_table, $excelFile);
	}

	public function downloadFailed($excelFile) {
		$this->performDownload($this->_table, $excelFile);
	}

	public function view() {
		$session = $this->_table->controller->request->session();
		if ($session->check($this->sessionKey)) {
			$completedData = $session->read($this->sessionKey);
			$this->_table->ControllerAction->field('select_file', ['visible' => false]);
			$this->_table->ControllerAction->field('results', [
				'type' => 'element',
				'override' => true,
				'visible' => true,
				'element' => 'Import./results',
				'results' => $completedData
			]);
			$session->delete($this->sessionKey);
		} else {
			return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
		}
	}

}
