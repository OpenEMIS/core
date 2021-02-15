<?php
use Migrations\AbstractMigration;

class POCOR5189 extends AbstractMigration
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
        // institution_associations
        $table = $this->table('institution_associations', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of guidance'
            ]);
        $table->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])        
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 50,
                'null' => true,
                'comment' => 'links to academic_periods.id'
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
            ->addIndex('academic_period_id')
            ->save();
        // end institution_associations

        // institution_association_staff
        $table = $this->table('institution_association_staff', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains staff in the institution association'
            ]);
        $table->addColumn('id', 'integer', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('security_user_id', 'integer', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_association_id', 'integer', [
                'default' => null,
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
            ->addIndex('security_user_id')
            ->addIndex('institution_association_id')
            ->save();
        // end institution_association_staff
        // institution_association_students
        $table = $this->table('institution_association_students', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains student in the institution association'
            ]);
        $table->addColumn('id', 'integer', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('security_user_id', 'integer', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_association_id', 'integer', [
                'default' => null,
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
            ->addIndex('security_user_id')
            ->addIndex('institution_association_id')
            ->save();
        // end institution_association_students

        // security_functions
        // $data = [
        //     'id' => '1061',
        //     'name' => 'Counselling',
        //     'controller' => 'Counsellings',
        //     'module' => 'Institutions',
        //     'category' => 'Students',
        //     'parent_id' => 8,
        //     '_view' => 'index|view|download',
        //     '_edit' => 'edit',
        //     '_add' => 'add',
        //     '_delete' => 'delete',
        //     'order' => '1061',
        //     'visible' => 1,
        //     'created_user_id' => '1',
        //     'created' => date('Y-m-d H:i:s')
        // ];

        // $table = $this->table('security_functions');
        // $table->insert($data);
        // $table->saveData();
        // // end security_functions

        // // security_role_functions
        // $sql = 'INSERT INTO `security_role_functions` (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `created_user_id`, `created`)
        //         SELECT 1, 1, 1, 1, 0, `security_role_id`, 1061, 1, NOW() FROM `security_role_functions`
        //         WHERE `security_function_id` = 1012';

        // $this->execute($sql);
        // end security_role_functions

       
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
