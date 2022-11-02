<?php
use Migrations\AbstractMigration;

class POCOR3791 extends AbstractMigration
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
        // server_credentials
        $table = $this->table('api_credentials', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table will contain the server credentials of the server accessing the application.'
            ]);
        $table->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('client_id', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('public_key', 'text', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('scope', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
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
            ->save();

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 163');

        $this->insert('security_functions', [
            'id' => 5077,
            'name' => 'Credentials',
            'controller' => 'Credentials',
            'module' => 'Administration',
            'category' => 'System Configurations',
            'parent_id' => 5000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            '_execute' => null,
            'order' => 164,
            'visible' => 1,
            'description' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => 1,
            'created' => '2017-08-11'
        ]);
    }

    // rollback
    public function down()
    {
        $this->dropTable('api_credentials');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 163');
        $this->execute('DELETE FROM security_functions WHERE id = 5077');
    }
}
