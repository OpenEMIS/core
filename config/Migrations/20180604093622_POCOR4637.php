<?php

use Phinx\Migration\AbstractMigration;

class POCOR4637 extends AbstractMigration
{
    public function up()
    {
        // workflow_rule_events
        $table = $this->table('workflow_rule_events', [
            'id' => false,
            'primary_key' => ['workflow_rule_id', 'event_key'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of workflow rule events'
        ]);

        $table
            ->addColumn('workflow_rule_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('event_key', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->save();

    }

    public function down()
    {
        // workflow_rule_events
        $this->dropTable('workflow_rule_events');
    }
}
