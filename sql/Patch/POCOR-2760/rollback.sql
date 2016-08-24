UPDATE `security_functions` SET _delete = 'Periods.remove' WHERE id = 5003;
-- SELECT * FROM `security_functions` WHERE id = 5003;

-- BACKING UP
UPDATE `security_role_functions`
    JOIN `z2760_security_role_functions` ON z2760_security_role_functions.id = security_role_functions.id
        SET security_role_functions._delete = z2760_security_role_functions._delete;

DROP TABLE z2760_security_role_functions;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2760';