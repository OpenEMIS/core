UPDATE security_functions
    SET 
    `_view` = REPLACE (`_view`, 'Leave', 'Leaves'),
    `_edit` = REPLACE (`_edit`, 'Leave', 'Leaves'),
    `_add` = REPLACE (`_add`, 'Leave', 'Leaves'),
    `_delete` = REPLACE (`_delete`, 'Leave', 'Leaves'),
    `_execute` = REPLACE (`_execute`, 'Leave', 'Leaves')
WHERE id IN (3016, 7025);

UPDATE security_functions
    SET `name` = 'Leaves'
        WHERE id = 7025;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2723';