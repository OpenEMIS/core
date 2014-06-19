<?php  
App::uses('AppTask', 'Console/Command/Task');

define("CENSUS_DATAENTRY", 0); 
define("CENSUS_EXTERNAL", 20);
define("CENSUS_INTERNAL", 30);
define("CENSUS_ESTIMATE", 40);

class EstTask extends AppTask {
	public $limit = 1000;

	
	public function genEST($settings){
		/* 
			ORDER FUNCTIONS ACCORDING TO THEIR DATA ESTIMATION ACCURACY - because only unique census rows are entered
			ie: censusShiftPastYearToCurrentYear uses pastyear data to estimate current year data
				is going to be more accurate than 
				censusEstimateMissingInstitutesforYear (part of it) generates data from the last found year
				so , run censusShiftPastYearToCurrentYear before censusEstimateMissingInstitutesforYear
				
				conversely ,censusShiftPastYearToCurrentYear might not generate the full set of census data for year 
				and censusEstimateMissingInstitutesforYear(or some other fucntion down the line) can possibly help to "fill in" missing data with less-accurate estimates
		*/
		$current_year = $this->getSchoolYearWithOffset();
		$previous_year = $this->getSchoolYearWithOffset(1);
		
		/* //this section is now ine batch_report table , item "Estimates from Current Student Data"
		$this->censusAggregateFromStudentRegisters($current_year);
		*/
		
		/* //this section is now ine batch_report table , item "Estimates from Census History"
		//shift last year census data into current year , increment age and grade accordingly. only shift grades supported by current year institution_site
		$this->censusShiftPastYearToCurrentYear($current_year,$previous_year);
		
		//enter data for starting year of each grade
		$this->censusEstimateGradeForYear($current_year);
		
		//find all institutions w/o census data in current year
		$this->censusEstimateMissingInstitutesforYear($current_year);
		*/

		try{
			eval($settings['sql']);
		} catch (Exception $e) {
			// Update the status for the Processed item to (-1) ERROR
			$errLog = $e->getMessage();
			$this->Common->updateStatus($settings['batchProcessId'],'-1');
			$this->Common->createLog($this->Common->getLogPath().$procId.'.log',$errLog);
		}
	}
	
	public function censusAggregateFromStudentRegisters($current_year,$options=array()){
		$aggregate="
		SELECT 
			COUNT( student_id ) as count,
			age, 
			gender, 
			education_grade_id, 
			school_year_id, 
			institution_site_id
		FROM (
			SELECT a.student_id, a.institution_site_class_grade_id, b.education_grade_id, c.institution_site_id, c.school_year_id, d.gender, FLOOR( DATEDIFF( CURDATE( ) , d.date_of_birth ) / 365.25 ) AS age
			FROM institution_site_class_grade_students a
			JOIN institution_site_class_grades b ON b.id = a.institution_site_class_grade_id
			JOIN institution_site_classes c ON c.id = b.institution_site_class_id and c.school_year_id =  %s
			JOIN students d ON d.id = a.student_id
			
		)AGGREGATE 
		GROUP BY institution_site_class_grade_id, age, gender
		";
		$aggregate_sql = sprintf($aggregate,$current_year);
		$current_this = isset($options['caller_this'])? $options['caller_this']:$this;
		
		$this->exeSQLwLimitnRowpayload(
			$current_this, 
			$aggregate_sql, //your SQL to chunk execute
			array('current_year'=>$current_year,  //params to feed into your row_yield 
					'options'=>$options
			),
			function($caller_this,$row,$params){
				$row['count'] = (int)$row['count'];
				$row['gender']=strtoupper(trim($row['gender']));
				$institution_site_programme_id = $caller_this->getInstitutionSiteProgrammeId($row['institution_site_id'],$params['current_year'],$row['education_grade_id']);

				$data = array('age'=>$row['age'],
								'male'=>$row['gender']=='M'?$row['count']:0,
								'female'=>$row['gender']=='F'?$row['count']:0,
								'student_category_id'=>1, //default to student category type 1 until we have patch
								'education_grade_id'=>$row['education_grade_id'],
								'institution_site_id'=>$row['institution_site_id'],
								'institution_site_programme_id'=>$institution_site_programme_id,
								'school_year_id' => $params['current_year'],
								'source' => CENSUS_INTERNAL,
								);
				$caller_this->insertUniqueCensusRow($data,array('overwrite'=>1));
			} 
		);
		

	}
	
