<?php
use Migrations\AbstractMigration;

class POCOR6473 extends AbstractMigration
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
        /** Enable the MySQL event scheduler */
        /** SET GLOBAL event_scheduler = ON; */

        /** Create OpenEMIS Core events */
        $this->execute('CREATE EVENT IF NOT EXISTS `openemis_core_minute` ON SCHEDULE EVERY 1 MINUTE ON COMPLETION NOT PRESERVE ENABLE DO CALL openemis_core_reports(`minute`)');
        $this->execute('CREATE EVENT IF NOT EXISTS `openemis_core_hour` ON SCHEDULE EVERY 1 HOUR ON COMPLETION NOT PRESERVE ENABLE DO CALL openemis_core_reports(`hour`)');
        $this->execute('CREATE EVENT IF NOT EXISTS `openemis_core_day` ON SCHEDULE EVERY 1 DAY ON COMPLETION NOT PRESERVE ENABLE DO CALL openemis_core_reports(`day`)');
        $this->execute('CREATE EVENT IF NOT EXISTS `openemis_core_week` ON SCHEDULE EVERY 1 WEEK ON COMPLETION NOT PRESERVE ENABLE DO CALL openemis_core_reports(`week`)');
        $this->execute('CREATE EVENT IF NOT EXISTS `openemis_core_month` ON SCHEDULE EVERY 1 MONTH ON COMPLETION NOT PRESERVE ENABLE DO CALL openemis_core_reports(`month`)');
        $this->execute('CREATE EVENT IF NOT EXISTS `openemis_core_year` ON SCHEDULE EVERY 1 YEAR ON COMPLETION NOT PRESERVE ENABLE DO CALL openemis_core_reports(`year`)');

        /** Create OpenEMIS Core procedure */
        $this->execute('CREATE PROCEDURE `openemis_core_reports`(IN `var_interval` VARCHAR(10)) NO SQL BEGIN  DECLARE var_row TEXT; DECLARE var_done INT DEFAULT FALSE; DECLARE var_cursor CURSOR FOR SELECT query_sql FROM report_queries WHERE status = 1 AND frequency LIKE var_interval ORDER BY id ASC; DECLARE CONTINUE HANDLER FOR NOT FOUND SET var_done = TRUE; OPEN var_cursor; read_loop: LOOP FETCH var_cursor INTO var_row; IF var_done THEN LEAVE read_loop; END IF; SET @var_row = var_row; PREPARE report_query FROM @var_row;  EXECUTE report_query;  DEALLOCATE PREPARE report_query; END LOOP; CLOSE var_cursor; END ;');
        
        /** Create OpenEMIS Core report_queries table */

        $this->execute("CREATE TABLE IF NOT EXISTS `report_queries` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `query_sql` text NOT NULL,
            `frequency` varchar(10) NOT NULL COMMENT '`minute`, `hour`, `day`, `week`, `month`, `year`',
            `status` int(11) NOT NULL COMMENT '0 = diabled and 1 = enabled',
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    //rollback
    public function down()
    {
        /** Delete OpenEMIS Core events */
        $this->execute('DROP EVENT IF EXISTS `openemis_core_minute`');
        $this->execute('DROP EVENT IF EXISTS `openemis_core_hour`');
        $this->execute('DROP EVENT IF EXISTS `openemis_core_day`');
        $this->execute('DROP EVENT IF EXISTS `openemis_core_week`');
        $this->execute('DROP EVENT IF EXISTS `openemis_core_month`');
        $this->execute('DROP EVENT IF EXISTS `openemis_core_year`');

        /** Delete OpenEMIS Core procedure */
        $this->execute('DROP PROCEDURE IF EXISTS `openemis_core_reports`');

        /** Delete OpenEMIS Core report_queries table */
        $this->execute('DROP TABLE IF EXISTS `report_queries`');

        /** Disable the MySQL event scheduler */
        /** SET GLOBAL event_scheduler = OFF */
    }
}
