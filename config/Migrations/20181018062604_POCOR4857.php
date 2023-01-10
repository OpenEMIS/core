<?php
/**
 * MIGRATION POCOR4857 - For MoodleAPI
 *
 * PHP version 7.2
 *
 * @category  Migrations
 * @package   Migrations
 * @author    Ervin Kwan <ekwan@kordit.com>
 * @copyright 2018 KORDIT PTE LTD
 */
use Phinx\Migration\AbstractMigration;

class POCOR4857 extends AbstractMigration
{
    public function up()
    {
        //Moodle Api Log Table

        $this->table('moodle_api_log', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table serves as a log. It also stores failed attempts.',
            'id' => true //Auto increment id and primary key
        ])
        ->addColumn('action', 'string', [
            'limit' => 255,
            'null' => false,
            'comment' => 'function name for moodle'
        ])
        ->addColumn('params', 'text', [
            'null' => false,
            'comment' => 'stores params data in json'
        ])
        ->addColumn('response', 'text', [
            'null' => false,
            'comment' => 'stores response data in json'
        ])
        ->addColumn('created_user_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false
        ])
        ->addColumn('status', 'integer', [
            'limit' => 1,
            'null' => false,
            'comment' => '1 = success, 2 = failed'
        ])
        ->addColumn('callback', 'string', [
            'limit' => 255,
            'null' => false,
            'comment' => 'function name from MoodleApi.php that was executed'
        ])
        ->addColumn('callback_param', 'text', [
            'null' => false,
            'comment' => 'stores serialized param data'
        ])
        ->addIndex('action')
        ->addIndex('created_user_id')
        ->addIndex('status')
        ->save();


        //Moodle Api Created Users Table

        $this->table('moodle_api_created_users', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'To store the moodle user_id that was created and link to which account in openemis',
            'id' => true //Auto increment id and primary key
        ])
        ->addColumn('moodle_user_id', 'integer', [
            'limit' => 11,
            'null' => false
        ])
        ->addColumn('core_user_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to security_users'
        ])
        ->addColumn('moodle_username', 'string', [
            'limit' => 255,
            'null' => false
        ])
        ->addColumn('created_user_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false
        ])
        ->addIndex('moodle_user_id')
        ->addIndex('core_user_id')
        ->addIndex('moodle_username')
        ->save();

        //Security Table backup
        $this->execute('CREATE TABLE `z_4857_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4857_security_functions` SELECT * FROM `security_functions`');

        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES ('9000', 'MoodleApi Log', 'MoodleApi', 'Administration', 'MoodleApi', '5000',
            'index|view',
            'edit',
            'add',
            'remove',
            NULL,
            '369', '1', NULL, NULL, NULL, '1', NOW())");

    }

    public function down()
    {
        $this->dropTable('moodle_api_log');
        $this->dropTable('moodle_api_created_users');

        // Security Table recover backup
        $this->execute('DROP TABLE security_functions');
        $this->execute('RENAME TABLE `z_4784_security_functions` TO `security_functions`');
    }
}
