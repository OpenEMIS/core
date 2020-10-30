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
        $this->execute("CREATE TABLE `archive_table_logs` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(250) NOT NULL, `path` varchar(250) NOT NULL,  `generated_on` datetime DEFAULT NULL, `generated_by` int(11) NOT NULL, PRIMARY KEY (`id`) )");
        
        $this->execute("CREATE TABLE `deleted_logs` ( `id` int(11) NOT NULL AUTO_INCREMENT, `academic_period_id` int(11) NOT NULL, `generated_on` datetime DEFAULT NULL, `generated_by` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `generated_on` (`generated_on`), KEY `generated_by` (`generated_by`) )");
        
        $this->execute("CREATE TABLE `archive_connections` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(50) NOT NULL, `db_type_id` int(3) NOT NULL, `host` varchar(100) NOT NULL, `host_port` int(11) NOT NULL, `db_name` varchar(100) NOT NULL, `username` varchar(50) NOT NULL, `password` varchar(255) NOT NULL, `conn_status_id` int(1) DEFAULT '0', `status_checked` datetime DEFAULT NULL, `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`), KEY `modified_user_id` (`modified_user_id`), KEY `created_user_id` (`created_user_id`) )");
    }

    // rollback
    public function down()
    {
        // institution_class_students
        $this->execute('DROP TABLE IF EXISTS `archive_table_logs`');
        $this->execute('DROP TABLE IF EXISTS `deleted_logs`');
        $this->execute('DROP TABLE IF EXISTS `archive_connections`');
    }
}