<?php
namespace Import\Model\Behavior;

use DateTime;
use DateInterval;
use ArrayObject;
use PHPExcel_Worksheet;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;

/**
 * ImportBehavior is to be used with import_mapping table.
 *
 * Depends on ControllerActionComponent.
 * Uses EventTrait.
 * Functions that require ControllerActionComponent events, CakePHP events,
 * and are controller actions functions, resides here.
 * Contains logics to import records through excel sheet.
 * This behavior could not be attached to a table file that loads ExportBehavior as well. Currently, there is a conflict
 * since both ImportBehavior and ExcelBehavior uses EventTrait.
 *
 * 
 * Usage:
 * - create a table file in a plugin and define its table as `import_mapping`.
 * - in the table file initialize function, add this behavior using one of the following ways
 * 
 * #1
 * `
 * $this->addBehavior('Import.Import');
 * `
 * - ImportBehavior will define the caller's plugin using `$this->_table->registryAlias()`
 * and extract the first word
 * - Caller's model will be defined by pluralizing the plugin name
 *
 * #2
 * `
 * $this->addBehavior('Import.Import', ['plugin'=>'Staff', 'model'=>'Staff']);
 * `
 * - ImportBehavior will acknowledge the plugin name and model name as defined above
 *
 * 
 * Default Configuration:
 * - Maximum size of uploaded is set to 512KB as PhpExcel class will not be able to handle files which are too large due to
 * php.ini setting on memory_limit. the size of 512KB will eventually becomes close to tripled when the file was 
 * passed to PhpExcel to read it.
 * 
 * @author  hanafi <hanafi.ahmat@kordit.com>
 */
class ImportBehavior extends Behavior {
	use EventTrait;

	const FIELD_OPTION = 1;
	const DIRECT_TABLE = 2;

	const RECORD_HEADER = 1;
	const FIRST_RECORD = 2;

	protected $labels = [];
	protected $directTables = [];
	
	protected $_defaultConfig = [
		'plugin' => '',
		'model' => '',
		'max_rows' => 2000,
		'max_size' => 524288
	];
	protected $rootFolder = 'import';
	private $_fileTypesMap = [
		// 'csv' 	=> 'text/plain',
		// 'csv' 	=> 'text/csv',
		'xls' 	=> ['application/vnd.ms-excel', 'application/vnd.ms-office'],
		// Use for openoffice .xls format
		'xlsx' 	=> ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
		'ods' 	=> ['application/vnd.oasis.opendocument.spreadsheet'],
		'zip' 	=> ['application/zip']
	];
	public $institutionId = false;

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

		// testing using file size limit set in php.ini settings
		// $this->config('max_size', $this->system_memory_limit());
		// $this->config('max_rows', 50000);
		//

		$plugin = $this->config('plugin');
		if (empty($plugin)) {
			$exploded = explode('.', $this->_table->registryAlias());
			if (count($exploded)==2) {
				$this->config('plugin', $exploded[0]);
			}
		}
		$plugin = $this->config('plugin');
		$model = $this->config('model');
		if (empty($model)) {
			$this->config('model', Inflector::pluralize($plugin));
		}

	    $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
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
			'ControllerAction.Model.onGetFormButtons' => 'onGetFormButtons',
			'ControllerAction.Model.beforeAction' => 'beforeAction',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.beforeSave' => 'addBeforeSave',
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
				if ($buttons['index']['url']['action']=='ImportInstitutionSurveys') {
					$toolbarButtons['import']['url'][1] = $buttons['add']['url'][1];
				}
				
