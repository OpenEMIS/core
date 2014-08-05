--
-- change colmn name from postal_code to post_code
--

ALTER TABLE `guardians` CHANGE `postal_code` `post_code` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;