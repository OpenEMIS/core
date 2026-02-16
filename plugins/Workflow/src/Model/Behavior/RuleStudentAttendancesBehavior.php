<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
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

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->rule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->getValidator();
                $validator->add('absence_type_id', 'notBlank', ['rule' => 'notBlank']);
                $validator->requirePresence('absence_type_id');
                $validator->add('days_absent', 'notBlank', ['rule' => 'notBlank']);
                $validator->requirePresence('days_absent');
                $validator->add('days_absent', 'notWholeNumber', [
                    'rule' => ['naturalNumber', false],
                    'message' => __('Please enter a valid number more than 0.')
                ]);
                $model->setValidator('forSave', $validator);

            }
        }
    }

    public function onUpdateFieldAbsenceTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $lookupModel = $this->getConfig('rule.absence_type_id.lookupModel');
            $modelTable = TableRegistry::getTableLocator()->get($lookupModel);
            $lateId = $modelTable->findByCode('LATE')->extract('id')->first();
            unset($attr['options'][$lateId]);
            return $attr;
        }
    }

    public function onGetStudentAttendancesRule(EventInterface $event, Entity $entity)
    {
        $model = $this->_table;
        if ($model->action == 'index' && $entity->has('rule')) {
            $ruleConfig = $this->getConfig('rule');
            $ruleArray = json_decode($entity->rule, true);

            $list = [];
            if (isset($ruleArray['where'])) {
                $where = $ruleArray['where'];
                foreach ($where as $field => $fieldValue) {
                    $label = Inflector::humanize($field);
                    if ($model->endsWith($field, '_id') && $model->endsWith($label, ' Id')) {
                        $label = str_replace(' Id', '', $label);
                    }
                    $value = __($label) . ': ';

                    if (isset($ruleConfig[$field]['lookupModel'])) {
                        $lookupModel = $this->getConfig('rule.'.$field.'.lookupModel');
                        $modelTable = TableRegistry::getTableLocator()->get($lookupModel);

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
