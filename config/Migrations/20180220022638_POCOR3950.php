<?php

use Phinx\Migration\AbstractMigration;

class POCOR3950 extends AbstractMigration
{
    public function up()
    {
        // API Scopes
        $ApiScopes = $this->table('api_scopes', [
            'collation' => 'utf8mb4_unicode_ci'
        ]);

        $ApiScopes
            ->addColumn('name', 'string', [
                'null' => false,
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

        $scopeData = [
            [
                'id' => 1,
                'name' => 'API',
                'created_user_id' => 2,
                'created' => '1990-01-01 00:00:00'
            ]
        ];

        $ApiScopes
            ->insert($scopeData)
            ->save();


        // API Securities
        $ApiSecurities = $this->table('api_securities', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci'
        ]);

        $ApiSecurities
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
            ->save();

        $securityData = [
            [
                'id' => 1000,
                'name' => 'Institutions',
                'model' => 'Institution.Institutions',
                'list' => 1,
                'view' => 1,
                'add' => 0,
                'edit' => 0,
                'delete' => 0,
                'execute' => 0
            ],
            [
                'id' => 1001,
                'name' => 'Users',
                'model' => 'User.Users',
                'list' => 1,
                'view' => 1,
                'add' => 0,
                'edit' => 0,
                'delete' => 0,
                'execute' => 0
            ]
        ];

        $ApiSecurities
            ->insert($securityData)
            ->save();

        // API Credentials Scopes
        $ApiCredentialsScopes = $this->table('api_credentials_scopes', [
            'id' => false,
            'primary_key' => ['api_credential_id', 'api_scope_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains what scope the credentials has'
        ]);

        $ApiCredentialsScopes
            ->addColumn('api_credential_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to api_credentials.id'
            ])
            ->addColumn('api_scope_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to api_scopes.id'
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

        $ApiSecuritiesScopes = $this->table('api_securities_scopes', [
            'id' => false,
            'primary_key' => ['api_security_id', 'api_scope_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the access permission of each table within the scopes'
        ]);

        $ApiSecuritiesScopes
            ->addColumn('api_security_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to api_securities.id'
            ])
            ->addColumn('api_scope_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to api_scopes.id'
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

        // API Credentials
        $ApiCredentials = $this->table('api_credentials');

        $ApiCredentials
            ->removeColumn('scope')
            ->save();

        // $this->execute("UPDATE `api_credentials` SET `api_scope_id` = 1");
    }

    public function down()
    {
        $this->dropTable('api_securities');
        $this->dropTable('api_scopes');
        $this->dropTable('api_credentials_scopes');
        $this->dropTable('api_securities_scopes');

        $ApiCredentials = $this->table('api_credentials');
        $ApiCredentials
            ->addColumn('scope', 'string', [
                'after' => 'public_key'
            ])
            ->save();
        $this->execute("UPDATE `api_credentials` SET `scope` = 'API'");
    }
}
