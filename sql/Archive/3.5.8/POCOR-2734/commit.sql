-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2734', NOW());

-- security_group_users
-- Re-run patch from POCOR-3003
UPDATE security_group_users
JOIN institution_staff s ON s.security_group_user_id = security_group_users.id
JOIN institution_positions p ON p.id = s.institution_position_id
JOIN staff_position_titles t 
    ON t.id = p.staff_position_title_id
    AND t.security_role_id <> security_group_users.security_role_id
SET security_group_users.security_role_id = t.security_role_id;