INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3092', NOW());

CREATE TABLE `z_3092_translations` LIKE `translations`;

INSERT INTO `z_3092_translations`
SELECT * FROM `translations`
WHERE `en` IN (
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

INSERT INTO `translations` (`en`, `created_user_id`, `created`) VALUES
('%s with %s', 1, NOW()),
('Transfer of student %s from %s', 1, NOW()),
('%s in %s', 1, NOW()),
('%s of %s', 1, NOW()),
('%s from %s', 1, NOW()),
('Transfer of staff %s to %s', 1, NOW()),
('Staff Transfer Approved of %s from %s', 1, NOW()),
('Admission of student %s', 1, NOW()),
('Withdraw request of %s', 1, NOW()),
('%s in %s on %s', 1, NOW()),
('%s applying for session %s in %s', 1, NOW()),
('Results of %s', 1, NOW());
