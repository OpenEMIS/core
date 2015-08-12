-- PHPOE-1462
-- removing duplicates of group users
DROP PROCEDURE IF EXISTS deleteDuplicateGroupUsers;
DELIMITER $$

CREATE PROCEDURE deleteDuplicateGroupUsers()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE groupUserId CHAR(36);
  DECLARE groupUsers CURSOR FOR 
  	SELECT `id` FROM `security_group_users` 
	GROUP BY `security_group_id`, `security_user_id`, `security_role_id`
	HAVING COUNT(1) > 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN groupUsers;

  read_loop: LOOP
    FETCH groupUsers INTO groupUserId;
    IF done THEN
      LEAVE read_loop;
    END IF;

    DELETE FROM `security_group_users` WHERE `id` = groupUserId;

  END LOOP read_loop;

  CLOSE groupUsers;
END
$$

DELIMITER ;

CALL deleteDuplicateGroupUsers;

DROP PROCEDURE IF EXISTS deleteDuplicateGroupUsers;

-- removing duplicates of group institutions

DROP PROCEDURE IF EXISTS deleteDuplicateGroupInstitutions;
DELIMITER $$

CREATE PROCEDURE deleteDuplicateGroupInstitutions()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE groupInstitutionId CHAR(36);
  DECLARE groupInstitutions CURSOR FOR 
  	SELECT `id` FROM `security_group_institution_sites` 
	GROUP BY `security_group_id`, `institution_site_id`
	HAVING COUNT(1) > 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN groupInstitutions;

  read_loop: LOOP
    FETCH groupInstitutions INTO groupInstitutionId;
    IF done THEN
      LEAVE read_loop;
    END IF;

    DELETE FROM `security_group_institution_sites` WHERE `id` = groupInstitutionId;

  END LOOP read_loop;

  CLOSE groupInstitutions;
END
$$

DELIMITER ;

CALL deleteDuplicateGroupInstitutions;

DROP PROCEDURE IF EXISTS deleteDuplicateGroupInstitutions;

ALTER TABLE `security_group_institution_sites` DROP `id` ;
ALTER TABLE `security_group_institution_sites` ADD PRIMARY KEY ( `security_group_id` , `institution_site_id` ) ;
-- end PHPOE-1462

-- DB version
UPDATE `config_items` SET `value` = '3.0.4' WHERE `code` = 'db_version';
-- end DB version
