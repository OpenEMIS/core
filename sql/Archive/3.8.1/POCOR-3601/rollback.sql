-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Institution.InstitutionTextbooks';

DELETE FROM `security_functions` WHERE `id` IN (1052);

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3601';
