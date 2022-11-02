DROP TABLE institution_shifts;
RENAME TABLE z2515_institution_shifts TO institution_shifts;

DELETE FROM labels WHERE field = 'location' AND module_name = 'Institutions -> Shifts';
UPDATE `labels` SET `field_name` = 'Institution' WHERE field = 'location_institution_id' AND module_name = 'Institutions -> Shifts';

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2515';