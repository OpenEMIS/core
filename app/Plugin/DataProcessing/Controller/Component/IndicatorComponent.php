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

App::uses('Component', 'Controller');

class IndicatorComponent extends Component {
	private $controller;
    private $indicatorsQueries;
    private $systemIndicatorPath;
    private $userIndicatorPath;
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

        if(Configure::read('xml.indicators.system.path')){
            $this->systemIndicatorPath = Configure::read('xml.indicators.system.path');
        }

        if(Configure::read('xml.indicators.custom.path')){
            $this->userIndicatorPath = Configure::read('xml.indicators.custom.path');
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
		foreach($indicators as $indicatorId) {
			if(!empty($onBeforeGenerate['callback'])) {
				if(!call_user_func_array($onBeforeGenerate['callback'], $onBeforeGenerate['params'])) {
					break;
				}
			}
			try {
				$this->generateIndicator($indicatorId, $userId);
			} catch(Exception $ex) {
				$error = $ex->getMessage();
				$this->Logger->write("Exception encountered while running indicator (" . $indicatorId . ")\n\n" . $error);
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
		$logFile = $this->Logger->end();
		return $logFile;
	}
	
	public function buildSQL($sql, $params) {
		$select = $params['select'];
		$join = $params['join'];
		$where = $params['where'];
		$group = $params['group'];
		
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
		$this->BatchIndicatorResult->truncate($id);
        $areaLevels = $this->AreaLevel->find('list', array('order' => 'level DESC'));
        $indicator = $this->BatchIndicator->find('first', array('conditions' => array('BatchIndicator.id' => $id)));
        $indicatorName = $indicator['BatchIndicator']['name'];
        $unitName = $indicator['BatchIndicator']['unit'];
        $path = ((isset($indicator['BatchIndicator']['type']) AND $indicator['BatchIndicator']['type'] == 'custom')? $this->userIndicatorPath: $this->systemIndicatorPath).$indicator['BatchIndicator']['filename'];
        if(!file_exists($path)) {
            throw new Exception("Error file do not exist in the location: {$path}");
        }
//        var_dump($path);
        $this->indicatorsQueries = simplexml_load_file($path);
        $indicatorXml = array_shift($this->indicatorsQueries->xpath('//indicator[@id='.$id.']'));

        if(is_null($indicatorXml)){
            $indicatorXml = array_shift($this->indicatorsQueries->xpath('//indicator'));
        }
        $query = $indicatorXml->query->mysql->insert;
//		$query = $indicator['BatchIndicator']['query']; # Read query from DB table

		$subgroupList = array();
//		$permutations = $this->BatchIndicatorSubgroup->generateSubgroups($id, $subgroupList);
        $permutations = $this->generateSubgroups($id, $subgroupList, $indicatorXml->subgroups->item);

        $permutationCounter = 0; # for debug
		
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
					$sql = str_replace('-- {LEVEL}', $levelId, $sql);
					
					// Start Logging
					//echo "Executing query (Subgroup: " . $subgroup . ")<br>";
					$logMsg = sprintf("\n\n----- %s (%s) (%s) -----\n\n", $indicatorName, $unitName, $subgroup);
					$logMsg .= $sql . "\n\n";
					$this->Logger->write($logMsg);
					// End Logging
					
					$result = $this->BatchIndicator->query($sql);
				}
			}
			
			$this->Logger->write(sprintf('(%s) Aggregate by Areas Start', $indicatorName));
			foreach($areaLevels as $levelId => $levelName) {
				$this->Logger->write(sprintf('(%s) Aggregate by level Id %d', $indicatorName, $levelId));
				$data = $this->BatchIndicatorResult->aggregateByAreaLevel($id, $levelId);
				foreach($data as $obj) {
					$this->BatchIndicatorResult->aggregateSave($obj, $unitName);
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
//                pr((string)$obj['reference']);
                $list = $model->findListAsSubgroups();
                //pr($list);

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
            $gradeIndex = null;
            if(isset($subgroupTypes['Grade'])){
            	$gradeIndex = $subgroupTypes['Grade'];
            }
            foreach($permutations as $obj) {
                foreach($ageList as $age => $attr) {
                	$grade = array();
                	if(!empty($gradeIndex)){
	                    $grade = $subgroups[key($obj[$gradeIndex])];
	                }
                    $newPermutation = $obj;
                    if(empty($grade) || $grade['id'] == 0) {
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

    private function isMaxPermutations(&$counter){
        $max = 5;
        if($counter > $max){
            return true;
        }else{
            $counter++;
            return false;
        }
    }
}
?>