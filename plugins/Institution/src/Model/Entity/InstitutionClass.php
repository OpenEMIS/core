<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionClass extends Entity
{
    protected function _getStaffName() {
       return (!empty($this->staff) || (!is_null($this->staff))) ? $this->staff->name_with_id : ''; 
    }
}
