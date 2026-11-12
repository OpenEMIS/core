<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalSlidersTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    const NUMBER = 'NUMBER';
    const TEXT = 'TEXT';

    public function validationDefault(Validator $validator): Validator
    {
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('slider_type', 'create')
            ->add('slider_type', 'inList', [
                'rule' => ['inList', [self::NUMBER, self::TEXT]],
                'message' => __('Please select a valid slider type')
            ])
            ->notEmpty('min', null, function ($context) {
                $sliderType = $context['data']['slider_type'] ?? self::NUMBER;
                return $sliderType !== self::TEXT;
            })
            ->notEmpty('max', null, function ($context) {
                $sliderType = $context['data']['slider_type'] ?? self::NUMBER;
                return $sliderType !== self::TEXT;
            })
            ->notEmpty('step', null, function ($context) {
                $sliderType = $context['data']['slider_type'] ?? self::NUMBER;
                return $sliderType !== self::TEXT;
            })
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

    public function getSliderTypeOptions()
    {
        return [
            self::NUMBER => __('Number'),
            self::TEXT => __('Text')
        ];
    }
}
