<?php

class Population extends AppModel {
	public $useTable = 'population';
	// public $belongTo = array('Area');

    public function findListAsSubgroups() {
        $this->formatResult = true;
        $list = $this->find('all', array(
            'fields' => array('DISTINCT(Population.age)')
        ));

        $ageList = array();
        foreach($list as $obj) {
            $age = $obj['age'];
            if(!isset($ageList[$age])) {
                $ageList[$age] = array('grades' => array());
            }
        }

        return $ageList;
    }

	public function getData($year=0, $areaId=0) {

		// if($year <= 0 || ($areaId <= 0 && $parentAreaId <= 0 )){
		if($year <= 0 || $areaId <= 0){
			return false;
		}

		$dbo = $this->getDataSource();

		$fields = array(
			"`AreaLevel`.`name` AS `area_level`",
			"`Population`.`id` AS `id`",
			"`Population`.`age` AS `age`",
			"`Population`.`year` AS `year`",
			"`Population`.`source` AS `source`",
			"`Population`.`male` AS `male`",
			"`Population`.`female` AS `female`",
			"`Population`.`area_id`"
		);

		$table = "population";
		$join = array(
			array(
				'table' => 'areas',
				'alias' => 'Area',
				'type' => 'left',
				'conditions' => array('Area.id = Population.area_id')
			),
			array(
				'table' => 'area_levels',
				'alias' => 'AreaLevel',
				'type' => 'left',
				'conditions' => array('Area.area_level_id = AreaLevel.id')
			)
		);
		$conditions = array(
			"Population.year" => $year,
			"Population.area_id" => $areaId
		);

		// if($parentAreaId < 1) {
			$subQuery = $dbo->buildStatement(
		           array(
						'fields' => $fields,
		               'table' => $table,
		               'alias' => $this->alias,
		               'limit' => null,
		               'offset' => 0,
		               'joins' => $join,
		               'conditions' => $conditions,
		               'order' => null,
		               'group' => null
		           ),
		           $this->alias
		       );
			// $sql = "
			// 	SELECT 
			// 		`area_levels`.`name` AS `area_level`, `population`.`id` AS `id`, `population`.`age` AS `age`, `population`.`year` AS `year`, `population`.`source` AS `source`, `population`.`male` AS `male`, `population`.`female` AS `female`, `population`.`area_id` 
			// 	FROM 
			// 		`population` 
			// 	LEFT JOIN 
			// 		`areas` 
			// 	ON 
			// 		`population`.`area_id` = `areas`.`id` 
			// 	LEFT JOIN
			// 		`area_levels`
			// 	ON
			// 		`areas`.`area_level_id` = `area_levels`.`id`
			// 	WHERE 
			// 		`population`.`year` = %d 
			// 		AND 
			// 		`population`.`area_id` = %d";

			// $list = $this->query(sprintf($sql, $year, $areaId));
			// $conditions = array(
			// 	'AND' => array("Population.year" => $year),
			// 	'OR' => array()
			// );
		// }else{

		// $sql = "
		// 	SELECT 
		// 	`area_levels`.`name` AS `area_level`, `population`.`id`, `population`.`age`, `population`.`year`, `population`.`source`, `population`.`male`, `population`.`female`, `population`.`area_id`
		// FROM 
		// 	`population` 
		// LEFT JOIN 
		// 	`areas` 
		// ON 
		// 	`population`.`area_id` = `areas`.`id` 
		// LEFT JOIN
		// 	`area_levels`
		// ON
		// 	`areas`.`area_level_id` = `area_levels`.`id`
		// WHERE 
		// 	`population`.`year` = %d 
		// 	AND ( 
		// 		`population`.`area_id` IN (
		// 			SELECT `areas`.`id`
		// 			FROM `areas`
		// 			WHERE 
		// 				`areas`.`parent_id` = %d
		// 		) 
		// 		OR 
		// 		`population`.`area_id` = %d
		// 	)";
			
		// 	$list = $this->query(sprintf($sql, $year, $parentAreaId, $areaId));
		// }

		$params = array(
						'fields' => $fields,
		               'table' => $table,
		               'alias' => $this->alias,
		               'limit' => null,
		               'offset' => 0,
		               'joins' => $join,
		               'conditions' => $conditions,
		               'order' => null,
		               'group' => null
		           );
		$query = $dbo->buildStatement(
		           $params,
		           $this->alias
		       );
		
		$list = $this->query($query);
				
		return $list;

	}

	public function getPopulationData($year, $areaId) {
		// App::uses('UtilityComponent', 'Component');

   		// $utility = new UtilityComponent();
		$population = $this->getData($year, $areaId);
		if($population){
			$population = $population;//$utility->formatResult($population);
		}else{
			$population = array();
		}
		$records = array();

		return $population;
	}

	public function savePopulationData($data) {
		$results = array( 'delete' => array(), 'insert' => array(), 'update' => array());
		$deleted = array();
		
		if(isset($data['deleted'])) {
			 $deleted = $data['deleted'];
			 unset($data['deleted']);
		}

		foreach($deleted as $id) {
			$this->delete($id);
			array_push($results['delete'], $id);
		}
		
		for($i=0; $i<sizeof($data); $i++) {
			$row = $data[$i];
			if($row['age'] > 0 /*&& ($row['male'] > 0 || $row['female'] > 0)*/) {
				if($row['id'] == 0) {
					$this->create();
				}
				$save = $this->save(array('Population' => $row));
				if($row['id'] == 0) {
					// $keys[strval($i+1)] = $save['Population']['id'];
					array_push($results['insert'], $save['Population']['id']);
				}elseif($row['id'] > 0 && $save['Population']['id'] > 0){
					array_push($results['update'], $save['Population']['id']);
				}
			// } else if($row['id'] > 0 && $row['male'] == 0 && $row['female'] == 0) {
				// $this->delete($row['id']);
				// array_push($results['delete'], $row['id']);
			}
		}
		$results['type'] = 0;
		return $results;
	}
}
