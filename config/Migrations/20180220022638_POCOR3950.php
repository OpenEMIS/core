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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
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
            ->addColumn('index', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('view', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('add', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('edit', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('delete', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('execute', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->save();

        $securityData = [
            [
                'id' => 1000,
                'name' => 'Institutions',
                'model' => 'Institution.Institutions',
                'index' => 1,
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
                'index' => 1,
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
            ->addIndex('api_credential_id')
            ->addIndex('api_scope_id')
            ->save();

        $credentialRows = $this->fetchAll('SELECT * FROM `api_credentials`');
        $credentialScopeData = [];
        foreach ($credentialRows as $row) {
            $credentialScopeData[] = [
                'api_credential_id' => $row['id'],
                'api_scope_id' => 1
            ];
        }

        if (!empty($credentialScopeData)) {
            $ApiCredentialsScopes
                ->insert($credentialScopeData)
                ->save();
        }

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
            ->addColumn('index', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('view', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('add', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('edit', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('delete', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('execute', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
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
            ->addIndex('api_security_id')
            ->addIndex('api_scope_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // API Credentials
        $ApiCredentials = $this->table('api_credentials');

        $ApiCredentials
            ->removeColumn('scope')
            ->save();
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
