<?php

use Phinx\Migration\AbstractMigration;

class POCOR3950 extends AbstractMigration
{
    public function up()
    {
        $apiSecurityTable = $this->table('api_securities', [
            'collation' => 'utf8mb4_unicode_ci'
        ]);
        $apiSecurityCredentialTable = $this->table('api_securities_credentials', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the access permission of the api credentials to the api models'
        ]);

        $apiSecurityTable
            ->addColumn('name', 'string', [
                'null' => false
            ])
            ->addColumn('model', 'string', [
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
            ->addColumn('add', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('view', 'integer', [
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
            ->addColumn('list', 'integer', [
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
                'name' => 'Institutions',
                'model' => 'Institution.Institutions'
            ],
            [
                'name' => 'Users',
                'model' => 'User.Users'
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
