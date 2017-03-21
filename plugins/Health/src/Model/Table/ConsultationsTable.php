<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class ConsultationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_health_consultations');
        parent::initialize($config);

        $this->belongsTo('ConsultationTypes', ['className' => 'Health.ConsultationTypes', 'foreignKey' => 'health_consultation_type_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('health_consultation_type_id', ['type' => 'select', 'after' => 'treatment']);
    }
}
