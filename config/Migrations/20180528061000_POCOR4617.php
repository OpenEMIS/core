<?php

use Phinx\Migration\AbstractMigration;

class POCOR4617 extends AbstractMigration
{
    public function up()
    {
        // institution_committees
        $table = $this->table('institution_committees', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of institution committees'
        ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('meeting_date', 'date', [
                'default' => null,
                'null' => false
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
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('institution_committee_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_committee_types.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
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
            ->addIndex('institution_committee_type_id')
            ->addIndex('academic_period_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_committees

        // institution_committee_types
        $table = $this->table('institution_committee_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains list of institution committee types'
            ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        //end institution_committee_types

       // committee_attachments
        $table = $this->table('institution_committee_attachments', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of attachments linked to specific committee'
        ]);

        $table
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
            ->addColumn('institution_committee_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_committees.id'
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
            ->addIndex('institution_committee_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        //end committee_attachments

            $this->execute('UPDATE security_functions SET `order` = `order` + 2 WHERE `order` > 102');

            // Institution Committees - Setup
            $this->insert('security_functions', [
                'id' => 2039,
                'name' => 'Institution Committees',
                'controller' => 'InstitutionCommittees',
                'module' => 'Institutions',
                'category' => 'Committees',
                'parent_id' => 1000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'remove',
                '_execute' => 'download',
                'order' => 103,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);

            // Institution Committee Attachments
            $this->insert('security_functions', [
                'id' => 2040,
                'name' => 'Institution Committee Attachments',
                'controller' => 'InstitutionCommitteeAttachments',
                'module' => 'Institutions',
                'category' => 'Committees',
                'parent_id' => 1000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'remove',
                '_execute' => 'download',
                'order' => 104,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE `institution_committees`');
        $this->execute('DROP TABLE `institution_committee_types`');
        $this->execute('DROP TABLE `institution_committee_attachments`');

        $this->execute('UPDATE security_functions SET `order` = `order` - 2 WHERE `order` > 102');
        $this->execute('DELETE FROM security_functions WHERE id = 2039');
        $this->execute('DELETE FROM security_functions WHERE id = 2040');
    }
}
