<?php

/**
 * generate a report for staff requirements
 * @author rishabh sharma <rishabh.sharma1@mail.valuecoders.com>
 */

namespace Report\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class StaffRequirementsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institutions');

        parent::initialize($config);

        $this->addBehavior('Excel', ['excludes' => [], 'pages' => ['index'],]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $studentPerTeacherRatio = $requestData->student_per_teacher_ratio;
        $upperTolerance = $requestData->upper_tolerance;
        $lowerTolerance = $requestData->lower_tolerance;
        $UpperTolerance = $upperTolerance/100;
        $LowerTolerance= $lowerTolerance/100;
        $join =[];
        $subQuery = "(SELECT institutions.id institutions_id
		,education_stages.id education_stages_id
		,education_subjects.id education_subjects_id
    	,IFNULL(area_level_layer_four.name, '') area_level_layer_four
		,IFNULL(area_layer_four.code, '') area_layer_four_code
		,IFNULL(area_layer_four.name, '') area_layer_four_name
    	,IFNULL(area_level_layer_three.name, '') area_level_layer_three
		,IFNULL(area_layer_three.code, '') area_layer_three_code
		,IFNULL(area_layer_three.name, '') area_layer_three_name
    	,IFNULL(area_level_layer_two.name, '') area_level_layer_two
		,IFNULL(area_layer_two.code, '') area_layer_two_code
		,IFNULL(area_layer_two.name, '') area_layer_two_name
    	,IFNULL(area_level_layer_one.name, '') area_level_layer_one
		,area_layer_one.code area_layer_one_code
		,area_layer_one.name area_layer_one_name
		,institutions.code institutions_code
		,institutions.name institutions_name
		,institutions.alternative_name institutions_alternative_name
		,institutions.address institutions_address
		,institutions.postal_code institutions_postal_code
		,institutions.contact_person institutions_contact_person
		,institutions.telephone institutions_telephone
		,institutions.fax institutions_fax
		,institutions.email institutions_email
		,institutions.website institutions_website
		,institutions.date_opened institutions_date_opened
		,institutions.year_opened institutions_year_opened
		,institutions.date_closed institutions_date_closed
		,institutions.year_closed institutions_year_closed
		,institutions.longitude institutions_longitude
		,institutions.latitude institutions_latitude
		,institutions.logo_name institutions_logo_name
		,institutions.logo_content institutions_logo_content
		,institutions.shift_type institutions_shift_type
		,institutions.classification institutions_classification
		,institutions.area_id institutions_area_id
		,institutions.area_administrative_id institutions_area_administrative_id
		,institutions.institution_locality_id institution_locality_id
		,institutions.institution_type_id institution_type_id
		,institutions.institution_ownership_id institutions_institution_ownership_id
		,institutions.institution_status_id institutions_institution_status_id
		,institutions.institution_sector_id institutions_institution_sector_id
		,institutions.institution_provider_id institutions_institution_provider_id
		,institutions.institution_gender_id institutions_institution_gender_id
		,institutions.security_group_id institutions_security_group_id	
		,institutions.modified_user_id institutions_modified_user_id
		,institutions.modified institutions_modified
		,institutions.created_user_id institutions_created_user_id
		,institutions.created institutions_created	
		,education_grades.code education_grades_code
		,education_grades.name education_grades_name
		,education_subjects.code education_subjects_code
		,education_subjects.name education_subjects_name
		,@Students_per_Teacher := $studentPerTeacherRatio Students_per_Teacher -- Based on what user selects as assumption
		,@Lower_Tolerance := $LowerTolerance Lower_Tolerance 		  -- Based on what user selects as assumption
		,@Upper_Tolerance := $UpperTolerance Upper_Tolerance 		  -- Based on what user selects as assumption
		,SUM(CASE WHEN student_subjects.academic_period_id = @previous_previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END) 2018_students
		,SUM(CASE WHEN student_subjects.academic_period_id = @previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END) 2019_students
		,SUM(CASE WHEN student_subjects.academic_period_id = @previous_previous_year_id THEN 1 ELSE 0.0000000000001 END) 2020_students
		,SUM(CASE WHEN student_subjects.academic_period_id = @previous_year_id THEN 1 ELSE 0.0000000000001 END) 2021_students
		,SUM(CASE WHEN student_subjects.academic_period_id = @current_year_id THEN 1 ELSE 0.0000000000001 END) 2022_students
	FROM 
	(
		SELECT institution_subject_students.*
		FROM institution_subject_students
		INNER JOIN academic_periods
		ON academic_periods.id = institution_subject_students.academic_period_id
		LEFT JOIN
		(
			SELECT @previous_previous_previous_previous_year_id := previous_current_join_4.academic_period_id previous_previous_previous_previous_year_id
				,@previous_previous_previous_year_id previous_previous_previous_year_id
				,@previous_previous_year_id previous_previous_year_id
				,@previous_year_id previous_year_id
				,@current_year_id current_year_id
			FROM
			(
				SELECT operational_academic_periods_4.academic_period_id
					,@previous_previous_previous_previous_start_year := MAX(academic_periods.start_date) previous_previous_previous_previous_start_year
				FROM 
				(
					SELECT institution_students.academic_period_id
					FROM institution_students
					GROUP BY institution_students.academic_period_id
				) operational_academic_periods_4
				INNER JOIN academic_periods
				ON academic_periods.id = operational_academic_periods_4.academic_period_id
				LEFT JOIN 
				(
					SELECT @previous_previous_previous_year_id := previous_current_join_3.academic_period_id previous_previous_previous_year_id
						,@previous_previous_previous_start_year previous_previous_previous_start_year
					FROM 
					(
						SELECT operational_academic_periods_3.academic_period_id
							,@previous_previous_previous_start_year := MAX(academic_periods.start_date) previous_start_year
						FROM 
						(
							SELECT institution_students.academic_period_id
							FROM institution_students
							GROUP BY institution_students.academic_period_id
						) operational_academic_periods_3
						INNER JOIN academic_periods
						ON academic_periods.id = operational_academic_periods_3.academic_period_id
						LEFT JOIN 
						(
							SELECT @previous_previous_year_id := previous_current_join_2.academic_period_id previous_previous_year_id
								,@previous_previous_start_year previous_previous_start_year
							FROM
							(
								SELECT operational_academic_periods_2.academic_period_id
									,@previous_previous_start_year := MAX(academic_periods.start_date) previous_previous_start_year
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) subq2
							INNER JOIN
							(
								SELECT operational_academic_periods_2.academic_period_id
									,academic_periods.start_date
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) previous_current_join_2
							ON previous_current_join_2.start_date = @previous_previous_start_year
						) t_2
						ON t_2.previous_previous_year_id = @previous_previous_year_id 
						WHERE academic_periods.start_date < @previous_previous_start_year
					) subq3
					INNER JOIN
					(
						SELECT operational_academic_periods_3.academic_period_id
							,academic_periods.start_date
						FROM 
						(
							SELECT institution_students.academic_period_id
							FROM institution_students
							GROUP BY institution_students.academic_period_id
						) operational_academic_periods_3
						INNER JOIN academic_periods
						ON academic_periods.id = operational_academic_periods_3.academic_period_id
						LEFT JOIN 
						(
							SELECT @previous_previous_year_id := previous_current_join_2.academic_period_id previous_previous_year_id
								,@previous_previous_start_year previous_previous_start_year
							FROM
							(
								SELECT operational_academic_periods_2.academic_period_id
									,@previous_previous_start_year := MAX(academic_periods.start_date) previous_previous_start_year
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) subq2
							INNER JOIN
							(
								SELECT operational_academic_periods_2.academic_period_id
									,academic_periods.start_date
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) previous_current_join_2
							ON previous_current_join_2.start_date = @previous_previous_start_year
						) t_2
						ON t_2.previous_previous_year_id = @previous_previous_year_id 
						WHERE academic_periods.start_date < @previous_previous_start_year
					) previous_current_join_3
					ON previous_current_join_3.start_date = @previous_previous_previous_start_year
				) t_3
				ON t_3.previous_previous_previous_year_id = @previous_previous_previous_year_id 
				WHERE academic_periods.start_date < @previous_previous_previous_start_year
			) subq4
			INNER JOIN 
			(
				SELECT operational_academic_periods_4.academic_period_id
					,academic_periods.start_date
				FROM 
				(
					SELECT institution_students.academic_period_id
					FROM institution_students
					GROUP BY institution_students.academic_period_id
				) operational_academic_periods_4
				INNER JOIN academic_periods
				ON academic_periods.id = operational_academic_periods_4.academic_period_id
				LEFT JOIN 
				(
					SELECT @previous_previous_previous_year_id := previous_current_join_3.academic_period_id previous_previous_previous_year_id
						,@previous_previous_previous_start_year previous_previous_previous_start_year
					FROM 
					(
						SELECT operational_academic_periods_3.academic_period_id
							,@previous_previous_previous_start_year := MAX(academic_periods.start_date) previous_start_year
						FROM 
						(
							SELECT institution_students.academic_period_id
							FROM institution_students
							GROUP BY institution_students.academic_period_id
						) operational_academic_periods_3
						INNER JOIN academic_periods
						ON academic_periods.id = operational_academic_periods_3.academic_period_id
						LEFT JOIN 
						(
							SELECT @previous_previous_year_id := previous_current_join_2.academic_period_id previous_previous_year_id
								,@previous_previous_start_year previous_previous_start_year
							FROM
							(
								SELECT operational_academic_periods_2.academic_period_id
									,@previous_previous_start_year := MAX(academic_periods.start_date) previous_previous_start_year
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) subq2
							INNER JOIN
							(
								SELECT operational_academic_periods_2.academic_period_id
									,academic_periods.start_date
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) previous_current_join_2
							ON previous_current_join_2.start_date = @previous_previous_start_year
						) t_2
						ON t_2.previous_previous_year_id = @previous_previous_year_id 
						WHERE academic_periods.start_date < @previous_previous_start_year
					) subq3
					INNER JOIN
					(
						SELECT operational_academic_periods_3.academic_period_id
							,academic_periods.start_date
						FROM 
						(
							SELECT institution_students.academic_period_id
							FROM institution_students
							GROUP BY institution_students.academic_period_id
						) operational_academic_periods_3
						INNER JOIN academic_periods
						ON academic_periods.id = operational_academic_periods_3.academic_period_id
						LEFT JOIN 
						(
							SELECT @previous_previous_year_id := previous_current_join_2.academic_period_id previous_previous_year_id
								,@previous_previous_start_year previous_previous_start_year
							FROM
							(
								SELECT operational_academic_periods_2.academic_period_id
									,@previous_previous_start_year := MAX(academic_periods.start_date) previous_previous_start_year
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) subq2
							INNER JOIN
							(
								SELECT operational_academic_periods_2.academic_period_id
									,academic_periods.start_date
								FROM 
								(
									SELECT institution_students.academic_period_id
									FROM institution_students
									GROUP BY institution_students.academic_period_id
								) operational_academic_periods_2
								INNER JOIN academic_periods
								ON academic_periods.id = operational_academic_periods_2.academic_period_id
								LEFT JOIN
								(
									SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
										,@previous_start_year previous_start_year
									FROM
									(
										SELECT operational_academic_periods_1.academic_period_id
											,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) subq
									INNER JOIN
									(
										SELECT operational_academic_periods_1.academic_period_id
											,academic_periods.start_date start_year
										FROM 
										(
											SELECT institution_students.academic_period_id
											FROM institution_students
											GROUP BY institution_students.academic_period_id
										) operational_academic_periods_1
										INNER JOIN academic_periods
										ON academic_periods.id = operational_academic_periods_1.academic_period_id
										LEFT JOIN 
										(
											SELECT @current_year_id := academic_periods.id current_academic_periods_id
												,@current_start_year := academic_periods.start_date curent_start_date
											FROM 
											(
												SELECT institution_students.academic_period_id
												FROM institution_students
												GROUP BY institution_students.academic_period_id
											) operational_academic_periods
											INNER JOIN academic_periods
											ON academic_periods.id = operational_academic_periods.academic_period_id
											WHERE academic_periods.current = 1
										) t
										ON t.current_academic_periods_id = @current_year_id
										WHERE academic_periods.start_date < @current_start_year
									) previous_current_join
									ON previous_current_join.start_year = @previous_start_year
								) t_1
								ON t_1.previous_academic_period_id = @previous_year_id
								WHERE academic_periods.start_date < @previous_start_year
							) previous_current_join_2
							ON previous_current_join_2.start_date = @previous_previous_start_year
						) t_2
						ON t_2.previous_previous_year_id = @previous_previous_year_id 
						WHERE academic_periods.start_date < @previous_previous_start_year
					) previous_current_join_3
					ON previous_current_join_3.start_date = @previous_previous_previous_start_year
				) t_3
				ON t_3.previous_previous_previous_year_id = @previous_previous_previous_year_id 
				WHERE academic_periods.start_date < @previous_previous_previous_start_year
			) previous_current_join_4
			ON previous_current_join_4.start_date = @previous_previous_previous_previous_start_year
		) dynamic_academic_periods
		ON academic_periods.id = @current_year_id
		WHERE IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_subject_students.student_status_id = 1, institution_subject_students.student_status_id IN (1, 7, 6, 8))
		GROUP BY institution_subject_students.student_id
			,institution_subject_students.education_subject_id
			,institution_subject_students.academic_period_id
	) student_subjects
	INNER JOIN institutions
	ON institutions.id = student_subjects.institution_id
	INNER JOIN areas area_layer_one
	ON area_layer_one.id = institutions.area_id
    INNER JOIN area_levels area_level_layer_one
    ON area_level_layer_one.id = area_layer_one.area_level_id
	LEFT JOIN areas area_layer_two
	ON area_layer_two.id = area_layer_one.parent_id
    LEFT JOIN area_levels area_level_layer_two
    ON area_level_layer_two.id = area_layer_two.area_level_id
	LEFT JOIN areas area_layer_three
	ON area_layer_three.id = area_layer_two.parent_id
    LEFT JOIN area_levels area_level_layer_three
    ON area_level_layer_three.id = area_layer_three.area_level_id
	LEFT JOIN areas area_layer_four
	ON area_layer_four.id = area_layer_three.parent_id
    LEFT JOIN area_levels area_level_layer_four
    ON area_level_layer_four.id = area_layer_four.area_level_id
	INNER JOIN education_grades
	ON education_grades.id = student_subjects.education_grade_id
	INNER JOIN education_stages
	ON education_stages.id = education_grades.education_stage_id
	INNER JOIN education_subjects
	ON education_subjects.id = student_subjects.education_subject_id
	GROUP BY area_layer_one.id
		,area_layer_two.id
		,area_layer_three.id
		,area_layer_four.id
		,institutions.id
		,education_stages.id
		,education_subjects.id)";


        $join['staff_query'] = [
            'type' => 'left',
            'table' => "(SELECT 
            staff_subjects.institution_id
            ,staff_subjects.education_stages_id
            ,staff_subjects.education_subject_id
            ,SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END) 2018_staff
            ,SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_previous_year_id THEN 1 ELSE 0.0000000000001 END) 2019_staff
            ,SUM(CASE WHEN staff_subjects.academic_period_id = @previous_previous_year_id THEN 1 ELSE 0.0000000000001 END) 2020_staff
            ,SUM(CASE WHEN staff_subjects.academic_period_id = @previous_year_id THEN 1 ELSE 0.0000000000001 END) 2021_staff
            ,SUM(CASE WHEN staff_subjects.academic_period_id = @current_year_id THEN 1 ELSE 0.0000000000001 END) 2022_staff
        FROM
        (
            SELECT institution_subject_staff.staff_id
                ,institution_subjects.education_subject_id
                ,education_stages.id education_stages_id
                ,institution_subject_staff.institution_id
                ,institution_subjects.academic_period_id
            FROM institution_subject_staff
            INNER JOIN institution_subjects
            ON institution_subjects.id = institution_subject_staff.institution_subject_id
            INNER JOIN 
            (
                SELECT institution_staff.* 
                FROM institution_staff
                GROUP BY institution_staff.staff_id
            ) inst_staff
            ON inst_staff.staff_id = institution_subject_staff.staff_id
            INNER JOIN education_grades
            ON education_grades.id = institution_subjects.education_grade_id
            INNER JOIN education_stages
            ON education_stages.id = education_grades.education_stage_id
            GROUP BY institution_subject_staff.staff_id
                ,institution_subjects.education_subject_id
                ,education_stages.id
                ,institution_subject_staff.institution_id
                ,institution_subjects.academic_period_id
        ) staff_subjects
        GROUP BY staff_subjects.education_subject_id
                ,staff_subjects.education_stages_id
                ,staff_subjects.institution_id)",
            'conditions'=>[
                'staff_query.institution_id = student_query.institutions_id',
                'staff_query.education_stages_id = student_query.education_stages_id',
                'staff_query.education_subject_id = student_query.education_subjects_id'
            ]
            ]; 
    $query
    ->select([
				'StaffRequirements__id' => 'student_query.institutions_id',
				'StaffRequirements__name' => 'student_query.institutions_name',
				'StaffRequirements__alternative_name' => 'student_query.institutions_alternative_name',
				'StaffRequirements__code' => 'student_query.institutions_code',
				'StaffRequirements__address' =>'student_query.institutions_address',
				'StaffRequirements__postal_code' => 'student_query.institutions_postal_code',

				'StaffRequirements__contact_person' => 'student_query.institutions_contact_person',
				'StaffRequirements__telephone' => 'student_query.institutions_telephone',
				'StaffRequirements__fax' => 'student_query.institutions_fax',
				'StaffRequirements__email' => 'student_query.institutions_email',
				'StaffRequirements__website' => 'student_query.institutions_website',
				'StaffRequirements__date_opened' => 'student_query.institutions_date_opened',
				'StaffRequirements__year_opened' => 'student_query.institutions_year_opened',
				'StaffRequirements__date_closed' => 'student_query.institutions_date_closed',
				'StaffRequirements__year_closed' => 'student_query.institutions_year_closed',
				'StaffRequirements__longitude' => 'student_query.institutions_longitude',
				'StaffRequirements__latitude' => 'student_query.institutions_latitude',
				'StaffRequirements__logo_name' => 'student_query.institutions_logo_name',

				'StaffRequirements__logo_content' => 'student_query.institutions_logo_content',
				'StaffRequirements__shift_type' => 'student_query.institutions_shift_type',
				'StaffRequirements__classification' => 'student_query.institutions_classification',
				'StaffRequirements__area_id' => 'student_query.institutions_area_id',
				'StaffRequirements__area_administrative_id' => 'student_query.institutions_area_administrative_id',
				'StaffRequirements__institution_locality_id' => 'student_query.institution_locality_id',
				'StaffRequirements__institution_type_id' => 'student_query.institution_type_id',

				'StaffRequirements__institution_ownership_id' => 'student_query.institutions_institution_ownership_id',
				'StaffRequirements__institution_status_id' => 'student_query.institutions_institution_status_id',
				'StaffRequirements__institution_sector_id' => 'student_query.institutions_institution_sector_id',
				'StaffRequirements__institution_provider_id' => 'student_query.institutions_institution_provider_id',
				'StaffRequirements__institution_gender_id' => 'student_query.institutions_institution_gender_id',
				'StaffRequirements__security_group_id' => 'student_query.institutions_security_group_id',
				'StaffRequirements__modified_user_id' => 'student_query.institutions_modified_user_id',
				'StaffRequirements__modified' => 'student_query.institutions_modified',
				'StaffRequirements__created_user_id' => 'student_query.institutions_created_user_id',
				'StaffRequirements__created' => 'student_query.institutions_created',


                'area_level_4' => 'student_query.area_level_layer_four',
                'area_code_4' => 'student_query.area_layer_four_code',
                'area_name_4' => 'student_query.area_layer_four_name',
                'area_level_3' => 'student_query.area_level_layer_three',
                'area_code_3' => 'student_query.area_layer_three_code',
                'area_name_3' => 'student_query.area_layer_three_name',
                'area_level_2' => 'student_query.area_level_layer_two',
                'area_code_2' => 'student_query.area_layer_two_code',
                'area_name_2' => 'student_query.area_layer_two_name',
                'area_level_1' => 'student_query.area_level_layer_one',
                'area_code_1' => 'student_query.area_layer_one_code',
                'area_name_1' => 'student_query.area_layer_one_name',
                'institution_code' => 'student_query.institutions_code',
                'institution_name' => 'student_query.institutions_name',
                'education_grade_code' => 'student_query.education_grades_code',
                'education_grade_name' => 'student_query.education_grades_name',
                'education_subject_code' => 'student_query.education_subjects_code',
                'education_subjects_name' => 'student_query.education_subjects_name',
                'students_per_teacher_benchmark' => 'student_query.Students_per_Teacher',

                'Historical_T_4_Students' => "(ROUND(IF(student_query.2018_students < 1, 0, student_query.2018_students), 0))",
                'Historical_T_3_Students' => "(ROUND(IF(student_query.2019_students < 1, 0, student_query.2019_students), 0))",
                'Historical_T_2_Students' => "(ROUND(IF(student_query.2020_students < 1, 0, student_query.2020_students), 0))",
                'Historical_T_1_Students' => "(ROUND(IF(student_query.2021_students < 1, 0, student_query.2021_students), 0))",
                'Current_T_Year_Students' => "(ROUND(IF(student_query.2022_students < 1, 0, student_query.2022_students), 0))",
                
                'Projected_T_plus_1_Students' => "(@2023_students := IFNULL(ROUND(2022_students * ((IF(2022_students/2021_students > @Upper_Tolerance, @Upper_Tolerance, IF(2022_students/2021_students < @Lower_Tolerance, @Lower_Tolerance, 2022_students/2021_students)) + IF(2021_students/2020_students > @Upper_Tolerance, @Upper_Tolerance, IF(2021_students/2020_students < @Lower_Tolerance, @Lower_Tolerance, 2021_students/2020_students)) + IF(2020_students/2019_students > @Upper_Tolerance, @Upper_Tolerance, IF(2020_students/2019_students < @Lower_Tolerance, @Lower_Tolerance, 2020_students/2019_students)) + IF(2019_students/2018_students > @Upper_Tolerance, @Upper_Tolerance, IF(2019_students/2018_students < @Lower_Tolerance, @Lower_Tolerance, 2019_students/2018_students)))/4), 0), 0))",
                'Projected_T_plus_2_Students' => "(@2024_students := IFNULL(ROUND(@2023_students * ((IF(@2023_students/2022_students > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_students/2022_students < @Lower_Tolerance, @Lower_Tolerance, @2023_students/2022_students)) + IF(2022_students/2021_students > @Upper_Tolerance, @Upper_Tolerance, IF(2022_students/2021_students < @Lower_Tolerance, @Lower_Tolerance, 2022_students/2021_students)) + IF(2021_students/2020_students > @Upper_Tolerance, @Upper_Tolerance, IF(2021_students/2020_students < @Lower_Tolerance, @Lower_Tolerance, 2021_students/2020_students)) + IF(2020_students/2019_students > @Upper_Tolerance, @Upper_Tolerance, IF(2020_students/2019_students < @Lower_Tolerance, @Lower_Tolerance, 2020_students/2019_students)))/4), 0), 0))",
                'Projected_T_plus_3_Students' => "(@2025_students := IFNULL(ROUND(@2024_students * ((IF(@2024_students/@2023_students > @Upper_Tolerance, @Upper_Tolerance, IF(@2024_students/@2023_students < @Lower_Tolerance, @Lower_Tolerance, @2024_students/@2023_students)) + IF(@2023_students/2022_students > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_students/2022_students < @Lower_Tolerance, @Lower_Tolerance, @2023_students/2022_students)) + IF(2022_students/2021_students > @Upper_Tolerance, @Upper_Tolerance, IF(2022_students/2021_students < @Lower_Tolerance, @Lower_Tolerance, 2022_students/2021_students)) + IF(2021_students/2020_students > @Upper_Tolerance, @Upper_Tolerance, IF(2021_students/2020_students < @Lower_Tolerance, @Lower_Tolerance, 2021_students/2020_students)))/4), 0), 0))",
                
                'Historical_T_4_Staff' => "(ROUND(IF(IFNULL(staff_query.2018_staff, 0) < 1, 0, IFNULL(staff_query.2018_staff, 0)), 0))",
                'Historical_T_3_Staff' => "(ROUND(IF(IFNULL(staff_query.2019_staff, 0) < 1, 0, IFNULL(staff_query.2019_staff, 0)), 0))",
                'Historical_T_2_Staff' => "(ROUND(IF(IFNULL(staff_query.2020_staff, 0) < 1, 0, IFNULL(staff_query.2020_staff, 0)), 0))",
                'Historical_T_1_Staff' => "(ROUND(IF(IFNULL(staff_query.2021_staff, 0) < 1, 0, IFNULL(staff_query.2021_staff, 0)), 0))",
                'Current_T_Year_Staff' => "(ROUND(IF(IFNULL(staff_query.2022_staff, 0) < 1, 0, IFNULL(staff_query.2022_staff, 0)), 0))",
                
                'Projected_T_plus_1_Staff' => "(@2023_staff := IFNULL(ROUND(2022_staff * ((IF(2022_staff/2021_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2022_staff/2021_staff < @Lower_Tolerance, @Lower_Tolerance, 2022_staff/2021_staff)) + IF(2021_staff/2020_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2021_staff/2020_staff < @Lower_Tolerance, @Lower_Tolerance, 2021_staff/2020_staff)) + IF(2020_staff/2019_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2020_staff/2019_staff < @Lower_Tolerance, @Lower_Tolerance, 2020_staff/2019_staff)) + IF(2019_staff/2018_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2019_staff/2018_staff < @Lower_Tolerance, @Lower_Tolerance, 2019_staff/2018_staff)))/4), 0), 0))",
                'Projected_T_plus_2_Staff' => "(@2024_staff := IFNULL(ROUND(@2023_staff * ((IF(@2023_staff/2022_staff > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_staff/2022_staff < @Lower_Tolerance, @Lower_Tolerance, @2023_staff/2022_staff)) + IF(2022_staff/2021_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2022_staff/2021_staff < @Lower_Tolerance, @Lower_Tolerance, 2022_staff/2021_staff)) + IF(2021_staff/2020_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2021_staff/2020_staff < @Lower_Tolerance, @Lower_Tolerance, 2021_staff/2020_staff)) + IF(2020_staff/2019_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2020_staff/2019_staff < @Lower_Tolerance, @Lower_Tolerance, 2020_staff/2019_staff)))/4), 0), 0))",
                'Projected_T_plus_3_Staff' => "(@2025_staff := IFNULL(ROUND(@2024_staff * ((IF(@2024_staff/@2023_staff > @Upper_Tolerance, @Upper_Tolerance, IF(@2024_staff/@2023_staff < @Lower_Tolerance, @Lower_Tolerance, @2024_staff/@2023_staff)) + IF(@2023_staff/2022_staff > @Upper_Tolerance, @Upper_Tolerance, IF(@2023_staff/2022_staff < @Lower_Tolerance, @Lower_Tolerance, @2023_staff/2022_staff)) + IF(2022_staff/2021_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2022_staff/2021_staff < @Lower_Tolerance, @Lower_Tolerance, 2022_staff/2021_staff)) + IF(2021_staff/2020_staff > @Upper_Tolerance, @Upper_Tolerance, IF(2021_staff/2020_staff < @Lower_Tolerance, @Lower_Tolerance, 2021_staff/2020_staff)))/4), 0), 0))",
                'required_staff_T_plus_1' => "(IF(@2023_staff = 0 AND @2023_students = 0, '0', IF(@2023_staff = 0 AND @2023_students != 0, CONCAT('+', CEILING(@2023_students/@Students_per_Teacher)), IF(@2023_students / @2023_staff = @Students_per_Teacher, '0', IF(@2023_students / @2023_staff > @Students_per_Teacher AND (@2023_staff - @2023_students/@Students_per_Teacher) < 0, CONCAT('+', CEILING((@2023_staff - @2023_students/@Students_per_Teacher)*(-1))), IF(@2023_students / @2023_staff > @Students_per_Teacher, CONCAT('+', CEILING(@2023_staff - @2023_students/@Students_per_Teacher)), IF(FLOOR(@2023_staff - @2023_students/@Students_per_Teacher) = 0, '0', CONCAT('-', FLOOR(@2023_staff - @2023_students/@Students_per_Teacher)))))))))",
                'required_staff_T_plus_2' => "(IF(@2024_staff = 0 AND @2024_students = 0, '0', IF(@2024_staff = 0 AND @2024_students != 0, CONCAT('+', CEILING(@2024_students/@Students_per_Teacher)), IF(@2024_students / @2024_staff = @Students_per_Teacher, '0', IF(@2024_students / @2024_staff > @Students_per_Teacher AND (@2024_staff - @2024_students/@Students_per_Teacher) < 0, CONCAT('+', CEILING((@2024_staff - @2024_students/@Students_per_Teacher)*(-1))), IF(@2024_students / @2024_staff > @Students_per_Teacher, CONCAT('+', CEILING(@2024_staff - @2024_students/@Students_per_Teacher)), IF(FLOOR(@2024_staff - @2024_students/@Students_per_Teacher) = 0, '0', CONCAT('-', FLOOR(@2024_staff - @2024_students/@Students_per_Teacher)))))))))",
                'required_staff_T_plus_3' => "(IF(@2025_staff = 0 AND @2025_students = 0, '0', IF(@2025_staff = 0 AND @2025_students != 0, CONCAT('+', CEILING(@2025_students/@Students_per_Teacher)), IF(@2025_students / @2025_staff = @Students_per_Teacher, '0', IF(@2025_students / @2025_staff > @Students_per_Teacher AND (@2025_staff - @2025_students/@Students_per_Teacher) < 0, CONCAT('+', CEILING((@2025_staff - @2025_students/@Students_per_Teacher)*(-1))), IF(@2025_students / @2025_staff > @Students_per_Teacher, CONCAT('+', CEILING(@2025_staff - @2025_students/@Students_per_Teacher)), IF(FLOOR(@2025_staff - @2025_students/@Students_per_Teacher) = 0, '0', CONCAT('-', FLOOR(@2025_staff - @2025_students/@Students_per_Teacher)))))))))"
        ])
    ->from(['student_query' => $subQuery]);
    
    $query->join($join);

    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];
        
        $newFields[] = [
            'key' => 'area_level_4',
            'field' => 'area_level_4',
            'type' => 'string',
            'label' => __('Area Level 4')
        ];

        $newFields[] = [
            'key' => 'area_code_4',
            'field' => 'area_code_4',
            'type' => 'string',
            'label' => __('Area Code 4')
        ];

        $newFields[] = [
            'key' => 'area_name_4',
            'field' => 'area_name_4',
            'type' => 'string',
            'label' => __('Area Name 4')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_level_3',
            'type' => 'string',
            'label' => __('Area Level 3')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_code_3',
            'type' => 'string',
            'label' => __('Area Code 3')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name_3',
            'type' => 'string',
            'label' => __('Area Name 3')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_level_2',
            'type' => 'string',
            'label' => __('Area Level 2')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_code_2',
            'type' => 'string',
            'label' => __('Area Code 2')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name_2',
            'type' => 'string',
            'label' => __('Area Name 2')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_level_1',
            'type' => 'string',
            'label' => __('Area Level 1')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_code_1',
            'type' => 'string',
            'label' => __('Area Code 1')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name_1',
            'type' => 'string',
            'label' => __('Area Name 1')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'education_grade_code',
            'type' => 'string',
            'label' => __('Education Grade Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'education_subject_code',
            'type' => 'string',
            'label' => __('Education Subject Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'education_subjects_name',
            'type' => 'string',
            'label' => __('Education Subjects Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'students_per_teacher_benchmark',
            'type' => 'string',
            'label' => __('Students Per Teacher Benchmark')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_4_Students',
            'type' => 'string',
            'label' => __('Historical (T-4) Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_3_Students',
            'type' => 'string',
            'label' => __('Historical (T-3) Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_2_Students',
            'type' => 'string',
            'label' => __('Historical (T-2) Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_1_Students',
            'type' => 'string',
            'label' => __('Historical (T-1) Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Current_T_Year_Students',
            'type' => 'string',
            'label' => __('Current (T) Year Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Projected_T_plus_1_Students',
            'type' => 'string',
            'label' => __('Projected (T+1) Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Projected_T_plus_2_Students',
            'type' => 'string',
            'label' => __('Projected (T+2) Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Projected_T_plus_3_Students',
            'type' => 'string',
            'label' => __('Projected (T+3) Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_4_Staff',
            'type' => 'string',
            'label' => __('Historical (T-4) Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_3_Staff',
            'type' => 'string',
            'label' => __('Historical (T-3) Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_2_Staff',
            'type' => 'string',
            'label' => __('Historical (T-2) Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Historical_T_1_Staff',
            'type' => 'string',
            'label' => __('Historical (T-1) Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Current_T_Year_Staff',
            'type' => 'string',
            'label' => __('Current (T) Year Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Projected_T_plus_1_Staff',
            'type' => 'string',
            'label' => __('Projected (T+1) Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Projected_T_plus_2_Staff',
            'type' => 'string',
            'label' => __('Projected (T+2) Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'Projected_T_plus_3_Staff',
            'type' => 'string',
            'label' => __('Projected (T+3) Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'required_staff_T_plus_1',
            'type' => 'integer',
            'label' => __('Required Staff T+1')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'required_staff_T_plus_2',
            'type' => 'integer',
            'label' => __('Required Staff T+2')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'required_staff_T_plus_3',
            'type' => 'integer',
            'label' => __('Required Staff T+3')
        ];

        $fields->exchangeArray($newFields);
    }

}
