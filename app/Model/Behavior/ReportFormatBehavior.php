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

class ReportFormatBehavior extends ModelBehavior {

	public $formatMapping = array(
		'csv' => 'generateCSV'
	);

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array) $settings);
		if (!method_exists($Model, 'reportsGetData')) {
			pr('reportsGetData must be implemented.');
		}
	}

	public function getSupportedFormats(Model $model) {
		$formats = $this->settings[$model->alias]['supportedFormats'];
		$mapping = $this->formatMapping;

		foreach ($mapping as $key => $function) {
			if (!in_array($key, $formats)) {
				unset($mapping[$key]);
			}
		}
		return $mapping;
	}

	public function generateCSV(Model $model, $args=array()) {
		if(isset($args['dataFormatted'])){
			$dataFormatted = $args['dataFormatted'];
		}else{
			$dataFormatted = false;
		}
		
		$data = $model->reportsGetData($args);
		//pr($data);
		$header = $model->reportsGetHeader($args);
		//pr($header);die;
		$fileName = $model->reportsGetFileName($args);

		$downloadedFile = $fileName . '.csv';

        ini_set('max_execution_time', 600); //increase max_execution_time to 10 min if data set is very large

        $csv_file = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename="' . $downloadedFile . '"');

        $header_row = $header;
		if(!empty($header_row)){
			fputcsv($csv_file, $header_row, ',', '"');
		}

		if($dataFormatted){
			foreach ($data as $row) {
				fputcsv($csv_file, $row, ',', '"');
			}
		}else{
			foreach ($data as $arrSingleResult) {
				$row = array();
				foreach ($arrSingleResult as $table => $arrFields) {

					foreach ($arrFields as $col) {
						$row[] = $col;
					}
				}

				fputcsv($csv_file, $row, ',', '"');
			}
		}
       
        $footer = array("Report Generated: " . date("Y-m-d H:i:s"));
        fputcsv($csv_file, array(), ',', '"');
        fputcsv($csv_file, $footer, ',', '"');
        
        fclose($csv_file);
	}

	public function getFormatFunction(Model $model, $format) {
		if (array_key_exists($format, $this->formatMapping)) {
			return $this->formatMapping[$format];
		}
		return false;
	}

	public function getCSVFields(Model $model, $mappingFields) {
		$new = array();

		foreach ($mappingFields as $model => &$arrcols) {
			foreach ($arrcols as $col => $value) {
				if (strpos(substr($col, 0, 4), 'SUM(') !== false) {
					$new[] = substr($col, 0, 4) . $model . "." . substr($col, 4);
				} else if (strpos(substr($col, 0, 13), 'GROUP_CONCAT(') !== false) {
					$new[] = $col;
				} else if (strpos(substr($col, 0, 13), 'COALESCE(SUM(') !== false) {
					$new[] = substr($col, 0, 13) . $model . "." . substr($col, 13);
				} else {
					$new[] = $model . "." . $col;
				}
			}
		}
		return $new;
	}

	public function getCSVHeader(Model $model, $mappingFields) {
		//'QA Report' is an exception
		$new = array();
		foreach ($mappingFields as $model => &$arrcols) {

			foreach ($arrcols as $col => $value) {
				if (substr($model, -11) == 'CustomField') {
					$new[] = __(Inflector::humanize($col));
				} else {
					if (empty($value)) {
						$new[] = __(Inflector::humanize(Inflector::underscore($model))) . ' ' . __(Inflector::humanize($col));
					} else {
						$new[] = __($value);
					}
				}
			}
		}
		return $new;
	}
	
	// copied from DatetimeComponent
	public function getConfigDateFormat() {
		$format = '';
		if (isset($_SESSION['Config.DateFormat'])) {
			$format = $_SESSION['Config.DateFormat'];
		} else {
			$configItem = ClassRegistry::init('ConfigItem');
			$format = $configItem->getValue('date_format');
			$_SESSION['Config.DateFormat'] = $format;
		}
		return $format;
	}

	// copied from DatetimeComponent
	public function formatDateByConfig(Model $model, $date) {
		$format = $this->getConfigDateFormat();
		$output = null;
		if ($date == '0000-00-00' || $date == '') {
			$output = '';
		} else {
			$date = new DateTime($date);
			$output = $date->format($format);
		}
		return $output;
	}
	
	public function formatGender($value) {
		return ($value == 'F') ? __('Female') : __('Male');
	}

}
