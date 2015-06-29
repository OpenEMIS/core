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
-- InstitutionSitePositions
('InstitutionSitePositions', 'modified', NULL, 'Modified On'),
('InstitutionSitePositions', 'staff_position_grade_id', NULL, 'Grade'),
('InstitutionSitePositions', 'staff_position_title_id', NULL, 'Title'),
-- Users
('Users', 'photo_content', NULL, 'Profile Image');

