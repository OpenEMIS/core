<?php

use Migrations\AbstractMigration;

class POCOR7876 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $this->execute('CREATE TABLE `zz_7876_institution_students_report_cards` LIKE `institution_students_report_cards`');
        $this->execute('INSERT INTO `zz_7876_institution_students_report_cards` SELECT * FROM `institution_students_report_cards`');
        $this->execute("UPDATE institution_students_report_cards 
INNER JOIN report_cards
ON report_cards.id = institution_students_report_cards.report_card_id
INNER JOIN
(
    SELECT subq.academic_period_id
        ,subq.institution_id
        ,subq.education_grade_id
        ,subq.student_id
        ,subq.academic_term
        ,subq.assessment_period_start_date
        ,subq.assessment_period_end_date
        ,ROUND(AVG(IFNULL(assessment_grading_options.point, 0)), 2) gpa_per_student
    FROM 
    (
        SELECT institution_subject_students.academic_period_id
            ,institution_subject_students.institution_id
            ,institution_subject_students.education_grade_id
            ,institution_subject_students.education_subject_id
            ,institution_subject_students.student_id
            ,term_info.academic_term
            ,term_info.assessment_period_start_date
            ,term_info.assessment_period_end_date
            ,term_info.assessment_grading_type_id
            ,IFNULL(subq2.total_mark, 0) total_mark
        FROM institution_subject_students
        INNER JOIN 
        (
            SELECT assessments.academic_period_id
                ,assessments.education_grade_id
                ,IFNULL(assessment_periods.academic_term, 1) academic_term
                ,MIN(assessment_periods.start_date) assessment_period_start_date
                ,MAX(assessment_periods.end_date) assessment_period_end_date
                ,MAX(assessments.assessment_grading_type_id) assessment_grading_type_id
            FROM assessment_periods
            INNER JOIN assessments
            ON assessments.id = assessment_periods.assessment_id
            -- WHERE assessments.academic_period_id = 32
            GROUP BY assessments.academic_period_id
                ,assessments.education_grade_id
                ,IFNULL(assessment_periods.academic_term, 1)
        ) term_info
        ON term_info.academic_period_id = institution_subject_students.academic_period_id
        AND term_info.education_grade_id = institution_subject_students.education_grade_id
        LEFT JOIN 
        (
            SELECT assessment_item_results.academic_period_id
                    ,assessment_item_results.institution_id
                    ,assessment_item_results.education_grade_id
                    ,assessment_item_results.education_subject_id
                    ,assessment_item_results.student_id
                    ,IFNULL(assessment_periods.academic_term, 1) academic_term
                    ,IFNULL(ROUND(SUM(assessment_item_results.marks * assessment_periods.weight) / IFNULL(assessment_grading_types.max, CEILING(MAX(assessment_item_results.marks) / 10) * 10) * 100, 1), '') total_mark
                FROM assessment_item_results
                INNER JOIN 
                (
                    SELECT assessment_item_results.academic_period_id
                        ,assessment_item_results.institution_id
                        ,assessment_item_results.education_grade_id
                        ,assessment_item_results.student_id
                        ,assessment_item_results.assessment_id
                        ,assessment_item_results.education_subject_id
                        ,assessment_item_results.assessment_period_id
                        ,MAX(assessment_item_results.created) latest_created
                    FROM assessment_item_results
                    -- WHERE assessment_item_results.academic_period_id = 32
                    -- AND assessment_item_results.student_id = 13866
                    GROUP BY assessment_item_results.academic_period_id
                        ,assessment_item_results.institution_id
                        ,assessment_item_results.education_grade_id
                        ,assessment_item_results.student_id
                        ,assessment_item_results.assessment_id
                        ,assessment_item_results.education_subject_id
                        ,assessment_item_results.assessment_period_id
                ) latest_grades
                ON latest_grades.academic_period_id = assessment_item_results.academic_period_id
                AND latest_grades.institution_id = assessment_item_results.institution_id
                AND latest_grades.education_grade_id = assessment_item_results.education_grade_id
                AND latest_grades.student_id = assessment_item_results.student_id
                AND latest_grades.assessment_id = assessment_item_results.assessment_id
                AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
                AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
                AND latest_grades.latest_created = assessment_item_results.created
                LEFT JOIN assessment_grading_options
                ON assessment_grading_options.id = assessment_item_results.assessment_grading_option_id
                LEFT JOIN assessment_grading_types
                ON assessment_grading_types.id = assessment_grading_options.assessment_grading_type_id
                INNER JOIN assessment_periods
                ON assessment_periods.id = assessment_item_results.assessment_period_id
                INNER JOIN education_subjects
                ON education_subjects.id = assessment_item_results.education_subject_id
                -- WHERE assessment_item_results.academic_period_id = 32
                -- AND assessment_item_results.student_id = 13866
                GROUP BY assessment_item_results.academic_period_id
                    ,assessment_item_results.institution_id
                    ,assessment_item_results.education_grade_id
                    ,assessment_item_results.education_subject_id
                    ,assessment_item_results.student_id
                    ,assessment_periods.academic_term
        ) subq2
        ON subq2.academic_period_id = institution_subject_students.academic_period_id
        AND subq2.institution_id = institution_subject_students.institution_id
        AND subq2.education_grade_id = institution_subject_students.education_grade_id
        AND subq2.student_id = institution_subject_students.student_id
        AND subq2.education_subject_id = institution_subject_students.education_subject_id
        AND subq2.academic_term = term_info.academic_term
        -- WHERE institution_subject_students.academic_period_id = 32
        -- AND institution_subject_students.student_id = 13866
        GROUP BY institution_subject_students.academic_period_id
            ,institution_subject_students.institution_id
            ,institution_subject_students.education_grade_id
            ,institution_subject_students.education_subject_id
            ,institution_subject_students.student_id
            ,term_info.academic_term
    ) subq
    LEFT JOIN assessment_grading_options
    ON subq.total_mark >= assessment_grading_options.min 
    AND subq.total_mark <= assessment_grading_options.max
    AND subq.assessment_grading_type_id = assessment_grading_options.assessment_grading_type_id
    GROUP BY subq.academic_period_id
        ,subq.institution_id
        ,subq.education_grade_id
        ,subq.student_id
        ,subq.academic_term
) subq1
ON subq1.academic_period_id = institution_students_report_cards.academic_period_id
AND subq1.institution_id = institution_students_report_cards.institution_id
AND subq1.education_grade_id = institution_students_report_cards.education_grade_id
AND subq1.student_id = institution_students_report_cards.student_id
AND subq1.assessment_period_start_date >= report_cards.start_date
AND subq1.assessment_period_end_date <= report_cards.end_date
SET institution_students_report_cards.gpa = subq1.gpa_per_student
-- WHERE institution_students_report_cards.academic_period_id = 32
-- AND institution_students_report_cards.student_id = 13866;
");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_students_report_cards`');
        $this->execute('RENAME TABLE `zz_7876_institution_students_report_cards` TO `institution_students_report_cards`');
    }
}
