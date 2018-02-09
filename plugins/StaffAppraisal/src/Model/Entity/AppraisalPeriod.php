<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use App\Model\Traits\UserTrait;

class AppraisalPeriod extends Entity
{

    protected $_virtual = ['name'];

    protected function _getName()
    {
        return $this->academic_period->name . ' - ' . $this->appraisal_form->name;
    }
}
