CREATE TABLE 1290_institution_site_class_students LIKE institution_site_class_students; 
INSERT 1290_institution_site_class_students SELECT * FROM institution_site_class_students;

ALTER TABLE `institution_site_class_students`
  DROP `student_category_id`,
  DROP `education_grade_id`;