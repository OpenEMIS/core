<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalSliderOptionsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->notEmpty('label')
            ->notEmpty('value')
            ->add('value', [
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^-?[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => __('Value cannot be more than two decimal places')
                ]
            ]);
    }
}
