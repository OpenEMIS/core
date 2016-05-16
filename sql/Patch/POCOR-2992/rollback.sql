-- labels
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'institution_position_id';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'start_date';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'FTE';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'staff_type_id';

-- db_patches
DELETE FROM db_patches where `issue` = 'POCOR-2992';