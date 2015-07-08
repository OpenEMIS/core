<?php
namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Utility\Inflector;

class HighChartBehavior extends Behavior {

	public function initialize(array $config) {}

	public function getHighChart($chart, $params = array()) {
		$model = $this->_table;
		$selectedConfig = $this->config($chart);
		$function = $selectedConfig['_function'];
		$params = call_user_func_array(array($model, $function), array($params));

		$dataSet = [];
		if (!empty($params['dataSet'])) {
			$dataSet = $params['dataSet'];
		}

		$options = [];
		if (!empty($params['options'])) {
			$options = $params['options'];
		}
		$selectedConfig['title'] = array('text' => Inflector::humanize($chart));
		unset($selectedConfig['_function']);
		$options = array_replace_recursive($selectedConfig, $options);

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
