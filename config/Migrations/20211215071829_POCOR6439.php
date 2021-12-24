<?php
use Migrations\AbstractMigration;

class POCOR6439 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `report_student_attendance_summary`');
        /** START: report_student_attendance_summary table */
        $this->execute('CREATE TABLE `report_student_attendance_summary` (
          `education_grade_id` int(10) DEFAULT NULL,
          `education_grade_name` varchar(70) DEFAULT NULL,
          `class_id` int(10) DEFAULT NULL,
          `class_name` varchar(70) DEFAULT NULL,
          `institution_id` int(10) DEFAULT NULL,
          `institution_name` varchar(70) DEFAULT NULL,
          `academic_period_id` int(10) DEFAULT NULL,
          `academic_period_name` varchar(50) DEFAULT NULL,
          `subject_name` varchar(70) DEFAULT NULL,
          `period_name` varchar(70) DEFAULT NULL,
          `attendance_date` date DEFAULT NULL,
          `mark_status` varchar(30) DEFAULT NULL,
          `female_count` int(10) DEFAULT NULL,
          `male_count` int(10) DEFAULT NULL,
          `total_count` int(10) DEFAULT NULL,
          `present_female_count` int(10) DEFAULT NULL,
          `present_male_count` int(10) DEFAULT NULL,
          `present_total_count` int(10) DEFAULT NULL,
          `absent_female_count` int(10) DEFAULT NULL,
          `absent_male_count` int(10) DEFAULT NULL,
          `absent_total_count` int(10) DEFAULT NULL,
          `late_female_count` int(10) DEFAULT NULL,
          `late_male_count` int(10) DEFAULT NULL,
          `late_total_count` int(10) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
        COMMIT');

        $this->execute("INSERT INTO report_student_attendance_summary 
        SELECT institution_class_grades.education_grade_id, education_grades.name education_grade_name, student_attendance_marked_records.institution_class_id, institution_classes.name, institutions.id institution_id, institutions.name institution_name, student_attendance_marked_records.academic_period_id, academic_periods.name academic_period_name, IF(student_attendance_marked_records.subject_id=0,CONCAT('Period ',student_attendance_marked_records.period),'-') period_name, IF(student_attendance_marked_records.subject_id!=0,institution_subjects.name,'-') subject_name, student_attendance_marked_records.date attendance_date, mark_status, institution_classes.total_female_students, institution_classes.total_male_students,(institution_classes.total_male_students+institution_classes.total_female_students) total_students,
            IF(student_absences.absent_female_count IS NULL,institution_classes.total_female_students,(institution_classes.total_female_students-student_absences.absent_female_count)) present_female_count,
            IF(student_absences.absent_male_count IS NULL,institution_classes.total_male_students,(institution_classes.total_male_students-student_absences.absent_male_count)) present_male_count,
            IF(student_absences.absent_total_count IS NULL,(institution_classes.total_male_students+institution_classes.total_female_students),(institution_classes.total_male_students+institution_classes.total_female_students-student_absences.absent_total_count)) present_total_count,
            IF(absent_female_count IS NULL,'0',absent_female_count) absent_female_count,
            IF(absent_male_count IS NULL,'0',absent_male_count) absent_male_count,
            IF(absent_total_count IS NULL,'0',absent_total_count) absent_total_count,
            IF(late_female_count IS NULL,'0',late_female_count) late_female_count,
            IF(late_male_count IS NULL,'0',late_male_count) late_male_count,
            IF(late_total_count IS NULL,'0',late_total_count) late_total_count
            FROM institution_classes
            INNER JOIN institution_class_grades ON institution_class_grades.institution_class_id = institution_classes.id
            LEFT JOIN student_attendance_marked_records ON student_attendance_marked_records.academic_period_id = institution_classes.academic_period_id
            AND student_attendance_marked_records.institution_id = institution_classes.institution_id
            AND student_attendance_marked_records.education_grade_id = institution_class_grades.education_grade_id
            INNER JOIN academic_periods ON academic_periods.id = student_attendance_marked_records.academic_period_id
            LEFT JOIN institution_subjects ON institution_subjects.id = student_attendance_marked_records.subject_id
            INNER JOIN education_grades ON education_grades.id = institution_class_grades.education_grade_id
            INNER JOIN institutions ON institutions.id = institution_classes.institution_id
            LEFT JOIN
            (SELECT institution_class_id,education_grade_id,date,period,
                   subject_id,
                   absence_type_id,
                   SUM(count_absent_male) absent_male_count,
                   SUM(count_absent_female) absent_female_count,
                   SUM(count_absent_total) absent_total_count,
                   SUM(count_late_male) late_male_count,
                   SUM(count_late_female) late_female_count,
                   SUM(count_late_total) late_total_count
             FROM
               (SELECT institution_class_id,education_grade_id,date,period,
                        subject_id,
                        absence_type_id,
                        IF(gender_id = 1
                           AND absence_type_id IN (1,2) ,count(*),0) count_absent_male,
                        IF(gender_id = 2
                           AND absence_type_id IN (1,2),count(*),0) count_absent_female,
                        IF(absence_type_id IN (1,2),count(*),0) count_absent_total,
                        IF(gender_id = 1
                           AND absence_type_id = 3 ,count(*),0) count_late_male,
                        IF(gender_id = 2
                           AND absence_type_id =3 ,count(*),0) count_late_female,
                        IF(absence_type_id = 3,count(*),0) count_late_total
                FROM institution_student_absence_details
                INNER JOIN security_users ON security_users.id = institution_student_absence_details.student_id
                GROUP BY institution_class_id,education_grade_id,date,period,
                          subject_id,
                          absence_type_id,
                          gender_id) subq
             GROUP BY institution_class_id,education_grade_id,date,period,
                                                                   subject_id) student_absences ON student_absences.institution_class_id = institution_classes.id
            AND student_absences.education_grade_id = institution_class_grades.education_grade_id
            AND student_absences.date = student_attendance_marked_records.date
            AND student_absences.period = student_attendance_marked_records.period
            AND student_absences.subject_id = student_attendance_marked_records.subject_id
            LEFT JOIN
            (SELECT count_marked_attendance.institution_class_id,
                    count_marked_attendance.education_grade_id,
                    count_marked_attendance.academic_period_id,
                    count_marked_attendance.date, IF(grade_attendance_types.education_grade_id IS NULL,'Full Marked',IF(count_marked=attendance_per_day,'Full Marked','Partial Marked')) mark_status
             FROM
               (SELECT count(*) count_marked,institution_class_id,education_grade_id,academic_period_id,date
                FROM student_attendance_marked_records
                GROUP BY institution_class_id,date) count_marked_attendance
             LEFT JOIN
               (SELECT student_mark_type_status_grades.education_grade_id,
                       student_mark_type_statuses.academic_period_id,
                       date_enabled,
                       date_disabled,
                       attendance_per_day,
                       student_attendance_mark_types.name student_attendance_mark_types_name
                FROM student_mark_type_status_grades
                INNER JOIN student_mark_type_statuses ON student_mark_type_statuses.id = student_mark_type_status_grades.student_mark_type_status_id
                INNER JOIN student_attendance_mark_types ON student_attendance_mark_types.id = student_mark_type_statuses.student_attendance_mark_type_id) grade_attendance_types ON count_marked_attendance.education_grade_id = grade_attendance_types.education_grade_id
             AND count_marked_attendance.date BETWEEN grade_attendance_types.date_enabled AND grade_attendance_types.date_disabled
             LEFT JOIN
               (SELECT institution_id,
                       education_grade_id,
                       academic_period_id,
                       count(*) count_subjects
                FROM
                  (SELECT institution_id,
                          education_grade_id,
                          academic_period_id
                   FROM institution_subjects
                   GROUP BY education_grade_id,
                            academic_period_id,
                            name) AS institution_subject_count
                GROUP BY education_grade_id,
                         academic_period_id) institution_subject_count ON grade_attendance_types.education_grade_id = institution_subject_count.education_grade_id
             AND grade_attendance_types.academic_period_id = institution_subject_count.academic_period_id) calculate_mark_status ON calculate_mark_status.institution_class_id = institution_classes.id
            AND calculate_mark_status.education_grade_id = institution_class_grades.education_grade_id
            AND calculate_mark_status.academic_period_id = academic_periods.id
            AND calculate_mark_status.date = student_attendance_marked_records.date
            ORDER BY student_attendance_marked_records.date DESC");
        /** END: report_student_attendance_summary table  */
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_student_attendance_summary`');
    }
}
