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

class PublicExpenditure extends AppModel {
	public $useTable = 'public_expenditure';

	public function getAreas($areaId = 0, $parentAreaId = 0) {
		$areaModel = ClassRegistry::init('Area');
		$options = array(
			'fields' => array("Area.id", "Area.name", "Area.parent_id", "Area.area_level_id", "AreaLevel.name"),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'area_levels',
					'alias' => 'AreaLevel',
					'conditions' => array(
						'Area.area_level_id = AreaLevel.id'
					)
				)
			),
			'conditions' => array(
				"Area.visible" => 1,

				"OR" => array(
					"Area.id" => $areaId,
					"Area.parent_id" => $parentAreaId,
				)
			)
		);
		$areas = $areaModel->find('all', $options);
		return $areas;
	}
	
	// Used by Yearbook
	public function geDataByYearAndArea($yearId, $areaId) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'joins' => array(
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array(
						'SchoolYear.start_year = PublicExpenditure.year',
						'SchoolYear.id = ' . $yearId
					)
				)
			),
			'conditions' => array('PublicExpenditure.area_id' => $areaId)
		));
		return $data;
	}

	public function getData($year = 0, $areaId = 0, $parentAreaId = 0) {
		
		if($year <= 0 || ($areaId <= 0 && $parentAreaId <= 0 )) {
			return false;
		}

		if ($areaId != $parentAreaId) {
			$areaId = $parentAreaId;
		}

		if ($parentAreaId >= 1) {
			$areas = $this->getAreas($areaId, $parentAreaId);

			foreach ($areas as $area) {

				$listAreaId = $area['Area']['id'];
				$listAreaName = $area['Area']['name'];
				$listParentId = $area['Area']['parent_id'];
				$listAreaLevelId = $area['Area']['area_level_id'];
				$listAreaLevelName = $area['AreaLevel']['name'];

				$result = $this->getPublicExpenditureByYearAndArea($year, $listAreaId);

				if (!empty($result)) {
					$expenditureResult = $result[0]['PublicExpenditure'];

					$list['children'][] = 
        			array(
	        			'name' => $listAreaName,
	        			'area_id' => $listAreaId,
	        			'parent_id' => $listParentId,
	        			'area_level_id' => $listAreaLevelId,
						'area_level_name' => $listAreaLevelName,
	        			'gross_national_product' => $expenditureResult['gross_national_product'],
	        			'year' => $expenditureResult['year'],
	        			'id' => $expenditureResult['id'],
	        			'total_public_expenditure' => $expenditureResult['total_public_expenditure'],
	        			'total_public_expenditure_education' => $expenditureResult['total_public_expenditure_education'],
        			);	
				} else {
					$list['children'][] =         			
					array(
	        			'name' => $listAreaName,
	        			'area_id' => $listAreaId,
	        			'parent_id' => $listParentId,
	        			'area_level_id' => $listAreaLevelId,
						'area_level_name' => $listAreaLevelName,
	        			'gross_national_product' => null,
	        			'year' => null,
	        			'id' => 0,
	        			'total_public_expenditure' => "",
	        			'total_public_expenditure_education' => ""
        			);
				}
				
			}

			$list['parent'][] = array_shift($list['children']);
				

		}
		return $list;
	}
	
	public function getPublicExpenditureByYearAndArea($year, $listAreaId) {
		$options = array(
			'fields' => array(
				"PublicExpenditure.id",
				"PublicExpenditure.area_id",
				"PublicExpenditure.year",
				"PublicExpenditure.gross_national_product",
				"PublicExpenditure.total_public_expenditure",
				"PublicExpenditure.total_public_expenditure_education"
			),
			'conditions' => array(
				"AND" => array("PublicExpenditure.year" => $year, "PublicExpenditure.area_id" => $listAreaId)
			)
		);
		$result = $this->find('all', $options);
		return $result;
	}

	public function getPublicExpenditureData($year, $areaId, $parentAreaId) {
		$data = $this->getData($year, $areaId, $parentAreaId);
		return $data;
	}

	public function saveGNP($year, $gnp) {
		$this->updateAll(
		    array('gross_national_product' => $gnp),
		    array('year' => $year)
		);
	}
	
	public function getRecordsCount($year, $areaId){
		$count = $this->find('count', array(
			'recursive' => -1,
			'conditions' => array(
				'year LIKE' => $year,
				'area_id' => $areaId
			)
		));
				
		return $count;
	}

}
