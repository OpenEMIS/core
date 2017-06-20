<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class AreaLevelsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('Areas', ['className' => 'Area.Areas', 'foreign_key' => 'area_level_id']);
        $this->addBehavior('RestrictAssociatedDelete');
        $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('level', ['before' => 'name']);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['level']['type'] = 'hidden';
    }

    // To fix institution_area_level_id in configitem
    // public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
    // 	$ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
    // 	$transferedValue = $this->request->data[$this->alias()]['convert_to'];
    // 	$ConfigItemsTable->updateAll(['default_value' => $transferedValue], ['type' => 'Institution', 'code' => 'Institution_area_level_id', 'default_value' => $entity->id]);
    // 	$ConfigItemsTable->updateAll(['value' => $transferedValue], ['type' => 'Institution', 'code' => 'Institution_area_level_id', 'value' => $entity->id]);
    // }

    public function onUpdateFieldLevel(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $query = $this->find();
            $results = $query
                ->select(['level' => $query->func()->max('level')])
                ->all();

            $maxLevel = 0;
            if (!$results->isEmpty()) {
                $data = $results->first();
                $maxLevel = $data->level;
            }

            $attr['attr']['value'] = ++$maxLevel;
        }

        return $attr;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        //check config
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $validateAreaLevel = $ConfigItems->value('institution_validate_area_level_id');
        if ($validateAreaLevel == $entity->level) {
            $extra['associatedRecords'][] = ['model' => 'System Configurations - Institution', 'count' => 1];
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::get('Configuration.ConfigItems')
        ];

        $this->dispatchEventToModels('Model.AreaLevel.afterDelete', [$entity], $this, $listeners);
    }
}
