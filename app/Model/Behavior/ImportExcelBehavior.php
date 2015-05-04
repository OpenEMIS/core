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

class ImportExcelBehavior extends ModelBehavior {
	public $rootFolder = 'import';
	public $LabelHelper;
	public $Model;
	public $MappingModel;
	public $limit = 500;

	public function setup(Model $Model, $settings = array()) {
		$this->MappingModel = ClassRegistry::init('ImportMapping');

		$this->LabelHelper = new LabelHelper(new View());
	}
	
	public function getMapping(Model $model){
		$mapping = $this->MappingModel->find('all', array(
			'conditions' => array($this->MappingModel->alias.'.model' => $model->alias),
			'order' => array($this->MappingModel->alias.'.order')
		));
		
		return $mapping;
	}
	
	public function getHeader(Model $model){
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
	
	public function getColumns(Model $model){
		$columns = array();
		$mapping = $this->getMapping($model);
		
		foreach($mapping as $key => $value){
			$column = $value[$this->MappingModel->alias]['column_name'];
			$columns[] = $column;
		}

		return $columns;
	}
	
	public function getExcelLabel(Model $model, $key) {
		$label = $this->LabelHelper->get($key);
		return $label;
	}
	
	public function prepareDownload(Model $model){
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
		
		return $folder;
	}
	
	public function performDownload(Model $model, $excelFile){
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

	public function downloadTemplate(Model $model) {
		$folder = $this->prepareDownload($model);
		$excelFile = sprintf('%s_%s_%s.xlsx', $this->getExcelLabel($model, 'general.import'), $this->getExcelLabel($model, 'general.'.  strtolower($model->alias)), $this->getExcelLabel($model, 'general.template'));
		$excelPath = $folder . DS . $excelFile;

		$writer = new XLSXWriter();
		
		$header = $model->getHeader($model);
		$writer->writeSheetRow('sheet1', array_values($header));
		$writer->writeToFile($excelPath);
		$this->performDownload($model, $excelFile);
	}
	
	public function getSupportedFormats(Model $model) {
		$formats = array(
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/zip'
		);
		return $formats;
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
}
