<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class ImmunizationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_health_immunizations');
        parent::initialize($config);

        $this->belongsTo('ImmunizationTypes', ['className' => 'Health.ImmunizationTypes', 'foreignKey' => 'health_immunization_type_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
    }

    //POCOR-5890 starts remain work
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'sss'],'type' => 'select', 'after' => 'comment']);
    }

    public function indexAfterAction(Event $event, $data)
    {
      // echo "<pre>"; print_r($extra); die;

        $this->field('dosage',['type'=>'hidden']);

    }
    //POCOR-5890 ends
}
