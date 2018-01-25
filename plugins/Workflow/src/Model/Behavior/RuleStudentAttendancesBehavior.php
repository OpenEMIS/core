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

class RuleStudentAttendancesBehavior extends RuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StudentAttendances',
        'rule' => [
            'absence_type_id' => [
                'type' => 'select',
                'after' => 'workflow_id',
                'lookupModel' => 'Institution.AbsenceTypes',
                'attr' => [
                    'required' => true
                ]
            ],
            'days_absent' => [
                'type' => 'string',
                'after' => 'absence_type_id',
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
                $validator->add('absence_type_id', 'notBlank', ['rule' => 'notBlank']);
                $validator->requirePresence('absence_type_id');
                $validator->add('days_absent', 'notBlank', ['rule' => 'notBlank']);
                $validator->requirePresence('days_absent');
                $validator->add('days_absent', 'notWholeNumber', [
                    'rule' => ['naturalNumber', false],
                    'message' => __('Please enter a valid number more than 0.')
                ]);
            }
        }
    }

    public function onUpdateFieldAbsenceTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $lookupModel = $this->config('rule.absence_type_id.lookupModel');
            $modelTable = TableRegistry::get($lookupModel);
            $lateId = $modelTable->findByCode('LATE')->extract('id')->first();
            unset($attr['options'][$lateId]);
            return $attr;
        }
    }

    public function onGetStudentAttendancesRule(Event $event, Entity $entity)
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
                    } else {
                        $value .= $fieldValue;
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
