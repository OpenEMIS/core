<?php  
App::uses('AppTask', 'Console/Command/Task');

define("CENSUS_DATAENTRY", 0); 
define("CENSUS_EXTERNAL", 20);
define("CENSUS_INTERNAL", 30);
define("CENSUS_ESTIMATE", 40);

class EstTask extends AppTask {
	public $limit = 1000;
	public $tasks = array('Common');
	public $fileFP;
	
	//Population Estimates Starts -
	private $arrMain = array();
	private $firstIndex = 0;
	private $firstNonZeroValue = 0;
	private $lastIndex = 0;
	private $lastNonZeroValue = 0;
	private $growthRate = 0;
	private $db;
	private $popYear = array();
	
	public function setData($arr){
		$this->arrMain = $arr;
	}
	
	public function getByGroup($field,$where ='',$flip = false){
		if($where != ''){
			$where = ' WHERE '.$where;
		}
		
		$sql = "SELECT $field FROM population $where GROUP BY ".$field;
		$years =  $this->db->fetchAll($sql);
		$tmp = array();
		foreach ($years as $key => $value) {
			$tmp[] = $value['population'][$field];
		}
		if($flip) $tmp = array_flip($tmp);
		return $tmp;
	}
	
	private  function getAgeForArea($area_id){
		return $this->getByGroup('age',' data_source = 0 AND area_id = '.$area_id);
	}
	
	

	public function populationEstimate(){
		$path = $this->Common->getReportWebRootPath();
		$this->fileFP = fopen($path.'/estimates/population_estimate', "w+");
		$gender = array('male','female');
		$this->db = ConnectionManager::getDataSource('default');
		$this->popYear = $this->getByGroup('year',' data_source = 0 ',true);
                
                //dont process if only one  year record or no record.
                if (count($this->popYear) < 2) return;
                
		$arrYears = $this->getByGroup('year');
		$area_ids = $this->getByGroup('area_id',' data_source = 0 ');
		
		foreach($area_ids as $areaV){
			$ages = $this->getAgeForArea($areaV);
			foreach($ages as $ageV){
				$garbage = array();
				
				$sql = "SELECT male,female,year FROM population WHERE age = $ageV AND area_id = $areaV AND year in (".implode(",",array_keys($this->popYear)).")";
				$res =  $this->db->fetchAll($sql);
				
				foreach ($res as $val){
						$garbage['male'][$this->popYear[$val['population']['year']]] = $val['population']['male'];
						$garbage['female'][$this->popYear[$val['population']['year']]] = $val['population']['female'];
					
				}
				
				
				$max = end(array_values($this->popYear)); //Get the final key as max!
				$fixMissing = array();
				foreach($gender as $gend){
					$missingKeys = array();
					//Get Missing Keys and also Fill in the main garbage with 0
					for($i = 0; $i < $max; $i++){
						if(!isset($garbage[$gend][$i])){
							$missingKeys[$gend][] = $i;
							$garbage[$gend][$i] = 0;
						}
					}

					//$this->cleanArray($garbage['male']);
					$this->setData($garbage[$gend]);
					$this->process();
					//$this->processOutofBoundary();
					$this->displayArr();
					
					
					if(count($missingKeys)>0){
						foreach ($missingKeys[$gend] as $key => $value) {
							$fixMissing[$arrYears[$value]][$gend] = floor($this->arrMain[$value]);
						}
					}
					
				}
				$this->writeSQL($fixMissing,$areaV,$ageV); 
				//echo 'Age:['.$ageV.'] , Areas:'.$areaV;
				//pr($garbage);
			}
		}
		//echo implode(array_keys($this->popYear));
		fclose ($this->fileFP);
                 
	}
	
        
        private function executeSQL(){
            $this->db->rawQuery(file_get_contents($path.'/estimates/population_estimate'));
            echo "executed";
        }
        
