-- rollback.sql
-- PHPOE-1262
--

UPDATE `config_items`
SET `label`='Data Outliers', `value`='21'
WHERE `name`='report_outlier_max_age' AND `type`='Data Outliers';

UPDATE `config_items`
SET `label`='Minimum Student Number', `value`='23131230'
WHERE `name`='report_outlier_min_student' AND `type`='Data Outliers';
