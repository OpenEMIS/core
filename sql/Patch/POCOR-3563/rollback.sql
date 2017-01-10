-- institution_subject_staff
ALTER TABLE `institution_subject_staff`
  DROP `start_date`,
  DROP `end_date`,
  DROP `institution_id`;

-- labels
DELETE FROM `labels` WHERE 
`id` IN ('1ebef019-d3df-11e6-907e-525400b263eb, 74436ffe-d63e-11e6-ad42-525400b263eb, 9c0c7533-d63e-11e6-ad42-525400b263eb, f94ed6be-d63e-11e6-ad42-525400b263eb');

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3563';