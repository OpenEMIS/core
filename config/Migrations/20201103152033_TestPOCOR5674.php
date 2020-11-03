<?php
use Migrations\AbstractMigration;

class TestPOCOR5674 extends AbstractMigration
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
        //backup_logs
        $this->execute("CREATE TABLE `backup_logs` ( `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(250) NOT NULL,
        `path` varchar(255) NOT NULL,
        `generated_on` datetime NOT NULL,
        `generated_by` int(11) DEFAULT NULL, PRIMARY KEY (`id`) )");

        //transfer_logs
        $this->execute("CREATE TABLE `transfer_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `academic_period_id` int(11) NOT NULL ,
        `generated_on` datetime NOT NULL DEFAULT current_timestamp(),
        `generated_by` int(11) NOT NULL, 
            PRIMARY KEY (`id`),
            KEY `generated_on` (`generated_on`),
            KEY `generated_by` (`generated_by`) )");

        //transfer_connections
        $this->execute("CREATE TABLE `transfer_connections` ( `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL,
        `db_type_id` int(11) NOT NULL COMMENT 'MySql,Postgres,SqlServer,Sqlite',
        `host` varchar(100) NOT NULL,
        `host_port` int(11) NOT NULL,
        `db_name` varchar(100) NOT NULL,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `conn_status_id` int(11) NOT NULL DEFAULT 0,
        `status_checked` datetime DEFAULT NULL,
        `modified_user_id` int(11) DEFAULT NULL,
        `modified` datetime DEFAULT NULL,
        `created_user_id` int(11) NOT NULL,
        `created` datetime NOT NULL, PRIMARY KEY (`id`), KEY `modified_user_id` (`modified_user_id`), KEY `created_user_id` (`created_user_id`) )");

        $this->insert('transfer_connections', [
            'name' => 'database warehouse',
            'db_type_id' => '1',
            'host' => 'localhost',
            'host_port' => '3306',
            'db_name' => 'prd_cor_arc',
            'username' => 'root',
            'password' => '',
            'conn_status_id' => 0,
            'status_checked' => NULL,
            'modified_user_id' => 2,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2020-11-03 13:54:29'
        ]);
    }

    // rollback
    public function down()
    {
        //rollback of backup_logs, transfer_connections and transfer_logs
        $this->execute('DROP TABLE IF EXISTS `backup_logs`');
        $this->execute('DROP TABLE IF EXISTS `transfer_connections`');
        $this->execute('DROP TABLE IF EXISTS `transfer_logs`');

        // Security Table recover backup
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 369');
        $this->execute('DELETE FROM security_functions WHERE name = "Archive"');
    }
}
