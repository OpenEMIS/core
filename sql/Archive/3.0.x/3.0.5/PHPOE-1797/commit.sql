-- www_openemis_jor middle name and third name not indexed
ALTER TABLE `security_users` ADD INDEX(`middle_name`);
ALTER TABLE `security_users` ADD INDEX(`third_name`);