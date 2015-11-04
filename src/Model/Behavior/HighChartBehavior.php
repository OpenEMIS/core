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
		$selectedConfig['tooltip'] = ['useHTML'=>true];
		$selectedConfig['legend'] = ['useHTML'=>true];
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
		$options['credits'] = ['enabled' => false];
		return json_encode($options, JSON_NUMERIC_CHECK);
	}

	public function getDonutChart($chart, $params = array()) {
		$model = $this->_table;
		$selectedConfig = $this->config($chart);
		$function = $selectedConfig['_function'];
		$params = call_user_func_array([$model, $function], [$params]);

		$dataSet = [];
		$key = '';
		if (!empty($params['dataSet'])) {
			$dataSet = $params['dataSet'];
			if(array_key_exists('key', $params)){
				$key = $params['key'];

			}
		}
		$options = [];
		if (!empty($params['options'])) {
			$options = $params['options'];
		}
		// Configuration for the donut chart
		$selectedConfig['title'] = 
					['text' => __($key),
					'align' => 'center',
					'verticalAlign' => 'middle',
					'x' => '-40',
					'y' => '11',
					'style' => ['fontSize' => '12px', 'fontWeight'=> '400']];
		$selectedConfig['chart'] = 
					['backgroundColor' => 'rgba(255, 255, 255, 0.002)',
					'margin' => 0,
					'spacingTop' => -5,
					'spacingBottom' => 10,
					'spacingLeft' => 90];
		$selectedConfig['tooltip'] = ['pointFormat' => '{point.y}', 'useHTML'=>true];
		$selectedConfig['plotOptions'] = 
					['pie' => ['dataLabels' => [
							'enabled' => false],
							'showInLegend' => false,
							'center' => ['50%', '50%']]];
		$selectedConfig['legend'] = 
					['enabled' => false,
					'verticalAlign' => 'bottom',
					'useHTML'=>true,
					'align' => 'left',
					'layout' => 'vertical',
					'itemStyle' => ['fontSize' => '8pt']];
		unset($selectedConfig['_function']);
		$options = array_replace_recursive($selectedConfig, $options);
		$options['series'][] = ['type' => 'pie', 'innerSize' => '85%', 'data' => array_values($dataSet)];
		$options['credits'] = ['enabled' => false];
		return json_encode($options, JSON_NUMERIC_CHECK);
	}

	public function getSearchConditions($model, $searchString) {
		$schema = $this->_table->ControllerAction->getSchema($model);
		$conditions = [];
		$OR = [];
		foreach ($schema as $name => $obj) {
			if ($obj['type'] == 'string' && $name != 'password') {
				$OR[$model->aliasField("$name").' LIKE'] = '%' . $searchString . '%';
			}
		}
		if (!empty($OR)) {
			$conditions = ['OR' => $OR];
		}
		return $conditions;
	}

}
