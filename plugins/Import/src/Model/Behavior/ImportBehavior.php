<?php
namespace Import\Model\Behavior;

use DateTime;
use DateInterval;
use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;
use Cake\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Datasource\Exception\RecordNotFoundException;
use ControllerAction\Model\Traits\EventTrait;
use Import\Model\Traits\ImportExcelTrait;

/**
 * ImportBehavior is to be used with import_mapping table.
 *
 * Depends on ControllerActionComponent.
 * Uses ImportExcelTrait.
 * Functions that require ControllerActionComponent events, CakePHP events,
 * and are controller actions functions, resides here.
 * Contains logics to import records through excel sheet.
 * Supported models:
 * #1 Institutions
 * #2 Students
 * #3 Staff 
 * Refer to @link(PHPOE-2083, https://kordit.atlassian.net/browse/PHPOE-2083) for the latest import_mapping table schema
 *
 * This behavior could not be attached to a table file that loads ExportBehavior as well. Currently, there is a conflict
 * since both ImportBehavior and ExcelBehavior uses ImportExcelTrait.
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
	use ImportExcelTrait;

	const FIELD_OPTION = 1;
	const DIRECT_TABLE = 2;

	const RECORD_HEADER = 1;
	const FIRST_RECORD = 2;

	protected $_defaultConfig = [
		'plugin' => '',
		'model' => '',
		'max_rows' => 2000,
		'max_size' => 524288
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

				$toolbarButtons['back']['url']['action'] = 'index';
				unset($toolbarButtons['back']['url'][0]);
				break;

			case 'view':
				$toolbarButtons['back']['url']['action'] = 'index';
				unset($toolbarButtons['back']['url'][0]);
				break;
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$buttons[0]['name'] = '<i class="fa kd-import"></i> ' . __('Import');
	}

	public function beforeAction($event) {
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
		$this->_table->ControllerAction->field('lookup_alias', ['visible' => false]);
		$this->_table->ControllerAction->field('lookup_column', ['visible' => false]);
		$this->_table->ControllerAction->field('foreign_key', ['visible' => false]);

		$comment = __('* Format Supported: ') . __(implode(', ', $this->config('allowable_file_types')));
		$comment .= '<br/>';
		$comment .= __('* Recommended Maximum File Size: ') . __($this->bytesToReadableFormat($this->config('max_size')));
		$comment .= '<br/>';
		$comment .= __('* Recommended Maximum Records: ') . __($this->config('max_rows'));

		$this->_table->ControllerAction->field('select_file', [
			'type' => 'binary',
			'visible' => true,
			'attr' => ['label' => 'Select File To Import'],
			'null' => false,
			'comment' => $comment
		]);
	}

	private function eventKey($key) {
		return 'Model.import.' . $key;
	}

	/**
	 * addBeforePatch turns off the validation when patching entity with post data, and check the uploaded file size. 
	 * @param Event       $event   [description]
	 * @param Entity      $entity  [description]
	 * @param ArrayObject $data    [description]
	 * @param ArrayObject $options [description]
	 * 
	 * Refer to ImportExcelTrait->phpFileUploadErrors for the list of file upload errors defination.
	 */
	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$options['validate'] = false;
		if (!array_key_exists($this->_table->alias(), $data)) {
			$options['validate'] = true;
		}
		if (!array_key_exists('select_file', $data[$this->_table->alias()])) {
			$options['validate'] = true;
		}
		if (empty($data[$this->_table->alias()]['select_file'])) {
			$options['validate'] = true;
		}
		if ($data[$this->_table->alias()]['select_file']['error']==4) {
			$options['validate'] = true;
		}

		$fileObj = $data[$this->_table->alias()]['select_file'];
		$supportedFormats = $this->_fileTypesMap;
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$fileFormat = finfo_file($finfo, $fileObj['tmp_name']);
		finfo_close($finfo);
		$model = $this->_table;

		if (!in_array($fileFormat, $supportedFormats)) {
			if (!empty($fileFormat)) {
				$entity->errors('select_file', [$model->getMessage('Import.not_supported_format')]);				
				$options['validate'] = true;
			}
		} 
		if ($event->subject()->request->env('CONTENT_LENGTH') >= $this->config('max_size')) {
			$entity->errors('select_file', [$model->getMessage('Import.over_max')]);
			$options['validate'] = true;
		} 
		if ($event->subject()->request->env('CONTENT_LENGTH') >= $this->file_upload_max_size()) {
			$entity->errors('select_file', [$model->getMessage('Import.over_max')]);
			$options['validate'] = true;
		} 
		if ($event->subject()->request->env('CONTENT_LENGTH') >= $this->post_upload_max_size()) {
			$entity->errors('select_file', [$model->getMessage('Import.over_max')]);
			$options['validate'] = true;
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
					$entity->errors('select_file', [$model->getMessage('Import.over_max_rows')]);
					return false;
				}

				for ($row = 1; $row <= $highestRow; ++$row) {
					if ($row == self::RECORD_HEADER) { // skip header but check if the uploaded template is correct
						if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
							$entity->errors('select_file', [$model->getMessage('Import.wrong_template')]);
							return false;
						}
						continue;
					}
					if ($row == $highestRow) {
						if ($this->checkRowCells($sheet, $totalColumns, $row) === false) {
							break;
						}
					}
					
