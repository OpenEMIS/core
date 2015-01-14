<?php

/*
  @OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

  OpenEMIS
  Open Education Management Information System

  Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by the Free Software Foundation
  , either version 3 of the License, or any later version.  This program is distributed in the hope
  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
  or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
  have received a copy of the GNU General Public License along with this program.  If not, see
  <http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
 */

App::import('Vendor', 'XLSXWriter', array('file' => 'XLSXWriter/xlsxwriter.class.php'));
App::uses('LabelHelper', 'View/Helper');

class ExcelBehavior extends ModelBehavior {
	public $rootFolder = 'export';
	public $LabelHelper;
	public $Model;
	public $conditions = array();

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array) $settings);

		$this->LabelHelper = new LabelHelper(new View());
	}

	public function setModel(Model $model, $newModel) {
		$this->Model = $newModel;
	}

	public function excel(Model $model, $format='xlsx') {
		$this->Model = $model;
		$folder = WWW_ROOT . $this->rootFolder;
		if (!file_exists($folder)) {
			umask(0);
			mkdir($folder, 0777);
		} else {
			$this->deleteOldFiles($folder, $format);
		}

		switch ($format) {
			case 'xlsx':
				$this->generateXLXS($model);
				break;
		}
	}

	private function deleteOldFiles($folder, $format) {
		$fileList = array_diff(scandir($folder), array('..', '.'));
		$now = new DateTime();
		// delete all old files that are more than one hour old
		$now->sub(new DateInterval('PT1H'));

		foreach ($fileList as $file) {
			$path = $folder.DS.$file;
			$timestamp = filectime($path);
			$date = new DateTime();
			$date->setTimestamp($timestamp);

			if ($now > $date) {
				if (!unlink($path)) {
					$this->log('Unable to delete ' . $path, 'export');
				}
			}
		}
	}

	public function generateXLXS(Model $model) {
		$filename = $this->Model->excelGetFileName($model) . '_' . date('Ymd') . 'T' . date('His') . '.xlsx';
		$path = WWW_ROOT . $this->rootFolder . DS . $filename;

		$writer = new XLSXWriter();

		$this->Model->generateSheet($writer);

		$writer->writeToFile($path);

		$this->download($path);
	}

	public function generateSheet(Model $model, $writer) {
		$models = $this->Model->excelGetModels();

		foreach ($models as $sheet) {
			$sheetModel = is_object($sheet['model']) ? $sheet['model'] : ClassRegistry::init($sheet['model']);
			$sheetName = array_key_exists('name', $sheet) ? __($sheet['name']) : $sheetModel->alias;

			if ($model->alias == $sheetModel->alias) {
				$this->conditions = $model->excelGetConditions();
			}
			$model->setModel($sheetModel);

			if (!$sheetModel->Behaviors->loaded('Excel')) {
				$sheetModel->Behaviors->load('Excel');
			}

			$header = $sheetModel->excelGetHeader();
			$footer = $sheetModel->excelGetFooter();
			$data = $sheetModel->excelGetData();

			$writer->writeSheetRow($sheetName, array_values($header));
			//pr($data);
			
			foreach ($data as $row) {
				$sheetRow = array();
				foreach ($header as $key => $label) {
					$value = $sheetModel->getValue($row, $key);
					$sheetRow[] = $value;
				}
				//pr($$sheetRow);
				$writer->writeSheetRow($sheetName, $sheetRow);
			}
			
			$writer->writeSheetRow($sheetName, array(''));
			$writer->writeSheetRow($sheetName, $footer);
			
		}//die;
	}

	public function excelGetModels(Model $model) {
		$models = array(
			array('name' => $this->Model->alias, 'model' => $this->Model)
		);
		return $models;
	}

	public function excelGetFileName(Model $model) {
		return $this->Model->name;
	}

	public function excelGetHeader(Model $model) {
		$model = $this->Model;
		$alias = $model->alias;
		$schema = $model->schema();

		$header = array();
		$exclude = array('id', 'photo_name', 'file_name', 'modified_user_id', 'modified', 'created_user_id', 'created');

		if (array_key_exists('header', $this->settings[$alias])) {
			$appendedHeader = $this->settings[$alias]['header'];
			foreach ($appendedHeader as $module => $fields) {
				foreach ($fields as $f) {
					$key = $module.'.'.$f;
					$label = $this->LabelHelper->get($key);
					if ($label === false) {
						$label = $key;
					}
					$header[$key] = __($label);
				}
			}
		}

		foreach ($schema as $field => $attr) {
			if (!in_array($field, $exclude) && $attr['type'] != 'binary') {
				$pos = strrpos($field, '_id');
				if ($pos !== false) {
					$fieldModel = Inflector::camelize(substr($field, 0, $pos));
					if (is_object($this->Model->{$fieldModel})) {
						$associatedSchema = $this->Model->{$fieldModel}->schema();
						if (array_key_exists('name', $associatedSchema)) {
							$key = $fieldModel.'.name';
						} else if (array_key_exists('title', $associatedSchema)) {
							$key = $fieldModel.'.title';
						}
					} else {
						$this->log($fieldModel . ' not found in ' . $this->Model->alias, 'debug');
					}
				} else {
					$key = $alias.'.'.$field;
				}
				$label = $this->LabelHelper->get($key);
				if ($label === false) {
					$label = $key;
				}
				$header[$key] = __($label);
			}
		}
		return $header;
	}

	public function excelGetData(Model $model) {
		$options = $this->Model->excelGetFindOptions();
		
		$data = $this->Model->find('all', $options);
		return $data;
	}

	public function excelGetFindOptions(Model $model) {
		$fields = array_keys($this->Model->excelGetHeader());
		$conditions = $this->Model->excelGetConditions();
		$contain = $this->getContain($fields);
		$order = $this->Model->excelGetOrder();

		$options = array();
		$options['recursive'] = 0;
		$options['fields'] = $fields;
		$options['contain'] = $contain;
		$options['conditions'] = $conditions;
		$options['order'] = $order;

		return $options;
	}

	public function excelGetConditions(Model $model) {
		return $this->conditions;
	}

	public function excelGetFieldLookup(Model $model) {
		return array();
	}

	public function excelGetOrder(Model $model) {
		return array();
	}

	public function excelGetFooter(Model $model) {
		$footer = array(__("Report Generated") . ": "  . date("Y-m-d H:i:s"));
		return $footer;
	}

	public function getValue(Model $model, $row, $key) {
		$index = explode('.', $key);
		$value = $row;
		foreach($index as $i) {
			if(isset($value[$i])) {
				$value = $value[$i];
			} else {
				$value = '';
				break;
			}
		}

		$lookup = $this->Model->excelGetFieldLookup();
		if (!empty($lookup) && array_key_exists($key, $lookup)) {
			$values = $lookup[$key];
			if (strlen($value)>0 && array_key_exists($value, $values)) {
				$value = $values[$value];
			}
		}
		return $value;
	}

	private function getContain($fields) {
		$contain = array();
		foreach ($fields as $field) {
			$split = explode('.', $field);
			if ($split[0] !== $this->Model->alias) {
				$contain[$split[0]] = array('fields' => $field);
			}
		}
		return $contain;
	}

	private function download($path) {
		$filename = basename($path);
		
		header("Pragma: public", true);
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=".$filename);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($path));
		echo file_get_contents($path);
	}
}
