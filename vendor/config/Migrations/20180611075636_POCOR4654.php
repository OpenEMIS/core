<?php

use Phinx\Migration\AbstractMigration;

class POCOR4654 extends AbstractMigration
{
    public function up()
    {   
        // security_user_password_requests
        $SecurityUserPasswordRequests = $this->table('security_user_password_requests', [
            'comment' => 'This table contains all the reset password requests by the users',
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci'
        ]);

        $SecurityUserPasswordRequests
            ->addColumn('id', 'string', [
                'null' => false,
                'limit' => 64
            ])
            ->addColumn('expiry_date', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex('user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_user_password_requests`');
    }
}
