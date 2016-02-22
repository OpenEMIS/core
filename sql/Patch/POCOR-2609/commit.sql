-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2609', NOW());

-- procedures
DROP PROCEDURE IF EXISTS patchOrder;
DROP PROCEDURE IF EXISTS tmpRefTable;
DELIMITER $$

CREATE PROCEDURE tmpRefTable(
    IN referenceTable varchar(50)
)
BEGIN
	DROP TABLE IF EXISTS `tmp_table`;
	CREATE TABLE `tmp_table` (
	  `id` int(11) NOT NULL
	);
    SET @updateRecord = CONCAT('INSERT INTO `tmp_table` SELECT `id` FROM `', referenceTable, '`');
    PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
	DEALLOCATE PREPARE updateRecord;
END
$$
DELIMITER;


DELIMITER $$
CREATE PROCEDURE patchOrder(
    IN updateTblName varchar(50),
    IN updateTblColumn varchar(50)
)
BEGIN

  DECLARE flag INT DEFAULT 0;
  DECLARE filterId INT;
  DECLARE system_cursor CURSOR FOR SELECT id from tmp_table;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

  OPEN system_cursor;

  forloop : LOOP
    FETCH system_cursor INTO filterId;
    SET @rank:=0;
    SET @updateRecord = CONCAT('UPDATE `', updateTblName,'` SET `order`=@rank:=@rank+1 WHERE `', updateTblColumn, '` = ', filterId,' ORDER BY `order`');
	PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
	DEALLOCATE PREPARE updateRecord;
	IF flag = 1 THEN
      LEAVE forloop;
	END IF;
    END LOOP forloop;
    CLOSE system_cursor;
END
$$

DELIMITER ;

-- education_levels
CREATE TABLE `z_2609_education_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2609_education_levels` SELECT `id`, `order` FROM `education_levels`;
CALL tmpRefTable('education_systems');
CALL patchOrder('education_levels', 'education_system_id');

-- education_cycles
CREATE TABLE `z_2609_education_cycles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2609_education_cycles` SELECT `id`, `order` FROM `education_cycles`;
CALL tmpRefTable('education_levels');
CALL patchOrder('education_cycles', 'education_level_id');

-- education_programmes
CREATE TABLE `z_2609_education_programmes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2609_education_programmes` SELECT `id`, `order` FROM `education_programmes`;
CALL tmpRefTable('education_cycles');
CALL patchOrder('education_programmes', 'education_cycle_id');

-- education_grades
CREATE TABLE `z_2609_education_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2609_education_grades` SELECT `id`, `order` FROM `education_grades`;
CALL tmpRefTable('education_programmes');
CALL patchOrder('education_grades', 'education_programme_id');

-- field_option_values
CREATE TABLE `z_2609_field_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2609_field_option_values` SELECT `id`, `order` FROM `field_option_values`;
CALL tmpRefTable('field_options');
CALL patchOrder('field_option_values', 'field_option_id');

-- drop procedures and tmp table
DROP PROCEDURE IF EXISTS patchOrder;
DROP PROCEDURE IF EXISTS tmpRefTable;
DROP TABLE IF EXISTS `tmp_table`;