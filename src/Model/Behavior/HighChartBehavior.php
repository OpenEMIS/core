<?php
namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Utility\Inflector;

class HighChartBehavior extends Behavior {

	public function initialize(array $config) {}

	public function getHighChart($chart, $params = []) {
		$model = $this->_table;
		$selectedConfig = $this->config($chart);
		$function = $selectedConfig['_function'];
		$params = call_user_func_array([$model, $function], [$params]);

		$dataSet = [];
		if (!empty($params['dataSet'])) {
			$dataSet = $params['dataSet'];
		}
		$options = [];
		if (!empty($params['options'])) {
			$options = $params['options'];
		}
		$selectedConfig['title'] = array('text' => Inflector::humanize($chart));
		//pr($selectedConfig);
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
		pr($dataSet);
		$options['series'] = array_values($dataSet);
		$options['credits'] = ['enabled' => false];
		return json_encode($options, JSON_NUMERIC_CHECK);
	}

	public function getDonutChart($chart, $params = array()) {
		$model = $this->_table;
		$chart = 'donut';
		$selectedConfig = $this->config($chart);
		$function = $selectedConfig['_function'];
		$params = call_user_func_array([$model, $function], [$params]);

		$dataSet = [];
		if (!empty($params['dataSet'])) {
			$dataSet = $params['dataSet'];
		}

		$options = [];
		if (!empty($params['options'])) {
			$options = $params['options'];
		}
		$selectedConfig['title'] = ['text' => Inflector::humanize($chart), 'floating'=>true, 'align' => 'center',
            'verticalAlign' => 'top', 'style' => ['fontSize' => '14px']];

		unset($selectedConfig['_function']);
		$options = array_replace_recursive($selectedConfig, $options);
		$options['series'][] = ['type' => 'pie', 'innerSize' => '50%', 'data' => array_values($dataSet)];
		$options['credits'] = ['enabled' => false];
		return json_encode($options, JSON_NUMERIC_CHECK);
	}

}
