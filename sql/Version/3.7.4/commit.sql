-- POCOR-2737
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2737', NOW());

-- security_functions
INSERT INTO `translations` (`en`, `ar`, `zh`, `es`, `fr`, `ru`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('There is no programme set for available Academic Period on this institution', 'لا يوجد برنامج المحدد لفترة الأكاديمية المتاحة في هذه المؤسسة', '沒有程序可用的學術時期設置該機構', 'No hay un programa establecido para el Período Académico disponibles en esta institución', "Il n'y a pas de programme défini pour la période académique disponible sur cette institution", 'Там нет программы установлены для доступного академического периода на данном учреждении', 1, 1, '2016-08-31', 1, '2016-08-31');


-- 3.7.4
UPDATE config_items SET value = '3.7.4' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