	//shift last year census data into current year , increment age and grade accordingly. only shift grades supported by current year institution_site
	public function censusShiftPastYearToCurrentYear($current_year,$previous_year,$options=array()){
		/*
			previous year census 
			filtered by programme/grades existing in current year
			* note1 : cant be filtered by existing records in current year census (ie:dont include if there is existing records)  
				because  age is shifted+1(ok can match) but grade is shifted by (the next one in order , can't just +1) 
				and students at the final grade gets promoted out will be excluded from insert 
				all these will result in bad matches , duplicate inserts will be handling at the individual row insert level.
			* to be used for shifting previous year census data into current year (ghetto estimation)
		*/
		$census="SELECT 
			prev_year.age AS age,
			prev_year.male AS male,
			prev_year.female AS female,
			prev_year.student_category_id AS student_category_id,
			prev_year.education_grade_id AS education_grade_id,
			prev_year.institution_site_id AS institution_site_id
		FROM (	SELECT * FROM census_students 
				WHERE `school_year_id`= %s ";
		if (isset($options['census_table'])){ //for limiting census size , ie:do for specific institution_site
			foreach ((array)$options['census_table'] as $key=>$val){
				$census.= " AND `" .  $key . "`=" . $val;
			}
		}
		$census.=") prev_year 
		INNER JOIN (SELECT 
						now_year_programmes.institution_site_id AS institution_site_id,
						grades.id AS education_grade_id
					FROM `institution_site_programmes` now_year_programmes
						JOIN education_grades grades ON grades.education_programme_id = now_year_programmes.education_programme_id
					WHERE now_year_programmes.school_year_id = %s
					) year_programmes ON
						prev_year.institution_site_id = year_programmes.institution_site_id AND prev_year.education_grade_id = year_programmes.education_grade_id
		";
		$census_sql = sprintf($census,$previous_year,$current_year);
		$current_this = isset($options['caller_this'])? $options['caller_this']:$this;
		$this->exeSQLwLimitnRowpayload(
			$current_this,
			$census_sql,
			//params
			array('current_year'=>$current_year,
					'previous_year'=>$previous_year,
					'options'=>$options
			),
			//row_yield
			function($caller_this,$row,$params){
				$difference = isset($params['options']['year_difference'])? $params['options']['year_difference']:1; //year difference between current and previous year defaults to 1
				//get the next promoted grade and the matching institution_site_programme_id for that grade/institution/year
				$next_grade ="SELECT next_grade.education_grade_id , isp.id as institution_site_programme_id from(
							SELECT 
								eg.id AS education_grade_id,
								ep.id AS education_programme_id
							FROM education_grades eg
							JOIN education_programmes ep ON eg.education_programme_id = ep.id
							WHERE eg.id > %s
								AND ep.id ";
								/*get next grades only from range of institution supported programmes in that year
								must be ordered by education_programme.order , education_grade.order cos  id ordering may be wrong
								ie: supported programmes are prog2 - grade1,2,3 and prog3 -grade4,5,6
								student will be promoted from 2->3->4->5->6 , grade gets shifted +1(or year difference) and programmes goes up if grade goes up above 3.
								if the institution_site happens to support prog2-grade 1,2,3 and prog4-grade 7,8,9. then bugs will happen.
								*/
				$next_grade .="		IN (SELECT 
										education_programme_id
									FROM institution_site_programmes
									WHERE institution_site_id = %s
										and school_year_id = %s
									)
							ORDER BY ep.`order` ASC , eg.`order` ASC ";
							//increment grade by difference in years , this assumes EVERY grade is 1 year
				$next_grade .= "LIMIT ". ($difference-1) .",1";

							//matching institution_programme_id for the newly incremented grade / institution_site/ year
				$next_grade .=") as next_grade join institution_site_programmes isp 
							on next_grade.education_programme_id = isp.education_programme_id
							and isp.institution_site_id = %s
							and isp.school_year_id= %s ";
				$next_grade_sql = sprintf($next_grade,$row['education_grade_id'],$row['institution_site_id'],$params['current_year'],$row['institution_site_id'],$params['current_year']);
				#echo "CHECK NEXT GRADE - " . $next_grade_sql."\n";die;
				$next_grade_result=mysqli_fetch_array($caller_this->mysqlquery($next_grade_sql));

				if ( !(isset($next_grade_result['education_grade_id']) && isset($next_grade_result['institution_site_programme_id'])) ){
					 //return null = students that have graduated beyond syllabus supported by institution will be not be included in new estimated census
					echo "GRADUATED grade-".$row['education_grade_id'] . " inst-".$row['institution_site_id'] . " year-".$params['current_year']."\n";
				} else {
					$data = array('age'=>($row['age']+$difference),
									'male'=>$row['male'],
									'female'=>$row['female'],
									'student_category_id'=>$row['student_category_id'],
									'education_grade_id'=>$next_grade_result['education_grade_id'],
									'institution_site_id'=>$row['institution_site_id'],
									'institution_site_programme_id'=>$next_grade_result['institution_site_programme_id'],
									'school_year_id' => $params['current_year'],
								);
					$caller_this->insertUniqueCensusRow($data);
				}
			}//end row yield
		);
    }
	
	
	//Estimates Grade for a year , using data from institution_site's own census history
	/* NOTE : if the institute has a history of having students of the non-official age , but still of type student_category 1 , it will still be added.
		ie: grade1 , age 6 : avg4 ppl
			grade1 , age 7 : avg5 ppl
			grade1 , age 8 : avg6 ppl
		estimation will have 3 rows for grade1 , even if the official age for grade1 is age6
	*/
	public function censusEstimateGradeForYear($current_year,$options=array()){
		$student_category = 1; // 1 is assumed to be "Promoted or New Enrolment" , the most generic category student type. transfers , repeats etc are all ignored.
		
		//find average for first supported grade(intake) for each institution_site
		$intake_avg = "SELECT 
			ROUND(AVG(cs.male),0) AS average_male,
			ROUND(AVG(cs.female),0) AS average_female,
			cs.education_grade_id AS education_grade_id,
			cs.institution_site_id AS institution_site_id,
			cs.age AS age
		FROM census_students cs 
		WHERE 
			cs.student_category_id= %s
			AND cs.education_grade_id = ( SELECT 
											eg.id FROM institution_site_programmes isp 
										JOIN education_grades eg on eg.education_programme_id = isp.education_programme_id 
										JOIN education_programmes ep on ep.id = eg.education_programme_id 
										WHERE institution_site_id = cs.institution_site_id
											AND school_year_id =%s ORDER BY eg.`order` asc,ep.`order` ASC";
										$grade_offset = isset($options['grade_offset'])? $options['grade_offset']:0;
										$intake_avg .= " LIMIT ". $grade_offset .",1 )";
			if (isset($options['census_table'])){ //for limiting census size , ie:do for specific institution_site
				foreach ((array)$options['census_table'] as $key=>$val){
					$intake_avg.= " AND cs.`" .  $key . "`=" . $val;
				}
			}
		$intake_avg .= " GROUP BY cs.education_grade_id,cs.institution_site_id,cs.age";
		$intake_avg_sql = sprintf($intake_avg,$student_category,$current_year);
		#echo $intake_avg_sql;die;
		
		$current_this = isset($options['caller_this'])? $options['caller_this']:$this;
		$this->exeSQLwLimitnRowpayload(
			$current_this,
			$intake_avg_sql,
			//params
			array('current_year'=>$current_year,
					'student_category'=>$student_category,
					'options'=>$options
			),
			//row_yield
			function($caller_this,$row,$params){
				$institution_site_programme_id = $caller_this->getInstitutionSiteProgrammeId($row['institution_site_id'],$params['current_year'],$row['education_grade_id']);
				$data = array('age'=>(int)$row['age'],
							'male'=>(int)$row['average_male'],
							'female'=>(int)$row['average_female'],
							'student_category_id'=>$params['student_category'],
							'education_grade_id'=>$row['education_grade_id'],
							'institution_site_id'=>$row['institution_site_id'],
							'institution_site_programme_id'=>$institution_site_programme_id,
							'school_year_id' => $params['current_year'],
						);
				$caller_this->insertUniqueCensusRow($data);
			}//end row yield
		);
			
	}
	
	/* Looks up institution_sites that dont have records in current year and attempt to generate data for them
			find last found year with census data
				//shift found year into current year , increment age and grade accordingly
				//estimate numbers only for student_category 1 , aggregate average intake from current institution_site history
			if not found at all
				aggregate median total number for each grade/student_category/age from institution_sites in the same area
	*/					
	public function censusEstimateMissingInstitutesforYear($current_year,$options=array()){
		//look for missing institutes from the current year census with basic matching for past census history and institutes in the same area(for estimates matching)
		$census_missing_institutes = "SELECT 
			missing_sites.institution_site_id AS institution_site_id,
			missing_sites.area_id AS area_id,
			similar_sites.similar_list AS similar_list,
			history.last_census_year AS last_census_year
		FROM (	SELECT 
					isites.id AS institution_site_id,
					isites.area_id AS area_id
				FROM institution_sites isites 
				WHERE isites.id NOT IN (SELECT institution_site_id 
										FROM census_students 
										WHERE school_year_id= %s 
										GROUP BY institution_site_id) 
				) missing_sites 
		JOIN (	SELECT area_id,
						GROUP_CONCAT(DISTINCT(id) ) AS similar_list 
				FROM institution_sites GROUP BY area_id ) similar_sites 
		ON missing_sites.area_id = similar_sites.area_id
		LEFT OUTER JOIN (	SELECT institution_site_id,
									max(distinct(school_year_id)) AS last_census_year 
							FROM census_students 
							GROUP BY institution_site_id
						) history 
		ON missing_sites.institution_site_id = history.institution_site_id
		ORDER BY missing_sites.institution_site_id ASC
		";
		$census_missing_institutes_sql = sprintf($census_missing_institutes,$current_year);
		
		$current_this = isset($options['caller_this'])? $options['caller_this']:$this;
		$this->exeSQLwLimitnRowpayload(
			$current_this, 
			$census_missing_institutes_sql, //your SQL to chunk execute
			array('current_year'=>$current_year,  //params to feed into your row_yield 
					'options'=>$options
			),
			function($caller_this,$row,$params){

				//has history
				if (!is_null($row['last_census_year'])){
					echo $row['institution_site_id'] . " HAS HISTORY \n";
					$year_difference = $caller_this->getSchoolYearDifference($params['current_year'],$row['last_census_year']);
					//fill in year shifted data 
					$caller_this->censusShiftPastYearToCurrentYear($params['current_year'],
																	$row['last_census_year'],
																	array('caller_this'=>$caller_this,
																		'census_table'=>array('institution_site_id'=>$row['institution_site_id']),
																		'year_difference'=>$year_difference
																		)
																);
					//fill in estimated data for missing grades
					for($i=0;$i<$year_difference;$i++){
						$caller_this->censusEstimateGradeForYear($params['current_year'],
																array('caller_this'=>$caller_this,
																	'grade_offset'=>$i,
																	'census_table'=>array('institution_site_id'=>$row['institution_site_id']),
																	)
																);
					}
				}

				/*	
					totally no history , will also help to fill in data incase "has history" produced few rows due to incomplete/sparse history
					finds institutions in the same area that has the same grades
					and use average numbers from them
					
				*/
				$supported_grades = "SELECT GROUP_CONCAT(eg.id) AS grades FROM institution_site_programmes isp
									JOIN education_grades eg
									ON eg.education_programme_id = isp.education_programme_id
									AND isp.school_year_id = %s
									AND isp.institution_site_id = %s ";
				$supported_grades_sql = sprintf($supported_grades,$params['current_year'],$row['institution_site_id']);
				$supported_grades_result=$caller_this->mysqlquery($supported_grades_sql);
				$supported_grades_row=mysqli_fetch_array($supported_grades_result);
				$grades = $supported_grades_row[0];
				
				//institution_site does support grades this year
				if ($grades != "") {
					$other_institution_numbers = "SELECT education_grade_id, 
														age, 
														ROUND( AVG( male ) , 0 ) AS average_male, 
														ROUND( AVG( female ) , 0 ) AS average_female
												FROM  `census_students` 
												WHERE education_grade_id
												IN ( %s ) 
												AND institution_site_id
												IN ( %s ) 
												AND student_category_id =1
												GROUP BY education_grade_id, age";
					$other_institution_numbers_sql = sprintf($other_institution_numbers,$grades,$row['similar_list']);
					$other_institution_numbers_result=$caller_this->mysqlquery($other_institution_numbers_sql);
					#echo $other_institution_numbers_sql."\n";
					while($other_institution_numbers_row = mysqli_fetch_array($other_institution_numbers_result)){
						if (!isset($other_institution_numbers_row['age'])) { 
							//no census history found for grades in institution_sites of this area
							//maybe should cut this section out as a function and then call itself recursively to find data from areas above/beside it if it cant data in current area
						} else {
							$institution_site_programme_id = $caller_this->getInstitutionSiteProgrammeId($row['institution_site_id'],$params['current_year'],$other_institution_numbers_row['education_grade_id']);
					
							$data = array('age'=>(int)$other_institution_numbers_row['age'],
								'male'=>(int)$other_institution_numbers_row['average_male'],
								'female'=>(int)$other_institution_numbers_row['average_female'],
								'student_category_id'=>1,
								'education_grade_id'=>$other_institution_numbers_row['education_grade_id'],
								'institution_site_id'=>$row['institution_site_id'],
								'institution_site_programme_id'=>$institution_site_programme_id,
								'school_year_id' => $params['current_year'],
							);
							$caller_this->insertUniqueCensusRow($data);
						}
					}
				}
			} 
		);
	}
	
	
	/* 
		CENSUS HELPERS
	*/
		
	public function getInstitutionSiteProgrammeId($institution_site_id,$year,$education_grade_id){
		$institution_site_programme = "SELECT isp.id AS institution_site_programme_id
												FROM education_grades eg
												JOIN institution_site_programmes isp ON isp.education_programme_id = eg.education_programme_id
												AND isp.institution_site_id = %s
												AND isp.school_year_id = %s
												AND eg.id = %s";
		$institution_site_programme_sql = sprintf($institution_site_programme,$institution_site_id,$year,$education_grade_id);
		$institution_site_programme_result=mysqli_fetch_array($this->mysqlquery($institution_site_programme_sql));
		if ( isset($institution_site_programme_result['institution_site_programme_id']) ){
			return $institution_site_programme_result['institution_site_programme_id'];
		}
		return null;
	}
	
	
	/*
		DATABASE HELPER SECTION
		SINCE NOT USING CAKE MODELS
	*/
	
	
	public function getCount($sql){
		$sql = "select count(*) as count from (" . $sql . ") as count_this";
		$result = mysqli_fetch_array($this->mysqlquery($sql));
		return (int)$result[0];
	}
	
	public function getSchoolYearDifference($current_year_id,$previous_year_id){
		$sql = "select start_year from school_years where id= $current_year_id";
		$result = mysqli_fetch_array($this->mysqlquery($sql));
		$current_year=(int)$result[0];
		
		$sql = "select start_year from school_years where id= $previous_year_id";
		$result = mysqli_fetch_array($this->mysqlquery($sql));
		$previous_year=(int)$result[0];
		
		$difference = $current_year - $previous_year;
		return $difference;
	}
	
	public function getSchoolYearWithOffset($offset=0){ //defaults to current
		$sql = "select id from school_years order by current desc,start_year desc limit " . $offset . ",1";
		$result = mysqli_fetch_array($this->mysqlquery($sql));
		$year=(int)$result[0];
		return $year;
	}
	
	public function mysqlquery($statement){
		if (!isset($this->dbconfig)) {
			$db = $this->CensusStudent->getDataSource();
			$this->dbconfig=$db->config;
		}
		$host = $this->dbconfig['host'];
		if (isset($this->dbconfig['port']) && !empty($this->dbconfig['port'])) {
			$host .= ":".$this->dbconfig['port'];
		}
		$mysql=mysqli_connect($host,$this->dbconfig['login'],$this->dbconfig['password'],$this->dbconfig['database']);
		return mysqli_query($mysql,$statement);
	}
	
	public function insertUniqueCensusRow($data=array(),$options=array()){
		$unique_identifiers = array('education_grade_id','institution_site_id','school_year_id','student_category_id','age');
		$default = array (							
							'source' => CENSUS_ESTIMATE,
							'modified_user_id'=> 1,
							'modified' => '0000-00-00 00:00:00',
							'created_user_id' => 1,
							'created' => '0000-00-00 00:00:00',
						);
		$data = array_replace($default, $data);//array behind will always replace duplicate values in arrays in front		
		
		$existing = "SELECT id,source FROM census_students WHERE ";
		foreach ($unique_identifiers as &$u){
			$u = $u . "='". $data[$u] ."'";
		}
		$existing .=  implode(' AND ', $unique_identifiers);
		$existing_result = mysqli_fetch_array($this->mysqlquery($existing));

		$update = "INSERT";
		if (!is_null($existing_result['id'])){
			if ( ($existing_result['source'] > $data['source'] ) || 
					(($existing_result['source'] == $data['source']) && isset($options['overwrite']) )
				){
				$update = "REPLACE";
				$data['id'] = $existing_result['id'];
			}else {
				return;
			}
		}
		$update .= " INTO census_students (". implode(",",array_keys($data)). ") ";	
		$update .= " VALUES ('". implode("','",array_values($data)). "')";	

		#echo "UPDATE - " . $update."\n";
		#die;
		$this->mysqlquery($update);
	}
	
	/*
		takes a sql statement , breaks down sql execution into smaller chunk size execution with limit / offsets by the defined $this->limit
		all row_yield should be just direct fire sql upates etc and don't store data , cos storing data cause out-of-memory when running huge statements
		example:
		$current_this = isset($options['caller_this'])? $options['caller_this']:$this;
		$this->exeSQLwLimitnRowpayload(
			$current_this, 
			$census_sql, //your SQL to chunk execute
			array('current_year'=>$current_year,  //params to feed into your row_yield 
					'previous_year'=>$previous_year,
					'options'=>$options
			),
			function($caller_this,$row,$params){} //row_yield , stuff to do for each row fetched , $caller_this is $this of the caller
		)
	*/
	public function exeSQLwLimitnRowpayload(&$caller_this,$sql,$params=array(),$row_yield){
		$count = $this->getCount($sql);
		echo "\n COUNT : $count \n";
		for($offset = 0;$offset <= $count; $offset=$offset+$this->limit){	
			$result=$this->mysqlquery($sql." limit ".$offset." , ".$this->limit);
			
			while($row = mysqli_fetch_array($result)){
				/* passed $caller_this over to row_yield , so they can call $caller_this as substitue for their $this native to the env they were run in 
				ugly but ran out of ideas */
				try {
					$row_yield($caller_this,$row,$params); //execute attached payload for each row
				}catch (Exception $e) {
					echo 'Error at row: ' . print_r($params, true)."\n\n".print_r($row, true)."\n\n". $e->getMessage(). "\n\n";
				}
			}
			echo "\n FETCHED : $offset , " . $this->limit . " \n";
		}
	}
}
/*
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


/****
 * SHIFT PRev Year to curr year
 */

/*
 * 
 * INSERT INTO census_students
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
 * 
DROP TABLE IF EXISTS magic;
CREATE TABLE magic ENGINE=MyISAM
SELECT COUNT( cs.id ) AS tot, AVG( cs.male ) as avg_male , AVG( cs.female ) as  avg_female, education_grade_id, institution_site_type_id, area_id, age
	FROM  `census_students` cs
	JOIN institution_sites ins ON ins.id = cs.institution_site_id
	WHERE cs.school_year_id =6
	GROUP BY institution_site_type_id, area_id, education_grade_id;
ALTER TABLE magic ADD INDEX (education_grade_id, institution_site_type_id,area_id,age);

INSERT INTO census_students
SELECT null,cst.age,FLOOR(avg_male),FLOOR(avg_female),cst.student_category_id, cst.education_grade_id,cst.institution_site_id,cst.institution_site_programme_id,cst.school_year_id,2,0,'0000-00-00 00:00:00',1,NOW()
FROM census_students cst
LEFT JOIN institution_sites p ON p.id = cst.institution_site_id
LEFT JOIN magic l ON l.area_id
AND p.area_id = l.area_id 
AND l.institution_site_type_id = p.institution_site_type_id
AND cst.education_grade_id = l.education_grade_id
WHERE cst.school_year_id =6;
DROP TABLE magic;
 */

/****
 * 
 * NEW VARIATION of 
 */

/**
 
 * 
 * 
 * SELECT isi.id as school_id,eg.name, isi.area_id, isi.institution_site_type_id , isi.name as school_name, eg.id , ec.admission_age, cens.* FROM institution_sites isi 

LEFT JOIN institution_site_programmes isp ON isi.id = isp.institution_site_id

LEFT JOIN education_programmes ep on ep.id = isp.education_programme_id

LEFT JOIN (

    SELECT p1.*
    FROM education_grades p1 LEFT JOIN education_grades p2
    ON (p1.education_programme_id = p2.education_programme_id AND p1.id > p2.id)
    WHERE p2.education_programme_id IS NULL 

) eg ON eg.education_programme_id = isp.education_programme_id

LEFT JOIN education_cycles ec on  ec.id = ep.education_cycle_id

LEFT JOIN census_students cens ON cens.education_grade_id = eg.id AND isi.id = cens.institution_site_id AND cens.school_year_id = 6  AND cens.student_category_id = 1

WHERE isp.school_year_id = 6 and eg.id is not null and cens.id is  null
 * 
 * 
 * 
 */
?>
