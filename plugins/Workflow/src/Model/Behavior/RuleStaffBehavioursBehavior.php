<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Workflow\Model\Behavior\RuleBehavior;

class RuleStaffBehavioursBehavior extends RuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StaffBehaviours',
        'rule' => [
            'behaviour_classification_id' => [
                'type' => 'select',
                'after' => 'workflow_id',
                'lookupModel' => 'Student.BehaviourClassifications',
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
                $validator->add('behaviour_classification_id', 'notBlank', ['rule' => 'notBlank']);
                $validator->requirePresence('behaviour_classification_id');
            }
        }
    }

    public function onGetStaffBehavioursRule(Event $event, Entity $entity)
    {
        $model = $this->_table;
        if ($model->action == 'index' && $entity->has('rule')) {
            $ruleConfig = $this->config('rule');
            $ruleArray = json_decode($entity->rule, true);

            $list = [];
            if (array_key_exists('where', $ruleArray)) {
                $where = $ruleArray['where'];
                foreach ($where as $field => $fieldValue) {
                    $label = Inflector::humanize($field);
                    if ($model->endsWith($field, '_id') && $model->endsWith($label, ' Id')) {
                        $label = str_replace(' Id', '', $label);
                    }
                    $value = __($label) . ': ';

                    if (isset($ruleConfig[$field]['lookupModel'])) {
                        $lookupModel = $this->config('rule.'.$field.'.lookupModel');
                        $modelTable = TableRegistry::get($lookupModel);

                        try {
                            $fieldRecord = $modelTable->get($fieldValue);
                            $value .= $fieldRecord->name;
                        } catch (\Exception $e) {
                            Log::write('debug', $e->getMessage());
                        }
                    }

                    $list[] = $value;
                }
            }

            if (!empty($list)) {
                return implode("<br>", $list);
            }
        }
    }
}
