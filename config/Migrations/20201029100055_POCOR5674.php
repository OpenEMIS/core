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
        //archives
        $this->execute("CREATE TABLE `archives` ( `id` int(11) NOT NULL,
        `name` varchar(250) NOT NULL,
        `path` varchar(255) NOT NULL,
        `generated_on` datetime NOT NULL,
        `generated_by` int(11) DEFAULT NULL, PRIMARY KEY (`id`) )");
        
        //deleted_logs
        $this->execute("CREATE TABLE `deleted_logs` (  `id` int(11) NOT NULL,
        `academic_period_id` int(11) NOT NULL,
        `generated_on` datetime NOT NULL DEFAULT current_timestamp(),
        `generated_by` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `generated_on` (`generated_on`), KEY `generated_by` (`generated_by`) )");
        
        //archive_connections
        $this->execute("CREATE TABLE `archive_connections` ( `id` int(11) NOT NULL,
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

        //Security Table backup
        $this->execute('CREATE TABLE `z_5674_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5674_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 369');

        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES ('Archive', 'Archives', 'Administration', 'Archive', '5000',
            'index|view|download',
            'edit',
            'add',
            'remove',
            NULL,
            '370', '1', NULL, NULL, NULL, '1', NOW())");
    }

    // rollback
    public function down()
    {
        //rollback of archives, deleted_logs and archive_connections
        $this->execute('DROP TABLE IF EXISTS `archives`');
        $this->execute('DROP TABLE IF EXISTS `deleted_logs`');
        $this->execute('DROP TABLE IF EXISTS `archive_connections`');

        // Security Table recover backup
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 369');
        $this->execute('DELETE FROM security_functions WHERE name = "Archive"');
    }
}