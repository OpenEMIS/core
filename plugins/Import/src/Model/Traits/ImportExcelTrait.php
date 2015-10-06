<?php
namespace Import\Model\Traits;

use DateTime;
use DateInterval;
use ArrayObject;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;

/**
 * ImportExcelTrait is to be used with import_mapping table
 *
 * Functions that does not require ControllerActionComponent events, CakePHP events,
 * and not controller actions functions, resides here.
 * 
 * @author  hanafi <hanafi.ahmat@kordit.com>
 */
trait ImportExcelTrait {

	/**
	 * Check if all the columns in the row is not empty
	 * @param  WorkSheet $sheet      The worksheet object
	 * @param  integer $totalColumns Total number of columns to be checked
	 * @param  integer $row          Row number
	 * @return boolean               the result to be return as true or false
	 */
	protected function checkRowCells($sheet, $totalColumns, $row) {
		$cellsState = [];
		for ($col=0; $col < $totalColumns; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			if (empty($cell->getValue())) {
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
	protected function isCorrectTemplate($header, $sheet, $totalColumns, $row) {
		$cellsValue = [];
		for ($col=0; $col < $totalColumns; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			$cellsValue[] = $cell->getValue();
		}
		return $header === $cellsValue;
	}
	
	protected function getMapping(Table $model) {
		$mapping = $model->find('all')
			->where([
				$model->aliasField('model') => $this->config('model')
			])
			->order($model->aliasField('order'))
			->toArray();
		return $mapping;
	}
	
	protected function getHeader(Table $model) {
		$header = [];
		$mapping = $this->getMapping($model);
		
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
			if ($mappingRow->foreign_key == self::FIELD_OPTION) {
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
				$model->aliasField('foreign_key') . ' IN' => [self::FIELD_OPTION, self::DIRECT_TABLE]
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
			if ($foreignKey == self::FIELD_OPTION) {
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
			} else if ($foreignKey == self::DIRECT_TABLE) {
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
	
	protected function prepareDownload() {
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
	
	protected function performDownload($excelFile) {
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
		if ($module=='Import') {
			$translatedCol = $this->_table->getMessage($module.'.'.$columnName);
		} else {
			/**
			 * $language should provide the current selected locale language
			 */
			$language = '';
			$translatedCol = $this->_table->onGetFieldLabel(new Event($this), $module, $columnName, $language);
		}

		return __($translatedCol);
	}

	protected function _extractRecord($references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		// $references = [$sheet, $mapping, $columns, $lookup, $totalColumns, $row, $activeModel];
		$sheet = $references['sheet'];
		$mapping = $references['mapping'];
		$columns = $references['columns'];
		$lookup = $references['lookup'];
		$totalColumns = $references['totalColumns'];
		$row = $references['row'];
		$activeModel = $references['activeModel'];

		$rowPass = true;
		for ($col = 0; $col < $totalColumns; ++$col) {
			$cell = $sheet->getCellByColumnAndRow($col, $row);
			$originalValue = $cell->getValue();

			// need to understand this check
			$cellValue = $originalValue;
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
				if($activeModel->fields[$columnName]['type'] == 'date') {// checking the main table schema data type
					// if date value is not numeric, let it fail validation since using PHPExcel_Shared_Date::ExcelToPHP($val)
					// will actually converts the non-numeric value to today's date
					if (is_numeric($val)) {
						$val = date('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($val));
						// converts val to DateTime object so that this field will pass 'validDate' check since
						// different model has different date format checking. Example; user->date_of_birth
						// so it is best to convert the date here instead of adjusting individual model's date validation format
						try {
							$val = new DateTime($val);
							$originalRow[$col] = $val->format(TableRegistry::get('ConfigItems')->value('date_format'));
						} catch (Exception $e) {
						    $originalRow[$col] = $val;
						}
					}
				}
			}
			$translatedCol = $this->getExcelLabel($this->config('model'), $columnName);
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
				$excelLookupModel = TableRegistry::get($excelMappingObj->lookup_plugin . '.' . $excelMappingObj->lookup_model);
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
		return $rowPass;
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

	protected function post_upload_max_size() {
		return $this->parse_size(ini_get('post_max_size'));
	}

	protected function system_memory_limit() {
		return $this->parse_size(ini_get('memory_limit'));
	}
/**
 * 
 */

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

}
