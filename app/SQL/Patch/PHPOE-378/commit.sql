INSERT INTO `field_options` (
`id` ,
`code` ,
`name` ,
`parent` ,
`params` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'GuardianRelation', 'Guardian Relations', 'Guardian', NULL , '68', '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

INSERT INTO `field_options` (
`id` ,
`code` ,
`name` ,
`parent` ,
`params` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'GuardianEducationLevel', 'Guardian Education Levels', 'Guardian', NULL , '69', '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

--
-- change colmn name from post_code to postal_code
--

ALTER TABLE `guardians` CHANGE `post_code` `postal_code` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
