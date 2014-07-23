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
App::uses('UtilityComponent', 'Component');

class PublicExpenditureEducationLevel extends AppModel {

	public $useTable = 'public_expenditure_education_level';
	public $belongsTo = array('Area', 'EducationLevel');

	public function getEducationLevels()
	{
		$utility = new UtilityComponent(new ComponentCollection);
		$this->EducationLevel->unbindModel(
  			array('hasMany' => array('EducationCycle'))
  		);
		$educationLevels = $this->EducationLevel->find('all', array(
				'fields' => array('EducationLevel.id', 'EducationLevel.name')
		));
		
		$educationLevels = $utility->formatResult($educationLevels);
		return $educationLevels;
	}
	
	public function getEducationLevelOptions() {
		$educationLevels = $this->EducationLevel->find('list', array(
			'recursive' => -1,
			'fields' => array('EducationLevel.id', 'EducationLevel.name')
		));

		return $educationLevels;
	}
	
	public function getEducationLevel($eduLevelId) {
		$educationLevel = $this->EducationLevel->find('first', array(
			'recursive' => -1,
			'fields' => array('EducationLevel.id', 'EducationLevel.name'),
			'conditions' => array(
				'EducationLevel.id' => $eduLevelId
			)
		));

		return $educationLevel;
	}

	public function getAreas($areaId = 0, $parentAreaId = 0) {

		if($parentAreaId != $areaId) {
			$areaId = $parentAreaId;
		}
		if (!$this->isWard($areaId)) {

			$options = array(
            	'fields' => array('Area.id', 'Area.name', 'Area.parent_id', 'Area.area_level_id', 'AreaLevel.name as area_level_name'),
				'conditions' => array(
					'OR' => array(
				        array('Area.id' => $areaId),
				        array('AND' => array("Area.visible" => 1, "Area.parent_id" => $parentAreaId))
				    )
				)
			);
			$areas = $this->Area->find('all', $options);

		} else {

			$options = array(
            	'fields' => array('Area.id', 'Area.name', 'Area.parent_id', 'Area.area_level_id', 'AreaLevel.name as area_level_name'),
				'conditions' => array(
					'OR' => array(
				        array('Area.id' => $areaId),
				        array('AND' => array("Area.visible" => 1, "Area.parent_id" => $areaId))
				    )
				)
			);
			$areas = $this->Area->find('all', $options);
		}
		return $areas;
	}

	private function isWard($areaId)
	{
		$digits = strlen($areaId);

		if ($digits >= 7) { return true; }
		return false;
	}
	
	public function getPublicExpenditureByYearAndArea($year, $listAreaId, $listEducationLevelId)
	{

		$options = array(
        	'fields' => array(
        		"PublicExpenditureEducationLevel.id",
				"PublicExpenditureEducationLevel.area_id",
				"PublicExpenditureEducationLevel.year",
				"PublicExpenditureEducationLevel.education_level_id",
				"PublicExpenditureEducationLevel.value"
        	),
			'conditions' => array(
				"AND" => array(
					"PublicExpenditureEducationLevel.year" => $year, 
					"PublicExpenditureEducationLevel.area_id" => $listAreaId,
					"PublicExpenditureEducationLevel.education_level_id" => $listEducationLevelId)
			)
		);
		$result = $this->find('all', $options);
		return $result;
	}

	public function getData($year = 0, $areaId = 0, $parentAreaId = 0, $eduLevelId) {

		$utility = new UtilityComponent(new ComponentCollection);
		if ($year <= 0 || ($areaId <= 0 && $parentAreaId <= 0 )) {
			return false;
		}

		if ($parentAreaId >= 1) {
			$level = $this->getEducationLevel($eduLevelId);
			
			if(empty($level)){
				return false;
			}

			$listEducationLevelId = $level['EducationLevel']['id'];
			$listEducationLevelName = $level['EducationLevel']['name'];

			$list[$listEducationLevelId]['education_level_id'] = $listEducationLevelId;
			$list[$listEducationLevelId]['education_level_name'] = $listEducationLevelName;

			$areas = $this->getAreas($areaId, $parentAreaId);
			$areas = $utility->formatResult($areas);

			foreach ($areas as $area) {

				$listAreaId = $area['id'];
				$listAreaName = $area['name'];
				$listParentId = $area['parent_id'];
				$listAreaLevel = $area['area_level_id'];
				$listAreaLevelName = $area['area_level_name'];

				$result = $this->getPublicExpenditureByYearAndArea($year, $listAreaId, $listEducationLevelId);

				if (!empty($result)) {
					$expenditureResult = $result[0]['PublicExpenditureEducationLevel'];

					$list[$listEducationLevelId]['areas'][] =
							array(
								'name' => $listAreaName,
								'area_id' => $listAreaId,
								'parent_id' => $listParentId,
								'area_level_id' => $listAreaLevel,
								'area_level_name' => $listAreaLevelName,
								'education_level_id' => $expenditureResult['education_level_id'],
								'year' => $expenditureResult['year'],
								'id' => $expenditureResult['id'],
								'value' => $expenditureResult['value']
					);
				} else {
					$list[$listEducationLevelId]['areas'][] =
							array(
								'name' => $listAreaName,
								'area_id' => $listAreaId,
								'parent_id' => $listParentId,
								'area_level_id' => $listAreaLevel,
								'area_level_name' => $listAreaLevelName,
								'education_level_id' => $listEducationLevelId,
								'year' => null,
								'id' => 0,
								'value' => ""
					);
				}
			}

			// $list['parent'][] = array_shift($list['children']);
		}
		return $list;
	}

	public function getPublicExpenditureData($year, $areaId, $parentAreaId, $eduLevelId) {
		$data = $this->getData($year, $areaId, $parentAreaId, $eduLevelId);
		return $data;
	}
	
	public function getRecordsCount($year, $areaId, $educationLevelId){
		$count = $this->find('count', array(
			'recursive' => -1,
			'conditions' => array(
				'year LIKE' => $year,
				'area_id' => $areaId,
				'education_level_id' => $educationLevelId
			)
		));
				
		return $count;
	}

}
