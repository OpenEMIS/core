UPDATE `custom_modules` SET `filter`='FieldOption.InstitutionSiteTypes' WHERE `code`='Institution' AND `model`='Institution.Institutions';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2257';
