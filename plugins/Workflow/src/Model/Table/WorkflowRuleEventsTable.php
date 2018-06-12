<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowRuleEventsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('WorkflowRules', ['className' => 'Workflow.WorkflowRules']);
    }
}
