<?php
App::uses('Component', 'Controller');

class IndicatorComponent extends Component {
	private $controller;
    private $indicatorsQueries;
    private $di6XmlPath;
	public $Area;
	public $AreaLevel;
	public $BatchIndicator;
	public $BatchIndicatorSubgroup;
	public $BatchIndicatorResult;
	
	public $components = array('Logger', 'Utility');
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->init();
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) { }
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) { }
	
	//called after Controller::render()
	public function shutdown(Controller $controller) { }
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) { }
	
	public function init() {
		$this->Area = ClassRegistry::init('Area');
		$this->AreaLevel = ClassRegistry::init('AreaLevel');
		$this->BatchIndicator = ClassRegistry::init('DataProcessing.BatchIndicator');
		$this->BatchIndicatorSubgroup = ClassRegistry::init('DataProcessing.BatchIndicatorSubgroup');
		$this->BatchIndicatorResult = ClassRegistry::init('DataProcessing.BatchIndicatorResult');
		
		$this->Logger->init('indicator');

        if(Configure::read('xml.indicators.query_path') && Configure::read('xml.indicators.filename')){
            $this->di6XmlPath = Configure::read('xml.indicators.query_path').Configure::read('xml.indicators.filename');
        }else{
            $this->di6XmlPath = APP.'Config'.DS.'indicatorQueries.xml';
        }
	}
	
	public function run($settings=array()) {
		$indicatorList = $this->BatchIndicator->find('list', array('conditions' => array('BatchIndicator.enabled' => 1)));
		$_settings = array(
			'userId' => 0,
			'indicators' => array_keys($indicatorList),
			'onBeforeGenerate' => array('callback' => array(), 'params' => array()),
			'onAfterGenerate' => array('callback' => array(), 'params' => array()),
			'onError' => array('callback' => array(), 'params' => array())
		);
		$_settings = array_merge($_settings, $settings);
		
		$indicators = $_settings['indicators'];		
		$onBeforeGenerate = $_settings['onBeforeGenerate'];
		$onAfterGenerate = $_settings['onAfterGenerate'];
		$onError = $_settings['onError'];
		$userId = $_settings['userId'];
	
		$this->Logger->start();
        try{
            if(!file_exists($this->di6XmlPath)) throw new Exception("Error file do not exist in the location: {$this->di6XmlPath}");

            $this->indicatorsQueries = simplexml_load_file($this->di6XmlPath);
            pr($indicators);
            foreach($indicators as $indicatorId) {
                if(!empty($onBeforeGenerate['callback'])) {
                    if(!call_user_func_array($onBeforeGenerate['callback'], $onBeforeGenerate['params'])) {
                        break;
                    }
                }
                try {
                    $this->BatchIndicatorResult->truncate($indicatorId);
                    $this->generateIndicator($indicatorId, $userId);
                } catch(Exception $ex) {
                    $error = $ex->getMessage();
                    $this->Logger->write("Exception encountered while running indicator (" . $indicatorId . ") ".PHP_EOL."File: ". $ex->getFile() . ' Line: ' . $ex->getLine() . PHP_EOL /*. $error*/);
                    $this->Logger->write($error);
                    $logFile = $this->Logger->end();

                    if(!empty($onError['callback'])) {
                        $params = array_merge($onError['params'], array($logFile));
                        if(!call_user_func_array($onError['callback'], $params)) {
                            break;
                        }
                    }
                }
                if(!empty($onAfterGenerate['callback'])) {
                    if(!call_user_func_array($onAfterGenerate['callback'], $onAfterGenerate['params'])) {
                        break;
                    }
                }
            }
        }catch (Exception $e) {
            $this->Logger->write($e->getMessage());
        }
		$logFile = $this->Logger->end();
		return $logFile;
	}
	
	public function buildSQL($sql, $params) {
//		$select = (string) (isset($item->mysql->select) AND !empty($item->mysql->select))? $item->mysql->select: null;
//		$join = (string) (isset($item->mysql->join) AND !empty($item->mysql->join))? $item->mysql->join: null;
//		$where = (string) (isset($item->mysql->where) AND !empty($item->mysql->where))? $item->mysql->where:null;
//		$group = (string) (isset($item->mysql->group) AND !empty($item->mysql->group))? $item->mysql->group:null;
        $select = empty($params['select'])? null : trim($params['select']);
        $join = empty($params['join'])? null : trim($params['join']);
        $where = empty($params['where'])? null : trim($params['where']);
        $group = empty($params['group'])? null : trim($params['group']);

		if(!is_null($select)) {
			$sql = str_replace('-- {SELECT}', $select . "\n-- {SELECT}", $sql);
		}
		
		if(!is_null($join)) {
			$sql = str_replace('-- {JOIN}', $join . "\n-- {JOIN}", $sql);
		}
		
		if(!is_null($where) && strpos($where, '{KEY}') === false) {
			$sql = str_replace('-- {WHERE}', $where . "\n-- {WHERE}", $sql);
		}
		
		if(!is_null($group)) {
			$sql = str_replace('-- {GROUP}', $group . "\n-- {GROUP}", $sql);
		}
		return $sql;
	}
	
	public function generateIndicator($id, $userId=0) {
        $indicatorXml = array_shift($this->indicatorsQueries->xpath('//indicator[@id='.$id.']'));
		$areaLevels = $this->AreaLevel->find('list', array('order' => 'level DESC'));
		$indicator = $this->BatchIndicator->find('first', array('conditions' => array('BatchIndicator.id' => $id)));
		$indicatorName = $indicator['BatchIndicator']['name'];
		$unitName = $indicator['BatchIndicator']['unit'];
//        $query = $indicator['BatchIndicator']['query'];
		$query = $indicatorXml->query->mysql->insert;
		
		$subgroupList = array();
//		$permutations = $this->BatchIndicatorSubgroup->generateSubgroups($id, $subgroupList);
		$permutations = $this->BatchIndicatorSubgroup->generateSubgroups($id, $subgroupList, $indicatorXml->subgroups->item);

		if(strpos($query, '-- {LEVEL}') === false) { // query does not execute per area level
			foreach($permutations as $pattern) {
				$sql = $query;
				$subgroups = array();
				foreach($pattern as $s) {
					$params = $subgroupList[key($s)];
					$subgroups[] = current($s);
					$sql = $this->buildSQL($sql, $params);
				}
				$subgroup = implode(' - ', $subgroups);
				$sql = str_replace('-- {INDICATOR_ID}', $id, $sql);
				$sql = str_replace('-- {SUBGROUPS}', "'" . $subgroup . "'", $sql);
				$sql = str_replace('-- {USER_ID}', $userId, $sql);
				
				// Start Logging
				//echo "Executing query (Subgroup: " . $subgroup . ")<br>";
				$logMsg = sprintf("\n\n----- %s (%s) (%s) -----\n\n", $indicatorName, $unitName, $subgroup);
				$logMsg .= $sql . "\n\n";
				$this->Logger->write($logMsg);
				// End Logging
				
				$result = $this->BatchIndicator->query($sql);
			}
		} else {
			foreach($areaLevels as $levelId => $levelName) {
//                echo '====================='.PHP_EOL;
//                echo 'level id: ' . $levelId . PHP_EOL;
//                echo 'level name: ' . $levelName . PHP_EOL;
				foreach($permutations as $pattern) {
//                    var_dump($pattern);
					$sql = $query;
					$subgroups = array();
					foreach($pattern as $s) {
//                        echo '---> s: ' . var_dump($s);
                        $params = $subgroupList[key($s)];
//                        echo '---> params: ' . var_dump($params);
                        $subgroups[] = current($s);
//                        echo '---> params: ' . var_dump($subgroups);
                        $sql = $this->buildSQL($sql, $params);
//                        echo 'Query: ' . $sql . PHP_EOL;
					}
					$subgroup = implode(' - ', $subgroups);
					$sql = str_replace('-- {INDICATOR_ID}', $id, $sql);
					$sql = str_replace('-- {SUBGROUPS}', "'" . $subgroup . "'", $sql);
					$sql = str_replace('-- {USER_ID}', $userId, $sql);
					$sql = str_replace('-- {LEVEL}', $levelId, $sql);
					
					// Start Logging
					//echo "Executing query (Subgroup: " . $subgroup . ")<br>";
					$logMsg = sprintf("\n\n----- %s (%s) (%s) -----\n\n", $indicatorName, $unitName, $subgroup);
					$logMsg .= $sql . "\n\n";
					$this->Logger->write($logMsg);
					// End Logging

//                    throw new Exception('GG man...');
					$result = $this->BatchIndicator->query($sql);
				}
			}
		}
	}

    public function generateSubgroups($indicatorId, &$subgroups, SimpleXMLElement $list) {
        $class = 'BatchIndicatorSubgroup';
        $index = 0;
        $subgroupIndex = 0;
        $subgroups = array();
        $subgroupTypes = array();
        $permutationList = array();
        $ageList = array();

        foreach($list as $item) {
            $obj = $item;
            $type = (string) $obj['type'];
            $name = (string) $obj['name'];
            $where = (string) $obj->mysql->where;

            if(!isset($subgroupTypes[$type])) {
                $subgroupTypes[$type] = $index++;
            }

            $subgroups[$subgroupIndex] = array(
                'id' => 0,
                'name' => $name,
                'type' => $type,
                'select' => (string) $obj->mysql->select,
                'join' => (string) $obj->mysql->join,
                'where' => $where,
                'group' => (string) $obj->mysql->group
            );

            $permutationList[$subgroupTypes[$type]][] = array($subgroupIndex++ => $name);

            if(!is_null($obj['reference']) AND !empty($obj['reference'])) {
                $model = ClassRegistry::init((string)$obj['reference']);
                $list = $model->findListAsSubgroups();
                pr($list);

                if($type==='Age') {
                    $ageList = $list;
                }

                foreach($list as $key => $value) {
                    if(!is_null($where) && strpos($where, '{KEY}') !== false) {
                        $whereClause = str_replace('{KEY}', $key, $where);
                    }

                    $subgroups[$subgroupIndex] = array(
                        'id' => $key,
                        'name' => $type==='Age' ? ('Age ' . $key) : $value,
                        'type' => $type,
                        'select' => (string) $obj->mysql->select,
                        'join' => (string) $obj->mysql->join,
                        'where' => $whereClause,
                        'group' => (string) $obj->mysql->group
                    );
                    if($type !== 'Age') {
                        $permutationList[$subgroupTypes[$type]][] = array($subgroupIndex => $value);
                    } else {
                        $ageList[$key]['index'] = $subgroupIndex;
                    }
                    $subgroupIndex++;
                }
            }
        }

        $permutations = $this->permutate($permutationList);

        // To add age permutations into the list
        if(sizeof($ageList) > 0) {
            $ageIndex = $subgroupTypes['Age'];
            $gradeIndex = $subgroupTypes['Grade'];
            foreach($permutations as $obj) {
                foreach($ageList as $age => $attr) {
                    $grade = $subgroups[key($obj[$gradeIndex])];
                    $newPermutation = $obj;
                    if($grade['id'] == 0) {
                        $newPermutation[$ageIndex] = array($attr['index'] => $subgroups[$attr['index']]['name']);
                        $permutations[] = $newPermutation;
                    } else {
                        if(in_array($grade['id'], $attr['grades'])) {
                            $newPermutation[$ageIndex] = array($attr['index'] => $subgroups[$attr['index']]['name']);
                            $permutations[] = $newPermutation;
                        }
                    }
                }
            }
        }
        // end age permutations

        return $permutations;
    }

    public function permutate($array) {
        $permutations = array();
        $iter = 0;

        while(1) {
            $num = $iter++;
            $pick = array();

            for($i=0; $i<sizeof($array); $i++) {
                $groupSize = sizeof($array[$i]);
                $r = $num % $groupSize;
                $num = ($num - $r) / $groupSize;
                array_push($pick, $array[$i][$r]);
            }
            if($num > 0) break;

            array_push($permutations, $pick);
        }
        return $permutations;
    }
}
?>
