<?php
use Phinx\Migration\AbstractMigration;

class POCOR4895 extends AbstractMigration
{
    public function up()
    {
        $HistoricalStaffLeave = $this->table(
            'historical_staff_leave', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the historical staff leave records'
            ]
        );

        $HistoricalStaffLeave
            ->addColumn('date_from', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_to', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('full_day', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_leave_type_id', 'integer', [
                'comment' => 'links to staff_leave_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('number_of_days', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 1,
                'null' => false,
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
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
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
            ->addIndex('staff_id')
            ->addIndex('institution_id')
            ->addIndex('staff_leave_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // security_functions
        $this->execute('CREATE TABLE `z_4895_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4895_security_functions` SELECT * FROM `security_functions`');

        /*
            7072 - Historical Positions - Order 335
            7073 - Historical Leaves
         */

        $this->execute('UPDATE `security_functions` SET `_view` = "StaffLeave.index|StaffLeave.view|HistoricalStaffLeave.view" WHERE `id` = 3016');

        $this->execute('UPDATE `security_functions` SET `_view` = "StaffLeave.index|StaffLeave.view|HistoricalStaffLeave.view", `_execute` = "StaffLeave.excel" WHERE `id` = 7025');

        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 7025');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > ' . $order);

        $securityData = [
            'id' => 7073,
            'name' => 'Historical Leave',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'Staff - Career',
            'parent_id' => 7000,
            '_edit' => 'HistoricalStaffLeave.edit',
            '_add' => 'HistoricalStaffLeave.add',
            '_delete' => 'HistoricalStaffLeave.remove',
            '_execute' => 'HistoricalStaffLeave.download',
            'order' => $order + 1,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];
        $this->insert('security_functions', $securityData);
    }

    public function down()
    {
        // historical_staff_leave
        $this->execute('DROP TABLE IF EXISTS `historical_staff_leave`');

        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4895_security_functions` TO `security_functions`');
    }
}
