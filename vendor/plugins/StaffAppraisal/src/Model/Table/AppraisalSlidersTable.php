<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalSlidersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->notEmpty('min')
            ->notEmpty('max')
            ->notEmpty('step')
            ->add('min', [
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => __('Value cannot be more than two decimal places'),
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 100],
                    'message' => __('Value must be within 0 to 100')
                ]
            ])
            ->add('max', [
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => __('Value cannot be more than two decimal places')
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 100],
                    'message' => __('Value must be within 0 to 100')
                ],
                'ruleCompareMin' => [
                    'rule' => ['compareValues', 'min'],
                    'message' => __('Max value must be greater than min value'),
                    'last' => true
                ],
                'ruleCompareStep' => [
                    'rule' => ['compareValues', 'step'],
                    'message' => __('Max value must be greater than step value'),
                    'last' => true
                ]
            ])
            ->add('step', [
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => __('Value cannot be more than two decimal places')
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 9.99],
                    'message' => __('Value must be within 0 to 9.99')
                ]
            ]);
    }
}
