TRUNCATE TABLE `labels`;

INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES
-- Institutions
('Institutions', 'institution_site_type_id', NULL, 'Type', 1, NOW()),
('Institutions', 'institution_site_provider_id', NULL, 'Provider', 1, NOW()),
('Institutions', 'institution_site_sector_id', NULL, 'Sector', 1, NOW()),
('Institutions', 'institution_site_ownership_id', NULL, 'Ownership', 1, NOW()),
('Institutions', 'institution_site_gender_id', NULL, 'Gender', 1, NOW()),
('Institutions', 'institution_site_status_id', NULL, 'Status', 1, NOW()),
('Institutions', 'institution_site_locality_id', NULL, 'Locality', 1, NOW()),
('Institutions', 'area_id', NULL, 'Area (Education)', 1, NOW()),
-- InstitutionQualityVisits
('InstitutionQualityVisits', 'security_user_id', NULL, 'Staff', 1, NOW()),
-- InstitutionRubrics
('InstitutionRubrics', 'security_user_id', NULL, 'Staff', 1, NOW()),
-- InstitutionSiteAttachments
('InstitutionSiteAttachments', 'modified', NULL, 'Modified On', 1, NOW()),
('InstitutionSiteAttachments', 'created', NULL, 'Uploaded On', 1, NOW()),
('InstitutionSiteAttachments', 'modified_user_id', NULL, 'Modified By', 1, NOW()),
('InstitutionSiteAttachments', 'created_user_id', NULL, 'Uploaded By', 1, NOW()),
-- InstitutionSitePositions
('InstitutionSitePositions', 'modified', NULL, 'Modified On', 1, NOW()),
('InstitutionSitePositions', 'created', NULL, 'Created On', 1, NOW()),
('InstitutionSitePositions', 'modified_user_id', NULL, 'Modified By', 1, NOW()),
('InstitutionSitePositions', 'created_user_id', NULL, 'Created By', 1, NOW()),
('InstitutionSitePositions', 'staff_position_grade_id', NULL, 'Grade', 1, NOW()),
('InstitutionSitePositions', 'staff_position_title_id', NULL, 'Title', 1, NOW()),
('InstitutionSitePositions', 'position_no', NULL, 'Number', 1, NOW()),
-- InstitutionSiteProgrammes
('InstitutionSiteProgrammes', 'modified', NULL, 'Modified On', 1, NOW()),
('InstitutionSiteProgrammes', 'created', NULL, 'Created On', 1, NOW()),
('InstitutionSiteProgrammes', 'modified_user_id', NULL, 'Modified By', 1, NOW()),
('InstitutionSiteProgrammes', 'created_user_id', NULL, 'Created By', 1, NOW()),
('InstitutionSiteProgrammes', 'education_programme_id', NULL, 'Programme', 1, NOW()),
('InstitutionSiteProgrammes', 'openemis_no', NULL, 'OpenEMIS ID', 1, NOW()),
-- InstitutionSiteShifts
('InstitutionSiteShifts', 'name', NULL, 'Shift Name', 1, NOW()),
('InstitutionSiteShifts', 'location_institution_site_id', NULL, 'Location', 1, NOW()),
('InstitutionSiteShifts', 'modified', NULL, 'Modified On', 1, NOW()),
('InstitutionSiteShifts', 'created', NULL, 'Created On', 1, NOW()),
('InstitutionSiteShifts', 'modified_user_id', NULL, 'Modified By', 1, NOW()),
('InstitutionSiteShifts', 'created_user_id', NULL, 'Created By', 1, NOW()),
-- InstitutionSiteSections
('InstitutionSiteSections', 'name', NULL, 'Section Name', 1, NOW()),
('InstitutionSiteSections', 'security_user_id', NULL, 'Home Room Teacher', 1, NOW()),
('InstitutionSiteSections', 'institution_site_shift_id', NULL, 'Shift', 1, NOW()),
-- InstitutionSiteClasses
('InstitutionSiteClasses', 'name', NULL, 'Class Name', 1, NOW()),
('InstitutionSiteClasses', 'education_subject_id', NULL, 'Subject Name', 1, NOW()),
('InstitutionSiteClasses', 'education_subject_code', NULL, 'Subject Code', 1, NOW()),
-- InstitutionSiteStaff
('InstitutionSiteStaff', 'staff_institution_name', NULL, 'Institution', 1, NOW()),
('InstitutionSiteStaff', 'start_date', NULL, 'Start Date', 1, NOW()),
('InstitutionSiteStaff', 'end_date', NULL, 'End Date', 1, NOW()),
('InstitutionSiteStaff', 'photo_content', NULL, 'Photo', 1, NOW()),
-- StaffBehaviours
('StaffBehaviours', 'date_of_behaviour', NULL, 'Date', 1, NOW()),
('StaffBehaviours', 'staff_behaviour_category_id', NULL, 'Category', 1, NOW()),
('StaffBehaviours', 'institution_site_id', NULL, 'Institution', 1, NOW()),
('StaffBehaviours', 'security_user_id', NULL, 'Staff', 1, NOW()),
-- Users
('Users', 'name', NULL, 'Name', 1, NOW()),
('Users', 'username', NULL, 'User Name', 1, NOW()),
('Users', 'openemis_no', NULL, 'OpenEMIS ID', 1, NOW()),
('Users', 'first_name', NULL, 'First Name', 1, NOW()),
('Users', 'middle_name', NULL, 'Middle Name', 1, NOW()),
('Users', 'third_name', NULL, 'Third Name', 1, NOW()),
('Users', 'last_name', NULL, 'Last Name', 1, NOW()),
('Users', 'preferred_name', NULL, 'Preferred Name', 1, NOW()),
('Users', 'address', NULL, 'Address', 1, NOW()),
('Users', 'postal_code', NULL, 'Postal', 1, NOW()),
('Users', 'address_area_id', NULL, 'Address Area', 1, NOW()),
('Users', 'birthplace_area_id', NULL, 'Birthplace Area', 1, NOW()),
('Users', 'gender_id', NULL, 'Gender', 1, NOW()),
('Users', 'date_of_birth', NULL, 'Date Of Birth', 1, NOW()),
('Users', 'date_of_death', NULL, 'Date Of Death', 1, NOW()),
('Users', 'status', NULL, 'Status', 1, NOW()),
('Users', 'photo_content', NULL, 'Photo', 1, NOW()),
-- Contacts
('Contacts', 'contact_type_id', NULL, 'Description', 1, NOW()),
('Contacts', 'contact_option_id', NULL, 'Type', 1, NOW()),
-- Identities
('Identities', 'number', NULL, 'Number', 1, NOW()),
('Identities', 'issue_date', NULL, 'Issue Date', 1, NOW()),
('Identities', 'expiry_date', NULL, 'Expiry Date', 1, NOW()),
('Identities', 'issue_location', NULL, 'Issuer', 1, NOW()),
('Identities', 'identity_type_id', NULL, 'Identity Type', 1, NOW()),
-- Languages
('Languages', 'evaluation_date', NULL, 'Evaluation Date', 1, NOW()),
('Languages', 'language_id', NULL, 'Language', 1, NOW()),
('Languages', 'listening', NULL, 'Listening', 1, NOW()),
('Languages', 'speaking', NULL, 'Speaking', 1, NOW()),
('Languages', 'reading', NULL, 'Reading', 1, NOW()),
('Languages', 'writing', NULL, 'Writing', 1, NOW()),
-- Comments
('Comments', 'comment_date', NULL, 'Date', 1, NOW()),
('Comments', 'comment', NULL, 'Comment', 1, NOW()),
-- SpecialNeeds
('SpecialNeeds', 'special_need_type_id', NULL, 'Type', 1, NOW()),
('SpecialNeeds', 'special_need_date', NULL, 'Date', 1, NOW()),
('SpecialNeeds', 'comment', NULL, 'Comment', 1, NOW()),
-- Awards
('Awards', 'issue_date', NULL, 'Issue Date', 1, NOW()),
('Awards', 'award', NULL, 'Name', 1, NOW()),
('Awards', 'issuer', NULL, 'Issuer', 1, NOW()),
('Awards', 'comment', NULL, 'Comment', 1, NOW()),
-- Absences
('Absences', 'academic_period_id', NULL, 'Academic Period', 1, NOW()),
('Absences', 'institution_site_class_id', NULL, 'Class', 1, NOW()),
('Absences', 'institution_site_section_id', NULL, 'Section', 1, NOW()),
('Absences', 'student_id', NULL, 'Student', 1, NOW()),
('Absences', 'absence_type', NULL, 'Type', 1, NOW()),
('Absences', 'student_absence_reason_id', NULL, 'Reason', 1, NOW()),
('Absences', 'select_section', NULL, 'Select Section', 1, NOW()),
-- Behaviours
('Behaviours', 'name', NULL, 'Title', 1, NOW()),
('Behaviours', 'student_behaviour_category_id', NULL, 'Category', 1, NOW()),
('Behaviours', 'staff_behaviour_category_id', NULL, 'Category', 1, NOW()),
('Behaviours', 'date_of_behaviour', NULL, 'Date', 1, NOW()),
('Behaviours', 'time_of_behaviour', NULL, 'Time', 1, NOW()),
-- StudentFees
('StudentFees', 'title', NULL, 'Fees', 1, NOW()),
('StudentFees', 'programme', NULL, 'Programme', 1, NOW()),
('StudentFees', 'grade', NULL, 'Grade', 1, NOW()),
('StudentFees', 'fees', NULL, 'Fees', 1, NOW()),
('StudentFees', 'paid', NULL, 'Paid', 1, NOW()),
('StudentFees', 'outstanding', NULL, 'Outstanding', 1, NOW()),
('StudentFees', 'no_student', NULL, 'No Student associated in the selected Education Grade and Academic Period.', 1, NOW()),
('StudentFees', 'no_payment', NULL, 'No Payment Records.', 1, NOW()),
('StudentFees', 'no_fees', NULL, 'No Fee Records.', 1, NOW()),
('StudentFees', 'created', NULL, 'Create', 1, NOW()),
-- Qualifications
('Qualifications', 'qualification_institution_country', NULL, 'Institution Country', 1, NOW()),
('Qualifications', 'gpa', NULL, 'Grade/Score', 1, NOW()),
('Qualifications', 'file_name', NULL, 'Attachment', 1, NOW()),
-- Positions
('Positions', 'title', NULL, 'Positions', 1, NOW()),
('Positions', 'name', NULL, 'Position', 1, NOW()),
('Positions', 'teaching', NULL, 'Teaching', 1, NOW()),
('Positions', 'number', NULL, 'Number', 1, NOW()),
('Positions', 'institution_site_position_id', NULL, 'Position', 1, NOW()),
-- Students
('Students', 'photo_content', NULL, 'Photo', 1, NOW()),
('Students', 'openemis_no', NULL, 'OpenEMIS ID', 1, NOW()),
('Staff', 'photo_content', NULL, 'Photo', 1, NOW()),
('Staff', 'openemis_no', NULL, 'OpenEMIS ID', 1, NOW()),
-- InstitutionSiteActivities
('InstitutionSiteActivities', 'created', NULL, 'Modified On', 1, NOW()),
('InstitutionSiteActivities', 'created_user_id', NULL, 'Modified By', 1, NOW()),
('InstitutionSiteActivities', 'model', NULL, 'Module', 1, NOW()),
-- StudentActivities
('StudentActivities', 'created', NULL, 'Modified On', 1, NOW()),
('StudentActivities', 'created_user_id', NULL, 'Modified By', 1, NOW()),
('StudentActivities', 'model', NULL, 'Module', 1, NOW()),
-- StaffActivities
('StaffActivities', 'created', NULL, 'Modified On', 1, NOW()),
('StaffActivities', 'created_user_id', NULL, 'Modified By', 1, NOW()),
('StaffActivities', 'model', NULL, 'Module', 1, NOW()),
-- StudentAttendances
('StudentAttendances', 'openemis_no', NULL, 'OpenEMIS No', 1, NOW()),
('StudentAttendances', 'security_user_id', NULL, 'Student', 1, NOW()),
-- StudentAbsences
('InstitutionSiteStudentAbsences', 'security_user_id', NULL, 'Student', 1, NOW()),
('InstitutionSiteStudentAbsences', 'full_day', NULL, 'Full Day Absent', 1, NOW()),
('InstitutionSiteStudentAbsences', 'start_date', NULL, 'First Date Absent', 1, NOW()),
('InstitutionSiteStudentAbsences', 'end_date', NULL, 'Last Date Absent', 1, NOW()),
('InstitutionSiteStudentAbsences', 'start_time', NULL, 'Start Time Absent', 1, NOW()),
('InstitutionSiteStudentAbsences', 'end_time', NULL, 'End Time Absent', 1, NOW()),
('InstitutionSiteStudentAbsences', 'absence_type', NULL, 'Type', 1, NOW()),
('InstitutionSiteStudentAbsences', 'student_absence_reason_id', NULL, 'Reason', 1, NOW()),
-- StudentBehaviours
('StudentBehaviours', 'security_user_id', NULL, 'Student', 1, NOW()),
-- StaffAttendances
('StaffAttendances', 'openemis_no', NULL, 'OpenEMIS No', 1, NOW()),
('StaffAttendances', 'security_user_id', NULL, 'Staff', 1, NOW()),
-- StaffAbsences
('StaffAbsences', 'security_user_id', NULL, 'Staff', 1, NOW()),
('StaffAbsences', 'full_day', NULL, 'Full Day Absent', 1, NOW()),
('StaffAbsences', 'start_date', NULL, 'First Date Absent', 1, NOW()),
('StaffAbsences', 'end_date', NULL, 'Last Date Absent', 1, NOW()),
('StaffAbsences', 'start_time', NULL, 'Start Time Absent', 1, NOW()),
('StaffAbsences', 'end_time', NULL, 'End Time Absent', 1, NOW()),
('StaffAbsences', 'absence_type', NULL, 'Type', 1, NOW()),
('StaffAbsences', 'staff_absence_reason_id', NULL, 'Reason', 1, NOW())
;
