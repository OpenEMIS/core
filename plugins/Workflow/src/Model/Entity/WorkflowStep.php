<?php
namespace Workflow\Model\Entity;

use Cake\ORM\Entity;

class WorkflowStep extends Entity
{
    protected function _getName($name) {
        return __($name);
    }
}
