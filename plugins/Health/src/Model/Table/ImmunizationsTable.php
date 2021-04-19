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
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'type' => 'select', 'before' => 'comment']);
        $this->field('dosage',['visible' => false]);
    }

    public function indexAfterAction(Event $event, $data)
    {
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'before' => 'comment']);
        $this->field('dosage',['visible' => false]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'health_immunization_type_id':
                return __('Vaccination Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {   
        $this->field('dosage', ['visible' => false]);
    }

    public function viewBeforeAction(Event $event)
    {
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'before' => 'comment']);
        $this->field('dosage', ['visible' => false]);
    }
    //POCOR-5890 ends
}
