-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3692', NOW());

-- examination_item_results
CREATE TABLE `z_3692_examination_item_results` LIKE `examination_item_results`;
INSERT INTO `z_3692_examination_item_results`
SELECT * FROM `examination_item_results`;

DELETE FROM `examination_item_results`
WHERE `marks` IS NULL
AND `examination_grading_option_id` IS NULL;