	/*function cleanArray(&$array){
		
		$max = end(array_values($this->popYear)); //Get the final key as max!
		for($i = 0; $i < $max; $i++)
		{
			if(!isset($array[$i]))
			{
				$array[$i] = 0;
			}
		}
	}*/
	private function writeSQL($arr,$area,$age){
		$insertSQL = '';
		foreach($arr as $year => $arrVal){
			//echo "\n INSERT INTO population () VALUES ($age, $year, $area, ".$arrVal['male'].",".$arrVal['female'].")";
			$query = array(
				'table' => 'population',
				'fields' => 'age,year,area_id,male,female,data_source,source,created_user_id,created',
				'values' => implode(",",array($age,$year,$area,$arrVal['male'],$arrVal['female'],1,"'OpenEMIS Population Estimates'",1,"NOW()"))
			);
                        
			echo "\n".$SQL = $this->db->renderStatement('create', $query).";";
                        //$this->db->q($SQL);
                        
                        $this->db->rawQuery($SQL);
                        $insertSQL .= $SQL;
		}
                
                
		fputs ($this->fileFP, $insertSQL);
	}
        
	public function process() {
		$tmp = array();
		$first = 0;
		$last = 0;
		$arrTmp = array();
		for($i=0;$i < count($this->arrMain);$i++){
			//FIRST
			if($first == 0 && $this->arrMain[$i] != 0 && @$this->arrMain[($i + 1)] === 0) {
				$this->firstNonZeroValue =  $first = $this->arrMain[$i]; 
				$this->firstIndex = $i;
			}elseif($this->arrMain[$i] != 0 && @$this->arrMain[($i - 1)] === 0) { //LAST
				$this->lastNonZeroValue = $last = $this->arrMain[$i]; 
				$this->lastIndex = $i;
				
				$this->processMissingValue();
					if($this->arrMain[$i] != 0 && @$this->arrMain[($i + 1)] === 0) {
						$first = $last;
						$this->firstIndex = $i;
						
					} 
			}elseif($first != 0 && $this->arrMain[$i] != 0 && @$this->arrMain[($i + 1)] === 0) { //insert
				 $first =  $this->arrMain[$i];
				$this->firstIndex = $i;
			}
		}
		$this->firstIndex = 0;
		$this->lastIndex = 0;
		
	}
	public function displayArr(){
		print '<pre>';
		print_r($this->arrMain);
		print'</pre>';
	}
	
	public function processMissingValue(){
		$diff = $this->arrMain[$this->lastIndex] - $this->arrMain[$this->firstIndex];
		$total = ($this->lastIndex - $this->firstIndex + 1);
		$intPerc = 1;
		for($i = $this->firstIndex; $i <= $this->lastIndex; $i++ ){
			if($i == $this->firstIndex || $i == $this->lastIndex) continue;
			//echo '<br> ['.$i.'] ';
			$this->arrMain[$i] = ($diff * ($intPerc/$total) ) + $this->arrMain[$this->firstIndex];
			$intPerc++;
		}
	}
	
	private function getGrowthRate($array){
		for($i = 1; $i < count($array); $i++) {
		  $resultado[] = $array[$i] - @$array[$i - 1];
		}
		 $this->growthRate =  array_sum($resultado)/count($resultado);
		 return $this->growthRate;
	}
	
	public function processOutofBoundary(){
		$p = $this->firstNonZeroValue;
		$l = $this->lastNonZeroValue;
		$array = $this->arrMain;
		//Trim the LEFT and RIGHT Zero Elements;
		$leftZeroItems = 0;
		while (reset($this->arrMain) == '') { 
			array_shift($this->arrMain);
			$leftZeroItems++;
		}
		$rightZeroItems = 0;
		while (end($this->arrMain) == ''){
			array_pop($this->arrMain);
			$rightZeroItems++;
		}
		
		//Growth Rate
		$gr = $this->getGrowthRate($this->arrMain);
		
		
		
		for($i = 0; $i <= count($leftZeroItems) ;$i++){
			$p = $p - $gr ;
			array_unshift($this->arrMain, $p);
		}
		
		for($a = 0; $a <= count($rightZeroItems) ;$a++){
			$l = $l + $gr ;
			array_push($this->arrMain, $l);	
		}
		 
		
	}
	
