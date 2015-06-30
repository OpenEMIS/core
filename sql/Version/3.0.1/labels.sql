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
-- Users
('Users', 'photo_content', NULL, 'Profile Image');

