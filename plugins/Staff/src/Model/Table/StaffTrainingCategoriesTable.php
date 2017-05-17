<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StaffTrainingCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_categories');
        parent::initialize($config);

        $this->hasMany('StaffTrainings', ['className' => 'Staff.StaffTrainings', 'foreignKey' => 'staff_training_category_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $AlertRules = TableRegistry::get('Alert.AlertRules');
        $alertRuleTypes = $AlertRules->getAlertRuleTypes();
        foreach ($alertRuleTypes as $feature => $config) {
            if (isset($config['threshold']) && !empty($config['threshold'])) {
                foreach ($config['threshold'] as $field => $attr) {
                    if (isset($attr['lookupModel']) && $attr['lookupModel'] == $this->registryAlias()) {
                        $records = $AlertRules->find()
                            ->where([
                                $AlertRules->aliasField('feature') => $feature,
                                $AlertRules->aliasField('threshold') . ' LIKE ' => '%"'. $field .'"%'
                            ])
                            ->all();

                        $recordCount = 0;
                        if (!$records->isEmpty() && array_key_exists('type', $attr)) {
                            if ($attr['type'] == 'select') {
                                foreach ($records as $obj) {
                                    $thresholdData = json_decode($obj->threshold, true);
                                    if (array_key_exists($field, $thresholdData) && ($thresholdData[$field] == $entity->id)) {
                                        $recordCount++;
                                    }
                                }
                            } else if ($attr['type'] == 'chosenSelect') {
                                foreach ($records as $obj) {
                                    $thresholdData = json_decode($obj->threshold, true);
                                    if (array_key_exists($field, $thresholdData) && in_array($entity->id, $thresholdData[$field])) {
                                        $recordCount++;
                                    }
                                }
                            }
                        }

                        $modelAlias = __('AlertRules') . ': ' . $feature;
                        $extra['associatedRecords'][] = ['model' => $modelAlias, 'count' => $recordCount];
                    }
                }
            }
        }
    }
}