	//Population Estimates Ends -
	
	
	public function genEST($settings){
		
		$current_year = $this->getSchoolYearWithOffset();
		$previous_year = $this->getSchoolYearWithOffset(1);
		
		try{
			eval($settings['sql']);
			echo "finissshh";
		} catch (Exception $e) {
			// Update the status for the Processed item to (-1) ERROR
			$errLog = $e->getMessage();
                        pr($errLog);
			$this->Common->updateStatus($settings['batchProcessId'],'-1');
			$this->Common->createLog($this->Common->getLogPath().'estimate.log',$errLog);
		}
	}
	
	public function getSchoolYearWithOffset($offset=0){ //defaults to current
		
		$sql = "select id from school_years order by current desc,start_year desc limit " . $offset . ",1";
		$db = ConnectionManager::getDataSource('default');
		$arr =  $db->fetchAll($sql);
		$year = (int)$arr[0]['school_years']['id'];
		return $year;
	}
	public function censusAggregateFromStudentRegisters($current_year,$options=array()){
		
		$sql = <<<EOD
				DELETE FROM census_students WHERE source = 3 AND school_year_id = {curr_year};
				INSERT INTO census_students
				(
					SELECT null, A1.age, IF(ISNULL(A1.male),0,A1.male), IF(ISNULL(A2.female),0,A2.female), A1.student_category_id,  A1.education_grade_id,  A1.institution_site_id, {curr_year},3,1,'0000-00-00 00:00:00',1,'0000-00-00 00:00:00'  
					FROM (
							SELECT count(a.student_id) as Male, 
									a.institution_site_class_grade_id, 
									a.student_category_id, 
									b.education_grade_id,
									c.institution_site_id, 
									c.school_year_id, 
									d.gender, 
									FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
							FROM institution_site_class_grade_students a
							JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
							JOIN institution_site_classes c ON c.id = b.institution_site_class_id AND c.school_year_id ={curr_year}
							JOIN students d ON d.id = a.student_id
							WHERE d.gender = 'M'
							GROUP BY institution_site_class_grade_id, age, gender, student_category_id
						) A1
					LEFT JOIN 
						( 
							SELECT count(a.student_id) as Female, 
									a.institution_site_class_grade_id, 
									a.student_category_id, 
									b.education_grade_id, 
									c.institution_site_id, 
									c.school_year_id, 
									d.gender, 
									FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
							FROM institution_site_class_grade_students a
							JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
							JOIN institution_site_classes c ON c.id = b.institution_site_class_id
							AND c.school_year_id ={curr_year}
							JOIN students d ON d.id = a.student_id
							WHERE d.gender = 'F'
							GROUP BY institution_site_class_grade_id, age, gender, student_category_id
						) A2
					ON 
						A1.age = A2.age AND A1.institution_site_class_grade_id = A2.institution_site_class_grade_id AND A1.student_category_id = A2.student_category_id 
				)
				UNION
				(
					SELECT null,A2.age, IF(ISNULL(A1.male),0,A1.male), IF(ISNULL(A2.female),0,A2.female), A2.student_category_id,  A2.education_grade_id,  A2.institution_site_id,  {curr_year},3,1,'0000-00-00 00:00:00',1,'0000-00-00 00:00:00' 
					FROM (
							SELECT count(a.student_id) as Male, 
									a.institution_site_class_grade_id, 
									a.student_category_id, 
									b.education_grade_id, 
									c.institution_site_id, 
									c.school_year_id, 
									d.gender, 
									FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
							FROM institution_site_class_grade_students a
							JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
							JOIN institution_site_classes c ON c.id = b.institution_site_class_id AND c.school_year_id ={curr_year}
							JOIN students d ON d.id = a.student_id
							WHERE d.gender = 'M'
							GROUP BY institution_site_class_grade_id, age, gender, student_category_id
						) A1
					RIGHT JOIN
						(
							SELECT count(a.student_id) as Female, 
									a.institution_site_class_grade_id, 
									a.student_category_id, 
									b.education_grade_id, 
									c.institution_site_id, 
									c.school_year_id, 
									d.gender, 
									FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
							FROM institution_site_class_grade_students a
							JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
							JOIN institution_site_classes c ON c.id = b.institution_site_class_id
							AND c.school_year_id ={curr_year}
							JOIN students d ON d.id = a.student_id
							WHERE d.gender = 'F'
							GROUP BY institution_site_class_grade_id, age, gender, student_category_id
						) A2

					ON 
						A1.age = A2.age AND A1.institution_site_class_grade_id = A2.institution_site_class_grade_id AND A1.student_category_id = A2.student_category_id 
				)

EOD;
				$sql = str_replace("{curr_year}", $current_year, $sql);
				$db = ConnectionManager::getDataSource('default');
				$db->rawQuery($sql);
	}
	public function censusShiftPastYearToCurrentYear($current_year,$previous_year,$options=array()){
		$sql_past_year_data = <<<EOD
				INSERT INTO census_students
				SELECT null,age+1,cs.male,cs.female,1,cs.eg.nextGradeId,institution_site_id,{curr_year},2,0,'0000-00-00 00:00:00',1,NOW()
				FROM census_students cs 
				LEFT JOIN (
							(SELECT * FROM education_grades t1 
								LEFT JOIN (
										SELECT a.id as currGradeId,
												a.name as currGradeName,
												b.id as nextGradeId, 
												b.name as nextGradeName, 
												b.order as nextGradeOrder  
										FROM education_grades a 
										JOIN education_grades b 
										ON a.order+1 = b.order AND  a.education_programme_id  = b.education_programme_id 
								) t2 ON t1.id = t2.currGradeId
								WHERE t1.id NOT IN ( SELECT max(id) FROM education_grades GROUP BY education_programme_id )
							)
						  ) eg ON eg.currGradeId = cs.education_grade_id
				WHERE 1
				AND  school_year_id = ({prev_year})
EOD;
	
		$sql_past_year_data = str_replace("{curr_year}", $current_year, $sql_past_year_data);
		$sql_past_year_data = str_replace("{prev_year}", $previous_year, $sql_past_year_data);
		$db = ConnectionManager::getDataSource('default');
		$db->rawQuery($sql_past_year_data);
		
		$sql_same_area_year_grade_data = <<<EOT
				DROP TABLE IF EXISTS magic;
				CREATE TABLE magic ENGINE=MyISAM
				SELECT COUNT( cs.id ) AS tot, AVG( cs.male ) as avg_male , AVG( cs.female ) as  avg_female, education_grade_id, institution_site_type_id, area_id, age
				FROM  `census_students` cs
				JOIN institution_sites ins ON ins.id = cs.institution_site_id
				WHERE cs.school_year_id ={curr_year} 
				GROUP BY institution_site_type_id, area_id, education_grade_id,age;
				ALTER TABLE magic ADD INDEX (education_grade_id, institution_site_type_id,area_id,age);


				SELECT null,ec.admission_age,IF(ISNULL(FLOOR(avg_male)),0,FLOOR(avg_male)),IF(ISNULL(FLOOR(avg_female)),0,FLOOR(avg_female)),1,eg.id,isi.institution_site_id,{curr_year},2,0,'0000-00-00 00:00:00',1,NOW()
				-- SELECT isi.id as school_id,eg.name, isi.area_id, isi.institution_site_type_id , isi.name as school_name, eg.id as Grade_ID, ec.admission_age, cens.* 
				FROM institution_sites isi 
				LEFT JOIN institution_site_programmes isp ON isi.id = isp.institution_site_id
				LEFT JOIN education_programmes ep on ep.id = isp.education_programme_id
				LEFT JOIN (

					SELECT p1.*
					FROM education_grades p1 LEFT JOIN education_grades p2
					ON (p1.education_programme_id = p2.education_programme_id AND p1.id > p2.id)
					WHERE p2.education_programme_id IS NULL 

				) eg ON eg.education_programme_id = isp.education_programme_id
				LEFT JOIN education_cycles ec on  ec.id = ep.education_cycle_id
				LEFT JOIN census_students cens ON cens.education_grade_id = eg.id AND isi.id = cens.institution_site_id AND cens.school_year_id = {curr_year}  AND cens.student_category_id = 1
				LEFT JOIN magic l ON l.area_id  = isi.area_id 
				AND l.institution_site_type_id = isi.institution_site_type_id
				AND l.education_grade_id = cens.education_grade_id
				WHERE isp.school_year_id = {curr_year} and eg.id is not null and cens.id is null and l.avg_male is not null
				DROP TABLE magic;
EOT;
		
		$sql_same_area_year_grade_data = str_replace("{curr_year}", $current_year, $sql_same_area_year_grade_data);
		$db = ConnectionManager::getDataSource('default');
		$db->rawQuery($sql_same_area_year_grade_data);
		
    }
}
/***************
 * GUIDE: 
 * 
 * 1. Estimate from Total Individuals
 * 
 * INSERT INTO census_students
(
	SELECT null, A1.age, IF(ISNULL(A1.male),0,A1.male), IF(ISNULL(A2.female),0,A2.female), 1,  A1.education_grade_id,  A1.institution_site_id, 1,  {curr_year},2,1,'0000-00-00 00:00:00',1,'0000-00-00 00:00:00'  
	FROM (
			SELECT count(a.student_id) as Male, 
					a.institution_site_class_grade_id, 
					b.education_grade_id, 
					c.institution_site_id, 
					c.school_year_id, 
					d.gender, 
					FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
			FROM institution_site_class_grade_students a
			JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
			JOIN institution_site_classes c ON c.id = b.institution_site_class_id AND c.school_year_id ={curr_year}
			JOIN students d ON d.id = a.student_id
			WHERE d.gender = 'M'
			GROUP BY institution_site_class_grade_id, age, gender
		) A1
	LEFT JOIN 
		( 
			SELECT count(a.student_id) as Female, 
				    a.institution_site_class_grade_id, 
					b.education_grade_id, 
					c.institution_site_id, 
					c.school_year_id, 
					d.gender, 
					FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
			FROM institution_site_class_grade_students a
			JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
			JOIN institution_site_classes c ON c.id = b.institution_site_class_id
			AND c.school_year_id ={curr_year}
			JOIN students d ON d.id = a.student_id
			WHERE d.gender = 'F'
			GROUP BY institution_site_class_grade_id, age, gender
		) A2
	ON 
		A1.age = A2.age AND A1.institution_site_class_grade_id = A2.institution_site_class_grade_id
)
UNION
(
	SELECT null,A2.age, IF(ISNULL(A1.male),0,A1.male), IF(ISNULL(A2.female),0,A2.female),1,  A2.education_grade_id,  A2.institution_site_id, 1,  {curr_year},2,1,'0000-00-00 00:00:00',1,'0000-00-00 00:00:00' 
	FROM (
			SELECT count(a.student_id) as Male, 
					a.institution_site_class_grade_id, 
					b.education_grade_id, 
					c.institution_site_id, 
					c.school_year_id, 
					d.gender, 
					FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
			FROM institution_site_class_grade_students a
			JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
			JOIN institution_site_classes c ON c.id = b.institution_site_class_id AND c.school_year_id ={curr_year}
			JOIN students d ON d.id = a.student_id
			WHERE d.gender = 'M'
			GROUP BY institution_site_class_grade_id, age, gender
		) A1
	RIGHT JOIN
		(
			SELECT count(a.student_id) as Female, 
					a.institution_site_class_grade_id, 
					b.education_grade_id, 
					c.institution_site_id, 
					c.school_year_id, 
					d.gender, 
					FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
			FROM institution_site_class_grade_students a
			JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
			JOIN institution_site_classes c ON c.id = b.institution_site_class_id
			AND c.school_year_id ={curr_year}
			JOIN students d ON d.id = a.student_id
			WHERE d.gender = 'F'
			GROUP BY institution_site_class_grade_id, age, gender
		) A2

	ON 
		A1.age = A2.age AND A1.institution_site_class_grade_id = A2.institution_site_class_grade_id
)
*/	


