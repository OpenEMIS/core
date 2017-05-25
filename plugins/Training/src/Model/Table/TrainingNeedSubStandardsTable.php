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

        $this->belongsTo('TrainingNeedStandards', ['className' => 'Training.TrainingNeedStandards']);
        $this->hasMany('StaffTrainingNeeds', ['className' => 'Institution.StaffTrainingNeeds']);

        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }

    public function afterAction(Event $event)
    {
        $this->field('training_need_standard_id', ['type' => 'select']);
    }

    public function onUpdateFieldTrainingNeedStandardId(Event $event, array $attr, $action, Request $request)
    {
        $query = $this->TrainingNeedStandards
                ->find('list')
                ->find('visible')
                ->order([$this->TrainingNeedStandards->aliasField('order')])
                ->toArray();

        $attr['options'] = $query;
        $attr['type'] = 'select';

        return $attr;
    }
}
