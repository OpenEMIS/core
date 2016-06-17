ALTER TABLE `translations` DROP `editable`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2232';