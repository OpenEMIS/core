<?php
namespace Training\Model\Behavior;

use DateTime;
use DateInterval;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;
use Cake\I18n\I18n;
use Cake\Datasource\Exception\RecordNotFoundException;
use Import\Model\Traits\ImportTrait;

class ImportTrainingSessionTraineesBehavior extends Behavior {
	use ImportTrait;

	const RECORD_HEADER = 2;
	const FIRST_RECORD = 3;

	protected $_defaultConfig = [
		'plugin' => '',
		'model' => '',
		'max_rows' => 2000,
		'max_size' => 524288
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
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1];
		$events['ControllerAction.Model.addEdit.afterAction'] = ['callable' => 'addEditAfterAction', 'priority' => 999];
		$events['ControllerAction.Model.addEdit.onMassAddTrainees'] = ['callable' => 'addEditOnMassAddTrainees', 'priority' => 999];
		return $events;
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		// $fieldOrder = [
		// 	'training_course_id', 'training_provider_id',
		// 	'code', 'name', 'start_date', 'end_date', 'comment',
		// 	'trainers'
		// ];
		if (isset($entity->id)) {
			$comment = __('* Format Supported: ' . implode(', ', $this->config('allowable_file_types')));
			$comment .= '<br/>';
			$comment .= __('* Recommended Maximum File Size: ' . $this->bytesToReadableFormat($this->config('max_size')));
			$comment .= '<br/>';
			$comment .= __('* Recommended Maximum Records: ' . $this->config('max_rows'));

			$this->_table->ControllerAction->field('trainees_import', [
				'type' => 'binary',
				'visible' => true,
				'attr' => ['label' => __('Import Trainees')],
				'upload-button' => [
					'onclick' => "$('#reload').val('massAddTrainees').click()"
				],
				'comment' => $comment
			]);
			// pr($event->subject()->request);die;
			$data = $event->subject()->request->data;
			if (is_object($data) && $data->offsetExists('trainees_import_error')) {
				$entity->errors('trainees_import', $data['trainees_import_error']);
			}
		}
		// $this->_table->ControllerAction->setFieldOrder($fieldOrder);
	}

	public function addEditOnMassAddTrainees(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $event->subject()->request;
		$model = $this->_table;
		$alias = $model->alias();
		$error = '';
		if ($request->env('CONTENT_LENGTH') >= $this->config('max_size')) {
			$error = $model->getMessage('Import.over_max');
		} 
		if ($request->env('CONTENT_LENGTH') >= $this->file_upload_max_size()) {
			$error = $model->getMessage('Import.over_max');
		} 
		if ($request->env('CONTENT_LENGTH') >= $this->post_upload_max_size()) {
			$error = $model->getMessage('Import.over_max');
		}
		if (!array_key_exists($alias, $data)) {
			$error = $model->getMessage('Import.not_supported_format');	
		}
		if (!array_key_exists('trainees_import', $data[$alias])) {
			$error = $model->getMessage('Import.not_supported_format');
		}
		if (empty($data[$alias]['trainees_import'])) {
			$error = $model->getMessage('Import.not_supported_format');
		}
		if ($data[$alias]['trainees_import']['error']==4) {
			$error = $model->getMessage('Import.not_supported_format');
		}
		if ($data[$alias]['trainees_import']['error']>0) {
			$error = $model->getMessage('Import.over_max');
		}

		$fileObj = $data[$alias]['trainees_import'];
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
				$error = $model->getMessage('Import.not_supported_format');
			}
		}				

		$fileExt = $fileObj['name'];
		$fileExt = explode('.', $fileExt);
		$fileExt = $fileExt[count($fileExt)-1];
		if (!array_key_exists($fileExt, $supportedFormats)) {
			if (!empty($fileFormat)) {
				$error = $model->getMessage('Import.not_supported_format');
			}
		}

		if (!empty($error)) {
			$data['trainees_import_error'] = $error;
		} else {
			ini_set('max_execution_time', 60);

			$controller = $model->controller;
			$controller->loadComponent('PhpExcel');
			$columns = ['trainees_import'];

			$fileObj = $data[$alias]['trainees_import'];		
			// $uploadedName = $fileObj['name'];
			$uploaded = $fileObj['tmp_name'];
			$objPHPExcel = $controller->PhpExcel->loadWorksheet($uploaded);

			$maxRows = $this->config('max_rows');
			$maxRows = $maxRows + 3;
			$sheet = $objPHPExcel->getSheet(0);
			$highestRow = $sheet->getHighestRow();
			if ($highestRow > $maxRows) {
				$error = $model->getMessage('Import', 'over_max_rows');
				return false;
			}

			for ($row = 2; $row <= $highestRow; ++$row) {
				if ($row == self::RECORD_HEADER) { // skip header but check if the uploaded template is correct
					// if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
					// 	$error = $model->getMessage('Import', 'wrong_template');
					// 	return false;
					// }
					continue;
				}
				if ($row == $highestRow) { // if $row == $highestRow, check if the row cells are really empty, if yes then end the loop
					if ($this->checkRowCells($sheet, 1, $row) === false) {
						break;
					}
				}
					
				$cell = $sheet->getCellByColumnAndRow(0, $row);
				$openemis_no = $cell->getValue();
				// pr('row: '.$row);pr($openemis_no);
				if (empty($openemis_no)) {
					continue;
				}
				$key = 'trainees';
				try {
					// pr($row);pr($openemis_no);die;
					$trainee = $model->Trainees->find()->where(['openemis_no' => $openemis_no])->first();

					if (!array_key_exists($key, $data[$alias])) {
						$data[$alias][$key] = [];
					}
					$data[$alias][$key][] = [
						'id' => $trainee->id,
						'_joinData' => ['openemis_no' => $trainee->openemis_no, 'trainee_id' => $trainee->id, 'name' => $trainee->name]
					];
				} catch (RecordNotFoundException $ex) {
					$model->log(__CLASS__.'->'.__METHOD__ . ': Record not found for id: ' . $openemis_no, 'debug');
				}
			}
			// pr($error);pr($data);pr($highestRow);die;
			// Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
			$options['associated'] = [
				'Trainees' => ['validate' => false]
			];
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$customButton = [];
		switch ($action) {
			case 'edit':
			case 'add':
				$toolbarButtons['import'] = $toolbarButtons['back'];
				$toolbarButtons['import']['url'][0] = 'template';
				$toolbarButtons['import']['attr']['title'] = __('Download Template');
				$toolbarButtons['import']['label'] = '<i class="fa kd-download"></i>';
				
				break;
		}
	}

	public function template() {
		$folder = $this->prepareDownload();
		// Do not lcalize file name as certain non-latin characters might cause issue 
		$excelFile = 'OpenEMIS_Core_Import_Training_Session_Trainees.xlsx';
		$excelPath = $folder . DS . $excelFile;

		$header = ['OpemEMIS ID'];
		$dataSheetName = __('Training Session Trainees');

		$objPHPExcel = new \PHPExcel();

		$this->setImportDataTemplate( $objPHPExcel, $dataSheetName, $header, false, 'F' );

		$objPHPExcel->setActiveSheetIndex(0);
		$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter->save($excelPath);

		$this->performDownload($excelFile);
		die;
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


// Styling
	// public function setImportDataTemplate( $objPHPExcel, $dataSheetName, $header ) {

	// 	$objPHPExcel->setActiveSheetIndex(0);

	// 	$this->beginExcelHeaderStyling( $objPHPExcel, $dataSheetName, 2 );

	// 	$activeSheet = $objPHPExcel->getActiveSheet();
	// 	$currentRowHeight = $activeSheet->getRowDimension(2)->getRowHeight();
	// 	foreach ($header as $key=>$value) {
	// 		$alpha = $this->getExcelColumnAlpha($key);
	// 		$activeSheet->setCellValue( $alpha . "2", $value);
	// 		if (strlen($value)<50) {
	// 			$activeSheet->getColumnDimension( $alpha )->setAutoSize(true);
	// 		} else {
	// 			$activeSheet->getColumnDimension( $alpha )->setWidth(35);
	// 			$currentRowHeight = $this->suggestRowHeight( strlen($value), $currentRowHeight );
	// 			$activeSheet->getRowDimension(2)->setRowHeight( $currentRowHeight );
	// 			$activeSheet->getStyle( $alpha . "2" )->getAlignment()->setWrapText(true);
	// 		}
	// 	}
	// 	$headerLastAlpha = $this->getExcelColumnAlpha(count($header)-1);

	// 	$this->endExcelHeaderStyling( $objPHPExcel, $headerLastAlpha );

	// }

	// public function beginExcelHeaderStyling( $objPHPExcel, $dataSheetName, $lastRowToAlign = 2, $title = '' ) {
	// 	if (empty($title)) {
	// 		$title = $dataSheetName;
	// 	}
	// 	$activeSheet = $objPHPExcel->getActiveSheet();
	// 	$activeSheet->setTitle( $dataSheetName );

	// 	$gdImage = imagecreatefromjpeg(ROOT . DS . 'plugins' . DS . 'Import' . DS . 'webroot' . DS . 'img' . DS . 'openemis_logo.jpg');
	// 	$objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
	// 	$objDrawing->setName('OpenEMIS Logo');
	// 	$objDrawing->setDescription('OpenEMIS Logo');
	// 	$objDrawing->setImageResource($gdImage);
	// 	$objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
	// 	$objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
	// 	$objDrawing->setHeight(100);
	// 	$objDrawing->setCoordinates('A1');
	// 	$objDrawing->setWorksheet($activeSheet);

	// 	$activeSheet->getRowDimension(1)->setRowHeight(75);
	// 	$activeSheet->getRowDimension(2)->setRowHeight(25);

	// 	$headerLastAlpha = $this->getExcelColumnAlpha('last');
	// 	$activeSheet->getStyle( "A1:" . $headerLastAlpha . "1" )->getFont()->setBold(true)->setSize(16);
	// 	$activeSheet->setCellValue( "G1", $title );
	// 	$style = [
	//         'alignment' => [
	//             'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	//             'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
	//         ]
	//     ];
	//     $activeSheet->getStyle("A1:". $headerLastAlpha . $lastRowToAlign)->applyFromArray($style);
	// }

	// public function endExcelHeaderStyling( $objPHPExcel, $headerLastAlpha, $applyFillFontSetting = [], $applyCellBorder = [] ) {
	// 	if (empty($applyFillFontSetting)) {
	// 		$applyFillFontSetting = ['s'=>2, 'e'=>2];
	// 	}
	// 	if (empty($applyCellBorder)) {
	// 		$applyCellBorder = ['s'=>2, 'e'=>2];
	// 	}

	// 	$activeSheet = $objPHPExcel->getActiveSheet();
	// 	$activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
 //        $activeSheet->getStyle("A". $applyFillFontSetting['s'] .":". $headerLastAlpha . $applyFillFontSetting['e'])->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('6699CC'); // OpenEMIS Core product color
	// 	$activeSheet->getStyle("A". $applyCellBorder['s'] .":". $headerLastAlpha . $applyCellBorder['e'])->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
	// }

}
