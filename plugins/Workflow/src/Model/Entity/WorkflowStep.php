<?php
namespace Workflow\Model\Entity;

use Cake\ORM\Entity;

class WorkflowStep extends Entity
{
    protected function _getName($name) {
        if (!empty($name)) {
            return __($name);
        } else {
            return ''; // Or any default value you want to return for null/empty names
        }
    }
}
