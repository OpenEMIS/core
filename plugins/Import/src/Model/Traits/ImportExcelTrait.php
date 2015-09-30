<?php
namespace Import\Model\Traits;

use DateTime;
use DateInterval;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;


trait ImportExcelTrait {

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

}
