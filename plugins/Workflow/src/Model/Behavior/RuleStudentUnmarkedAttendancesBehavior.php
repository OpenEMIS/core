<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Workflow\Model\Behavior\RuleBehavior;

class RuleStudentUnmarkedAttendancesBehavior extends RuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StudentUnmarkedAttendances',
        'rule' => [            
            'days_unmarked' => [                
                'type' => 'string',
                'attr' => [
                    'required' => true
                ]
            ],
            'security_role_id' => [                
                'type' => 'select',
                'after' => 'days_unmarked',
                'lookupModel' => 'Security.SecurityRoles',
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
                $validator->add('days_unmarked', 'notBlank', ['rule' => 'notBlank']);
                $validator->requirePresence('days_unmarked');
                $validator->add('days_unmarked', 'notWholeNumber', [
                    'rule' => ['naturalNumber', false],
                    'message' => __('Please enter a valid number more than 0.')
                ]);
            }
        }
    }
}
