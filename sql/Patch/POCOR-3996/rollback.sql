-- `import_mapping`
DELETE FROM `import_mapping`
WHERE `model` = 'Training.TrainingSessionsTrainees';

-- `system_patches`
DELETE FROM `system_patches` WHERE `issue`='POCOR-3996';

-- `labels`
DELETE FROM `labels`
WHERE `id` = '6c3d2497-4b27-11e7-9846-525400b263eb';