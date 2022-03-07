<?php
use Migrations\AbstractMigration;

class POCOR6546 extends AbstractMigration
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
        /** Create OpenEMIS Core report_student_assessment_summary table */
        $this->execute('
        CREATE TABLE IF NOT EXISTS `data_dictionary`(
            `database_name` varchar(200) DEFAULT NULL,
            `table_name` varchar(200) DEFAULT NULL,
            `table_description` varchar(200) DEFAULT NULL,
            `primary_keys` varchar(500) DEFAULT NULL,
            `foreign_keys` varchar(500) DEFAULT NULL,
            `linked_tables` varchar(500) DEFAULT NULL,
            `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
      ');

      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
      VALUES ("data_dictionary_truncate","TRUNCATE data_dictionary;","week", 1, 1, NOW())');
      
      $this->execute('INSERT INTO `report_queries` (`id`, `name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "data_dictionary_insert", "INSERT INTO `data_dictionary` SELECT `TABLES`.`table_schema` AS `database_name`, `TABLES`.`TABLE_NAME` AS `table_name`, `TABLES`.`TABLE_COMMENT` AS `table_description`, GROUP_CONCAT(IF(`COLUMNS`.`COLUMN_KEY` LIKE \"PRI\",`COLUMNS`.`COLUMN_NAME`,NULL)) AS `primary_keys`, GROUP_CONCAT(IF(SUBSTRING(`COLUMNS`.`COLUMN_COMMENT`,1,4) LIKE \"link\",`COLUMNS`.`COLUMN_NAME`, NULL)) AS `foreign_keys`, GROUP_CONCAT(IF(SUBSTRING(`COLUMNS`.`COLUMN_COMMENT`,1,4) LIKE \"link\",`COLUMNS`.`COLUMN_COMMENT`,NULL)) AS `linked_tables`, CURRENT_TIMESTAMP `created` FROM `information_schema`.`COLUMNS` INNER JOIN `information_schema`.`TABLES` ON `TABLES`.`table_schema` = `COLUMNS`.`TABLE_SCHEMA` AND `TABLES`.`TABLE_NAME` = `COLUMNS`.`TABLE_NAME` WHERE(`COLUMNS`.`TABLE_SCHEMA` = \"prd_cor_dmo\" OR `COLUMNS`.`TABLE_SCHEMA` = \"tst_cor_dmo\" OR `COLUMNS`.`TABLE_SCHEMA` = \"openemis_core\") AND `TABLES`.`TABLE_NAME` NOT LIKE \"z_%\" GROUP BY `COLUMNS`.`TABLE_NAME`", "week", 1, NULL, NULL, 1, NOW())');
    }
    //rollback
    public function down()
    {
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('DROP TABLE IF EXISTS `data_dictionary`');
        
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry row in report_queries table */
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "data_dictionary_truncate"'); 
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "data_dictionary_insert"'); 
    }
}