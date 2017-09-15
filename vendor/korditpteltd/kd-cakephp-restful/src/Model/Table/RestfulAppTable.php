<?php
namespace Restful\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Log\Log;

use Restful\Model\Entity\Field;
use Restful\Model\Entity\Schema;

class RestfulAppTable extends Table
{
    private $schema = null;
    private $excludedFields = ['order', 'modified', 'modified_user_id', 'created', 'created_user_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->schema = new Schema($this);
    }

    public function implementedEvents()
    {
        $eventMap = [
            'Restful.Model.onBuildSchema' => ['callable' => 'onBuildSchema', 'priority' => 0],
            'Restful.Model.index.updateSchema' => ['callable' => 'indexUpdateSchema', 'priority' => 0],
            'Restful.Model.index.beforeQuery' => ['callable' => 'indexBeforeQuery', 'priority' => 0],
            'Restful.Model.index.beforeAction' => ['callable' => 'indexBeforeAction', 'priority' => 0],
            'Restful.Model.view.updateSchema' => ['callable' => 'viewUpdateSchema', 'priority' => 0],
            'Restful.Model.add.updateSchema' => ['callable' => 'addUpdateSchema', 'priority' => 0],
            'Restful.Model.edit.updateSchema' => ['callable' => 'editUpdateSchema', 'priority' => 0],
            'Restful.Model.delete.updateSchema' => ['callable' => 'deleteUpdateSchema', 'priority' => 0]
        ];

        $events = parent::implementedEvents();
        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method['callable'])) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    public function onBuildSchema(Event $event, ArrayObject $extra)
    {
        $columns = $this->schema()->columns();

        foreach ($columns as $columnName) {
            if (!in_array($columnName, $this->excludedFields)) {
                $attributes = $this->schema()->column($columnName);
                $attributes['foreignKey'] = $this->schema->isForeignKey($columnName);
                $this->schema->add(new Field($columnName, $attributes));
            }
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function indexUpdateSchema(Event $event, Schema $schema, ArrayObject $extra)
    {
        $schema->excludePrimaryKey();
        $schema->exclude($this->excludedFields);
    }

    public function viewUpdateSchema(Event $event, Schema $schema, Entity $entity, ArrayObject $extra)
    {
        $schema->excludePrimaryKey();
        $schema->exclude($this->excludedFields);
    }
}