/***************
 * 2. SHIFT prev year to curr year and fill the other data with ave student of same area, grade, of the current school_year
 * a. Get Data from Census Student's past year and shift it to the current year
 * b. Generate numbers for those blank data from past year based on same site's area, grade, 
 *
 *
	INSERT INTO census_students
	SELECT null,age+1,cs.male,cs.female,1,cs.eg.nextGradeId,institution_site_id,institution_site_programme_id,{curr_year},2,0,'0000-00-00 00:00:00',1,NOW()
	FROM census_students cs 
	LEFT JOIN (
				(SELECT * FROM education_grades t1 
 					LEFT JOIN (
							SELECT a.id as currGradeId,
									b.id as nextGradeId, 
									b.name as nextGradeName, 
									b.order as nextGradeOrder  
							FROM education_grades a 
							JOIN education_grades b 
							ON a.order+1 = b.order AND  a.education_programme_id  = b.education_programme_id 
					) t2 ON t1.id = t2.currGradeId
					WHERE t1.id NOT IN ( SELECT max(id) FROM education_grades GROUP BY education_programme_id )
				)
			  ) eg ON eg.currGradeId = cs.education_grade_id
	WHERE education_grade_id IN ( 
						SELECT id 
						FROM education_grades 
						WHERE id NOT IN (
							SELECT max(id) FROM education_grades GROUP BY education_programme_id
						) 
	) 
	AND  school_year_id = ({curr_year} - 1)

 */

