<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\HtmlTrait;

class InstitutionIndexesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('indexes');
        parent::initialize($config);

        $this->hasMany('IndexesCriterias', ['className' => 'Indexes.IndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('search', false);
        $this->toggle('add', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name',['sort' => false]);
        $this->field('average_index',['sort' => false]);
        $this->field('total_index',['sort' => false]);

        $this->field('generated_on',['sort' => false, 'after' => 'generated_by']);
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('generated_by',['visible' => false]);

        $this->setFieldOrder(['name']);
    }

    public function onGetTotalIndex(Event $event, Entity $entity)
    {
        $indexId = $entity->id;
        $indexTotal = $this->IndexesCriterias->getTotalIndex($indexId);

        return $indexTotal;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'InstitutionStudentIndexes',
                'index_id' => $entity->id,
            ];
        }
        unset($buttons['edit']);//remove edit action from the action button
        unset($buttons['remove']);// remove delete action from the action button
        return $buttons;
    }
}
