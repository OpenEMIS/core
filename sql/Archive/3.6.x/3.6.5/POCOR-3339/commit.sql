-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3339', NOW());

-- change workflow code and name to 'change in assignment'
UPDATE `workflows`
SET `code` = 'CHANGE-IN-ASSIGNMENT-01', `name` = 'Change in Assignment'
WHERE `code` = 'STAFF-POSITION-PROFILE-01';