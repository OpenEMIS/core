<?php
use Migrations\AbstractMigration;

class POCOR4177 extends AbstractMigration
{
    public function up()
    {
        $this->table('staff_employments')->rename('z_4177_staff_employments');
        $table1 = $this->table('staff_employment_statuses');
        $table1
            ->addColumn('status_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('status_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
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
            ->addIndex('staff_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT INTO `staff_employment_statuses` (id, status_date, comment, file_name, file_content, status_type_id, staff_id, modified_user_id, modified, created_user_id, created) SELECT id, employment_date, comment, file_name, file_content, employment_type_id, staff_id, modified_user_id, modified, created_user_id, created FROM `z_4177_staff_employments`');


        $this->table('employment_types')->rename('z_4177_employment_types');
        $this->execute('CREATE TABLE `employment_status_types` LIKE `z_4177_employment_types`');
        $this->execute('INSERT INTO `employment_status_types` SELECT * FROM `z_4177_employment_types`');

        $table2 = $this->table('user_employments');
        $table2
            ->addColumn('date_from', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('date_to', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('organisation', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('position', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('security_user_id', 'integer', [
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
            ->addIndex('security_user_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->table('security_functions')->rename('z_4177_security_functions');
        $this->execute('CREATE TABLE `security_functions` LIKE `z_4177_security_functions`');
        $this->execute('INSERT INTO `security_functions` SELECT * FROM `z_4177_security_functions`');

        $sql = "UPDATE `security_functions`
                SET name = 'Employment Status',
                _view = 'EmploymentStatuses.index|EmploymentStatuses.view',
                _edit = 'EmploymentStatuses.edit',
                _add = 'EmploymentStatuses.add',
                _delete = 'EmploymentStatuses.remove',
                _execute = 'EmploymentStatuses.download'
                WHERE id = '3019'";
        $this->execute($sql);

        $sql2 = "UPDATE `security_functions`
                SET name = 'Employment Status',
                _view = 'StaffEmploymentStatuses.index|StaffEmploymentStatuses.view',
                _edit = 'StaffEmploymentStatuses.edit',
                _add = 'StaffEmploymentStatuses.add',
                _delete = 'StaffEmploymentStatuses.remove',
                _execute = 'StaffEmploymentStatuses.download'
                WHERE id = '7020'";
        $this->execute($sql2);

        $sql3 = "UPDATE `security_functions` SET category = 'Staff - Professional' WHERE category = 'Staff - Professional Development'";
        $this->execute($sql3);

        //Awards
        $sql4 = "UPDATE `security_functions` SET category = 'Staff - Professional' WHERE id = '3007'";
        $this->execute($sql4);

        //Awards
        $sql5 = "UPDATE `security_functions` SET category = 'Staff - Professional' WHERE id = '7027'";
        $this->execute($sql5);

        //Appraisals
        $sql6 = "UPDATE `security_functions` SET category = 'Staff - Career' WHERE id = '3037'";
        $this->execute($sql6);

        //Appraisals
        $sql7 = "UPDATE `security_functions` SET category = 'Staff - Career' WHERE id = '7049'";
        $this->execute($sql7);

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 117');

        $this->insert('security_functions', [
            'id' => 3040,
            'name' => 'Employment',
            'controller' => 'Staff',
            'module' => 'Institutions',
            'category' => 'Staff - Professional',
            'parent_id' => 3000,
            '_view' => 'Employments.index|Employments.view',
            '_edit' => 'Employments.edit',
            '_add' => 'Employments.add',
            '_delete' => 'Employments.remove',
            'order' => 118,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 93');

        $this->insert('security_functions', [
            'id' => 2036,
            'name' => 'Employment',
            'controller' => 'Students',
            'module' => 'Institutions',
            'category' => 'Students - Professional',
            'parent_id' => 2000,
            '_view' => 'Employments.index|Employments.view',
            '_edit' => 'Employments.edit',
            '_add' => 'Employments.add',
            '_delete' => 'Employments.remove',
            'order' => 94,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 288');

        $this->insert('security_functions', [
            'id' => 7054,
            'name' => 'Employment',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'Professional',
            'parent_id' => 7000,
            '_view' => 'Employments.index|Employments.view',
            '_edit' => 'Employments.edit',
            '_add' => 'Employments.add',
            '_delete' => 'Employments.remove',
            'order' => 289,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->execute('DROP TABLE staff_employment_statuses');
        $this->table('z_4177_staff_employments')->rename('staff_employments');

        $this->execute('DROP TABLE employment_status_types');
        $this->table('z_4177_employment_types')->rename('employment_types');

        $this->execute('DROP TABLE user_employments');

        $this->execute('DROP TABLE security_functions');
        $this->table('z_4177_security_functions')->rename('security_functions');
    }
}