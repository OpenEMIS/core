DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'fromAcademicPeriod';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'toAcademicPeriod';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'fromGrade';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'toGrade';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'status';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2291';