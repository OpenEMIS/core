<?php
namespace Workflow\Model\Entity;

use Cake\ORM\Entity;

class WorkflowTransition extends Entity
{
    protected function _getPrevWorkflowStepName($name) {
        return __($name);
    }

    protected function _getWorkflowStepName($name) {
        return __($name);
    }

    protected function _getWorkflowActionName($name) {
        return __($name);
    }
}
