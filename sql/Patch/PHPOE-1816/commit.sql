INSERT INTO institution_site_section_grades
(institution_site_section_id, education_grade_id, status, created_user_id,created)
select DISTINCT section_students.institution_site_section_id, section_students.education_grade_id, '1', '1', NOW()
from institution_sites sites, 
institution_site_students site_students,
institution_site_section_students section_students
where sites.id = site_students.institution_site_id
and site_students.security_user_id =  section_students.security_user_id
and section_students.institution_site_section_id is not null
and section_students.institution_site_section_id != 0 
and (section_students.institution_site_section_id, section_students.education_grade_id) NOT IN
(select section_grades.institution_site_section_id, section_grades.education_grade_id
from institution_site_section_grades section_grades);