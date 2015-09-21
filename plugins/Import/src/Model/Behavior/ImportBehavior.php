<?php
namespace Import\Model\Behavior;

use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Network\Session;
use Cake\Datasource\Exception\RecordNotFoundException;

/**
 * Depends on ControllerActionComponent's function "getAssociatedBelongsToModel()"
 */
class ImportBehavior extends Behavior {
	private $_fileTypesMap = [
		// 'rtf' 	=> 'text/rtf',
		// 'txt' 	=> 'text/plain',
		// 'pdf' 	=> 'application/pdf',
		// 'ppt' 	=> 'application/vnd.ms-powerpoint',
		// 'pptx' 	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		// 'doc' 	=> 'application/msword',
		// 'docx' 	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		
		// 'csv' 	=> 'text/csv',
		'xls' 	=> 'application/vnd.ms-excel',
		'xlsx' 	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'zip' 	=> 'application/zip',
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
	}
	
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
			'ControllerAction.Model.onGetFormButtons' => 'onGetFormButtons', // called to add/remove form buttons
			'ControllerAction.Model.beforeAction' => 'beforeAction',
			'ControllerAction.Model.add.onNew' => 'beforeSave',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.afterPatch' => 'addAfterPatch',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$toolbarButtons['import'] = $toolbarButtons['back'];
		$toolbarButtons['import']['url'][0] = 'template';
		$toolbarButtons['import']['attr']['title'] = 'Download Template';
		$toolbarButtons['import']['label'] = '<i class="fa kd-download"></i>';

