<?php
namespace Workflow\Model\Entity;

use Cake\ORM\Entity;

class WorkflowAction extends Entity
{
	protected function _getName($name) {
        return __($name);
    }
}
