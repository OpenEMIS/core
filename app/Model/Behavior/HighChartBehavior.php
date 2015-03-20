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

class HighChartBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array) $settings);
	}

	public function getHighChart(Model $model, $chart, $params = array()) {
		$function = $this->settings[$model->alias][$chart]['_function'];
		$params = call_user_func_array(array($model, $function), array($params));

		if (!empty($params['dataSet'])) {
			$dataSet = $params['dataSet'];
		}

		$options = array();
		if (!empty($params['options'])) {
			$options = $params['options'];
		}
		$_options = $this->settings[$model->alias][$chart];
		$_options['title'] = array('text' => Inflector::humanize($chart));
		unset($_options['_function']);
		$options = array_replace_recursive($_options, $options);

		if (!empty($dataSet)) {
			if (empty($options['xAxis']['categories'])) {
				$firstItem = current($dataSet);
				$options['xAxis']['categories'] = array_keys($firstItem['data']);
			}
		}

		foreach ($dataSet as $key => $obj) {
			$dataSet[$key]['data'] = array_values($obj['data']);
		}

		$options['series'] = array_values($dataSet);

		return json_encode($options, JSON_NUMERIC_CHECK);
	}
}
