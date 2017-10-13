<?php
use Phinx\Migration\AbstractMigration;

class POCOR4218 extends AbstractMigration
{
    // commit
    public function up()
    {
        // transport_providers
        $table = $this->table('transport_providers', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of providers used by transport'
        ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('address', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true
            ])
            ->addColumn('contact_number', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => false
            ])
            ->addColumn('registration_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end transport_providers

        // buses
        $table = $this->table('buses', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of buses operate by transport providers'
        ]);
        $table
            ->addColumn('plate_number', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('capacity', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('transport_provider_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to transport_providers.id'
            ])
            ->addColumn('bus_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to bus_types.id'
            ])
            ->addColumn('bus_status_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to bus_statuses.id'
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
            ->addIndex('transport_provider_id')
            ->addIndex('bus_type_id')
            ->addIndex('bus_status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end buses

        // bus_types
        $table = $this->table('bus_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of buses'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end bus_types

        // bus_statuses
        $table = $this->table('bus_statuses', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains statuses of buses'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end bus_statuses

        // bus_features
        $table = $this->table('bus_features', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains features of buses'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end bus_features
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE `transport_providers`');
        $this->execute('DROP TABLE `buses`');
        $this->execute('DROP TABLE `bus_types`');
        $this->execute('DROP TABLE `bus_statuses`');
        $this->execute('DROP TABLE `bus_features`');
    }
}
