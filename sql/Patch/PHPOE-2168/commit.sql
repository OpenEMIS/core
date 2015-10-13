-- backing up data to be deleted
create table z2168_institution_site_class_students like institution_site_class_students;
insert into z2168_institution_site_class_students (
	z2168_institution_site_class_students.id, 
	z2168_institution_site_class_students.status, 
	z2168_institution_site_class_students.student_id, 
	z2168_institution_site_class_students.institution_site_class_id, 
	z2168_institution_site_class_students.institution_site_section_id, 
	z2168_institution_site_class_students.modified_user_id, 
	z2168_institution_site_class_students.modified, 
	z2168_institution_site_class_students.created_user_id, 
	z2168_institution_site_class_students.created
)
(
	select 
	institution_site_class_students.id, 
	institution_site_class_students.status, 
	institution_site_class_students.student_id, 
	institution_site_class_students.institution_site_class_id, 
	institution_site_class_students.institution_site_section_id, 
	institution_site_class_students.modified_user_id, 
	institution_site_class_students.modified, 
	institution_site_class_students.created_user_id, 
	institution_site_class_students.created
	from 
	institution_site_class_students
	left join institution_site_sections on (institution_site_class_students.institution_site_section_id = institution_site_sections.id)
	where institution_site_sections.id IS NULL
); 

-- deletion
delete from institution_site_class_students 
where institution_site_class_students.id in 
	(
		select 
		z2168_institution_site_class_students.id
		from 
		z2168_institution_site_class_students
	);

drop table z2168_institution_site_class_students;