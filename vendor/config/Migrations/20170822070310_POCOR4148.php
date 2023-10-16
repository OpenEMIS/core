<?php

use Phinx\Migration\AbstractMigration;

class POCOR4148 extends AbstractMigration
{
    // commit
    public function up()
    {
        // institution_competency_results
        $this->table('student_competency_results')
            ->rename('institution_competency_results');

        // institution_competency_item_comments
        $table = $this->table('institution_competency_item_comments', [
            'id' => false,
            'primary_key' => [
                'student_id',
                'competency_template_id',
                'competency_period_id',
                'competency_item_id',
                'institution_id',
                'academic_period_id'
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the comments for a competency item for an individual student in an institution'
        ]);

        $table
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('competency_template_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to competency_templates.id'
            ])
            ->addColumn('competency_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to competency_periods.id'
            ])
            ->addColumn('competency_item_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to competency_items.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
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
            ->addIndex('id')
            ->addIndex('competency_template_id')
            ->addIndex('competency_period_id')
            ->addIndex('competency_item_id')
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_competency_period_comments
        $table = $this->table('institution_competency_period_comments', [
            'id' => false,
            'primary_key' => [
                'student_id',
                'competency_template_id',
                'competency_period_id',
                'institution_id',
                'academic_period_id'
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the comments for a competency period for an individual student in an institution'
        ]);

        $table
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('competency_template_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to competency_templates.id'
            ])
            ->addColumn('competency_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to competency_periods.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
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
            ->addIndex('id')
            ->addIndex('competency_template_id')
            ->addIndex('competency_period_id')
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 59');

        $data = [
            'id' => '1062',
            'name' => 'Competency Comments',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 8,
            '_view' => 'StudentCompetencyComments.index|StudentCompetencyComments.view',
            '_edit' => 'StudentCompetencyComments.edit',
            'order' => '60',
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];

        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();
    }

    // rollback
    public function down()
    {
        $this->table('institution_competency_results')
            ->rename('student_competency_results');

        $this->execute('DROP TABLE institution_competency_item_comments');
        $this->execute('DROP TABLE institution_competency_period_comments');
        $this->execute('DELETE FROM security_functions WHERE `id` = 1062');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 59');
    }
}
