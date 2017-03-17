<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class StaffBehaviourCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_behaviour_categories');
        parent::initialize($config);

        $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);
        $this->hasMany('StaffBehaviours', ['className' => 'Staff.StaffBehaviours', 'foreignKey' => 'staff_behaviour_category_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('behaviour_classification_id', ['after' => 'name', 'type' => 'select']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('behaviour_classification_id', ['after' => 'name']);
    }
}
