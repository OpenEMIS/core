<?php
namespace Workflow\Model\Table;

use App\Model\Table\ControllerActionTable;

class WorkflowStepsParamsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
    }
}
