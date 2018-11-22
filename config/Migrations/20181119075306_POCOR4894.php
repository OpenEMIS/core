<?php

use Phinx\Migration\AbstractMigration;

class POCOR4894 extends AbstractMigration
{
    public function up()
    {
        // historial_staff_positions
        $HistorialStaffPositions = $this->table('historial_staff_positions', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the historial positions for all the staff'
        ]);

        $HistorialStaffPositions
            // ->addColumn('FTE', 'decimal', [
            //     'precision' => 5,
            //     'scale' => 2,
            //     'null' => true,
            //     'default' => null
            // ])
            ->addColumn('start_date', 'date', [
                'null' => false,
                'default' => null
            ])
            ->addColumn('end_date', 'date', [
                'null' => false,
                'default' => null
            ])
            ->addColumn('institution_name', 'string', [
                'limit' => 150,
                'null' => false,
                'default' => null
            ])
            ->addColumn('institution_position_name', 'string', [
                'limit' => 150,
                'null' => false,
                'default' => null
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('staff_type_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null,
                'comment' => 'links to staff_types.id'
            ])
            ->addColumn('staff_status_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => 2, // END_OF_ASSIGNMENT
                'comment' => 'links to staff_statuses.id'
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
            ->addIndex('staff_id')
            ->addIndex('staff_type_id')
            ->addIndex('staff_status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        // historial_staff_positions
        $this->execute('DROP TABLE IF EXISTS `historial_staff_positions`');
    }
}
