-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2723', NOW());

UPDATE security_functions
    SET 
    `_view` = REPLACE (`_view`, 'Leaves', 'Leave'),
    `_edit` = REPLACE (`_edit`, 'Leaves', 'Leave'),
    `_add` = REPLACE (`_add`, 'Leaves', 'Leave'),
    `_delete` = REPLACE (`_delete`, 'Leaves', 'Leave'),
    `_execute` = REPLACE (`_execute`, 'Leaves', 'Leave')
WHERE id IN (3016, 7025);

UPDATE security_functions
    SET `name` = 'Leave'
        WHERE id = 7025;