<?php
use Migrations\AbstractMigration;

class POCOR3953 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // guidance_types
        $table = $this->table('guidance_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of guidance'
            ]);
        $table->addColumn('name', 'string', [
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
            ->save();
        // end guidance_types

        // institution_counsellings
        $table = $this->table('institution_counsellings', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains counsellings in the institution'
            ]);
        $table->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('intervention', 'text', [
                'default' => null,
                'null' => false
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
            ->addColumn('counselor_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('guidance_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to guidance_types.id'
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
            ->addIndex('counselor_id')
            ->addIndex('student_id')
            ->addIndex('guidance_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_counsellings

        // security_functions
        $data = [
            'id' => '1061',
            'name' => 'Counselling',
            'controller' => 'Counsellings',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 8,
            '_view' => 'index|view|download',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => '1061',
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];

        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();
        // end security_functions

        // security_role_functions
        $sql = 'INSERT INTO `security_role_functions` (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `created_user_id`, `created`)
                SELECT 1, 1, 1, 1, 0, `security_role_id`, 1061, 1, NOW() FROM `security_role_functions`
                WHERE `security_function_id` = 1012';

        $this->execute($sql);
        // end security_role_functions

        $this->execute('ALTER TABLE `deleted_records` CHANGE `id` `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE guidance_types');
        $this->execute('DROP TABLE institution_counsellings');
        $this->execute('DELETE FROM security_functions WHERE id = 1061');
        $this->execute('DELETE FROM security_role_functions WHERE security_function_id = 1061');
    }
}
