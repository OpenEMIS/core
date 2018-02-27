<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Entity;
use App\Model\Table\ControllerActionTable;

class AppraisalPeriodsTypesTable extends ControllerActionTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
    }
}