		$toolbarButtons['back']['url']['action'] = 'index';
		unset($toolbarButtons['back']['url'][0]);
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$buttons[0]['name'] = '<i class="fa kd-upload"></i> ' . __('Import');
		// $buttons[0]['attr']['value'] = 'new';
	}

	public function beforeAction($event) {
		if (strtolower($this->_table->action) == 'index') {
			return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
		}

		// $this->_table->ControllerAction->field('model', ['type' => 'hidden']);
		$this->_table->ControllerAction->field('model', ['visible' => false]);
		$this->_table->ControllerAction->field('column_name', ['visible' => false]);
		$this->_table->ControllerAction->field('description', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_model', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_column', ['visible' => false]);
		$this->_table->ControllerAction->field('foreign_key', ['visible' => false]);

		// $comment = '* File size should not be larger than ' . $this->config('size');
		$comment .= '* Format Supported: ' . implode(', ', $this->config('allowable_file_types'));
		$comment .= '<br/>* Recommended Maximum Records: 3000';

		$this->_table->ControllerAction->field('select_file', [
			'type' => 'binary',
			'visible' => true,
			'attr' => ['label' => 'Select File To Import'],
			'null' => false,
			'comment' => $comment
		]);
	}


	

	public function getMapping(Table $model) {
		$mapping = $model->find('all', array(
			'conditions' => array($model->aliasField('model') => $model->alias),
			'order' => array($model->aliasField('order'))
		));
		
		return $mapping;
	}
	
	public function getHeader(Table $model){
		$header = array();
		$mapping = $this->getMapping($model);
		
		foreach($mapping as $key => $value){
			$column = $value[$this->MappingModel->alias]['column_name'];
			$label = $this->getExcelLabel($model, sprintf('%s.%s', $model->alias, $column));
			if($column == 'openemis_no'){
				$headerCol = $this->getExcelLabel($model, sprintf('%s.%s', 'Import', $column));
			}else if(!empty($label)){
				$headerCol = $label;
			}else{
				$headerCol = __(Inflector::humanize($column));
			}
			
			if(!empty($value[$this->MappingModel->alias]['description'])){
				$headerCol .= ' ' . $value[$this->MappingModel->alias]['description'];
			}
			
			$header[] = $headerCol;
		}

		return $header;
	}
	
	public function getColumns(Table $model){
		$columns = array();
		$mapping = $this->getMapping($model);
		
		foreach($mapping as $key => $value){
			$column = $value[$this->MappingModel->alias]['column_name'];
			$columns[] = $column;
		}

		return $columns;
	}
	
	public function getCodesByMapping(Model $model, $mapping) {
		$lookup = array();
		//pr($mapping);
		foreach ($mapping as $key => $obj) {
			$mappingRow = $obj['ImportMapping'];
			if ($mappingRow['foreign_key'] == 1) {
				$lookupModel = $mappingRow['lookup_model'];
				$lookupColumn = $mappingRow['lookup_column'];
				$lookupModelObj = ClassRegistry::init($lookupModel);
				$lookupValues = $lookupModelObj->getList();
				//pr($lookupValues);
				$lookup[$key] = array();
				foreach ($lookupValues as $valId => $valObj) {
					$lookupColumnValue = $valObj[$lookupColumn];
					if(!empty($lookupColumnValue)){
						$lookup[$key][$lookupColumnValue] = $valObj['id'];
					}
				}
			}
		}
		
		return $lookup;
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$fileObj = $event->subject()->request->data[$this->_table->alias()]['select_file'];
		if ($fileObj['error'] == 0) {
			$options['validate'] = false;
		}
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// pr($data);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$fileObj = $entity->select_file;
		if ($fileObj['error'] > 0) {
			$entity->errors('select_file', [__('File is required.')]);
		} else {
					$supportedFormats = $this->_fileTypesMap;
					$uploadedName = $fileObj['name'];
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$fileFormat = finfo_file($finfo, $fileObj['tmp_name']);
					finfo_close($finfo);

					if(!in_array($fileFormat, $supportedFormats)){

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
						$dataFailed = array();
						foreach ($worksheets as $sheet) {
							if ($firstSheetOnly) {break;}

							$highestRow = $sheet->getHighestRow();
							$totalRows = $highestRow;
							//$highestColumn = $sheet->getHighestColumn();
							//$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
							for ($row = 1; $row <= $highestRow; ++$row) {
								$tempRow = array();
								$originalRow = array();
								$rowPass = true;
								$rowInvalidCodeCols = array();
								for ($col = 0; $col < $totalColumns; ++$col) {
									$cell = $sheet->getCellByColumnAndRow($col, $row);
									$originalValue = $cell->getValue();
									$cellValue = $originalValue;
									if(gettype($cellValue) == 'double' || gettype($cellValue) == 'boolean'){
										$cellValue = (string) $cellValue;
									}
									$excelMappingObj = $mapping[$col]['ImportMapping'];
									$foreignKey = $excelMappingObj['foreign_key'];
									$columnName = $columns[$col];
									$originalRow[$col] = $originalValue;
									$val = $cellValue;
									
									if($row > 1){
										if(!empty($val)){
											if($columnName == 'date_opened' || $columnName == 'date_closed'){
												$val = date('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($val));
												$originalRow[$col] = $val;
											}
										}
										
										$translatedCol = $this->{$model}->getExcelLabel($model.'.'.$columnName);
										if(empty($translatedCol)){
											$translatedCol = __($columnName);
										}

										if ($foreignKey == 1) {
											if(!empty($cellValue)){
												if (array_key_exists($cellValue, $lookup[$col])) {
													$val = $lookup[$col][$cellValue];
												} else {
													if($row !== 1 && $cellValue != ''){
														$rowPass = false;
														$rowInvalidCodeCols[] = $translatedCol;
													}
												}
											}
										} else if ($foreignKey == 2) {
											$excelLookupModel = TableRegistry::get($excelMappingObj['lookup_model']);
											$recordId = $excelLookupModel->field('id', array($excelMappingObj['lookup_column'] => $cellValue));
											if(!empty($recordId)){
												$val = $recordId;
											}else{
												if($row !== 1 && $cellValue != ''){
													$rowPass = false;
													$rowInvalidCodeCols[] = $translatedCol;
												}
											}
										}
									}
									
									$tempRow[$columnName] = $val;
								}

								if(!$rowPass){
									$rowCodeError = $this->{$model}->getExcelLabel('Import.invalid_code');
									$colCount = 1;
									foreach($rowInvalidCodeCols as $codeCol){
										if($colCount == 1){
											$rowCodeError .= ': ' . $codeCol;
										}else{
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
									$dataFailed = array();
									continue;
								}

								$this->{$model}->set($tempRow);
								$this->{$model}->validator()->remove('area_id_select');
								if ($this->{$model}->validates()) {
									$this->{$model}->create();
									if ($this->{$model}->save($tempRow)) {
										$totalImported++;
									} else {
										$totalUpdated++;
									}
								} else {
									$validationErrors = $this->{$model}->validationErrors;
									if(array_key_exists('code', $validationErrors) && count($validationErrors) == 1){
										$idExisting = $this->{$model}->field('id', array('code' => $tempRow['code']));
										$updateRow = $tempRow;
										$updateRow['id'] = $idExisting;
										if ($this->{$model}->save($updateRow)) {
											$totalUpdated++;
										}else{
											$dataFailed[] = array(
												'row_number' => $row,
												'error' => $this->{$model}->getExcelLabel('Import.saving_failed'),
												'data' => $originalRow
											);
										}
									}else{
										$errorStr = $this->{$model}->getExcelLabel('Import.validation_failed');
										$count = 1;
										foreach($validationErrors as $field => $arr){
											$fieldName = $this->{$model}->getExcelLabel($model.'.'.$field);
											if(empty($fieldName)){
												$fieldName = __($field);
											}

											if($count === 1){
												$errorStr .= ': ' . $fieldName;
											}else{
												$errorStr .= ', ' . $fieldName;
											}
											$count ++;
										}
										
										$dataFailed[] = array(
											'row_number' => $row,
											'error' => $errorStr,
											'data' => $originalRow
										);
										$this->log($this->{$model}->validationErrors, 'debug');
									}
								}
							}

							$firstSheetOnly = true;
						}
						
						if(!empty($dataFailed)){
							$downloadFolder = $this->{$model}->prepareDownload();
							$excelFile = sprintf('%s_%s_%s_%s.xlsx', 
									$this->{$model}->getExcelLabel('general.import'), 
									$this->{$model}->getExcelLabel('general.'.  $this->{$model}->alias), 
									$this->{$model}->getExcelLabel('general.failed'),
									time()
							);
							$excelPath = $downloadFolder . DS . $excelFile;

							$writer = new XLSXWriter();
							$newHeader = $header;
							$newHeader[] = $this->{$model}->getExcelLabel('general.errors');
							$dataSheetName = $this->{$model}->getExcelLabel('general.data');
							$writer->writeSheetRow($dataSheetName, array_values($newHeader));
							foreach($dataFailed as $record){
								$record['data'][] = $record['error'];
								$writer->writeSheetRow($dataSheetName, array_values($record['data']));
							}
							
							$codesData = $this->{$model}->excelGetCodesData();
							foreach($codesData as $modelName => $modelArr){
								foreach($modelArr as $row){
									$writer->writeSheetRow($modelName, array_values($row));
								}
							}
							
							$writer->writeToFile($excelPath);
						}else{
							$excelFile = null;
						}

						$this->set(compact('uploadedName', 'totalRows', 'dataFailed', 'totalImported', 'totalUpdated', 'header', 'excelFile'));
					}


		}
	}

	public function template() {
		pr('template');die;
	}


		
}
