-- 9th July 2015

RENAME TABLE assessment_results TO z_1461_assessment_results;

ALTER TABLE `assessment_item_results` DROP `assessment_result_id`;
ALTER TABLE `assessment_item_results` DROP `assessment_result_type_id`;

-- New table - institution_site_assessments
DROP TABLE IF EXISTS `institution_site_assessments`;
CREATE TABLE IF NOT EXISTS `institution_site_assessments` (
  `id` char(36) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed',
  `academic_period_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_assessments`
  ADD PRIMARY KEY (`id`), ADD KEY `institution_site_id` (`institution_site_id`);


ALTER TABLE `institution_site_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
