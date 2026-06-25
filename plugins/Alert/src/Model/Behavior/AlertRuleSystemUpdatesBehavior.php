<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;

/* POCOR-7462 for cases alert rule */
class AlertRuleSystemUpdatesBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'SystemUpdates',
        'name' => 'System Updates',
        'method' => ['Email','SMS'], // POCOR-8286
        'threshold' => [
            // 'value' => [
            //     'type' => 'integer',
            //     'after' => 'security_roles',
            //     'attr' => [
            //         'min' => 50,
            //         'max' => 75,
            //         'required' => true
            //     ],
            //     'tooltip' => [
            //         'label' => 'Value',
            //         'sprintf' => [50, 75]
            //     ]
            // ],
            // 'condition' => [
            //     'type' => 'select',
            //     'select' => false,
            //     'after' => 'value',
            //     'options' => 'SystemUpdates.before_after'
            // ]
        ],
        'placeholder' => [
            '${new_version}'     => 'New version available (e.g. 5.7.0).', //POCOR-9509
            '${release_date}'    => 'Release date of the new version (e.g. 11.12.2025).', //POCOR-9509
            '${current_version}' => 'Currently installed version (e.g. 5.5.0).', //POCOR-9509
        ]
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    // public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    // {
    //     $model = $this->_table;
    //     if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
    //         if (isset($data['submit']) && $data['submit'] == 'save') {
    //             $validator = $model->getValidator();
    //             $validator->add('value', [
    //                 'ruleRange' => [
    //                     'rule' => ['range', 1, 5],
    //                     'message' => __('Version should be.... ')
    //                 ]
    //             ]);
    //         }
    //     }
    // }

    public function onSystemUpdatesSetupFields(EventInterface $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetSystemUpdatesThreshold(EventInterface $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }



}
