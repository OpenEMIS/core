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

class ExportBehavior extends ModelBehavior {
	public $rootFolder = 'export';
	public $LabelHelper;
	public $Model;

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array) $settings);

		$this->LabelHelper = new LabelHelper(new View());
	}

	public function export(Model $model, $format='xlsx') {
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
		$sheet = 'Sheet1';
		$header = $model->exportGetHeader($model);//pr($header);die;
		$footer = $model->exportGetFooter($model);
		$data = $model->exportGetData($model);

		$filename = $model->exportGetFileName($model) . '_' . date('Ymd') . 'T' . date('His') . '.xlsx';
		$path = WWW_ROOT . $this->rootFolder . DS . $filename;

		$writer = new XLSXWriter();
		$writer->writeSheetRow($sheet, array_values($header));
		foreach ($data as $row) {
			$sheetRow = array();
			foreach ($header as $key => $label) {
				$value = $this->getValue($row, $key);
				$sheetRow[] = $value;
			}
			$writer->writeSheetRow($sheet, $sheetRow);
		}
		$writer->writeSheetRow($sheet, array(''));
		$writer->writeSheetRow($sheet, $footer);
		$writer->writeToFile($path);

		$this->download($path);
	}

	public function exportGetFileName(Model $model) {
		return $model->name;
	}

	public function exportGetHeader(Model $model) {
		$alias = $model->alias;
		$schema = array_keys($model->schema());

		$header = array();
		$exclude = array('id', 'modified_user_id', 'modified', 'created_user_id', 'created');

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
		foreach ($schema as $field) {

			if (!in_array($field, $exclude)) {
				$pos = strrpos($field, '_id');
				if ($pos !== false) {
					$fieldModel = Inflector::camelize(substr($field, 0, $pos));
					$associatedSchema = $model->{$fieldModel}->schema();
					if (array_key_exists('name', $associatedSchema)) {
						$key = $fieldModel.'.name';
					} else if (array_key_exists('title', $associatedSchema)) {
						$key = $fieldModel.'.title';
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

	public function exportGetData(Model $model) {
		$options = $model->exportGetFindOptions($model);
		
		$data = $model->find('all', $options);
		return $data;
	}

	public function exportGetFindOptions(Model $model) {
		$fields = array_keys($model->exportGetHeader($model));
		$conditions = $model->exportGetConditions($model);
		$contain = $this->getContain($fields);
		$order = $this->exportGetOrder($model);

		$options = array();
		$options['recursive'] = 0;
		$options['fields'] = $fields;
		$options['contain'] = $contain;
		$options['conditions'] = $conditions;
		$options['order'] = $order;

		return $options;
	}

	public function exportGetConditions(Model $model) {
		return array();
	}

	public function exportGetFieldLookup(Model $model) {
		return array();
	}

	public function exportGetOrder(Model $model) {
		return array();
	}

	public function exportGetFooter(Model $model) {
		$footer = array(__("Report Generated") . ": "  . date("Y-m-d H:i:s"));
		return $footer;
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

	private function getValue($row, $key) {
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

		$lookup = $this->Model->exportGetFieldLookup();
		if (!empty($lookup) && array_key_exists($key, $lookup)) {
			$values = $lookup[$key];
			if (strlen($value)>0 && array_key_exists($value, $values)) {
				$value = $values[$value];
			}
		}
		return $value;
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
