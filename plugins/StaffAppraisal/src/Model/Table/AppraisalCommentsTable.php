<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalCommentsTable extends AppTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
    }
}
