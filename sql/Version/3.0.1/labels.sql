TRUNCATE TABLE `labels`;

--
-- 29th June 2015 - last updated
--
INSERT INTO `labels` (`module`, `field`, `code`, `en`) VALUES
-- Institutions
('Institutions', 'institution_site_type_id', NULL, 'Type'),
('Institutions', 'institution_site_provider_id', NULL, 'Provider'),
('Institutions', 'institution_site_sector_id', NULL, 'Sector'),
('Institutions', 'institution_site_ownership_id', NULL, 'Ownership'),
('Institutions', 'institution_site_gender_id', NULL, 'Gender'),
('Institutions', 'institution_site_status_id', NULL, 'Status'),
('Institutions', 'institution_site_locality_id', NULL, 'Locality'),
('Institutions', 'area_id', NULL, 'Area (Education)'),
-- InstitutionSiteAttachments
('InstitutionSiteAttachments', 'modified', NULL, 'Modified On'),
('InstitutionSiteAttachments', 'created', NULL, 'Uploaded On'),
('InstitutionSiteAttachments', 'modified_user_id', NULL, 'Modified By'),
('InstitutionSiteAttachments', 'created_user_id', NULL, 'Uploaded By'),
-- InstitutionSitePositions
('InstitutionSitePositions', 'modified', NULL, 'Modified On'),
('InstitutionSitePositions', 'created', NULL, 'Created On'),
('InstitutionSitePositions', 'modified_user_id', NULL, 'Modified By'),
('InstitutionSitePositions', 'created_user_id', NULL, 'Created By'),
('InstitutionSitePositions', 'staff_position_grade_id', NULL, 'Grade'),
('InstitutionSitePositions', 'staff_position_title_id', NULL, 'Title'),
('InstitutionSitePositions', 'position_no', NULL, 'Number'),
-- InstitutionSiteProgrammes
('InstitutionSiteProgrammes', 'modified', NULL, 'Modified On'),
('InstitutionSiteProgrammes', 'created', NULL, 'Created On'),
('InstitutionSiteProgrammes', 'modified_user_id', NULL, 'Modified By'),
('InstitutionSiteProgrammes', 'created_user_id', NULL, 'Created By'),
('InstitutionSiteProgrammes', 'education_programme_id', NULL, 'Programme'),
('InstitutionSiteProgrammes', 'openemis_no', NULL, 'OpenEMIS ID'),
-- InstitutionSiteShifts
('InstitutionSiteShifts', 'name', NULL, 'Shift Name'),
('InstitutionSiteShifts', 'location_institution_site_id', NULL, 'Location'),
('InstitutionSiteShifts', 'modified', NULL, 'Modified On'),
('InstitutionSiteShifts', 'created', NULL, 'Created On'),
('InstitutionSiteShifts', 'modified_user_id', NULL, 'Modified By'),
('InstitutionSiteShifts', 'created_user_id', NULL, 'Created By'),
-- InstitutionSiteSections
('InstitutionSiteSections', 'name', NULL, 'Section Name'),
('InstitutionSiteSections', 'security_user_id', NULL, 'Home Room Teacher'),
('InstitutionSiteSections', 'institution_site_shift_id', NULL, 'Shift'),
-- InstitutionSiteClasses
('InstitutionSiteClasses', 'name', NULL, 'Class Name'),
('InstitutionSiteClasses', 'education_subject_id', NULL, 'Subject Name'),
('InstitutionSiteClasses', 'education_subject_code', NULL, 'Subject Code'),
-- InstitutionSiteStaff
('InstitutionSiteStaff', 'staff_institution_name', NULL, 'Institution'),
('InstitutionSiteStaff', 'start_date', NULL, 'Start Date'),
('InstitutionSiteStaff', 'end_date', NULL, 'End Date'),
('InstitutionSiteStaff', 'photo_content', NULL, 'Profile Image'),
-- StaffBehaviours
('StaffBehaviours', 'date_of_behaviour', NULL, 'Date'),
('StaffBehaviours', 'staff_behaviour_category_id', NULL, 'Category'),
('StaffBehaviours', 'institution_site_id', NULL, 'Institution'),
('StaffBehaviours', 'security_user_id', NULL, 'Staff'),
-- Users
('Users', 'name', NULL, 'Name'),
('Users', 'username', NULL, 'User Name'),
('Users', 'openemis_no', NULL, 'OpenEMIS ID'),
('Users', 'first_name', NULL, 'First Name'),
('Users', 'middle_name', NULL, 'Middle Name'),
('Users', 'third_name', NULL, 'Third Name'),
('Users', 'last_name', NULL, 'Last Name'),
('Users', 'preferred_name', NULL, 'Preferred Name'),
('Users', 'address', NULL, 'Address'),
('Users', 'postal_code', NULL, 'Postal'),
('Users', 'address_area_id', NULL, 'Address Area'),
('Users', 'birthplace_area_id', NULL, 'Birthplace Area'),
('Users', 'gender_id', NULL, 'Gender'),
('Users', 'date_of_birth', NULL, 'Date Of Birth'),
('Users', 'date_of_death', NULL, 'Date Of Death'),
('Users', 'status', NULL, 'Status'),
('Users', 'photo_content', NULL, 'Profile Image'),
-- Contacts
('Contacts', 'contact_type_id', NULL, 'Description'),
('Contacts', 'contact_option_id', NULL, 'Type'),
-- Identities
('Identities', 'number', NULL, 'Number'),
('Identities', 'issue_date', NULL, 'Issue Date'),
('Identities', 'expiry_date', NULL, 'Expiry Date'),
('Identities', 'issue_location', NULL, 'Issuer'),
('Identities', 'identity_type_id', NULL, 'Identity Type'),
-- Languages
('Languages', 'evaluation_date', NULL, 'Evaluation Date'),
('Languages', 'language_id', NULL, 'Language'),
('Languages', 'listening', NULL, 'Listening'),
('Languages', 'speaking', NULL, 'Speaking'),
('Languages', 'reading', NULL, 'Reading'),
('Languages', 'writing', NULL, 'Writing'),
-- Comments
('Comments', 'comment_date', NULL, 'Date'),
('Comments', 'comment', NULL, 'Comment'),
-- SpecialNeeds
('SpecialNeeds', 'special_need_type_id', NULL, 'Type'),
('SpecialNeeds', 'special_need_date', NULL, 'Date'),
('SpecialNeeds', 'comment', NULL, 'Comment'),
-- Awards
('Awards', 'issue_date', NULL, 'Issue Date'),
('Awards', 'award', NULL, 'Name'),
('Awards', 'issuer', NULL, 'Issuer'),
('Awards', 'comment', NULL, 'Comment'),
-- Absences
('Absences', 'academic_period_id', NULL, 'Academic Period'),
('Absences', 'institution_site_class_id', NULL, 'Class'),
('Absences', 'institution_site_section_id', NULL, 'Section'),
('Absences', 'student_id', NULL, 'Student'),
('Absences', 'absence_type', NULL, 'Type'),
('Absences', 'student_absence_reason_id', NULL, 'Reason'),
('Absences', 'select_section', NULL, 'Select Section'),
-- Behaviours
('Behaviours', 'name', NULL, 'Title'),
('Behaviours', 'student_behaviour_category_id', NULL, 'Category'),
('Behaviours', 'staff_behaviour_category_id', NULL, 'Category'),
('Behaviours', 'date_of_behaviour', NULL, 'Date'),
('Behaviours', 'time_of_behaviour', NULL, 'Time'),
-- StudentFees
('StudentFees', 'title', NULL, 'Fees'),
('StudentFees', 'programme', NULL, 'Programme'),
('StudentFees', 'grade', NULL, 'Grade'),
('StudentFees', 'fees', NULL, 'Fees'),
('StudentFees', 'paid', NULL, 'Paid'),
('StudentFees', 'outstanding', NULL, 'Outstanding'),
('StudentFees', 'no_student', NULL, 'No Student associated in the selected Education Grade and Academic Period.'),
('StudentFees', 'no_payment', NULL, 'No Payment Records.'),
('StudentFees', 'no_fees', NULL, 'No Fee Records.'),
('StudentFees', 'created', NULL, 'Create'),
-- Qualifications
('Qualifications', 'qualification_institution_country', NULL, 'Institution Country'),
('Qualifications', 'gpa', NULL, 'Grade/Score'),
('Qualifications', 'file_name', NULL, 'Attachment'),
-- Positions
('Positions', 'title', NULL, 'Positions'),
('Positions', 'name', NULL, 'Position'),
('Positions', 'teaching', NULL, 'Teaching'),
('Positions', 'number', NULL, 'Number'),
('Positions', 'institution_site_position_id', NULL, 'Position')


;

