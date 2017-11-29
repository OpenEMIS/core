<?php
use Migrations\AbstractMigration;

class POCOR4177 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('staff_employments');
        $table
        ->renameColumn('employment_date', 'status_date')
        ->renameColumn('employment_type_id', 'status_type_id')
        ->changeColumn('status_type_id', 'integer', ['comment' => 'links to employment_types.id'])
        ->rename('staff_employment_statuses')
        ->save();

        $table2 = $this->table('staff_employments');
        $table2
            ->addColumn('date_from', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('date_to', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('position', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('organisation', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        $this->execute('DROP TABLE staff_employments');

        $table = $this->table('staff_employment_statuses');
        $table
        ->renameColumn('status_date', 'employment_date')
        ->renameColumn('status_type_id', 'employment_type_id')
        ->changeColumn('employment_type_id', 'integer', ['comment' => 'employment_types'])
        ->rename('staff_employments')
        ->save();        
    }
}