-- Restore from backup tables
DROP PROCEDURE IF EXISTS restoreOrdersTable;
DELIMITER $$

CREATE PROCEDURE restoreOrdersTable()
BEGIN
	DECLARE flag INT DEFAULT FALSE;
	DECLARE backupTable VARCHAR(100);
    DECLARE mainTable VARCHAR(100);
	DECLARE system_cursor CURSOR FOR SELECT reference_table, backup_table from z_2609_backup_reference_table;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = TRUE;
    
    OPEN system_cursor;
    
    forloop : LOOP
		FETCH system_cursor INTO mainTable, backupTable;
        IF flag THEN
			LEAVE forloop;
		END IF;
        
		SET @updateRecord = CONCAT('UPDATE `', mainTable,'` INNER JOIN `', backupTable,'` ON `', backupTable, '`.`id` = `', mainTable, '`.`id`
			SET `', mainTable, '`.`order` = `', backupTable, '`.`order`');
		PREPARE updateRecord FROM @updateRecord;
		EXECUTE updateRecord;
		DEALLOCATE PREPARE updateRecord;
        
        SET @dropTable = CONCAT('DROP TABLE IF EXISTS `', backupTable, '`');
		PREPARE dropTable FROM @dropTable;
		EXECUTE dropTable;
		DEALLOCATE PREPARE dropTable;
        
		END LOOP forloop;
		CLOSE system_cursor;
		
	DROP TABLE z_2609_backup_reference_table;
    
END
$$
DELIMITER ;

CALL restoreOrdersTable;
DROP PROCEDURE IF EXISTS restoreOrdersTable;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2609';