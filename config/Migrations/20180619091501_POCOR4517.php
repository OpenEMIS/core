<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR4517 extends AbstractMigration
{
    public function up()
    {
        // student_behaviour_attachments
        $table = $this->table('student_behaviour_attachments', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of attachments linked to specific student behaviour'
        ]);

        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_behaviour_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_behaviours.id'
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
            ->addIndex('student_behaviour_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
            // end student_behaviour_attachments

        // staff_behaviour_attachments
        $table = $this->table('staff_behaviour_attachments', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of attachments linked to specific staff behaviour'
        ]);

        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => false
            ])
            ->addColumn('staff_behaviour_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_behaviours.id'
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
            ->addIndex('staff_behaviour_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
            // end staff_behaviour_attachments

            //Student Behaviour Attachments
            //Directory Module
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 284');
            $this->insert('security_functions', [
                'id' => 7059,
                'name' => 'Student Behaviour Attachments',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Students - Academic',
                'parent_id' => '7000',
                '_view' => 'StudentBehaviours.index',
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                '_execute' => null,
                'order' => 285,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);

            //Institutions Module
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 54');
            $this->insert('security_functions', [
                'id' => 3045,
                'name' => 'Student Behaviour Attachments',
                'controller' => 'StudentBehaviourAttachments',
                'module' => 'Institutions',
                'category' => 'Students',
                'parent_id' => 8,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 55,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);

            //Institutions Module
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 122');
            $this->insert('security_functions', [
                'id' => 3046,
                'name' => 'Student Behaviour Attachments',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Academic',
                'parent_id' => '2000',
                '_view' => 'Behaviours.index|Behaviours.view',
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                'order' => 123,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);

            //Staff Behaviour Attachments
            //Directory Module
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 300');
            $this->insert('security_functions', [
                'id' => 7060,
                'name' => 'Staff Behaviour Attachments',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Staff - Career',
                'parent_id' => '7000',
                '_view' => 'StaffBehaviours.index',
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                'order' => 301,
                'visible' => '1',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);
            //Institutions Module
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 61');
            $this->insert('security_functions', [
                'id' => 3047,
                'name' => 'Staff Behaviour Attachments',
                'controller' => 'StaffBehaviourAttachments',
                'module' => 'Institutions',
                'category' => 'Staff',
                'parent_id' => 8,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 62,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);

            //Institutions Module
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 159');
            $this->insert('security_functions', [
                'id' => 3048,
                'name' => 'Staff Behaviour Attachments',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Career',
                'parent_id' => '3000',
                '_view' => 'Behaviours.index|Behaviours.view',
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                'order' => 160,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);
    }

    public function down()
    {
        $this->dropTable('student_behaviour_attachments');
        $this->dropTable('staff_behaviour_attachments');

        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 54');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 61');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 122');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 159');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 284');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 300');

        $this->execute('DELETE FROM security_functions WHERE id = 3045');
        $this->execute('DELETE FROM security_functions WHERE id = 3046');
        $this->execute('DELETE FROM security_functions WHERE id = 3047');
        $this->execute('DELETE FROM security_functions WHERE id = 3048');
        $this->execute('DELETE FROM security_functions WHERE id = 7059');
        $this->execute('DELETE FROM security_functions WHERE id = 7060');
    }
}
