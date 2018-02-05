<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalSlidersTable extends AppTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->notEmpty('min')
            ->notEmpty('max');
    }
}