// $model->log('===============================================================================================', 'info');
					// $model->log('start ImportBehavior: '.__LINE__, 'info');
					// check for unique record
					$tempRow = new ArrayObject;
					$tempRow['duplicates'] = false;
					$params = [$sheet, $row, $columns, $tempRow, $importedUniqueCodes];
					$this->dispatchEvent($this->_table, $this->eventKey('onImportCheckUnique'), 'onImportCheckUnique', $params);
					// $model->log('ImportBehavior after checking Unique: '.__LINE__, 'info');
					
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

					// $tempRow['entity'] must exists!!! should be set in individual model's onImportCheckUnique function
					if (!isset($tempRow['entity'])) {
						pr($tempRow);die;
					}
					$tableEntity = $tempRow['entity'];
					$tempRow = $tempRow->getArrayCopy();
					$duplicates = $tempRow['duplicates'];
					unset($tempRow['duplicates']);
					unset($tempRow['entity']);
					$activeModel->patchEntity($tableEntity, $tempRow);
					$errors = $tableEntity->errors();

					if (!$rowPass || $duplicates || $errors) { // row contains error or record is a duplicate based on unique key(s)

						$rowCodeError = '';
						if ($duplicates) {
							$rowCodeError .= $this->getExcelLabel('Import', 'duplicate_unique_key');
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

						$model->log($tableEntity->errors(), 'debug');

						continue;
					}

					// $model->log('ImportBehavior after patch entity: '.__LINE__, 'info');
					$isNew = $tableEntity->isNew();
					if ($activeModel->save($tableEntity)) {
						// $model->log('ImportBehavior able to save: '.__LINE__, 'info');
						if ($isNew) {
							$totalImported++;
						} else {
							$totalUpdated++;
						}

						// update importedUniqueCodes either a single key or composite primary keys
						$this->dispatchEvent($this->_table, $this->eventKey('onImportUpdateUniqueKeys'), 'onImportUpdateUniqueKeys', [$importedUniqueCodes, $tableEntity]);
						// $model->log('ImportBehavior: '.__LINE__, 'info');
					
					// } else {
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
		$excelFile = sprintf('%s_%s_%s_%s.xlsx', __('Import'), __($this->config('plugin')), __($this->config('model')), __('Template'));
		$excelPath = $folder . DS . $excelFile;

		$writer = new \XLSXWriter();
		
		$header = $this->getHeader();
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
				'results' => $completedData
			]);
			$session->delete($this->sessionKey);
			// define data as empty entity so that the view file will not throw an undefined notice
			if ($completedData['excelFile']) {
				if (count($completedData['totalImported'])>0 || count($completedData['totalUpdated'])>0) {
					$this->_table->Alert->warning('Import.has_failure', ['reset' => true]);
				} else {
					$this->_table->Alert->error('general.edit.failed', ['reset' => true]);
				}
			} else {
				$this->_table->Alert->success('general.edit.success', ['reset' => true]);
			}
			$this->_table->controller->set('data', $this->_table->newEntity());
			$this->_table->ControllerAction->renderView('/ControllerAction/view');
		} else {
			return $this->_table->controller->redirect($this->_table->ControllerAction->url('add'));
		}
	}

}
