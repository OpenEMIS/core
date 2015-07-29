DELETE FROM labels where module = 'StudentBehaviours' and field = 'security_user_id' and en = 'Student';
DELETE FROM labels where module = 'StaffBehaviours' and field = 'security_user_id' and en = 'Staff';
DELETE FROM labels where module = 'InstitutionSiteStudentAbsences' and field = 'security_user_id' and en = 'Student';
DELETE FROM labels where module = 'StaffAbsences' and field = 'security_user_id' and en = 'Staff';
DELETE FROM labels where module = 'InstitutionQualityVisits' and field = 'security_user_id' and en = 'Staff';
DELETE FROM labels where module = 'StudentAttendances' and field = 'security_user_id' and en = 'Student';
DELETE FROM labels where module = 'StaffAttendances' and field = 'security_user_id' and en = 'Staff';
DELETE FROM labels where module = 'InstitutionRubrics' and field = 'security_user_id' and en = 'Staff';