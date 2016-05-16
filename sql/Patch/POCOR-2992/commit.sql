-- db_patches
INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2992', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'institution_position_id', 'Institution -> Staff Transfer Approvals', 'Requested Position', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'start_date', 'Institution -> Staff Transfer Approvals', 'Requested Start Date', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'FTE', 'Institution -> Staff Transfer Approvals', 'Requested FTE', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'staff_type_id', 'Institution -> Staff Transfer Approvals', 'Requested Staff Type', 1, 1, NOW());