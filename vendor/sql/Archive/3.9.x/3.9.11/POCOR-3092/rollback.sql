DELETE FROM `translations` WHERE `en` IN (
    '%s with %s',
    'Transfer of student %s from %s',
    '%s in %s',
    '%s of %s',
    '%s from %s',
    'Transfer of staff %s to %s',
    'Staff Transfer Approved of %s from %s',
    'Admission of student %s',
    'Withdraw request of %s',
    '%s in %s on %s',
    '%s applying for session %s in %s',
    'Results of %s');

INSERT INTO `translations`
SELECT * FROM `z_3092_translations`;

DROP TABLE `z_3092_translations`;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3092';