				break;
		}
		if ($this->institutionId && $toolbarButtons['back']['url']['plugin']=='Institution') {
			$back = str_replace('Import', '', $this->_table->alias());
			if (!array_key_exists($back, $this->_table->ControllerAction->models)) {
				$back = str_replace('Institution', '', $back);
			}
			$toolbarButtons['back']['url']['action'] = $back;
		} else {
			$toolbarButtons['back']['url']['action'] = 'index';
		}
		unset($toolbarButtons['back']['url'][0]);
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$buttons[0]['name'] = '<i class="fa kd-import"></i> ' . __('Import');
	}

	public function beforeAction($event) {
		$session = $this->_table->controller->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$this->institutionId = $session->read('Institution.Institutions.id');
		}
		$this->sessionKey = $this->config('plugin').'.'.$this->config('model').'.Import.data';
		if (strtolower($this->_table->action) == 'index') {
			$event->stopPropagation();
			return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
		}

		$this->_table->ControllerAction->field('plugin', ['visible' => false]);
		$this->_table->ControllerAction->field('model', ['visible' => false]);
		$this->_table->ControllerAction->field('column_name', ['visible' => false]);
		$this->_table->ControllerAction->field('description', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_plugin', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_model', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_column', ['visible' => false]);
		$this->_table->ControllerAction->field('foreign_key', ['visible' => false]);

		$comment = __('* Format Supported: ' . implode(', ', $this->config('allowable_file_types')));
		$comment .= '<br/>';
		$comment .= __('* Recommended Maximum File Size: ' . $this->bytesToReadableFormat($this->config('max_size')));
		$comment .= '<br/>';
		$comment .= __('* Recommended Maximum Records: ' . $this->config('max_rows'));

		$this->_table->ControllerAction->field('select_file', [
			'type' => 'binary',
			'visible' => true,
			'attr' => ['label' => __('Select File To Import')],
			'null' => false,
			'comment' => $comment
		]);
	}

	/**
	 * addBeforePatch turns off the validation when patching entity with post data, and check the uploaded file size. 
	 * @param Event       $event   [description]
	 * @param Entity      $entity  [description]
	 * @param ArrayObject $data    [description]
	 * @param ArrayObject $options [description]
	 * 
	 * Refer to phpFileUploadErrors below for the list of file upload errors defination.
	 */
	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$options['validate'] = false;
		if (!array_key_exists($this->_table->alias(), $data)) {
			$options['validate'] = true;
			return $event->response;
		}
		if (!array_key_exists('select_file', $data[$this->_table->alias()])) {
			$options['validate'] = true;
			return $event->response;
		}
		if (empty($data[$this->_table->alias()]['select_file'])) {
			$options['validate'] = true;
			return $event->response;
		}
		if ($data[$this->_table->alias()]['select_file']['error']==4) {
			$options['validate'] = true;
			return $event->response;
		}
		if ($data[$this->_table->alias()]['select_file']['error']>0) {
			$options['validate'] = true;
			$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max')], true);
			return $event->response;
		}
		if ($event->subject()->request->env('CONTENT_LENGTH') >= $this->config('max_size')) {
			$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max')], true);
			$options['validate'] = true;
		} 
		if ($event->subject()->request->env('CONTENT_LENGTH') >= $this->file_upload_max_size()) {
			$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max')], true);
			$options['validate'] = true;
		} 
		if ($event->subject()->request->env('CONTENT_LENGTH') >= $this->post_upload_max_size()) {
			$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max')], true);
			$options['validate'] = true;
		}

		$fileObj = $data[$this->_table->alias()]['select_file'];
		$supportedFormats = $this->_fileTypesMap;

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$fileFormat = finfo_file($finfo, $fileObj['tmp_name']);
		finfo_close($finfo);
		$formatFound = false;
		foreach ($supportedFormats as $eachformat) {
			if (in_array($fileFormat, $eachformat)) {
				$formatFound = true;
			} 
		}
		if (!$formatFound) {
			if (!empty($fileFormat)) {
				$entity->errors('select_file', [$this->getExcelLabel('Import', 'not_supported_format')], true);
				$options['validate'] = true;
			}
		}				

		$fileExt = $fileObj['name'];
		$fileExt = explode('.', $fileExt);
		$fileExt = $fileExt[count($fileExt)-1];
		if (!array_key_exists($fileExt, $supportedFormats)) {
			if (!empty($fileFormat)) {
				$entity->errors('select_file', [$this->getExcelLabel('Import', 'not_supported_format')], true);
				$options['validate'] = true;
			}
		} 

	}

	/**
	 * Actual Import business logics reside in this function
	 * @param  Event  		$event  Event object
	 * @param  Entity 		$entity Entity object containing the uploaded file parameters 
	 * @param  ArrayObject  $data  	Event object
	 * @return Response       		Response object
	 */
	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		/**
		 * currently, extending the max execution time for individual scripts from the default of 30 seconds to 60 seconds
		 * to avoid server timed out issue.
		 * to be reviewed...
		 */
		ini_set('max_execution_time', 60);
		/**
		 */

		return function ($model, $entity) {
			$errors = $entity->errors();
			if (!empty($errors)) {
				return false;
			}

			$systemDateFormat = TableRegistry::get('ConfigItems')->value('date_format');

			$controller = $model->controller;
			$controller->loadComponent('PhpExcel');

			$mapping = $this->getMapping();
			$header = $this->getHeader($mapping);
			$columns = $this->getColumns($mapping);
			$totalColumns = count($columns);
			$lookup = $this->getCodesByMapping($mapping);

			$fileObj = $entity->select_file;		
			$uploadedName = $fileObj['name'];
			$uploaded = $fileObj['tmp_name'];
			$objPHPExcel = $controller->PhpExcel->loadWorksheet($uploaded);
			$worksheets = $objPHPExcel->getWorksheetIterator();

			$totalImported = 0;
			$totalUpdated = 0;
			$importedUniqueCodes = new ArrayObject;
			$dataFailed = [];

			$activeModel = TableRegistry::get($this->config('plugin').'.'.$this->config('model'));

			$maxRows = $this->config('max_rows');
			$maxRows = $maxRows + 2;
			foreach ($worksheets as $sheet) {
				$highestRow = $sheet->getHighestRow();
				if ($highestRow > $maxRows) {
					$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max_rows')], true);
					return false;
				}

				for ($row = 1; $row <= $highestRow; ++$row) {
					if ($row == self::RECORD_HEADER) { // skip header but check if the uploaded template is correct
						if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
							$entity->errors('select_file', [$this->getExcelLabel('Import', 'wrong_template')], true);
							return false;
						}
						continue;
					}
					if ($row == $highestRow) { // if $row == $highestRow, check if the row cells are really empty, if yes then end the loop
						if ($this->checkRowCells($sheet, $totalColumns, $row) === false) {
							break;
						}
					}
					
					// check for unique record
					$tempRow = new ArrayObject;
					$tempRow['duplicates'] = false;
					$params = [$sheet, $row, $columns, $tempRow, $importedUniqueCodes];
					$this->dispatchEvent($this->_table, $this->eventKey('onImportCheckUnique'), 'onImportCheckUnique', $params);
			
					// pr($tempRow);die;
					
					// for each columns
					$references = [
						'sheet'=>$sheet, 
						'mapping'=>$mapping, 
						'columns'=>$columns, 
						'lookup'=>$lookup,
						'totalColumns'=>$totalColumns, 
						'row'=>$row, 
						'activeModel'=>$activeModel,
						'systemDateFormat'=>$systemDateFormat,
					];
					$rowInvalidCodeCols = new ArrayObject;
					$originalRow = new ArrayObject;
					$rowPass = $this->_extractRecord($references, $tempRow, $originalRow, $rowInvalidCodeCols);

					$tempRow = $tempRow->getArrayCopy();
					// $tempRow['entity'] must exists!!! should be set in individual model's onImportCheckUnique function
					if (!isset($tempRow['entity'])) {
						$tableEntity = $activeModel->newEntity();
					} else {
						$tableEntity = $tempRow['entity'];
						unset($tempRow['entity']);
					}
					$duplicates = $tempRow['duplicates'];
					unset($tempRow['duplicates']);
					$activeModel->patchEntity($tableEntity, $tempRow);
					$errors = $tableEntity->errors();
					// pr($errors);die;
					if (!$rowPass || $duplicates || $errors) { // row contains error or record is a duplicate based on unique key(s)

						$rowCodeError = '';
						if (is_bool($duplicates) && $duplicates) {
							$rowCodeError .= $this->getExcelLabel('Import', 'duplicate_unique_key');
						} else if (!empty($duplicates)) {
							$rowCodeError .= $duplicates;
						}
						if (!$rowPass) {
							if ($rowCodeError!='') {
								$rowCodeError .= '
								';
							}
							$rowCodeError .= $this->getExcelLabel('Import', 'invalid_code').': ';
							$rowCodeError .= implode(', ', $rowInvalidCodeCols->getArrayCopy());
						}
						if (!empty($errors)) {
							$rowCodeError = (!empty($rowCodeError)) ? $rowCodeError.'; ' : '';
							$rowCodeError .= $this->getExcelLabel('Import', 'validation_failed').': ';
							$count = 1;
							foreach($errors as $field => $arr) {
								$fieldName = $this->getExcelLabel($activeModel->registryAlias(), $field);
								if ($count === 1) {
									$rowCodeError .= $fieldName . ' => ' . $arr[key($arr)];
								} else {
									$rowCodeError .= ', ' . $fieldName . ' => ' . $arr[key($arr)];
								}
								$count ++;
							}
						}
						$dataFailed[] = array(
							'row_number' => $row,
							'error' => $rowCodeError,
							'data' => $originalRow
						);

						$model->log('ImportBehavior @ line '.__LINE__, 'debug');
						$model->log($rowCodeError, 'debug');

						continue;
					}

					$isNew = $tableEntity->isNew();
					if ($activeModel->save($tableEntity)) {
						if ($isNew) {
							$totalImported++;
						} else {
							$totalUpdated++;
						}

						// update importedUniqueCodes either a single key or composite primary keys
						$this->dispatchEvent($this->_table, $this->eventKey('onImportUpdateUniqueKeys'), 'onImportUpdateUniqueKeys', [$importedUniqueCodes, $tableEntity]);
					
					}
	
					// $model->log('ImportBehavior: '.$row.' records imported', 'info');

				} // for ($row = 1; $row <= $highestRow; ++$row)

				break; // only process first sheet
			} // foreach ($worksheets as $sheet)

			if (!empty($dataFailed)) {
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
					// pr($record);die;
					$writer->writeSheetRow($dataSheetName, array_values($record['data']->getArrayCopy()));
				}
				
				$codesData = $this->excelGetCodesData($this->_table);
				foreach($codesData as $modelName => $modelArr) {
					foreach($modelArr as $row) {
						$writer->writeSheetRow($modelName, array_values($row));
					}
				}
				
				$writer->writeToFile($excelPath);
				$downloadUrl = $this->_table->ControllerAction->url('downloadFailed');
				$downloadUrl[] = $excelFile;
				$excelFile = $downloadUrl;
			} else {
				$excelFile = null;
			}

			$session = $model->controller->request->session();
			$completedData = [
				'uploadedName' => $uploadedName,
				'dataFailed' => $dataFailed,
				'totalImported' => $totalImported,
				'totalUpdated' => $totalUpdated,
				'totalRows' => count($dataFailed) + $totalImported + $totalUpdated,
				'header' => $header,
				'excelFile' => $excelFile,
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
	public function template() {
		$folder = $this->prepareDownload();
		$modelName = $this->config('model');
		$modelName = str_replace(' ', '_', Inflector::humanize(Inflector::tableize($modelName)));
		$excelFile = sprintf('%s_%s_%s_%s.xlsx', 'Import', $this->config('plugin'), $modelName, 'Template');

		$excelPath = $folder . DS . $excelFile;

		$writer = new \XLSXWriter();
		
		$mapping = $this->getMapping();
		$header = $this->getHeader($mapping);
		$writer->writeSheetRow(__('Data'), array_values($header));
		
		$codesData = $this->excelGetCodesData($this->_table);
		foreach($codesData as $modelName => $modelArr) {
			foreach($modelArr as $row) {
				$writer->writeSheetRow($modelName, array_values($row));
			}
		}
		
		$writer->writeToFile($excelPath);
		$this->performDownload($excelFile);
		die;
	}

	public function downloadFailed($excelFile) {
		$this->performDownload($excelFile);
		die;
	}

	public function results() {
		$session = $this->_table->controller->request->session();
		if ($session->check($this->sessionKey)) {
			$completedData = $session->read($this->sessionKey);
			$this->_table->ControllerAction->field('select_file', ['visible' => false]);
			$this->_table->ControllerAction->field('results', [
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
				$this->_table->Alert->error($message, ['type' => 'string', 'reset' => true]);
			} else {
				$message = '<i class="fa fa-check-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'success');
				$this->_table->Alert->ok($message, ['type' => 'string', 'reset' => true]);
			}
			// define data as empty entity so that the view file will not throw an undefined notice
			$this->_table->controller->set('data', $this->_table->newEntity());
			$this->_table->ControllerAction->renderView('/ControllerAction/view');
		} else {
			return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
		}
	}


/******************************************************************************************************************
**
** Import Functions
**
******************************************************************************************************************/
	/**
	 * Check if all the columns in the row is not empty
	 * @param  WorkSheet $sheet      The worksheet object
	 * @param  integer $totalColumns Total number of columns to be checked
	 * @param  integer $row          Row number
	 * @return boolean               the result to be return as true or false
	 */
	public function checkRowCells($sheet, $totalColumns, $row) {
		$cellsState = [];
		for ($col=0; $col < $totalColumns; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			$value = $cell->getValue();
			if (empty($value)) {
				$cellsState[] = false;
			} else {
				$cellsState[] = true;
			}
		}
		return in_array(true, $cellsState);
	}
	
	/**
	 * Check if the uploaded file is the correct template by comparing the headers extracted from mapping table
	 * and first row of the uploaded file record
	 * @param  array  		$header      	The headers extracted from mapping table according to active model
	 * @param  WorkSheet 	$sheet      	The worksheet object
	 * @param  integer 		$totalColumns 	Total number of columns to be checked
	 * @param  integer 		$row          	Row number
	 * @return boolean               		the result to be return as true or false
	 */
	public function isCorrectTemplate($header, $sheet, $totalColumns, $row) {
		$cellsValue = [];
		for ($col=0; $col < $totalColumns; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			$cellsValue[] = $cell->getValue();
		}
		return $header === $cellsValue;
	}
	
	public function getMapping() {
		$model = $this->_table;
		$mapping = $model->find('all')
			->where([
				$model->aliasField('model') => $this->config('model')
			])
			->order($model->aliasField('order'))
			->toArray();
		return $mapping;
	}
	
	protected function getHeader($mapping=[]) {
		$model = $this->_table;
		if (empty($mapping)) {
			$mapping = $this->getMapping($model);
		}
		
		$header = [];
		foreach ($mapping as $key => $value) {
			$column = $value->column_name;
			$label = $this->getExcelLabel($value->model, $column);
			if (empty($label)) {
				$headerCol = __(Inflector::humanize($column));
			} else {
				$headerCol = $label;
			}
			
			if (!empty($value->description)) {
				$headerCol .= ' ' . __($value->description);
			}
			
			$header[] = $headerCol;
		}

		return $header;
	}
	
	protected function getColumns($mapping=[]) {
		$columns = [];
		if (empty($mapping)) {
			$mapping = $this->getMapping($model);
		}
		
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
			if ($mappingRow->foreign_key == self::FIELD_OPTION) {
				$lookupPlugin = $mappingRow->lookup_plugin;
				$lookupModel = $mappingRow->lookup_model;
				$lookupColumn = $mappingRow->lookup_column;
				$lookupModelObj = TableRegistry::get($lookupModel, ['className' => $lookupPlugin . '.' . $lookupModel]);
				$lookupValues = $lookupModelObj->getList()->toArray();
				$lookup[$key] = [];
				foreach ($lookupValues as $valId => $valObj) {
					$lookup[$key][$valId] = $valObj;
				}
			}
		}
		
		return $lookup;
	}

	public function excelGetCodesData(Table $model) {
		$mapping = $model->find('all')
			->where([
				$model->aliasField('model') => $this->config('model'),
				$model->aliasField('foreign_key') . ' IN' => [self::FIELD_OPTION, self::DIRECT_TABLE]
			])
			->order($model->aliasField('order'))
			->toArray()
			;
		
		$data = new ArrayObject;
		foreach($mapping as $row) {
			$foreignKey = $row->foreign_key;
			$lookupPlugin = $row->lookup_plugin;
			$lookupModel = $row->lookup_model;
			$lookupColumn = $row->lookup_column;
			
			$translatedCol = $this->getExcelLabel($model, $lookupColumn);

			$sheetName = $this->getExcelLabel($row->model, $row->column_name);
			$data[$sheetName] = [];
			$modelData = [];
			if ($foreignKey == self::FIELD_OPTION) {
				if (TableRegistry::exists($lookupModel)) {
					$relatedModel = TableRegistry::get($lookupModel);
				} else {
					$relatedModel = TableRegistry::get($lookupModel, ['className' => $lookupPlugin . '\Model\Table\\' . $lookupModel.'Table']);
				}
				$modelData = $relatedModel->getList()->toArray();
				$data[$sheetName][] = [__('Name'), $translatedCol];
				if (!empty($modelData)) {
					foreach($modelData as $key=>$row) {
						$data[$sheetName][] = [$row, $key];
					}
				}
			} else if ($foreignKey == self::DIRECT_TABLE) {

				$params = [$lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, $data];
				$this->dispatchEvent($this->_table, $this->eventKey('onImportPopulate'.$lookupModel.'Data'), 'onImportPopulate'.$lookupModel.'Data', $params);

			}
		}

		return $data;
	}
	
	public function prepareDownload() {
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
	
	public function performDownload($excelFile) {
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

	public function getExcelLabel($module, $columnName) {
		$translatedCol = '';
		if ($module instanceof Table) {
			$module = $module->alias();
		}

		if (!empty($this->labels) && isset($this->labels[$module]) && isset($this->labels[$module][$columnName])) {
			$translatedCol = $this->labels[$module][$columnName];
		} else {
			if ($module=='Import') {
				$translatedCol = $this->_table->getMessage($module.'.'.$columnName);
			} else {
				/**
				 * $language should provide the current selected locale language
				 */
				$language = '';
				$translatedCol = $this->_table->onGetFieldLabel(new Event($this), $module, $columnName, $language);
				if (empty($translatedCol)) {
					$translatedCol = Inflector::humanize(substr($columnName, 0, strpos($columnName, '_id')));
				}
			}
			// saves label in runtime array to avoid multiple calls to the db or cache
			$this->labels[$module][$columnName] = $translatedCol;
		}

		return __($translatedCol);
	}

	/**
	 * Extract the values in every columns
	 * @param  array      	$references         the variables/arrays in this array are for references
	 * @param  ArrayObject 	$tempRow            for holding converted values extracted from the excel sheet on a per row basis
	 * @param  ArrayObject 	$originalRow        for holding the original value extracted from the excel sheet on a per row basis 
	 * @param  ArrayObject 	$rowInvalidCodeCols for holding error messages found on option field columns 
	 * @return boolean                          returns whether the row being checked pass option field columns check
	 */
	protected function _extractRecord($references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		// $references = [$sheet, $mapping, $columns, $lookup, $totalColumns, $row, $activeModel, $systemDateFormat];
		$sheet = $references['sheet'];
		$mapping = $references['mapping'];
		$columns = $references['columns'];
		$lookup = $references['lookup'];
		$totalColumns = $references['totalColumns'];
		$row = $references['row'];
		$activeModel = $references['activeModel'];
		$systemDateFormat = $references['systemDateFormat'];
		$references = null;

		$rowPass = true;
		for ($col = 0; $col < $totalColumns; ++$col) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			$originalValue = $cell->getValue();

			$cellValue = $originalValue;
			// need to understand this check
			// @hanafi - this might be for type casting a double or boolean value to a string to avoid data loss when assigning
			// them to $val. Example: the value of latitude, "1.05647" might become "1" if not casted as a string type.
			if(gettype($cellValue) == 'double' || gettype($cellValue) == 'boolean') {
				$cellValue = (string) $cellValue;
			}
			// need to understand the above check

			$excelMappingObj = $mapping[$col];
			$foreignKey = $excelMappingObj->foreign_key;
			$columnName = $columns[$col];
			$originalRow[$col] = $originalValue;
			$val = $cellValue;
			
			// skip a record column which has value defined earlier before this function is called
			// example; openemis_no
			if (!empty($tempRow[$columnName])) {
				continue;
			}
			if (!empty($val)) {
				if($activeModel->schema()->column($columnName)['type'] == 'date') {// checking the main table schema data type
					// if date value is not numeric, let it fail validation since using PHPExcel_Shared_Date::ExcelToPHP($val)
					// will actually converts the non-numeric value to today's date
					if (is_numeric($val)) {
						$val = date('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($val));
						// converts val to Time object so that this field will pass 'validDate' check since
						// different model has different date format checking. Example; user->date_of_birth is using dmY while others using Y-m-d,
						// so it is best to convert the date here instead of adjusting individual model's date validation format
						try {
							$val = new Time($val);
							$originalRow[$col] = $val->format($systemDateFormat);
						} catch (Exception $e) {
						    $originalRow[$col] = $val;
						}
					}
				}
			}
			$translatedCol = $this->getExcelLabel($activeModel->alias(), $columnName);
			if ($foreignKey == self::FIELD_OPTION) {
				if (!empty($cellValue)) {
					if (array_key_exists($cellValue, $lookup[$col])) {
						$val = $cellValue;
					} else { // if the cell value not found in lookup
						$rowPass = false;
						$rowInvalidCodeCols[] = $translatedCol;
					}
				} else { // if cell is empty
					$rowPass = false;
					$rowInvalidCodeCols[] = $translatedCol;
				}
			} else if ($foreignKey == self::DIRECT_TABLE) {
				$registryAlias = $excelMappingObj->lookup_plugin . '.' . $excelMappingObj->lookup_model;
				if (!empty($this->directTables) && isset($this->directTables[$registryAlias])) {
					$excelLookupModel = $this->directTables[$registryAlias];
				} else {
					$excelLookupModel = TableRegistry::get($registryAlias);
					$this->directTables[$registryAlias] = $excelLookupModel;
				}
				if (!empty($cellValue)) {
					$recordId = $excelLookupModel->find()->where([$excelLookupModel->aliasField($excelMappingObj->lookup_column) => $cellValue])->first();
				} else {
					$recordId = '';
				}
				if (!empty($recordId)) {
					$val = $recordId->id;
				} else {
					$rowPass = false;
					$rowInvalidCodeCols[] = $translatedCol;
				}
			}
			$tempRow[$columnName] = $val;
		}
		if ($rowPass) {
			$rowPass = $this->dispatchEvent($this->_table, $this->eventKey('onImportModelSpecificValidation'), 'onImportModelSpecificValidation', [$references, $tempRow, $originalRow, $rowInvalidCodeCols]);
		}
		return $rowPass;
	}


/******************************************************************************************************************
**
** Miscelleneous Functions
**
******************************************************************************************************************/
	public function getAcademicPeriodByStartDate($date) {
		if ($date instanceof DateTime) {
			$date = $date->format('Y-m-d');
		}
		return $this->AcademicPeriods
					->find()
					->where([
						"date(start_date) <= date '".$date."'",
						"date(end_date) >= date '".$date."'",
						'parent_id <> 0'
					])
					->first()
					;
	}

	private function eventKey($key) {
		return 'Model.import.' . $key;
	}

/**
 * @link("PHP get actual maximum upload size", http://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size)
 */
	// Returns a file size limit in bytes based on the PHP upload_max_filesize
	// and post_max_size
	protected function file_upload_max_size() {
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$max_size = $this->parse_size(ini_get('post_max_size'));

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parse_size(ini_get('upload_max_filesize'));

			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size;
	}

	protected function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}
/**
 * 
 */

	protected function post_upload_max_size() {
		return $this->parse_size(ini_get('post_max_size'));
	}

	protected function system_memory_limit() {
		return $this->parse_size(ini_get('memory_limit'));
	}

/**
 * http://codereview.stackexchange.com/questions/6476/quick-way-to-convert-bytes-to-a-more-readable-format
 * @param  [type] $bytes [description]
 * @return [type]        [description]
 */
	protected function bytesToReadableFormat($bytes) {
		$KILO = 1024;
		$MEGA = $KILO * 1024;
		$GIGA = $MEGA * 1024;
		$TERA = $GIGA * 1024;

		if ($bytes < $KILO) {
	        return $bytes . 'B';
	    }
	    if ($bytes < $MEGA) {
	        return round($bytes / $KILO, 2) . 'KB';
	    }
	    if ($bytes < $GIGA) {
	        return round($bytes / $MEGA, 2) . 'MB';
	    }
	    if ($bytes < $TERA) {
	        return round($bytes / $GIGA, 2) . 'GB';
	    }
	    return round($bytes / $TERA, 2) . 'TB';
	}

/**
 * @link("Upload errors defination", http://php.net/manual/en/features.file-upload.errors.php#115746)
 * For reference.
 */
	protected $phpFileUploadErrors = array(
	    0 => 'There is no error, the file uploaded with success',
	    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
	    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
	    3 => 'The uploaded file was only partially uploaded',
	    4 => 'No file was uploaded',
	    6 => 'Missing a temporary folder',
	    7 => 'Failed to write file to disk.',
	    8 => 'A PHP extension stopped the file upload.',
	);

}