/****
 * Get All Institution in same area, grade, year
 */

/**
	DROP TABLE IF EXISTS magic;
	CREATE TABLE magic ENGINE=MyISAM
	SELECT COUNT( cs.id ) AS tot, AVG( cs.male ) as avg_male , AVG( cs.female ) as  avg_female, education_grade_id, institution_site_type_id, area_id, age
	FROM  `census_students` cs
	JOIN institution_sites ins ON ins.id = cs.institution_site_id
	WHERE cs.school_year_id ={curr_year}
	GROUP BY institution_site_type_id, area_id, education_grade_id,age;
	ALTER TABLE magic ADD INDEX (education_grade_id, institution_site_type_id,area_id,age);


	SELECT null,ec.admission_age,IF(ISNULL(FLOOR(avg_male)),0,FLOOR(avg_male)),IF(ISNULL(FLOOR(avg_female)),0,FLOOR(avg_female)),1,eg.id,isi.institution_site_type_id,1,{curr_year},2,0,'0000-00-00 00:00:00',1,NOW()
	-- SELECT isi.id as school_id,eg.name, isi.area_id, isi.institution_site_type_id , isi.name as school_name, eg.id as Grade_ID, ec.admission_age, cens.* 
	FROM institution_sites isi 
	LEFT JOIN institution_site_programmes isp ON isi.id = isp.institution_site_id
	LEFT JOIN education_programmes ep on ep.id = isp.education_programme_id
	LEFT JOIN (

		SELECT p1.*
		FROM education_grades p1 LEFT JOIN education_grades p2
		ON (p1.education_programme_id = p2.education_programme_id AND p1.id > p2.id)
		WHERE p2.education_programme_id IS NULL 

	) eg ON eg.education_programme_id = isp.education_programme_id
	LEFT JOIN education_cycles ec on  ec.id = ep.education_cycle_id
	LEFT JOIN census_students cens ON cens.education_grade_id = eg.id AND isi.id = cens.institution_site_id AND cens.school_year_id = {curr_year}  AND cens.student_category_id = 1
	LEFT JOIN magic l ON l.area_id  = isi.area_id 
	AND l.institution_site_type_id = isi.institution_site_type_id
	AND l.education_grade_id = cens.education_grade_id
	WHERE isp.school_year_id = {curr_year} and eg.id is not null and cens.id is null and l.avg_male is not null

 */


?>
