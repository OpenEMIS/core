<?php
use Migrations\AbstractMigration;

class POCOR7230 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
   
        /** Delete existing Core procedure */
        $this->execute('DROP PROCEDURE IF EXISTS `openemis_core_reports`');

        /** Create new OpenEMIS Core procedure that now does not stop each time when error occurs, but rather ignores the error and proceedes with another query */
        $this->execute('CREATE PROCEDURE `openemis_core_reports`(IN `var_interval` VARCHAR(10)) NO SQL BEGIN DECLARE var_row TEXT; DECLARE var_done INT DEFAULT FALSE; DECLARE var_cursor CURSOR FOR SELECT query_sql FROM report_queries WHERE status = 1 AND frequency LIKE var_interval ORDER BY id ASC; DECLARE CONTINUE HANDLER FOR NOT FOUND SET var_done = TRUE; DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET var_done = FALSE; OPEN var_cursor; read_loop: LOOP FETCH var_cursor INTO var_row; IF var_done THEN LEAVE read_loop; END IF; SET @var_row = var_row; PREPARE report_query FROM @var_row; EXECUTE report_query; DEALLOCATE PREPARE report_query; END LOOP; CLOSE var_cursor; END ;');
     
    }

    //rollback
    public function down()
    {
        
        /** Delete latest OpenEMIS Core procedure */
        $this->execute('DROP PROCEDURE IF EXISTS `openemis_core_reports`');

        /** Restaure the procedure that was used before */
        $this->execute('CREATE PROCEDURE `openemis_core_reports`(IN `var_interval` VARCHAR(10)) NO SQL BEGIN  DECLARE var_row TEXT; DECLARE var_done INT DEFAULT FALSE; DECLARE var_cursor CURSOR FOR SELECT query_sql FROM report_queries WHERE status = 1 AND frequency LIKE var_interval ORDER BY id ASC; DECLARE CONTINUE HANDLER FOR NOT FOUND SET var_done = TRUE; OPEN var_cursor; read_loop: LOOP FETCH var_cursor INTO var_row; IF var_done THEN LEAVE read_loop; END IF; SET @var_row = var_row; PREPARE report_query FROM @var_row;  EXECUTE report_query;  DEALLOCATE PREPARE report_query; END LOOP; CLOSE var_cursor; END ;');
    }
}