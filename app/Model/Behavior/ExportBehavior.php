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
		$this->Model = $Model;
	}

	public function export(Model $model, $format='xlsx') {
		if (!file_exists(WWW_ROOT . $this->rootFolder)) {
			umask(0);
			mkdir(WWW_ROOT . $this->rootFolder, 0777);
		}

		switch ($format) {
			case 'xlsx':
				$this->generateXLXS($model);
				break;
		}
	}

	public function generateXLXS(Model $model) {
		$sheet = 'Sheet1';
		$header = $model->exportGetHeader($model);
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

		foreach ($schema as $field) {
			if (!in_array($field, $exclude)) {
				$pos = strrpos($field, '_id');
				if ($pos !== false) {
					$fieldModel = Inflector::camelize(substr($field, 0, $pos));
					$key = $fieldModel.'.name';
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
		$conditions = $model->exportGetConditions($model);

		if (!empty($conditions)) {
			$options['conditions'] = $conditions;
		}
		$data = $model->find('all', $options);
		return $data;
	}

	public function exportGetFindOptions(Model $model) {
		$fields = array_keys($model->exportGetHeader($model));
		$contain = $this->getContain($fields);

		$options = array();
		$options['recursive'] = 0;
		$options['fields'] = $fields;
		$options['contain'] = $contain;
		return $options;
	}

	public function exportGetConditions(Model $model) {

	}

	public function exportGetFieldLookup(Model $model) {
		
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
