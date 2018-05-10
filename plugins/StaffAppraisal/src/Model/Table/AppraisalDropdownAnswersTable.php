<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use StaffAppraisal\Model\Table\AppraisalAnswersTable;

class AppraisalDropdownAnswersTable extends AppraisalAnswersTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalDropdownOptions', ['className' => 'StaffAppraisal.AppraisalDropdownOptions', 'foreignKey' => 'answer']);
    }
}
