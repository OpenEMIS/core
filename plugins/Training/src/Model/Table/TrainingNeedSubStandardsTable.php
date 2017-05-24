<?php
namespace Training\Model\Table;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use ArrayObject;

use App\Model\Table\ControllerActionTable;

class TrainingNeedSubStandardsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('TrainingNeeds', ['className' => 'Staff.StaffTrainingNeeds']);
        $this->belongsTo('TrainingNeedStandards', ['className' => 'Staff.TrainingNeedStandards']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function afterAction(Event $event)
    {
        $this->field('training_need_standard_id', ['type' => 'select']);
    }

    public function onUpdateFieldTrainingNeedStandardId(Event $event, array $attr, $action, Request $request)
    {
        $query = $this->TrainingNeedStandards
                ->find('list')
                ->where([$this->TrainingNeedStandards->aliasField('visible') => 1])
                ->order([$this->TrainingNeedStandards->aliasField('order')])
                ->toArray();

        $attr['options'] = $query;
    }
}
