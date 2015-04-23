DROP TABLE IF EXISTS `survey_responses`;
CREATE TABLE IF NOT EXISTS `survey_responses` (
`id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `response` text NOT NULL,
  `survey_template_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_responses`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_responses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;