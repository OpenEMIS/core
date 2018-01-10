<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Workflow\Model\Behavior\RuleBehavior;

class RuleStudentAttendancesBehavior extends RuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StudentAttendances',
        'rule' => [
            'days_absent' => [
                'type' => 'string',
                'after' => 'workflow_id',
                'attr' => [
                    'required' => true
                ]
            ]
        ]
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->rule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->validator();
                $validator->add('days_absent', 'notBlank', ['rule' => 'notBlank']);
                $validator->requirePresence('days_absent');
                $validator->add('days_absent', 'wrongNumberRange', [
                    'rule' => ['comparison', '>', 0],
                    'message' => __('Please enter a valid number more than 0.')
                ]);
            }
        }
    }
}
