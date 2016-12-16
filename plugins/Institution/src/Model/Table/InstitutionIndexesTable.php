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
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name',['sort' => false]);
        $this->field('average_index',['sort' => false]);
        $this->field('total_risk_index',['sort' => false]);

        $this->field('generated_on',['sort' => false, 'after' => 'generated_by']);
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('generated_by',['visible' => false]);

        $this->setFieldOrder(['name']);
    }

    public function onGetTotalRiskIndex(Event $event, Entity $entity)
    {
        $indexId = $entity->id;
        $indexTotal = $this->IndexesCriterias->getTotalIndex($indexId);

        return $indexTotal;
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $generatedById = $entity->generated_by;

        $Users = TableRegistry::get('Security.Users');
        $userName = $Users->get($generatedById)->first_name . ' ' . $Users->get($generatedById)->last_name;

        return $userName;
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

        return $buttons;
    }
}
