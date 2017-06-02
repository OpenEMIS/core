-- `import_mapping`
DELETE FROM `import_mapping`
WHERE `model` = 'Training.TrainingSessionsTrainees';

-- `system_patches`
DELETE FROM `system_patches` WHERE `issue`='POCOR-3996';
