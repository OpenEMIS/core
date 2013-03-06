<?php
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

	public function getData($year = 0, $areaId = 0, $parentAreaId = 0) {

		$utility = new UtilityComponent(new ComponentCollection);
		if($year <= 0 || ($areaId <= 0 && $parentAreaId <= 0 )) {
			return false;
		}

		if ($parentAreaId >= 1) {

			$levels = $this->getEducationLevels();

			foreach ($levels as $level) {
				$listEducationLevelId = $level['id'];
				$listEducationLevelName = $level['name'];

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
			}

			// $list['parent'][] = array_shift($list['children']);

		}
		return $list;
	}

	public function getPublicExpenditureData($year, $areaId, $parentAreaId) {
		$data = $this->getData($year, $areaId, $parentAreaId);
		return $data;
	}

}
