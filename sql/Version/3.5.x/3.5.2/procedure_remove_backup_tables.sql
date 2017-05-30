DROP PROCEDURE IF EXISTS removeBackupTables;
DELIMITER $$

CREATE PROCEDURE removeBackupTables()
BEGIN
  DECLARE done, table_done BOOLEAN DEFAULT FALSE;
  DECLARE versionNo, issueNo, tableName VARCHAR(100);
  
  DECLARE cursor_patches CURSOR FOR 
    SELECT CONCAT('z_', SUBSTRING_INDEX(issue, '-', -1), '%') FROM db_patches WHERE version = versionNo;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  SELECT DISTINCT version INTO versionNo FROM db_patches ORDER BY created DESC LIMIT 5,1;

  OPEN cursor_patches;

  read_loop: LOOP
	FETCH cursor_patches INTO issueNo;
	IF done THEN
	  LEAVE read_loop;
	END IF;

    BLOCK2: BEGIN
        DECLARE cursor_tables CURSOR FOR 
            SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE issueNo GROUP BY TABLE_NAME;

        DECLARE CONTINUE HANDLER FOR NOT FOUND SET table_done = TRUE;
        
        OPEN cursor_tables; 
        
        cur_table_loop: LOOP

            FETCH FROM cursor_tables INTO tableName;
            IF table_done THEN
                SET table_done = false;
                LEAVE cur_table_loop;
            END IF;
            -- SELECT tableName;

            DROP TABLE IF EXISTS tableName;
                
        END LOOP cur_table_loop;

        CLOSE cursor_tables;
    END BLOCK2;

  END LOOP read_loop;

  CLOSE cursor_patches;
END
$$

DELIMITER ;

CALL removeBackupTables;
