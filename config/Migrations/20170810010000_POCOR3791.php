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
        $table = $this->table('server_credentials', [
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
    }

    // rollback
    public function down()
    {
        $this->dropTable('server_credentials');
    }
}
