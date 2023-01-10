<?php
namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Utility\Inflector;

class HighChartBehavior extends Behavior
{
    private $colors = [
        '#7D68D5',
        '#F1658D',
        '#D5AA68'
    ];

    public function initialize(array $config)
    {
    }

    public function getHighChart($chart, $params = [])
    {
        $model = $this->_table;
        $selectedConfig = $this->config($chart);
        $function = $selectedConfig['_function'];
        $defaultColors = isset($selectedConfig['_defaultColors']) ? $selectedConfig['_defaultColors'] : true;
        $params = call_user_func_array([$model, $function], [$params]);

        $dataSet = [];
        if (!empty($params['dataSet'])) {
            $dataSet = $params['dataSet'];
        }
        $options = [];
        if (!empty($params['options'])) {
            $options = $params['options'];
        }
        $selectedConfig['title'] = array('text' => __(Inflector::humanize($chart)));
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
        if (!array_key_exists('colors', $options) && !$defaultColors) {
            $options['colors'] = $this->colors;
        }
        return json_encode($options, JSON_NUMERIC_CHECK);
    }

    public function getDonutChart($chart, $params = array())
    {
        $model = $this->_table;
        $selectedConfig = $this->config($chart);
        $function = $selectedConfig['_function'];
        $defaultColors = isset($selectedConfig['_defaultColors']) ? $selectedConfig['_defaultColors'] : true;
        $params = call_user_func_array([$model, $function], [$params]);
        $dataSet = [];
        $key = '';
        if (!empty($params['dataSet'])) {
            $dataSet = $params['dataSet'];
            if (array_key_exists('key', $params)) {
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
        if (!array_key_exists('colors', $options) && !$defaultColors) {
            $options['colors'] = $this->colors;
        }
        return json_encode($options, JSON_NUMERIC_CHECK);
    }

    public function getSearchConditions($model, $searchString)
    {
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

    // Modification from advance name search behavior
    public function advanceNameSearch($model, $search)
    {
        $alias = $model->alias();

        $searchParams = explode(' ', $search);
        foreach ($searchParams as $key => $value) {
            if (empty($searchParams[$key])) {
                unset($searchParams[$key]);
            }
        }
        $conditions = [];
        // note that CONCAT_WS is not supported by cakephp and also not supported by some dbs like sqlite and mysqlserver, thus this condition
        if ($this->_table->connection()->config()['driver'] == 'Cake\Database\Driver\Mysql') {
            switch (count($searchParams)) {
                case 1:
                    // 1 word - search by openemis id or 1st or middle or third or last
                    $searchString = '%' . $search . '%';
                    $conditions['OR'] = [
                            $alias . '.openemis_no LIKE' => $searchString,
                            $alias . '.first_name LIKE' => $searchString,
                            $alias . '.middle_name LIKE' => $searchString,
                            $alias . '.third_name LIKE' => $searchString,
                            $alias . '.last_name LIKE' => $searchString
                        ];
                    break;

                case 2:
                    // 2 words - search by 1st and last name
                    $names = ["$alias.first_name", "$alias.last_name"];
                    $conditions = ['CONCAT_WS(" ", trim(' . $names[0] . '), trim(' . $names[1] . ') ) LIKE "%' . trim($search) . '%"'];
                    break;

                case 3:
                    // 3 words - search by 1st middle last
                    $names = ["$alias.first_name", "$alias.middle_name", "$alias.last_name"];
                    $conditions = ['CONCAT_WS(" ", trim(' . $names[0] . '), trim(' . $names[1] . '), trim(' . $names[2] . ') ) LIKE "%' . trim($search) . '%"'];
                    break;

                case 4:
                    // 4 words - search by 1st middle third last
                    $names = ["$alias.first_name", "$alias.middle_name", "$alias.third_name", "$alias.last_name"];
                    $conditions = ['CONCAT_WS(" ", trim(' . $names[0] . '), trim(' . $names[1] . '), trim(' . $names[2] . '), trim(' . $names[3] . ') ) LIKE "%' . trim($search) . '%"'];
                    break;

                default:
                    foreach ($searchParams as $key => $value) {
                        $searchString = '%' . $value . '%';
                        $conditions['OR'] = [
                                $alias . '.openemis_no LIKE' => $searchString,
                                $alias . '.first_name LIKE' => $searchString,
                                $alias . '.middle_name LIKE' => $searchString,
                                $alias . '.third_name LIKE' => $searchString,
                                $alias . '.last_name LIKE' => $searchString
                            ];
                    }
                    break;
            }
        } else {
            foreach ($searchParams as $key => $value) {
                $searchString = '%' . $value . '%';
                $conditions['OR'] = [
                        $alias . '.openemis_no LIKE' => $searchString,
                        $alias . '.first_name LIKE' => $searchString,
                        $alias . '.middle_name LIKE' => $searchString,
                        $alias . '.third_name LIKE' => $searchString,
                        $alias . '.last_name LIKE' => $searchString
                    ];
            }
        }
        return $conditions;
    }
}
