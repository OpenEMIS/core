<?php

use Phinx\Migration\AbstractMigration;

class POCOR4894 extends AbstractMigration
{
    public function up()
    {
        // historical_staff_positions
        $HistoricalStaffPositions = $this->table('historical_staff_positions', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the Historical positions for all the staff'
        ]);

        $HistoricalStaffPositions
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
    
        // security_functions
        $this->execute('CREATE TABLE `z_4894_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4894_security_functions` SELECT * FROM `security_functions`');

        /*
            7072 - Historical Positions - Order 335
            7073 - Historical Leaves
         */
        
        $this->execute('UPDATE `security_functions` SET `_view` = "StaffPositions.index|StaffPositions.view", `_execute` = "StaffPositions.excel" WHERE `id` = 7021');
        
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 335');
        $securityData = [
            'id' => 7072,
            'name' => 'Historical Positions',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'Staff - Career',
            'parent_id' => 7000,
            '_view' => 'HistoricalStaffPositions.view',
            '_edit' => 'HistoricalStaffPositions.edit',
            '_add' => 'HistoricalStaffPositions.add',
            '_delete' => 'HistoricalStaffPositions.delete',
            '_execute' => null,
            'order' => 335,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];
        $this->insert('security_functions', $securityData);
    }

    public function down()
    {
        // historical_staff_positions
        $this->execute('DROP TABLE IF EXISTS `historical_staff_positions`');

        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4894_security_functions` TO `security_functions`');
    }
}
