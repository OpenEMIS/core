<?php

use Phinx\Migration\AbstractMigration;

class POCOR4250 extends AbstractMigration
{
    // commit
    public function up()
    {
        $this->execute('RENAME TABLE `institution_buses` TO `z_4250_institution_buses`');

        // institution_buses
        $table = $this->table('institution_buses', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of buses operate by transport providers from school'
        ]);

        $table
            ->addColumn('plate_number', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('capacity', 'integer', [
                'default' => 0,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('institution_transport_provider_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_transport_providers.id'
            ])
            ->addColumn('bus_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to bus_types.id'
            ])
            ->addColumn('transport_status_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to transport_statuses.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
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
            ->addIndex('institution_transport_provider_id')
            ->addIndex('bus_type_id')
            ->addIndex('transport_status_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
            // end institution_buses

        $this->execute('INSERT INTO `institution_buses` (`id`, `plate_number`, `capacity`, `comment`, `institution_transport_provider_id`, `bus_type_id`, `transport_status_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `plate_number`, IFNULL(`capacity`, 0), `comment`, `institution_transport_provider_id`, `bus_type_id`, `transport_status_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4250_institution_buses`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_buses`');
        $this->execute('RENAME TABLE `z_4250_institution_buses` TO `institution_buses`');
    }
}
