<?php

use Phinx\Migration\AbstractMigration;

class POCOR4724 extends AbstractMigration
{
    public function up()
    {
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1052');
        $order = $row['order'];

        $this->execute('UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` > ' . $order);

        $order = $order + 1;
        $this->insert('security_functions', [
            'id' => 1086,
            'name' => 'Feeder Outgoing Institutions',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Academic',
            'parent_id' => 8,
            '_view' => 'FeederOutgoingInstitutions.index|FeederOutgoingInstitutions.view',
            '_edit' => null,
            '_add' => 'FeederOutgoingInstitutions.add',
            '_delete' => 'FeederOutgoingInstitutions.delete',
            '_execute' => null,
            'order' => $order,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $order = $order + 1;
        $this->insert('security_functions', [
            'id' => 1087,
            'name' => 'Feeder Incoming Institutions',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Academic',
            'parent_id' => 8,
            '_view' => 'FeederIncomingInstitutions.index|FeederIncomingInstitutions.view',
            '_edit' => null,
            '_add' => null,
            '_delete' => null,
            '_execute' => null,
            'order' => $order,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        // feeders_institutions
        $table = $this->table('feeders_institutions', [
                'id' => false,
                'primary_key' => ['institution_id', 'feeder_institution_id', 'academic_period_id', 'education_grade_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the relation between institutions and feeder institution'
        ]);

        $table
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('feeder_institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
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
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('institution_id')
            ->addIndex('feeder_institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end
    }

    public function down()
    {
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1086');
        $order = $row['order'];

        $this->execute('UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` >= ' . $order);

        $this->execute('DELETE FROM `security_functions` WHERE `id` IN (1086, 1087)');

        $this->dropTable('feeders_institutions');
    }
}
