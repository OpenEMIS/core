<?php
use Migrations\AbstractMigration;

class POCOR5674 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    // commit
    public function up()
    {
        // institution_class_students
        $this->execute('CREATE TABLE `archive_table_logs` (
            `id` int(11) NOT NULL,
            `name` varchar(250) NOT NULL,
            `path` varchar(255) NOT NULL,
            `generated_on` datetime NOT NULL,
            `generated_by` varchar(100) NOT NULL
          )');
        $this->execute('CREATE TABLE `deleted_table_logs` (
            `id` int(11) NOT NULL,
            `archive_table_logs_id` int(11) NOT NULL,
            `academic_period_id` int(11) NOT NULL,
            `deleted_on` datetime NOT NULL,
            `deleted_by` varchar(100) NOT NULL
          )');
        $this->execute('CREATE TABLE `archive_connections` (
            `id` int(11) NOT NULL,
            `name` varchar(50) NOT NULL,
            `db_type_id` int(11) NOT NULL,
            `host` varchar(100) NOT NULL,
            `host_port` int(11) NOT NULL,
            `db_name` varchar(50) NOT NULL,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `conn_status_id` int(11) NOT NULL DEFAULT 0,
            `status_checked` datetime DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL
          )');
        
        
    }

    // rollback
    public function down()
    {
        // institution_class_students
        $this->execute('DROP TABLE IF EXISTS `archive_table_logs`');
        $this->execute('DROP TABLE IF EXISTS `deleted_table_logs`');
        $this->execute('DROP TABLE IF EXISTS `archive_connections`');
    }
