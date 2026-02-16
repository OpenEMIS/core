<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use StaffAppraisal\Model\Table\AppraisalAnswersTable;

class AppraisalTextAnswersTable extends AppraisalAnswersTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }
}
