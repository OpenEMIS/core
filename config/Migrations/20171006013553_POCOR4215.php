<?php

use Phinx\Migration\AbstractMigration;

class POCOR4215 extends AbstractMigration
{
    // commit
    public function up()
    {
        // infrastructure_projects_needs
        $table = $this->table('infrastructure_projects_needs', [
            'id' => false,
            'primary_key' => [
                'infrastructure_project_id',
                'infrastructure_need_id',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the infrastructure_needs for the infrastructure_projects'
        ]);

        $table
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('infrastructure_project_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_projects.id'
            ])
            ->addColumn('infrastructure_need_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_needs.id'
            ])
            ->addIndex('id')
            ->addIndex('infrastructure_project_id')
            ->addIndex('infrastructure_need_id')
            ->save();
        // end infrastructure_projects_needs

        // infrastructure_project_funding_sources
        $table = $this->table('infrastructure_project_funding_sources', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains funding sources of infrastructure projects'
            ]);
        $table
            ->addColumn('name', 'string', [
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_project_funding_sources

        // infrastructure_projects
        $table = $this->table('infrastructure_projects', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains infrastructure project'
            ]);
        $table
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('funding_source_description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('contract_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('contract_amount', 'decimal', [
                'default' => null,
                'precision' => 50, // total digit
                'scale' => 2, // digit after decimal point
                'null' => true
            ])
            ->addColumn('status', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => '1 => Active, 2 => Inactive'
            ])
            ->addColumn('date_started', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('date_completed', 'date', [
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
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('infrastructure_project_funding_source_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_project_funding_sources.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
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
            ->addIndex('status')
            ->addIndex('infrastructure_project_funding_source_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_projects

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 12');

        $this->insert('security_functions', [
            'id' => 1064,
            'name' => 'Infrastructure Project',
            'controller' => 'InfrastructureProjects',
            'module' => 'Institutions',
            'category' => 'Details',
            'parent_id' => 8,
            '_view' => 'index|view|download',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 13,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE infrastructure_projects_needs');
        $this->execute('DROP TABLE infrastructure_project_funding_sources');
        $this->execute('DROP TABLE infrastructure_projects');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 12');
        $this->execute('DELETE FROM security_functions WHERE id = 1064');
    }
}
