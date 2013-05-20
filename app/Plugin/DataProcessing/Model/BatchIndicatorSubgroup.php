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

App::uses('AppModel', 'Model');

class BatchIndicatorSubgroup extends DataProcessingAppModel {
	public $dataSet = array(
		'subgroupList' => array()
	);
	
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
	
	public function getSubgroupTypes($indicatorId) {
		$class = 'BatchIndicatorSubgroup';
		$list = $this->find('all', array(
			'conditions' => array($class.'.batch_indicator_id' => $indicatorId),
			'order' => array($class.'.order', $class.'.name')
		));
		
		$subgroupTypes = array();
		foreach($list as $item) {
			$obj = $item[$class];
			$type = $obj['type'];
			$name = $obj['name'];
			$order = $obj['order'];
			$reference = $obj['reference'];
			
			if(!isset($subgroupTypes[$type])) {
				$subgroupTypes[$type] = array('order' => $order);
			}
			$subgroupTypes[$type][] = $name;
			
			if(!is_null($obj['reference'])) {
				$model = ClassRegistry::init($obj['reference']);
				$list = $model->findListAsSubgroups();
				
				if($type!=='Age') {
					foreach($list as $key => $value) {
						$subgroupTypes[$type][$key] = $value;
					}
				} else {
					foreach($list as $key => $value) {
						$subgroupTypes[$type][$key] = $type . ' ' . $key;
					}
				}
			}
		}
		return $subgroupTypes;
	}
	
	public function generateSubgroups($indicatorId, &$subgroups) {
		$class = 'BatchIndicatorSubgroup';
		$list = $this->find('all', array(
			'conditions' => array($class.'.batch_indicator_id' => $indicatorId),
			'order' => array($class.'.order', $class.'.name')
		));
		
		$index = 0;
		$subgroupIndex = 0;
		$subgroups = array();
		$subgroupTypes = array();
		$permutationList = array();
		$ageList = array();
		
		foreach($list as $item) {
			$obj = $item[$class];
			$type = $obj['type'];
			$name = $obj['name'];
			$where = $obj['_where'];
			
			if(!isset($subgroupTypes[$type])) {
				$subgroupTypes[$type] = $index++;
			}
			
			$subgroups[$subgroupIndex] = array(
				'id' => 0,
				'name' => $name,
				'type' => $type,
				'select' => $obj['_select'],
				'join' => $obj['_join'],
				'where' => $where,
				'group' => $obj['_group']
			);
			
			$permutationList[$subgroupTypes[$type]][] = array($subgroupIndex++ => $name);
			
			if(!is_null($obj['reference'])) {
				$model = ClassRegistry::init($obj['reference']);
				$list = $model->findListAsSubgroups();
				
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
						'select' => $obj['_select'],
						'join' => $obj['_join'],
						'where' => $whereClause,
						'group' => $obj['_group']
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
		
		//pr($permutations);
		//pr($ageList);
		//pr($subgroups);
		//pr($permutations);
		//die;
		return $permutations;
	}
}
