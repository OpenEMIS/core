<?php  
App::uses('AppTask', 'Console/Command/Task');

define("CENSUS_DATAENTRY", 0); 
define("CENSUS_EXTERNAL", 20);
define("CENSUS_INTERNAL", 30);
define("CENSUS_ESTIMATE", 40);

class EstTask extends AppTask {
	public $limit = 1000;
	public $tasks = array('Common');
	public function genEST($settings){
		
		$current_year = $this->getSchoolYearWithOffset();
		$previous_year = $this->getSchoolYearWithOffset(1);
	
		try{
			eval($settings['sql']);
			echo "finissshh";
		} catch (Exception $e) {
			// Update the status for the Processed item to (-1) ERROR
			$errLog = $e->getMessage();
			$this->Common->updateStatus($settings['batchProcessId'],'-1');
			$this->Common->createLog($this->Common->getLogPath().$procId.'.log',$errLog);
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
					SELECT null, A1.age, IF(ISNULL(A1.male),0,A1.male), IF(ISNULL(A2.female),0,A2.female), 1,  A1.education_grade_id,  A1.institution_site_id, {curr_year},3,1,'0000-00-00 00:00:00',1,'0000-00-00 00:00:00'  
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
					SELECT null,A2.age, IF(ISNULL(A1.male),0,A1.male), IF(ISNULL(A2.female),0,A2.female),1,  A2.education_grade_id,  A2.institution_site_id,  {curr_year},3,1,'0000-00-00 00:00:00',1,'0000-00-00 00:00:00' 
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
