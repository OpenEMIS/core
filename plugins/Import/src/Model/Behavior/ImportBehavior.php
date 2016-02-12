<?php
namespace Import\Model\Behavior;

use DateTime;
use DateInterval;
use ArrayObject;
use PHPExcel_Worksheet;
use InvalidArgumentException;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Network\Session;
use Cake\Routing\Router;
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
	const NON_TABLE_LIST = 3;

	const RECORD_HEADER = 2;
	const FIRST_RECORD = 3;

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
	private $institutionId = false;

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
				$downloadUrl = $toolbarButtons['back']['url'];
				$downloadUrl[0] = 'template';
				if ($buttons['add']['url']['action']=='ImportInstitutionSurveys') {
					$downloadUrl[1] = $buttons['add']['url'][1];
				}
				$this->_table->controller->set('downloadUrl', Router::url($downloadUrl));
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
		$session = $this->_table->Session;
		if ($session->check('Institution.Institutions.id')) {
			$this->institutionId = $session->read('Institution.Institutions.id');
		}
		$this->sessionKey = $this->config('plugin').'.'.$this->config('model').'.Import.data';

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
			'attr' => [
				'label' => __('Select File To Import')
			],
			'null' => false,
			'comment' => $comment,
			'startWithOneLeftButton' => 'download'
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
		if ($data[$this->_table->alias()]['select_file']['error']>0) {
			$options['validate'] = true;
			$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max')], true);
		}

		if ($options['validate']) {
			return $event->response;
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
			$dataPassed = [];

			$activeModel = TableRegistry::get($this->config('plugin').'.'.$this->config('model'));
			$activeModel->addBehavior('DefaultValidation');

			$maxRows = $this->config('max_rows');
			$maxRows = $maxRows + 3;
			$sheet = $objPHPExcel->getSheet(0);
			$highestRow = $sheet->getHighestRow();
			if ($highestRow > $maxRows) {
				$entity->errors('select_file', [$this->getExcelLabel('Import', 'over_max_rows')], true);
				return false;
			}
			if ($highestRow == self::RECORD_HEADER) {
				$entity->errors('select_file', [$this->getExcelLabel('Import', 'no_answers')], true);
				return false;
			}

			for ($row = 2; $row <= $highestRow; ++$row) {
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
				$rowInvalidCodeCols = new ArrayObject;
				$params = [$sheet, $row, $columns, $tempRow, $importedUniqueCodes, $rowInvalidCodeCols];
				$this->dispatchEvent($this->_table, $this->eventKey('onImportCheckUnique'), 'onImportCheckUnique', $params);
		
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
				$activeModel->patchEntity($tableEntity, $tempRow);
				$errors = $tableEntity->errors();
				$rowInvalidCodeCols = $rowInvalidCodeCols->getArrayCopy();
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
								if ($field == 'student_name') {
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

					$tempPassedRecord = [
						'row_number' => $row,
						'data' => $this->_getReorderedEntityArray($clonedEntity, $columns, $originalRow, $systemDateFormat)
					];
					$tempPassedRecord = new ArrayObject($tempPassedRecord);

					// individual import models can specifically define the passed record values which are to be exported
					$params = [$clonedEntity, $columns, $tempPassedRecord, $originalRow];
					$this->dispatchEvent($this->_table, $this->eventKey('onImportSetModelPassedRecord'), 'onImportSetModelPassedRecord', $params);

					$dataPassed[] = $tempPassedRecord->getArrayCopy();
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

			$session = $this->_table->Session;
			$completedData = [
				'uploadedName' => $uploadedName,
				'dataFailed' => $dataFailed,
				'totalImported' => $totalImported,
				'totalUpdated' => $totalUpdated,
				'totalRows' => count($dataFailed) + $totalImported + $totalUpdated,
				'header' => $header,
				'failedExcelFile' => $this->_generateDownloadableFile( $dataFailed, 'failed', $header, $systemDateFormat ),
				'passedExcelFile' => $this->_generateDownloadableFile( $dataPassed, 'passed', $header, $systemDateFormat ),
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
		// Do not lcalize file name as certain non-latin characters might cause issue 
		$excelFile = sprintf('OpenEMIS_Core_Import_%s_Template.xlsx', $modelName);
		$excelPath = $folder . DS . $excelFile;

		$mapping = $this->getMapping();
		$header = $this->getHeader($mapping);
		$dataSheetName = $this->getExcelLabel('general', 'data');

		$objPHPExcel = new \PHPExcel();

		$this->setImportDataTemplate( $objPHPExcel, $dataSheetName, $header );

		$this->setCodesDataTemplate( $objPHPExcel );

		$objPHPExcel->setActiveSheetIndex(0);
		$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter->save($excelPath);

		$this->performDownload($excelFile);
		die;
	}

	public function downloadFailed($excelFile) {
		$this->performDownload($excelFile);
		die;
	}

	public function downloadPassed($excelFile) {
		$this->performDownload($excelFile);
		die;
	}

	public function results() {
		$session = $this->_table->Session;
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
			// $session->delete($this->sessionKey);
			if (!empty($completedData['failedExcelFile'])) {
				if (!empty($completedData['passedExcelFile'])) {
					$message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'partial_failed');
				} else {
					$message = '<i class="fa fa-exclamation-circle fa-lg"></i> ' . $this->getExcelLabel('Import', 'the_file') . ' "' . $completedData['uploadedName'] . '" ' . $this->getExcelLabel('Import', 'failed');
				}
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
	public function beginExcelHeaderStyling( $objPHPExcel, $dataSheetName, $lastRowToAlign = 2, $title = '' ) {
		if (empty($title)) {
			$title = $dataSheetName;
		}
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setTitle( $dataSheetName );

		$gdImage = imagecreatefromjpeg(ROOT . DS . 'plugins' . DS . 'Import' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.jpg');
		$objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
		$objDrawing->setName('OpenEMIS Logo');
		$objDrawing->setDescription('OpenEMIS Logo');
		$objDrawing->setImageResource($gdImage);
		$objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
		$objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
		$objDrawing->setHeight(100);
		$objDrawing->setCoordinates('A1');
		$objDrawing->setWorksheet($activeSheet);

		$activeSheet->getRowDimension(1)->setRowHeight(75);
		$activeSheet->getRowDimension(2)->setRowHeight(25);

		$headerLastAlpha = $this->getExcelColumnAlpha('last');
		$activeSheet->getStyle( "A1:" . $headerLastAlpha . "1" )->getFont()->setBold(true)->setSize(16);
		$activeSheet->setCellValue( "C1", $title );
		$style = [
	        'alignment' => [
	            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
	        ]
	    ];
	    $activeSheet->getStyle("A1:". $headerLastAlpha . $lastRowToAlign)->applyFromArray($style);
	}

	public function endExcelHeaderStyling( $objPHPExcel, $headerLastAlpha, $applyFillFontSetting = [], $applyCellBorder = [] ) {
		if (empty($applyFillFontSetting)) {
			$applyFillFontSetting = ['s'=>2, 'e'=>2];
		}
		if (empty($applyCellBorder)) {
			$applyCellBorder = ['s'=>2, 'e'=>2];
		}

		$activeSheet = $objPHPExcel->getActiveSheet();

		// merging should start from cell C1 instead of A1 since the title is already set in cell C1 in beginExcelHeaderStyling()
		if (!in_array($headerLastAlpha, ['A','B','C'])) {
			$activeSheet->mergeCells('C1:'. $headerLastAlpha .'1');
		}
		$activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('6699CC'); // OpenEMIS Core product color
		$activeSheet->getStyle("A". $applyCellBorder['s'] .":". $headerLastAlpha . $applyCellBorder['e'])->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
	}

	public function setImportDataTemplate( $objPHPExcel, $dataSheetName, $header ) {

		$objPHPExcel->setActiveSheetIndex(0);

		$this->beginExcelHeaderStyling( $objPHPExcel, $dataSheetName, 2, __(Inflector::humanize(Inflector::tableize($this->_table->alias()))) .' '. $dataSheetName );

		$activeSheet = $objPHPExcel->getActiveSheet();
		$currentRowHeight = $activeSheet->getRowDimension(2)->getRowHeight();
		foreach ($header as $key=>$value) {
			$alpha = $this->getExcelColumnAlpha($key);
			$activeSheet->setCellValue( $alpha . "2", $value);
			if (strlen($value)<50) {
				$activeSheet->getColumnDimension( $alpha )->setAutoSize(true);
			} else {
				$activeSheet->getColumnDimension( $alpha )->setWidth(35);
				$currentRowHeight = $this->suggestRowHeight( strlen($value), $currentRowHeight );
				$activeSheet->getRowDimension(2)->setRowHeight( $currentRowHeight );
				$activeSheet->getStyle( $alpha . "2" )->getAlignment()->setWrapText(true);
			}
		}
		$headerLastAlpha = $this->getExcelColumnAlpha(count($header)-1);

		$this->endExcelHeaderStyling( $objPHPExcel, $headerLastAlpha );

	}

	public function suggestRowHeight($stringLen, $currentRowHeight) {
		if ($stringLen>=50) {
			$multiplier = $stringLen % 50;
		} else {
			$multiplier = 0;
		}
		$rowHeight = (3 * $multiplier) + 25;
		if ($rowHeight > $currentRowHeight && $rowHeight<=250) {
			$currentRowHeight = $rowHeight;
		}
		return $currentRowHeight;
	}

	public function setCodesDataTemplate( $objPHPExcel ) {
        $sheetName = __('References');
        $objPHPExcel->createSheet(1);
        $objPHPExcel->setActiveSheetIndex(1);
		
		$this->beginExcelHeaderStyling( $objPHPExcel, $sheetName, 3 );

		$objPHPExcel->getActiveSheet()->getRowDimension(3)->setRowHeight(25);

        if (method_exists($this->_table, 'excelGetCodesData') ) {
			$codesData = $this->_table->excelGetCodesData();
        } else {
			$codesData = $this->excelGetCodesData($this->_table);
        }
        $lastColumn = -1;
		$currentRowHeight = $objPHPExcel->getActiveSheet()->getRowDimension(2)->getRowHeight();
		foreach($codesData as $columnOrder => $modelArr) {
			$modelData = $modelArr['data'];
			$firstColumn = $lastColumn + 1;
			$lastColumn = $firstColumn + count($modelArr['data'][0]) - 1;

			$objPHPExcel->getActiveSheet()->mergeCells( $this->getExcelColumnAlpha($firstColumn) ."2:". $this->getExcelColumnAlpha($lastColumn) ."2" );
			$objPHPExcel->getActiveSheet()->setCellValue( $this->getExcelColumnAlpha($firstColumn) ."2", $modelArr['sheetName'] );
			if (strlen($modelArr['sheetName'])<50) {
				$objPHPExcel->getActiveSheet()->getColumnDimension( $this->getExcelColumnAlpha($firstColumn) )->setAutoSize(true);
			} else {
				// $objPHPExcel->getActiveSheet()->getColumnDimension( $this->getExcelColumnAlpha($firstColumn) )->setWidth(35);
				$currentRowHeight = $this->suggestRowHeight( strlen($modelArr['sheetName']), $currentRowHeight );
				$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight( $currentRowHeight );
				$objPHPExcel->getActiveSheet()->getStyle( $this->getExcelColumnAlpha($firstColumn) . "2" )->getAlignment()->setWrapText(true);
			}

			foreach ($modelData as $index => $sets) {
				foreach ($sets as $key => $value) {
					$alpha = $this->getExcelColumnAlpha( ($key + $firstColumn) );
					$objPHPExcel->getActiveSheet()->setCellValue( $alpha . ($index + 3), $value);
					$objPHPExcel->getActiveSheet()->getColumnDimension( $alpha )->setAutoSize(true);
				}
			}
		
			if (count($modelData)>1 && !array_key_exists('noDropDownList', $modelArr)) {
				$lookupColumn = $firstColumn + intval($modelArr['lookupColumn']) - 1;
				$alpha = $this->getExcelColumnAlpha( $columnOrder - 1 );
				$lookupColumnAlpha = $this->getExcelColumnAlpha( $lookupColumn );
				for ($i=3; $i < 103; $i++) {
					$objPHPExcel->setActiveSheetIndex(0);
					$objValidation = $objPHPExcel->getActiveSheet()->getCell( $alpha . $i )->getDataValidation();
					$objValidation->setType( \PHPExcel_Cell_DataValidation::TYPE_LIST );
					$objValidation->setErrorStyle( \PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
					$objValidation->setAllowBlank(false);
					$objValidation->setShowInputMessage(true);
					$objValidation->setShowErrorMessage(true);
					$objValidation->setShowDropDown(true);
					$listLocation = "'". $sheetName ."'!$". $lookupColumnAlpha ."$4:$". $lookupColumnAlpha ."$". (count($modelData)+2);
					$objValidation->setFormula1( $listLocation );
				}
				$objPHPExcel->setActiveSheetIndex(1);
			}
		}
	    $headerLastAlpha = $this->getExcelColumnAlpha( $lastColumn );
		$objPHPExcel->getActiveSheet()->getStyle( "A2:" . $headerLastAlpha . "2" )->getFont()->setBold(true)->setSize(12);
		$this->endExcelHeaderStyling( $objPHPExcel, $headerLastAlpha, ['s'=>3, 'e'=>3], ['s'=>2, 'e'=>3] );
	}

	/**
	 * Set a record columns value based on what is being saved in the table.
	 * @param  Entity $entity           Cloned entity. The actual entity is not saved yet but already validated but we are using a cloned entity in case it might be messed up.
	 * @param  Array  $columns          Target Model columns defined in import_mapping table.
	 * @param  string $systemDateFormat System Date Format which varies across deployed environments.
	 * @return Array                   	The columns value that will be written to a downloadable excel file.
	 */
	private function _getReorderedEntityArray( Entity $entity, Array $columns, ArrayObject $originalRow, $systemDateFormat ) {
		$array = [];
		foreach ($columns as $col=>$property) {
			$value = ( $entity->$property instanceof Time ) ? $entity->$property->format( $systemDateFormat ) : $originalRow[$col];
			$array[] = $value;
		}
		return $array;
	}

	private function _generateDownloadableFile( $data, $type, $header, $systemDateFormat ) {
		if (!empty($data)) {
			$downloadFolder = $this->prepareDownload();
			// Do not lcalize file name as certain non-latin characters might cause issue 
			$excelFile = sprintf( 'OpenEMIS_Core_Import_%s_%s_%s.xlsx', $this->config('model'), ucwords($type), time() );
			$excelPath = $downloadFolder . DS . $excelFile;

			$newHeader = $header;
			if ($type == 'failed') {
				$newHeader[] = $this->getExcelLabel('general', 'errors');
			}
			$dataSheetName = $this->getExcelLabel('general', 'data');

			$objPHPExcel = new \PHPExcel();

			$this->setImportDataTemplate( $objPHPExcel, $dataSheetName, $newHeader );
			$activeSheet = $objPHPExcel->getActiveSheet();
			foreach($data as $index => $record) {
				if ($type == 'failed') {
					$values = array_values($record['data']->getArrayCopy());
					$values[] = $record['errorForExcel'];
				} else {
					$values = $record['data'];
				}
				$activeSheet->getRowDimension( ($index + 3) )->setRowHeight( 15 );
				foreach ($values as $key => $value) {
					$alpha = $this->getExcelColumnAlpha($key);
					$activeSheet->setCellValue( $alpha . ($index + 3), $value);
					$activeSheet->getColumnDimension( $alpha )->setAutoSize(true);

					if ($key==(count($values)-1) && $type == 'failed') {
						$suggestedRowHeight = $this->suggestRowHeight( strlen($value), 15 );
						$activeSheet->getRowDimension( ($index + 3) )->setRowHeight( $suggestedRowHeight );
						$activeSheet->getStyle( $alpha . ($index + 3) )->getAlignment()->setWrapText(true);
					}
				}
			}

			if ($type == 'failed') {
				$this->setCodesDataTemplate( $objPHPExcel );
			}
			
			$objPHPExcel->setActiveSheetIndex(0);
			$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save($excelPath);

			$downloadUrl = $this->_table->ControllerAction->url( 'download' . ucwords($type) );
			$downloadUrl[] = $excelFile;
			$excelFile = $downloadUrl;
		} else {
			$excelFile = null;
		}

		return $excelFile;
	}

	/**
	 * Get the string representation of a column based on excel grid
	 * @param  mixed $column_number either an integer or a string named as "last"
	 * @return string               the string representation of a column based on excel grid
	 * @todo  the alpha string array values should be auto-generated instead of hard-coded
	 */
	public function getExcelColumnAlpha($column_number) {
		$alpha = [
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
			'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
			'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
			'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM','CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ',
			'DA','DB','DC','DD','DE','DF','DG','DH','DI','DJ','DK','DL','DM','DN','DO','DP','DQ','DR','DS','DT','DU','DV','DW','DX','DY','DZ',
			'EA','EB','EC','ED','EE','EF','EG','EH','EI','EJ','EK','EL','EM','EN','EO','EP','EQ','ER','ES','ET','EU','EV','EW','EX','EY','EZ',
			'FA','FB','FC','FD','FE','FF','FG','FH','FI','FJ','FK','FL','FM','FN','FO','FP','FQ','FR','FS','FT','FU','FV','FW','FX','FY','FZ'
		];
		if ($column_number === 'last') {
			$column_number = count($alpha) - 1;
		}
		return $alpha[$column_number];		
	}

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
				$model->aliasField('model') => $this->config('plugin').'.'.$this->config('model')
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
			$label = $this->getExcelLabel('Imports', $value->lookup_model);
			if (empty($label)) {
				$label = $this->getExcelLabel($value->model, $column);
			}
			if (!empty($value->description)) {
				$label .= ' ' . __($value->description);
			}
			
			$header[] = __($label);
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
				$model->aliasField('model') => $this->config('plugin').'.'.$this->config('model'),
				$model->aliasField('foreign_key') . ' IN' => [self::FIELD_OPTION, self::DIRECT_TABLE, self::NON_TABLE_LIST]
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

			$sheetName = trim($this->getExcelLabel($row->model, $row->column_name));
			$data[$row->order] = [
				'data'=>[], 
				'sheetName'=>$sheetName
			];
			$modelData = [];
			if ($foreignKey == self::FIELD_OPTION) {
				if (TableRegistry::exists($lookupModel)) {
					$relatedModel = TableRegistry::get($lookupModel);
				} else {
					$relatedModel = TableRegistry::get($lookupModel, ['className' => $lookupPlugin . '\Model\Table\\' . $lookupModel.'Table']);
				}
				$modelData = $relatedModel->getList()->toArray();
				$data[$row->order]['lookupColumn'] = 2;
				$data[$row->order]['data'][] = [__('Name'), $translatedCol];
				if (!empty($modelData)) {
					foreach($modelData as $key=>$value) {
						$data[$row->order]['data'][] = [$value, $key];
					}
				}
			} else if ($foreignKey == self::DIRECT_TABLE || $foreignKey == self::NON_TABLE_LIST) {

				$params = [$lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, $data, $row->order];
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
		$dotPost = strpos($module, '.');
		if ($dotPost > -1) {
			$module = substr($module, ($dotPost + 1));
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
				if (empty($translatedCol) || $translatedCol==$columnName) {
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
					if (is_numeric($val)) {
						$val = date($systemDateFormat, \PHPExcel_Shared_Date::ExcelToPHP($val));
					}
					$originalRow[$col] = $val;
					// converts val to Time object so that this field will pass 'validDate' check since
					// different model has different date format checking. Example; user->date_of_birth is using dmY while others using Y-m-d,
					// so it is best to convert the date here instead of adjusting individual model's date validation format
					try {
						$formattedDate = Time::createFromFormat($systemDateFormat, $val);
						if ($formattedDate instanceof Time) {
							$val = $formattedDate;
						}
					} catch (InvalidArgumentException $e) {
					    // $val = '';
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
						$rowInvalidCodeCols[$columnName] = __('Selected value is not in the list');
					}
				} else { // if cell is empty
					$rowPass = false;
					$rowInvalidCodeCols[$columnName] = __('This field cannot be left empty');
				}
			} else if ($foreignKey == self::DIRECT_TABLE) {
				$registryAlias = $excelMappingObj->lookup_plugin . '.' . $excelMappingObj->lookup_model;
				if (!empty($this->directTables) && isset($this->directTables[$registryAlias])) {
					$excelLookupModel = $this->directTables[$registryAlias]['excelLookupModel'];
				} else {
					$excelLookupModel = TableRegistry::get($registryAlias);
					$this->directTables[$registryAlias] = ['excelLookupModel' => $excelLookupModel];
				}
				$excludeValidation = false;
				if (!empty($cellValue)) {
					$record = $excelLookupModel->find()->where([$excelLookupModel->aliasField($excelMappingObj->lookup_column) => $cellValue]);
					// if($excelLookupModel->alias()=='Students') {pr($cellValue);pr($record->sql());die;}
					$record = $record->first();
				} else {
					if ($activeModel->schema()->column($columnName) && !$activeModel->schema()->column($columnName)['null']) {
						$record = '';
					} else {
						$excludeValidation = true;
					}
				}
				if (!$excludeValidation) {
					if (!empty($record)) {
						$val = $record->id;
						$this->directTables[$registryAlias][$val] = $record->name;
					} else {
						if (!empty($cellValue)) {
							$rowPass = false;
							$rowInvalidCodeCols[$columnName] = __('Selected value is not in the list');
						} else {
							$rowPass = false;
							$rowInvalidCodeCols[$columnName] = __('This field cannot be left empty');
						}
					}
				} else {
					$val = $cellValue;
				}
			} else if ($foreignKey == self::NON_TABLE_LIST) {
				if (!empty($cellValue)) {
					$recordId = $this->dispatchEvent($this->_table, $this->eventKey('onImportGet'.$excelMappingObj->lookup_model.'Id'), 'onImportGet'.$excelMappingObj->lookup_model.'Id', [$cellValue]);
					if (!empty($recordId)) {
						$val = $recordId->id;
					} else {
						$rowPass = false;
						$rowInvalidCodeCols[$columnName] = __('Selected value is not in the list');
					}
				} else {
					$rowPass = false;
					$rowInvalidCodeCols[$columnName] = __('This field cannot be left empty');
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
		if (empty($date)) {
			// die('date is empty');
			return false;
		}

		if ($date instanceof DateTime) {
			$date = $date->format('Y-m-d');
		}
		$period = $this->AcademicPeriods
					->find()
					->where([
						"date(start_date) <= date '".$date."'",
						"date(end_date) >= date '".$date."'",
						'parent_id <> 0',
						'visible = 1'
					]);
		return $period->toArray();
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
			$max_size = $this->post_upload_max_size();

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->upload_max_filesize();
			
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
		$max_size = $this->parse_size(ini_get('post_max_size'));
		$system_limit = $this->system_memory_limit();

		if ($max_size == 0) {
			$max_size = $system_limit;
		}
		return $max_size;
	}

	protected function system_memory_limit() {
		return $this->parse_size(ini_get('memory_limit'));
	}

	protected function upload_max_filesize() {
		return $this->parse_size(ini_get('upload_max_filesize'));
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
