<?php

use Phinx\Migration\AbstractMigration;

class POCOR3950 extends AbstractMigration
{
    public function up()
    {
        $apiSecurityTable = $this->table('api_securities', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $apiSecurityCredentialTable = $this->table('api_securities_credentials', [
            'id' => false,
            'primary_key' => ['api_credential_id', 'api_security_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the access permission of the api credentials to the api models'
        ]);

        $apiSecurityTable
            ->addColumn('id', 'integer', [
                'limit' => '11',
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'null' => false
            ])
            ->addColumn('model', 'string', [
                'null' => false
            ])
            ->addColumn('_list', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('_view', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('_add', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('_edit', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('_delete', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('_execute', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->save();

        $apiSecurityCredentialTable
            ->addColumn('api_credential_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to api_credentials.id'
            ])
            ->addColumn('api_security_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to api_securities.id'
            ])
            ->addColumn('list', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('view', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('add', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('edit', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('delete', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('execute', 'integer', [
                'limit' => 1,
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

        $modelData = [
            [
                'id' => 1000,
                'name' => 'Institutions',
                'model' => 'Institution.Institutions',
                '_list' => 1,
                '_view' => 1,
                '_add' => 0,
                '_edit' => 0,
                '_delete' => 0,
                '_execute' => 0
            ],
            [
                'id' => 1001,
                'name' => 'Users',
                'model' => 'User.Users',
                '_list' => 1,
                '_view' => 1,
                '_add' => 0,
                '_edit' => 0,
                '_delete' => 0,
                '_execute' => 0
            ]
        ];

        $apiSecurityTable
            ->insert($modelData)
            ->save();
    }

    public function down()
    {
        $this->dropTable('api_securities');
        $this->dropTable('api_securities_credentials');
    }
}
