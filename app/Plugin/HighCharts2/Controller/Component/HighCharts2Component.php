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

class HighCharts2Component extends Component {
	private $controller;
	private $categories;
	private $series;

	public $components = array('Session', 'Message', 'Auth');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
	}

	public function initHighChartData($mapping=array()) {
		$this->categories = array();
		$this->series = array();

		if (isset($mapping['xAxis'])) {
			foreach ($mapping['xAxis'] as $key => $value) {
				$this->series[] = array(
					'id' => $key,
					'name' => $value,
					'data' => array()
				);
			}
		}
	}

	public function getHighChartData($chartDataSet=array(), $chartOptions=array(), $mapping=array()) {
		$this->initHighChartData($mapping);
		$categoryNames = isset($mapping['yAxis']) ? $mapping['yAxis'] : array();

		$index = 0;
		foreach ($chartDataSet as $key => $obj) {
			if (!in_array($key, $this->categories)) {
				$this->categories[$index] = array_key_exists($key, $categoryNames)? $categoryNames[$key] : $key;
			}

			foreach ($obj as $key2 => $value2) {
				foreach ($this->series as $key3 => $value3) {
					if ($this->series[$key3]['id'] == $key2) {
						$this->series[$key3]['data'][] = $value2;
					}
				}
			}
			$index++;
		}

		$_chartOptions = array();
		$_chartOptions['xAxis']['categories'] = $this->categories;
		$_chartOptions['series'] = $this->series;
		$chartOptions = array_merge($chartOptions, $_chartOptions);
		
		return  json_encode($chartOptions, JSON_NUMERIC_CHECK);
	}
}
