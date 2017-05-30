-- restore workflow code and name
UPDATE `workflows`
SET `code` = 'STAFF-POSITION-PROFILE-01', `name` = 'Staff Position Profile'
WHERE `code` = 'CHANGE-IN-ASSIGNMENT-01';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3339';