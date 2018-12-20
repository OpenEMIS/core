<?php
use Phinx\Migration\AbstractMigration;

class POCOR4367 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('student_status_updates', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the records that need to be updated during the effective date'
        ]);

        $table
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('model_reference', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('effective_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('execution_status', 'integer', [
                'comment' => '1 -> Not Executed , 2 -> Executed',
                'default' => 1,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('education_grade_id', 'integer', [
                'comment' => 'links to education_grades.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to student_statuses.id',
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
            ->addIndex('security_user_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // security_functions
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1091');
        $order = $row['order']; //90

        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > ' . $order);

        $table = $this->table('security_functions');
        $data = [
            'id' => 1092,
            'name' => 'Student Status Updates',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 1000,
            '_view' => 'StudentStatusUpdates.index|StudentStatusUpdates.view',
            'order' => $order + 1,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table->insert($data);
        $table->saveData();
    }

    public function down()
    {
        $this->execute('DROP TABLE student_status_updates');
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1091');
        $order = $row['order'];
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > ' . $order);
        $this->execute('DELETE FROM security_functions WHERE `id` = 1092');
    }
}
